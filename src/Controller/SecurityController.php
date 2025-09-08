<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(Request $request,
        AuthenticationUtils $authenticationUtils,
        RateLimiterFactory $_login_local_main_limiter,
        LoggerInterface $logger
    ): Response
    {
        try{
            // recuperation de l'erreur d'authentification si il y'en a une
        $error = $authenticationUtils->getLastAuthenticationError();
        // dernier username entré par l'utilisateur
        $lastUsername = $authenticationUtils->getLastUsername();

        $limiter = $_login_local_main_limiter->create($request->getClientIp());

        if ($request->isMethod('POST')) {
            $limiter->consume(1);
        }

        $limit = $limiter->consume(0);
        $remainingAttempts = $limit->getRemainingTokens();
        $maxAttempts = 5;

        $usedAttempts = $maxAttempts - $remainingAttempts;

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'used_attempts' => $usedAttempts,
            'max_attempts' => $maxAttempts,
            'recaptcha_site_key' => $_ENV['RECAPTCHA_SITE_KEY'] ?? '',
        ]);
        }catch (\Doctrine\DBAL\Exception $e) {
            $logger->error('Erreur BDD dans AnimauxController::index() : ' . $e->getMessage());

            $this->addFlash('error', 'Impossible de charger la liste des animaux pour le moment.');

            return $this->render('errors/database_error.html.twig', [
                'message' => 'Erreur lors du chargement des informations a la connexion',
            ]);
        } catch (\Exception $e) {
            $logger->critical('Erreur inattendue dans AnimauxController::index() : ' . $e->getMessage());

            $this->addFlash('error', 'Une erreur est survenue.');

            return $this->render('errors/general_error.html.twig', [
                'message' => 'Une erreur est survenue lors de la connexion.',
            ]);
        }
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
