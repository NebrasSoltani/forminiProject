<?php

namespace App\Security;

use App\Entity\User;
use KnpU\OAuth2ClientBundle\Security\User\OAuthUser;
use KnpU\OAuth2ClientBundle\Security\User\OAuthUserProviderInterface;

class OAuthUserProvider implements OAuthUserProviderInterface
{
    public function loadUserByOAuthUserResponse($userResponse): OAuthUser
    {
        // This method is called when the OAuth user is authenticated
        // We'll handle user creation/loading in the controller instead
        return new OAuthUser($userResponse->getEmail());
    }
}
