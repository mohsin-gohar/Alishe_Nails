<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function create()
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $directResetUrl = null;
        if (app()->isLocal() || config('mail.default') === 'log') {
            ResetPassword::createUrlUsing(function ($notifiable, string $token) use (&$directResetUrl) {
                $directResetUrl = route('password.reset', [
                    'token' => $token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ]);

                return $directResetUrl;
            });
        }

        try {
            $status = Password::broker('users')->sendResetLink(
                $request->only('email')
            );
        } catch (\Throwable $e) {
            Log::error('Password reset email error: '.$e->getMessage(), [
                'exception' => $e,
                'email' => $request->email,
            ]);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Unable to send password reset email at this moment. Please try again.']);
        }

        if ($status === Password::RESET_LINK_SENT) {
            $redirect = back()->with('success', 'A password reset link has been sent to your email.');

            if ($directResetUrl) {
                $redirect->with('direct_reset_url', $directResetUrl);
            }

            return $redirect;
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
