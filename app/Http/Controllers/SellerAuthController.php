<?php

namespace App\Http\Controllers;

use App\Http\Requests\SellerApplicationRequest;
use App\Http\Requests\SellerLoginRequest;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class SellerAuthController extends Controller
{
    public function apply()
    {
        return view('seller.apply');
    }

    public function storeApplication(SellerApplicationRequest $request)
    {
        // Honeypot: pretend success so bots do not probe the form.
        if ($request->filled('website')) {
            return redirect()->route('seller.login')->with('success', 'Application submitted. We will email you after review.');
        }

        Seller::create([
            ...$request->validated(),
            'password' => Hash::make($request->validated()['password']),
            'status' => 'pending',
        ]);

        return redirect()->route('seller.login')->with('success', 'Application submitted. We will email you after review.');
    }

    public function login()
    {
        return view('seller.login');
    }

    public function authenticate(SellerLoginRequest $request)
    {
        $credentials = $request->validated();

        $throttleKey = 'seller-login|'.Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => "Too many login attempts. Try again in {$seconds} seconds.",
            ])->onlyInput('email');
        }

        $seller = Seller::where('email', $credentials['email'])->first();

        if (! $seller || ! Hash::check($credentials['password'], $seller->password)) {
            RateLimiter::hit($throttleKey, 900);

            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);

        if ($seller->status !== 'approved') {
            return back()->withErrors(['email' => 'Your seller application is still awaiting approval.'])->onlyInput('email');
        }

        Auth::guard('seller')->login($seller, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('seller.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('seller')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('seller.login');
    }
}
