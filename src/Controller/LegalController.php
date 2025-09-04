<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LegalController extends AbstractController
{
    #[Route('/legal/condition_utilisation', name: 'condition_utilisation')]
    public function condition_utilisation(): Response
    {
        return $this->render('legal/conditions_utilisation.html.twig', [
            'controller_name' => 'LegalController',
        ]);
    }

#[Route('/legal/mention_legal', name: 'mention_legal')]
    public function mention_legal(): Response
    {
        return $this->render('legal/mentions_legales.html.twig', [
            'controller_name' => 'LegalController',
        ]);
    }

#[Route('/legal/politique_confi', name: 'politique_confi')]
    public function politique_confi(): Response
    {
        return $this->render('legal/politique_confidentialite.html.twig', [
            'controller_name' => 'LegalController',
        ]);
    }
#[Route('/legal/cookies', name: 'cookies_policy')]
public function index(): Response
{
    return $this->render('cookies/index.html.twig');
}
}
