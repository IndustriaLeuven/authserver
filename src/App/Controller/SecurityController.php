<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;

class SecurityController extends Controller
{
    private function getError(Request $request)
    {
        $error = null;
        // get the login error if there is one
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $request->getSession()->get(SecurityContext::AUTHENTICATION_ERROR);
            $request->getSession()->remove(SecurityContext::AUTHENTICATION_ERROR);
        }
        return $error;
    }

    public function loginAction(Request $request)
    {
        $session = $request->getSession();

        $error = $this->getError($request);

        // Add the following lines
        if ($session->has('_security.target_path')) {
            if (false !== strpos($session->get('_security.target_path'), $this->generateUrl('fos_oauth_server_authorize'))) {
                $session->set('_fos_oauth_server.ensure_logout', true);
            }
        }

        return $this->render('AppBundle:Security:login.html.twig', array(
            // last username entered by the user
            'last_username' => $session->get(SecurityContext::LAST_USERNAME),
            'error'         => $error,
            'error_type'    => $error?get_class($error):null,
        ));
    }

    public function shibbolethAction(Request $request)
    {
        $session = $request->getSession();

        if($this->isGranted('ROLE_USER'))
            return new RedirectResponse($session->get('_security.target_path', $this->generateUrl('home')));

        $error = $this->getError($request);

        if($error === null)
            return new RedirectResponse($this->get('shibboleth')->getLoginUrl($request));

        return $this->render('AppBundle:Security:login.html.twig', array(
            // last username entered by the user
            'last_username' => $session->get(SecurityContext::LAST_USERNAME),
            'error'         => $error,
            'error_type'    => $error?get_class($error):null,
        ));
    }
}
