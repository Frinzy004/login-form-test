<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/login', [
            'status' => session('status'),
            'canResetPassword' => true,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $email = $request->email;
        $password = $request->password;

        // ✅ Step 1: Check if user exists with this email
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => 'No account found with this email address.', // ← Specific to email field
            ]);
        }

        // ✅ Step 2: Check user status and provide specific error messages
        $statusError = $this->getUserStatusError($user);
        if ($statusError) {
            throw ValidationException::withMessages([
                'email' => $statusError, // ← Status errors go to email field
            ]);
        }

        // ✅ Step 3: Check email verification
        if (!$user->email_verified_at) {
            throw ValidationException::withMessages([
                'email' => 'Please verify your email address before logging in.', // ← Specific to email field
            ]);
        }

        // ✅ Step 4: Check if account is approved
        if (!$user->isApproved()) {
            throw ValidationException::withMessages([
                'email' => 'Your account is pending admin approval.', // ← Specific to email field
            ]);
        }

        // ✅ Step 5: Verify password specifically
        if (!Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'The password you entered is incorrect.', // ← Specific to password field
            ]);
        }

        // ✅ Step 6: Final authentication attempt
        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            // Fallback error
            throw ValidationException::withMessages([
                'email' => 'Authentication failed. Please try again.',
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();
        
        // ✅ Redirect based on user type
        $redirectTo = match($user->user_type) {
            User::ROLE_ADMIN => route('admin.dashboard'),
            User::ROLE_BHW => route('bhw.dashboard'),
            User::ROLE_MEDICAL_STAFF => route('medical.dashboard'),
            default => route('dashboard'),
        };

        return redirect()->intended($redirectTo);
    }

    /**
     * ✅ Check user status and return appropriate error message
     */
    private function getUserStatusError(User $user): ?string
    {
        return match($user->status) {
            User::STATUS_PENDING => 'Your account is pending admin approval.',
            User::STATUS_REJECTED => 'Your registration has been rejected. Reason: ' . 
                                   ($user->rejection_reason ?? 'Contact administrator'),
            User::STATUS_SUSPENDED => 'Your account has been suspended. Please contact administrator.',
            User::STATUS_ACTIVE => null, // No error - user can proceed
            default => 'Your account status is invalid. Please contact administrator.',
        };
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}