<?php

namespace App\Listeners;

use BadMethodCallException;
use Illuminate\Contracts\Auth\Authenticatable;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Lab404\Impersonate\Events\TakeImpersonation;

class SyncImpersonationSessionHash
{
    public function handleTake(TakeImpersonation $event): void
    {
        $this->syncSessionHash($event->impersonated);
    }

    public function handleLeave(LeaveImpersonation $event): void
    {
        $this->syncSessionHash($event->impersonator);
    }

    private function syncSessionHash(Authenticatable $user): void
    {
        $guard = config('auth.defaults.guard', 'web');
        $hash = $user->getAuthPassword();

        try {
            $hash = app('auth')->guard($guard)->hashPasswordForCookie($hash);
        } catch (BadMethodCallException) {
        }

        session()->put("password_hash_{$guard}", $hash);
    }
}
