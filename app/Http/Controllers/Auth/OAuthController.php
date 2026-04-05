<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as ProviderUser;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class OAuthController extends Controller
{
    private const SUPPORTED_PROVIDERS = ['google', 'github'];

    public function redirect(string $provider): RedirectResponse
    {
        abort_unless($this->isSupportedProvider($provider), 404);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        abort_unless($this->isSupportedProvider($provider), 404);

        if ($request->filled('error')) {
            return redirect()->route('login')->withErrors([
                'oauth' => 'Sign in was canceled. Please try again.',
            ]);
        }

        try {
            $providerUser = Socialite::driver($provider)->user();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')->withErrors([
                'oauth' => 'Unable to sign in with '.$this->providerLabel($provider).'. Please try again.',
            ]);
        }

        $providerUserId = (string) $providerUser->getId();

        if ($providerUserId === '') {
            return redirect()->route('login')->withErrors([
                'oauth' => 'Unable to identify your account from '.$this->providerLabel($provider).'.',
            ]);
        }

        $socialAccount = SocialAccount::query()
            ->with('user')
            ->where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->first();

        if ($socialAccount !== null) {
            $user = $socialAccount->user;
        } else {
            $email = $providerUser->getEmail();
            $user = null;

            if ($email !== null && $email !== '') {
                $user = User::query()
                    ->where('email', $email)
                    ->first();
            }

            if ($user === null) {
                $user = User::query()->create([
                    'name' => $this->resolveDisplayName($providerUser, $provider),
                    'email' => $email ?: $this->resolveFallbackEmail($provider, $providerUserId),
                    'password' => Str::random(40),
                ]);
            }

            SocialAccount::query()->create([
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_user_id' => $providerUserId,
                'provider_email' => $email,
                'provider_avatar' => $providerUser->getAvatar(),
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended('/')->with('success', 'Welcome back!');
    }

    private function resolveDisplayName(ProviderUser $providerUser, string $provider): string
    {
        $name = $providerUser->getName();

        if (is_string($name) && $name !== '') {
            return $name;
        }

        $nickname = $providerUser->getNickname();

        if (is_string($nickname) && $nickname !== '') {
            return $nickname;
        }

        return $this->providerLabel($provider).' User';
    }

    private function resolveFallbackEmail(string $provider, string $providerUserId): string
    {
        $base = Str::slug($provider, '_').'_'.$providerUserId;
        $candidate = $base.'@oauth.chirper.local';
        $counter = 1;

        while (User::query()->where('email', $candidate)->exists()) {
            $candidate = $base.'_'.$counter.'@oauth.chirper.local';
            $counter++;
        }

        return $candidate;
    }

    private function isSupportedProvider(string $provider): bool
    {
        return in_array($provider, self::SUPPORTED_PROVIDERS, true);
    }

    private function providerLabel(string $provider): string
    {
        return Str::headline($provider);
    }
}
