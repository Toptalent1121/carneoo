<?php

namespace App\Controller\Panel;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LoginController extends AbstractController
{

    public function login(AuthenticationUtils $authenticationUtils)
    {

        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->generateUrl('panel_admin_index'));
        }

        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastEmail = $authenticationUtils->getLastUsername();

        return $this->render('panel/login/index.html.twig',
                array(
                'last_email' => $lastEmail,
                'error' => $error,
        ));
    }
}