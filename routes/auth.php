<?php

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
        return Auth::user()->role === 'moderator'
            ? redirect()->route('moderator.index')
            : redirect()->route('dashboard');
    }

    if ($request->isMethod('get')) {
        return view('auth.login');
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

    if (Auth::user()->role === 'moderator') {
        return redirect()->route('moderator.index');
    }

    return redirect()->intended(route('dashboard'));
})->name('login')->middleware('guest');

Route::match(['get', 'post'], '/register', function (Request $request) {
    if (Auth::check()) {
        return Auth::user()->role === 'moderator'
            ? redirect()->route('moderator.index')
            : redirect()->route('dashboard');
    }

    if ($request->isMethod('get')) {
        return view('auth.register');
    }

    $data = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'confirmed', PasswordRule::defaults()],
    ]);

    $user = User::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'password' => Hash::make($data['password']),
    ]);

    Auth::login($user);
    $request->session()->regenerate();

    return redirect()->route('dashboard');
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
    // Передаем весь $request, чтобы в reset-vieве работал $request->email
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
