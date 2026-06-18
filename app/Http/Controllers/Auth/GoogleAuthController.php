<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    private const GMAIL_SCOPE = 'https://www.googleapis.com/auth/gmail.send';

    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'email', 'profile', self::GMAIL_SCOPE])
            ->with(['access_type' => 'offline', 'prompt' => 'consent'])
            ->redirect();
    }

    public function connect(): RedirectResponse
    {
        session(['google_connect' => true]);

        return $this->redirect();
    }

    public function callback(): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();
        $connectMode = session()->pull('google_connect', false);

        if ($connectMode && Auth::check()) {
            $user = Auth::user();
            $user->update($this->googleAttributes($googleUser));

            return redirect()->route('profile.edit')->with('status', 'gmail-connected');
        }

        $user = User::query()->where('google_id', $googleUser->getId())->first();

        if (! $user) {
            $user = User::query()->where('email', $googleUser->getEmail())->first();
        }

        if ($user) {
            $user->update([
                ...$this->googleAttributes($googleUser),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);
        } else {
            $user = User::create([
                'name' => $googleUser->getName() ?: $googleUser->getEmail(),
                'email' => $googleUser->getEmail(),
                'password' => Str::password(32),
                'email_verified_at' => now(),
                ...$this->googleAttributes($googleUser),
            ]);
        }

        Auth::login($user, true);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * @return array<string, mixed>
     */
    private function googleAttributes(\Laravel\Socialite\Contracts\User $googleUser): array
    {
        $attributes = [
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'google_token' => $googleUser->token,
        ];

        if ($googleUser->refreshToken) {
            $attributes['google_refresh_token'] = $googleUser->refreshToken;
        }

        return $attributes;
    }
}
