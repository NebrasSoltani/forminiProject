<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginRedirectSubscriber implements EventSubscriberInterface
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        
        // Vérifier que c'est bien notre entité User
        if (!$user instanceof User) {
            return;
        }
        
        // Vérifier si l'utilisateur est admin
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles, true)) {
            $response = new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
            $event->setResponse($response);
            return;
        }
        
        $roleUtilisateur = $user->getRoleUtilisateur();
        
        if ($roleUtilisateur === 'formateur') {
            // Rediriger vers le dashboard formateur
            $response = new RedirectResponse($this->urlGenerator->generate('formateur_dashboard'));
            $event->setResponse($response);
        } elseif ($roleUtilisateur === 'apprenant') {
            // Rediriger vers le dashboard apprenant
            $response = new RedirectResponse($this->urlGenerator->generate('apprenant_dashboard'));
            $event->setResponse($response);
        } elseif ($roleUtilisateur === 'societe') {
            // Rediriger vers le dashboard société
            $response = new RedirectResponse($this->urlGenerator->generate('societe_dashboard'));
            $event->setResponse($response);
        }
    }
}