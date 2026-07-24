<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/login
     * Body: email, password, portal (designer|supplier)
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'portal' => ['required', 'in:designer,supplier'],
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if (! $this->portalMatchesRole($data['portal'], $user->role)) {
            throw ValidationException::withMessages([
                'email' => [
                    $data['portal'] === 'supplier'
                        ? __('auth_labels.wrong_portal_supplier')
                        : __('auth_labels.wrong_portal_designer'),
                ],
            ]);
        }

        // Один токен на устройство: старые mobile-токены можно удалить при желании
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
            'subscription_required' => $user->role === 'designer'
                && ! \App\Support\DesignerSubscription::hasAccess($user),
        ]);
    }

    /**
     * POST /api/register
     * Body: name, email, password, password_confirmation, portal (designer|supplier)
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'portal' => ['required', 'in:designer,supplier'],
        ]);

        $isSupplier = $data['portal'] === 'supplier';

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $isSupplier ? 'supplier' : 'designer',
        ]);

        if ($isSupplier) {
            Supplier::query()->firstOrCreate(
                ['user_id' => (int) $user->id],
                [
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_status' => 'draft',
                    'moderation_status' => 'draft',
                    'account_status' => \App\Support\SupplierDeposit::ACCOUNT_DEPOSIT_REQUIRED,
                    'guarantee_balance' => 0,
                ]
            );
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
            'subscription_required' => $user->role === 'designer'
                && ! \App\Support\DesignerSubscription::hasAccess($user),
            'deposit_required' => $user->role === 'supplier'
                && ! \App\Support\SupplierDeposit::isDepositPaid($user),
        ], 201);
    }

    /**
     * GET /api/me
     * Header: Authorization: Bearer {token}
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user' => self::userPayloadPublic($request->user()),
        ]);
    }

    /**
     * POST /api/logout
     * Header: Authorization: Bearer {token}
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function userPayloadPublic(User $user): array
    {
        return (new self)->userPayload($user);
    }

    private function portalMatchesRole(string $portal, string $role): bool
    {
        return match ($portal) {
            'supplier' => $role === 'supplier',
            'designer' => in_array($role, ['designer', 'moderator'], true),
            default => false,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        $payload = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'phone' => $user->phone,
            'city' => $user->city,
            'must_change_password' => (bool) $user->must_change_password,
        ];

        if ($user->role === 'designer' || $user->role === 'moderator') {
            $payload['subscription'] = [
                'has_access' => \App\Support\DesignerSubscription::hasAccess($user),
                'status' => \App\Support\DesignerSubscription::status($user),
                'plan' => $user->subscription_plan,
                'can_use_trial' => \App\Support\DesignerSubscription::canUseTrial($user),
                'trial_days_left' => \App\Support\DesignerSubscription::trialDaysLeft($user),
                'ends_at' => optional(\App\Support\DesignerSubscription::accessEndsAt($user))->toIso8601String(),
            ];

            $profile = $user->designerProfile;
            $payload['profile'] = $profile ? [
                'phone' => $profile->phone,
                'city' => $profile->city,
                'short_description' => $profile->short_description,
                'work_regions' => $profile->work_regions,
                'about_designer' => $profile->about_designer,
                'website_portfolio' => $profile->website_portfolio,
                'telegram' => $profile->telegram,
                'whatsapp' => $profile->whatsapp,
                'vk' => $profile->vk,
                'instagram' => $profile->instagram,
                'experience' => $profile->experience,
                'price_per_m2' => $profile->price_per_m2,
                'education' => $profile->education,
                'awards' => $profile->awards,
                'specialization' => $profile->specialization,
                'styles' => $profile->styles,
            ] : null;
        }

        if ($user->role === 'supplier') {
            $supplier = $user->supplierProfile;
            $payload['deposit_required'] = ! \App\Support\SupplierDeposit::isDepositPaid($user);
            $payload['supplier'] = $supplier ? [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'phone' => $supplier->phone,
                'email' => $supplier->email,
                'city' => $supplier->city,
                'address' => $supplier->address,
                'website' => $supplier->website,
                'telegram' => $supplier->telegram,
                'whatsapp' => $supplier->whatsapp,
                'sphere' => $supplier->sphere,
                'profile_status' => $supplier->profile_status,
                'moderation_status' => $supplier->moderation_status,
                'account_status' => $supplier->account_status,
            ] : null;
        }

        return $payload;
    }
}
