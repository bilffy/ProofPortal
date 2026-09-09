<?php

namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Lab404\Impersonate\Guard\SessionGuard as BaseSessionGuard;

class ImpersonateSessionGuard extends BaseSessionGuard
{
    /**
     * Log in without firing events and without rotating the session ID.
     *
     * The default implementation calls updateSession(), which runs session->migrate(true).
     * That issues a new session cookie on redirect; Firefox and Edge often fail to persist
     * it across the impersonation redirect, which looks like an immediate logout.
     */
    public function quietLogin(Authenticatable $user): void
    {
        $this->session->put($this->getName(), $user->getAuthIdentifier());
        $this->loggedOut = false;
        $this->setUser($user);
    }
}
