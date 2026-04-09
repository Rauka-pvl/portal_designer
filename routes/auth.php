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
 * Ð”ÐµÐ»Ð°ÐµÐ¼ "match"-Ñ€Ð¾ÑƒÑ‚Ñ‹ Ñ Ð¾Ð´Ð¸Ð½Ð°ÐºÐ¾Ð²Ñ‹Ð¼ Ð¸Ð¼ÐµÐ½ÐµÐ¼ (`login`/`register`),
 * Ñ‡Ñ‚Ð¾Ð±Ñ‹ Ð²Ð°ÑˆÐ¸ Ñ„Ð¾Ñ€Ð¼Ñ‹ Ð² Blade (action="{{ route('login') }}") Ñ€Ð°Ð±Ð¾Ñ‚Ð°Ð»Ð¸ Ð¸ Ð´Ð»Ñ GET, Ð¸ Ð´Ð»Ñ POST.
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
    ]);

    $remember = $request->boolean('remember');

    if (! Auth::attempt([
        'email' => $credentials['email'],
        'password' => $credentials['password'],
    ], $remember)) {
        return back()
            ->withErrors(['email' => __('auth.failed')])
            ->withInput($request->except('password'));
    }

    $request->session()->regenerate();

    $user = Auth::user();

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
        'account_type' => ['nullable', 'in:supplier,designer'],
    ]);

    $isSupplier = ($data['account_type'] ?? 'designer') === 'supplier';

    $user = User::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'password' => Hash::make($data['password']),
        'role' => $isSupplier ? 'supplier' : 'designer',
    ]);

    if ($isSupplier) {
        Supplier::query()->firstOrCreate(
            ['account_user_id' => (int) $user->id],
            [
                'user_id' => (int) $user->id,
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
    // ÐŸÐµÑ€ÐµÐ´Ð°ÐµÐ¼ Ð²ÐµÑÑŒ $request, Ñ‡Ñ‚Ð¾Ð±Ñ‹ Ð² reset-vieÐ²Ðµ Ñ€Ð°Ð±Ð¾Ñ‚Ð°Ð» $request->email
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
