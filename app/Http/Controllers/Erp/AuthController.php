<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\LoginAttempt;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('erp.dashboard');
        }

        return view('erp.auth.login');
    }

    public function show2fa()
    {
        if (! session('2fa_user_id')) {
            return redirect()->route('erp.login');
        }

        return view('erp.auth.2fa');
    }

    public function verify2fa(Request $request)
    {
        $userId = session('2fa_user_id');
        if (! $userId) {
            return redirect()->route('erp.login');
        }

        $request->validate(['code' => ['required', 'string', 'size:6']]);

        $user = User::find($userId);
        if (! $user || ! $user->two_factor_enabled) {
            return redirect()->route('erp.login');
        }

        if (! $user->two_factor_code || $user->two_factor_expires_at?->isPast() || ! Hash::check($request->code, $user->two_factor_code)) {
            return back()->withErrors(['code' => 'Invalid or expired verification code.']);
        }

        // Complete login
        $user->forceFill(['two_factor_code' => null, 'two_factor_expires_at' => null])->save();
        Auth::login($user, session('2fa_remember', false));
        session()->forget(['2fa_user_id', '2fa_remember']);
        $request->session()->regenerate();

        // Single active session
        $user->forceFill([
            'active_session_id' => $request->session()->getId(),
            'last_activity_at' => now(),
        ])->save();

        SecurityEvent::create([
            'user_id' => $user->id,
            'event_type' => 'LOGIN_SUCCESS_2FA',
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 500),
        ]);

        AuditService::log('LOGIN', 'user', $user->id, ['method' => 'password_2fa']);

        if ($user->must_change_password) {
            return redirect()->route('erp.password.change')->with('info', 'Please set a new password to continue.');
        }

        return redirect()->intended(route('erp.dashboard'));
    }

    public function toggle2fa(Request $request)
    {
        $request->validate([
            'enable' => ['required', 'boolean'],
            'password' => ['required'],
        ]);

        $user = Auth::user();

        if (! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $user->update(['two_factor_enabled' => (bool) $request->enable]);

        SecurityEvent::create([
            'user_id' => $user->id,
            'event_type' => $request->enable ? '2FA_ENABLED' : '2FA_DISABLED',
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 500),
        ]);

        AuditService::log($request->enable ? '2FA_ENABLED' : '2FA_DISABLED', 'user', $user->id);

        return back()->with('success', $request->enable ? 'Two-factor authentication enabled.' : 'Two-factor authentication disabled.');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Rate limiting: 5 attempts per minute per IP+email
        $throttleKey = 'login:'.$request->ip().':'.$credentials['email'];
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors(['email' => 'Too many attempts. Try again in '.ceil($seconds / 60).' minute(s).']);
        }

        $user = User::where('email', $credentials['email'])->first();

        // Log the attempt
        LoginAttempt::create([
            'email' => $credentials['email'],
            'ip_address' => $request->ip(),
            'success' => false,
        ]);

        if (! $user || ! $user->is_active) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors(['email' => 'Invalid credentials or account deactivated.']);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors(['email' => 'Invalid credentials.']);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        $user = Auth::user();

        // 2FA: if enabled, send code and redirect to verification
        if ($user->two_factor_enabled) {
            $code = (string) random_int(100000, 999999);
            $user->forceFill([
                'two_factor_code' => Hash::make($code),
                'two_factor_expires_at' => now()->addMinutes(10),
            ])->save();

            // Send the code via email
            try {
                Mail::raw("Your ARS ERP Finance Software verification code: {$code}\n\nThis code expires in 10 minutes.", function ($message) use ($user): void {
                    $message->to($user->email, $user->name)
                        ->subject('2FA Verification Code');
                });
            } catch (\Throwable $e) {
                // If mail fails, log and proceed anyway
                \Illuminate\Support\Facades\Log::warning('2FA email failed: '.$e->getMessage());
            }

            Auth::logout();
            session(['2fa_user_id' => $user->id, '2fa_remember' => $request->boolean('remember')]);

            return redirect()->route('erp.login.2fa');
        }

        // Single active session: revoke any previous session tokens
        $user->forceFill([
            'active_session_id' => $request->session()->getId(),
            'last_activity_at' => now(),
        ])->save();

        LoginAttempt::where('email', $credentials['email'])->latest()->first()
            ?->update(['success' => true]);

        SecurityEvent::create([
            'user_id' => $user->id,
            'event_type' => 'LOGIN_SUCCESS',
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 500),
        ]);

        AuditService::log('LOGIN', 'user', $user->id, ['method' => 'password']);

        if ($user->must_change_password) {
            return redirect()->route('erp.password.change')->with('info', 'Please set a new password to continue.');
        }

        return redirect()->intended(route('erp.dashboard'));
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            AuditService::log('LOGOUT', 'user', $user->id);
            $user->forceFill(['active_session_id' => null])->save();
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('erp.login');
    }

    public function showChangePassword()
    {
        return view('erp.auth.change-password');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        SecurityEvent::create([
            'user_id' => $user->id,
            'event_type' => 'PASSWORD_CHANGED',
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 500),
        ]);

        AuditService::log('PASSWORD_CHANGED', 'user', $user->id);

        return redirect()->route('erp.dashboard')->with('success', 'Password updated successfully.');
    }

    // ---- Profile ----

    public function showProfile()
    {
        $user = Auth::user();

        return view('erp.auth.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $user->update($data);

        AuditService::log('PROFILE_UPDATE', 'user', $user->id, $data);

        return back()->with('success', 'Profile updated successfully.');
    }

    // ---- Forgot / Reset Password ----

    public function showForgotPassword()
    {
        return view('erp.auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $token = Str::random(64);

            \DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now(),
                ]
            );

            $resetUrl = route('erp.password.reset', $token);

            try {
                Mail::raw(
                    "Hello {$user->name},\n\n".
                    "You requested a password reset for your ARS ERP Finance Software account.\n\n".
                    "Click the link below to reset your password:\n\n".
                    "{$resetUrl}\n\n".
                    "This link will expire in 60 minutes.\n\n".
                    "If you did not request this, please ignore this email.\n\n".
                    '— ARS ERP Finance Software Team',
                    function ($message) use ($user): void {
                        $message->to($user->email, $user->name)
                            ->subject('Password Reset Request — ARS ERP Finance Software');
                    }
                );
            } catch (\Throwable $e) {
                \Log::warning('Password reset email failed: '.$e->getMessage());
            }

            AuditService::log('PASSWORD_RESET_REQUESTED', 'user', $user->id);
        }

        return back()->with('success', 'If an account exists with that email, a reset link has been sent.');
    }

    public function showResetForm($token)
    {
        return view('erp.auth.reset-password', compact('token'));
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $record = \DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->latest()
            ->first();

        if (! $record || ! Hash::check($request->token, $record->token)) {
            return back()->withErrors(['email' => 'Invalid or expired reset token.']);
        }

        if (now()->diffInMinutes($record->created_at) > 60) {
            return back()->withErrors(['email' => 'Reset token has expired. Please request a new one.']);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        \DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        SecurityEvent::create([
            'user_id' => $user->id,
            'event_type' => 'PASSWORD_RESET',
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 500),
        ]);

        AuditService::log('PASSWORD_RESET', 'user', $user->id);

        return redirect()->route('erp.login')->with('success', 'Password reset successfully. You can now log in.');
    }

    public function sessions(Request $request)
    {
        $user = Auth::user();

        return view('erp.auth.sessions', [
            'currentSessionId' => $request->session()->getId(),
        ]);
    }

    public function logoutOtherSessions(Request $request)
    {
        // Revoke all other Sanctum tokens + reset active session flag
        $user = Auth::user();
        $user->tokens()->delete();

        AuditService::log('LOGOUT_OTHER_SESSIONS', 'user', $user->id);

        return back()->with('success', 'All other sessions have been terminated.');
    }
}
