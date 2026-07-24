<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DesignerProfile;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * PUT/PATCH /api/me/profile
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = (string) ($user->role ?? '');

        if ($role === 'supplier') {
            $this->updateSupplierProfile($request, $user);
        } elseif (in_array($role, ['designer', 'moderator'], true)) {
            $this->updateDesignerProfile($request, $user);
        } else {
            abort(403);
        }

        return response()->json([
            'success' => true,
            'message' => __('settings.profile_saved'),
            'user' => AuthController::userPayloadPublic($user->fresh()),
        ]);
    }

    /**
     * POST /api/me/password
     * Body: current_password, password, password_confirmation
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('auth.password')],
            ]);
        }

        $user->password = $data['password'];
        $user->must_change_password = false;
        $user->password_changed_at = now();
        $user->save();

        return response()->json([
            'success' => true,
            'message' => __('settings.password_saved'),
            'user' => AuthController::userPayloadPublic($user->fresh()),
        ]);
    }

    /**
     * POST /api/forgot-password
     * Body: email
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink(['email' => $data['email']]);

        // Always the same response (do not leak whether the email exists).
        return response()->json([
            'success' => true,
            'message' => __('passwords.sent'),
        ]);
    }

    /**
     * POST /api/reset-password
     * Body: token, email, password, password_confirmation
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'must_change_password' => false,
                    'password_changed_at' => now(),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => __($status),
        ]);
    }

    private function updateDesignerProfile(Request $request, User $user): void
    {
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

        $profile = $user->designerProfile ?? new DesignerProfile(['user_id' => $user->id]);
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
    }

    private function updateSupplierProfile(Request $request, User $user): void
    {
        $supplier = Supplier::query()->firstOrNew([
            'user_id' => (int) $user->id,
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'city' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'url', 'max:255'],
            'telegram' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:255'],
            'sphere' => ['nullable', 'string', 'max:255'],
            'work_terms_type' => ['nullable', Rule::in(['percent', 'amount'])],
            'work_terms_value' => ['nullable', 'string', 'max:255'],
            'inn' => ['nullable', 'string', 'max:32'],
            'kpp' => ['nullable', 'string', 'max:255'],
            'ogrn' => ['nullable', 'string', 'max:255'],
            'okpo' => ['nullable', 'string', 'max:255'],
            'legal_address' => ['nullable', 'string', 'max:1000'],
            'actual_address' => ['nullable', 'string', 'max:1000'],
            'director' => ['nullable', 'string', 'max:255'],
            'accountant' => ['nullable', 'string', 'max:255'],
            'bik' => ['nullable', 'string', 'max:255'],
            'bank' => ['nullable', 'string', 'max:255'],
            'checking_account' => ['nullable', 'string', 'max:255'],
            'corr_account' => ['nullable', 'string', 'max:255'],
            'comment_main' => ['nullable', 'string'],
        ]);

        $user->name = trim((string) $data['name']);
        $user->phone = $data['phone'] ?? null;
        $user->email = $data['email'];
        $user->city = $data['city'] ?? null;
        $user->save();

        $supplier->name = trim((string) $data['name']);
        $supplier->email = $data['email'];
        $supplier->phone = $data['phone'] ?? null;
        $supplier->telegram = $data['telegram'] ?? null;
        $supplier->whatsapp = $data['whatsapp'] ?? null;
        $supplier->website = $data['website'] ?? null;
        $supplier->city = $data['city'] ?? null;
        $supplier->address = $data['address'] ?? null;
        $supplier->sphere = $data['sphere'] ?? null;
        $supplier->work_terms_type = $data['work_terms_type'] ?? null;
        $supplier->work_terms_value = $data['work_terms_value'] ?? null;
        $supplier->inn = $data['inn'] ?? null;
        $supplier->kpp = $data['kpp'] ?? null;
        $supplier->ogrn = $data['ogrn'] ?? null;
        $supplier->okpo = $data['okpo'] ?? null;
        $supplier->legal_address = $data['legal_address'] ?? null;
        $supplier->actual_address = $data['actual_address'] ?? null;
        $supplier->director = $data['director'] ?? null;
        $supplier->accountant = $data['accountant'] ?? null;
        $supplier->bik = $data['bik'] ?? null;
        $supplier->bank = $data['bank'] ?? null;
        $supplier->checking_account = $data['checking_account'] ?? null;
        $supplier->corr_account = $data['corr_account'] ?? null;
        $supplier->comment = $data['comment_main'] ?? null;
        $supplier->save();
    }
}
