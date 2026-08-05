<?php

namespace App\Http\Controllers\Designer;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Models\DesignerTeamInvitation;
use App\Models\DesignerTeamMember;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Team\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class TeamController extends Controller
{
    public function __construct(
        private readonly TeamService $teams,
    ) {}

    public function addMember(Request $request): RedirectResponse
    {
        $user = $request->user();
        $team = $this->requireManageableTeam($user);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in([TeamRole::Admin->value, TeamRole::Designer->value])],
        ]);

        $target = User::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($data['email']))])
            ->first();

        if (! $target) {
            throw ValidationException::withMessages([
                'email' => [__('team.user_not_found')],
            ]);
        }

        $this->teams->addExistingUser(
            $team,
            $user,
            $target,
            TeamRole::from($data['role'])
        );

        return redirect()
            ->route('settings.index', ['tab' => 'team'])
            ->with('status', __('team.invite_sent'));
    }

    public function invite(Request $request): RedirectResponse
    {
        $user = $request->user();
        $team = $this->requireManageableTeam($user);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in([TeamRole::Admin->value, TeamRole::Designer->value])],
        ]);

        $this->teams->inviteByEmail(
            $team,
            $user,
            $data['email'],
            TeamRole::from($data['role'])
        );

        return redirect()
            ->route('settings.index', ['tab' => 'team'])
            ->with('status', __('team.invite_sent'));
    }

    public function createMember(Request $request): RedirectResponse
    {
        $user = $request->user();
        $team = $this->requireManageableTeam($user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in([TeamRole::Admin->value, TeamRole::Designer->value])],
        ]);

        $this->teams->assertSeatAvailable($team);

        $newUser = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'account_type' => 'designer',
            // Corporate access via team — no personal paid plan required.
            'subscription_plan' => null,
            'subscription_ends_at' => null,
            'subscription_trial_ends_at' => null,
            'subscription_trial_used' => false,
        ]);

        $this->teams->inviteByEmail(
            $team,
            $user,
            $newUser->email,
            TeamRole::from($data['role'])
        );

        return redirect()
            ->route('settings.index', ['tab' => 'team'])
            ->with('status', __('team.member_created'));
    }

    public function changeRole(Request $request, DesignerTeamMember $member): RedirectResponse
    {
        $user = $request->user();
        $team = $this->requireManageableTeam($user);

        $data = $request->validate([
            'role' => ['required', Rule::in([TeamRole::Admin->value, TeamRole::Designer->value])],
        ]);

        $this->teams->changeRole($team, $user, $member, TeamRole::from($data['role']));

        return redirect()
            ->route('settings.index', ['tab' => 'team'])
            ->with('status', __('team.role_changed'));
    }

    public function removeMember(Request $request, DesignerTeamMember $member): RedirectResponse
    {
        $user = $request->user();
        $team = $this->requireManageableTeam($user);

        $this->teams->removeMember($team, $user, $member);

        return redirect()
            ->route('settings.index', ['tab' => 'team'])
            ->with('status', __('team.member_removed'));
    }

    public function cancelInvitation(Request $request, DesignerTeamInvitation $invitation): RedirectResponse
    {
        $user = $request->user();
        $team = $this->requireManageableTeam($user);

        if ((int) $invitation->team_id !== (int) $team->id) {
            abort(404);
        }

        $invitation->update(['status' => 'cancelled']);

        UserNotification::query()
            ->where('related_invitation_id', $invitation->id)
            ->where('action_key', 'team_invited')
            ->update([
                'action_key' => 'team_invite_declined',
                'is_read' => true,
                'read_at' => now(),
            ]);

        return redirect()
            ->route('settings.index', ['tab' => 'team'])
            ->with('status', __('team.invite_cancelled'));
    }

    private function requireManageableTeam(User $user): \App\Models\DesignerTeam
    {
        $team = $this->teams->activeTeamFor($user);
        if (! $team || ! $this->teams->teamHasCorporateAccess($team)) {
            throw ValidationException::withMessages([
                'team' => [__('team.corporate_required')],
            ]);
        }

        $this->teams->assertCanManageMembers($user, $team);

        return $team;
    }
}
