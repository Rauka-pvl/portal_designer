<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Models\DesignerProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user();
        $profile = $this->designerProfile($user);

        return view('designer.profile.show', [
            'user' => $user,
            'profile' => $profile,
            'referralSupplierUrl' => ($user->role ?? null) === 'designer'
                ? URL::signedRoute('referrals.suppliers.create', ['designer' => $user->id])
                : null,
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $tab = $request->query('tab', 'profile');
        $profile = $this->designerProfile($user);

        return view('designer.settings.index', [
            'activeTab' => in_array($tab, ['profile', 'security'], true) ? $tab : 'profile',
            'user' => $user,
            'profile' => $profile,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $profile = $this->designerProfile($user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:255'],
            'work_regions' => ['nullable', 'string', 'max:2000'],
            'about_designer' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'website_portfolio' => ['nullable', 'string', 'max:255'],
            'telegram' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:255'],
            'vk' => ['nullable', 'string', 'max:255'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'experience' => ['nullable', 'string', 'max:255'],
            'price_per_m2' => ['nullable', 'numeric', 'min:0'],
            'education' => ['nullable', 'string'],
            'awards' => ['nullable', 'string'],
            'specialization' => ['nullable', 'string'],
            'styles' => ['nullable', 'string'],
        ]);

        $user->name = trim((string) $data['name']);
        $user->email = $data['email'];
        $user->save();

        $profile->fill([
            'phone' => $data['phone'] ?? null,
            'city' => $data['city'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'work_regions' => $data['work_regions'] ?? null,
            'about_designer' => $data['about_designer'] ?? null,
            'website_portfolio' => $data['website_portfolio'] ?? null,
            'telegram' => $data['telegram'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'vk' => $data['vk'] ?? null,
            'instagram' => $data['instagram'] ?? null,
            'experience' => $data['experience'] ?? null,
            'price_per_m2' => isset($data['price_per_m2']) ? (float) $data['price_per_m2'] : null,
            'education' => $data['education'] ?? null,
            'awards' => $data['awards'] ?? null,
            'specialization' => $data['specialization'] ?? null,
            'styles' => $data['styles'] ?? null,
        ]);
        $profile->save();

        return redirect()
            ->route('settings.index', ['tab' => 'profile'])
            ->with('status', __('settings.profile_saved'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();
        $user->password = $data['password'];
        $user->save();

        return redirect()
            ->route('settings.index', ['tab' => 'security'])
            ->with('status', __('settings.password_saved'));
    }

    private function designerProfile($user): DesignerProfile
    {
        return $user->designerProfile ?? new DesignerProfile([
            'user_id' => $user->id,
        ]);
    }
}


