<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/admin';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * The user has been authenticated.
     */
    protected function authenticated(Request $request, mixed $user): ?Response
    {
        if (! ($user instanceof User)) {
            return null;
        }

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Ce compte est inactif ou désactivé. Veuillez contacter un administrateur.',
            ]);
        }

        if ($user->hasTwoFactorEnabled()) {
            $remember = $request->filled('remember');

            Auth::logout();
            $request->session()->put('auth.2fa.user_id', $user->id);
            $request->session()->put('auth.2fa.remember', $remember);

            return redirect()->route('2fa.challenge');
        }

        $user->last_login_at = now();
        $user->last_login_ip = $request->ip();
        $user->save();

        return redirect()->intended($this->redirectPath());
    }
}
