<?php

namespace App\Http\Controllers\Api;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AddExistingTeamMemberRequest;
use App\Http\Requests\Api\ChangeTeamMemberRoleRequest;
use App\Http\Requests\Api\CreateTeamMemberRequest;
use App\Http\Requests\Api\InviteTeamMemberRequest;
use App\Http\Resources\AssigneeResource;
use App\Http\Resources\TeamInvitationResource;
use App\Http\Resources\TeamMemberResource;
use App\Http\Resources\TeamResource;
use App\Models\DesignerTeamInvitation;
use App\Models\DesignerTeamMember;
use App\Models\User;
use App\Services\Team\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TeamApiController extends Controller
{
    public function __construct(private readonly TeamService $teams) {}

    public function show(Request $request): JsonResponse
    {
        $team = $this->teams->activeTeamFor($request->user());

        return response()->json(['data' => $team ? (new TeamResource($team))->resolve() : null]);
    }

    public function members(Request $request): JsonResponse
    {
        $team = $this->requireTeam($request->user());
        $members = $team->members()->with('user')->where('status', 'active')->get();

        return response()->json(['data' => ['members' => TeamMemberResource::collection($members)->resolve()]]);
    }

    public function invitations(Request $request): JsonResponse
    {
        $team = $this->requireManageableTeam($request->user());
        $invitations = $team->invitations()->latest('id')->get();

        return response()->json(['data' => ['invitations' => TeamInvitationResource::collection($invitations)->resolve()]]);
    }

    public function invite(InviteTeamMemberRequest $request): JsonResponse
    {
        $team = $this->requireManageableTeam($request->user());
        $data = $request->validated();
        $invitation = $this->teams->inviteByEmail($team, $request->user(), $data['email'], TeamRole::from($data['role']));

        return response()->json(['data' => new TeamInvitationResource($invitation)], 201);
    }

    public function createAccount(CreateTeamMemberRequest $request): JsonResponse
    {
        $team = $this->requireManageableTeam($request->user());
        $data = $request->validated();
        $this->teams->assertSeatAvailable($team);
        User::query()->create([
            'name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password']),
            'account_type' => 'designer', 'subscription_plan' => null, 'subscription_ends_at' => null,
            'subscription_trial_ends_at' => null, 'subscription_trial_used' => false,
        ]);
        $invitation = $this->teams->inviteByEmail($team, $request->user(), $data['email'], TeamRole::from($data['role']));

        return response()->json(['data' => (new TeamInvitationResource($invitation))->resolve()], 201);
    }

    public function addMember(AddExistingTeamMemberRequest $request): JsonResponse
    {
        $team = $this->requireManageableTeam($request->user());
        $data = $request->validated();
        $email = mb_strtolower(trim($data['email']));
        $target = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $target) {
            throw ValidationException::withMessages([
                'email' => [__('team.user_not_found')],
            ]);
        }

        $invitation = $this->teams->addExistingUser(
            $team,
            $request->user(),
            $target,
            TeamRole::from($data['role'])
        );

        return response()->json(['data' => (new TeamInvitationResource($invitation))->resolve()], 201);
    }

    public function acceptInvitation(Request $request, DesignerTeamInvitation $invitation): JsonResponse
    {
        $member = $this->teams->acceptInvitation($request->user(), $invitation);

        return response()->json(['data' => new TeamMemberResource($member->load('user'))]);
    }

    public function declineInvitation(Request $request, DesignerTeamInvitation $invitation): JsonResponse
    {
        $this->teams->declineInvitation($request->user(), $invitation);

        return response()->json(['data' => ['id' => $invitation->id, 'status' => 'cancelled']]);
    }

    public function changeRole(ChangeTeamMemberRoleRequest $request, DesignerTeamMember $member): JsonResponse
    {
        $team = $this->requireManageableTeam($request->user());
        $this->teams->changeRole($team, $request->user(), $member, TeamRole::from($request->validated('role')));

        return response()->json(['data' => new TeamMemberResource($member->fresh('user'))]);
    }

    public function removeMember(Request $request, DesignerTeamMember $member): JsonResponse
    {
        $team = $this->requireManageableTeam($request->user());
        $this->teams->removeMember($team, $request->user(), $member);

        return response()->json(['data' => ['id' => $member->id, 'status' => 'inactive']]);
    }

    public function resendInvitation(Request $request, DesignerTeamInvitation $invitation): JsonResponse
    {
        $team = $this->requireManageableTeam($request->user());
        $fresh = $this->teams->resendInvitation($team, $request->user(), $invitation);

        return response()->json(['data' => new TeamInvitationResource($fresh)]);
    }

    public function cancelInvitation(Request $request, DesignerTeamInvitation $invitation): JsonResponse
    {
        $team = $this->requireManageableTeam($request->user());
        $this->assertInvitationInTeam($invitation, $team->id);
        $invitation->update(['status' => 'cancelled']);

        \App\Models\UserNotification::query()
            ->where('related_invitation_id', $invitation->id)
            ->where('action_key', 'team_invited')
            ->update([
                'action_key' => 'team_invite_declined',
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['data' => new TeamInvitationResource($invitation->fresh())]);
    }

    public function assignees(Request $request): JsonResponse
    {
        return response()->json(['data' => ['assignees' => AssigneeResource::collection($this->teams->assigneeOptions($request->user()))->resolve()]]);
    }

    private function requireTeam(User $user): \App\Models\DesignerTeam
    {
        $team = $this->teams->activeTeamFor($user);
        if (! $team || ! $this->teams->teamHasCorporateAccess($team)) {
            throw ValidationException::withMessages(['team' => [__('team.corporate_required')]]);
        }

        return $team;
    }

    private function requireManageableTeam(User $user): \App\Models\DesignerTeam
    {
        $team = $this->requireTeam($user);
        $this->teams->assertCanManageMembers($user, $team);

        return $team;
    }

    private function assertInvitationInTeam(DesignerTeamInvitation $invitation, int $teamId): void
    {
        if ((int) $invitation->team_id !== $teamId) {
            abort(404);
        }
    }
}
