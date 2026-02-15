<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class EmailVerifiedUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isEmailVerified()) {
            throw new CustomUserMessageAccountStatusException('⚠️ Votre email n\'est pas encore vérifié. Consultez votre boîte mail ou demandez un nouveau lien de vérification.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
