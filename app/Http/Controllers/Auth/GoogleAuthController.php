<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        if (! $this->isGoogleConfigured()) {
            return $this->configurationError();
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        if (! $this->isGoogleConfigured()) {
            return $this->configurationError();
        }

        $googleUser = Socialite::driver('google')->user();

        $user = User::firstOrNew(['email' => $googleUser->getEmail()]);
        $user->name = $user->exists
            ? $user->name
            : ($googleUser->getName() ?: $googleUser->getNickname() ?: $googleUser->getEmail());
        $user->google_id = $googleUser->getId();
        $user->google_avatar_url = $googleUser->getAvatar();
        $user->email_verified_at = $user->email_verified_at ?: now();

        if (! $user->exists) {
            $user->password = Str::random(32);
        }

        $user->save();

        Auth::login($user, true);

        return redirect()->intended(route('dashboard'));
    }

    private function isGoogleConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    private function configurationError()
    {
        return redirect()
            ->route('login')
            ->withErrors(['email' => 'Configura GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET y GOOGLE_REDIRECT_URI para activar el acceso con Google.']);
    }
}
