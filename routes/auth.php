<?php

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * AUTH
 *
 * Делаем "match"-роуты с одинаковым именем (`login`/`register`),
 * чтобы ваши формы в Blade (action="{{ route('login') }}") работали и для GET, и для POST.
 */
Route::match(['get', 'post'], '/login', function (Request $request) {
    if (Auth::check()) {
        return match (Auth::user()->role) {
            'moderator' => redirect()->route('moderator.index'),
            'supplier' => redirect()->route('supplier.index'),
            default => redirect()->route('dashboard'),
        };
    }

    if ($request->isMethod('get')) {
        return view('auth.login', [
            'authAsSupplier' => $request->query('as') === 'supplier',
        ]);
    }

    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
        'portal' => ['required', 'in:supplier,designer'],
    ]);

    $portal = $credentials['portal'];
    $loginRouteParams = $portal === 'supplier' ? ['as' => 'supplier'] : [];

    $remember = $request->boolean('remember');

    if (! Auth::attempt([
        'email' => $credentials['email'],
        'password' => $credentials['password'],
    ], $remember)) {
        return redirect()
            ->route('login', $loginRouteParams)
            ->withErrors(['email' => __('auth.failed')])
            ->withInput($request->except('password'));
    }

    $user = Auth::user();

    $portalMatchesRole = match ($portal) {
        'supplier' => $user->role === 'supplier',
        'designer' => in_array($user->role, ['designer', 'moderator'], true),
        default => false,
    };

    if (! $portalMatchesRole) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = $portal === 'supplier'
            ? __('auth_labels.wrong_portal_supplier')
            : __('auth_labels.wrong_portal_designer');

        return redirect()
            ->route('login', $loginRouteParams)
            ->withErrors(['email' => $message])
            ->withInput($request->except('password'));
    }

    $request->session()->regenerate();

    if ($user->must_change_password) {
        return redirect()->route('supplier.force-password.edit');
    }

    if ($user->role === 'moderator') {
        return redirect()->route('moderator.index');
    }

    if ($user->role === 'supplier') {
        return redirect()->intended(route('supplier.index'));
    }

    return redirect()->intended(route('dashboard'));
})->name('login')->middleware('guest');

Route::match(['get', 'post'], '/register', function (Request $request) {
    if (Auth::check()) {
        return match (Auth::user()->role) {
            'moderator' => redirect()->route('moderator.index'),
            'supplier' => redirect()->route('supplier.index'),
            default => redirect()->route('dashboard'),
        };
    }

    if ($request->isMethod('get')) {
        return view('auth.register', [
            'authAsSupplier' => $request->query('as') === 'supplier',
        ]);
    }

    $data = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'confirmed', PasswordRule::defaults()],
        'portal' => ['required', 'in:supplier,designer'],
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
            ]
        );
    }

    Auth::login($user);
    $request->session()->regenerate();

    return $isSupplier
        ? redirect()->route('supplier.index')
        : redirect()->route('dashboard');
})->name('register')->middleware('guest');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout')->middleware('auth');

Route::middleware(['auth', 'role:supplier'])->group(function () {
    Route::get('/supplier/force-password', function (Request $request) {
        return view('auth.force-password-change', [
            'user' => $request->user(),
        ]);
    })->name('supplier.force-password.edit');

    Route::post('/supplier/force-password', function (Request $request) {
        $data = $request->validate([
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $user = $request->user();
        $user->password = Hash::make($data['password']);
        $user->must_change_password = false;
        $user->password_changed_at = now();
        $user->save();

        return redirect()
            ->route('supplier.index')
            ->with('status', __('auth_labels.password_updated_successfully'));
    })->name('supplier.force-password.update');
});

Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->name('password.request')->middleware('guest');

Route::post('/password/email', function (Request $request) {
    $request->validate(['email' => ['required', 'email']]);

    $status = Password::sendResetLink($request->only('email'));

    return $status === Password::RESET_LINK_SENT
        ? back()->with('status', __($status))
        : back()->withErrors(['email' => __($status)]);
})->name('password.email')->middleware('guest');

Route::get('/password/reset/{token}', function (string $token, Request $request) {
    // Передаем весь $request, чтобы в reset-view работал $request->email
    $request->merge(['token' => $token]);

    return view('auth.reset-password', ['request' => $request]);
})->name('password.reset')->middleware('guest');

Route::post('/password/reset', function (Request $request) {
    $request->validate([
        'token' => ['required'],
        'email' => ['required', 'email'],
        'password' => ['required', 'confirmed', PasswordRule::defaults()],
    ]);

    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->save();
        }
    );

    return $status === Password::PASSWORD_RESET
        ? redirect()->route('login')->with('status', __($status))
        : back()->withErrors(['email' => __($status)])->withInput();
})->name('password.store')->middleware('guest');
