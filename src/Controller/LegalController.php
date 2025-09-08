<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LegalController extends AbstractController
{
    #[Route('/legal/condition_utilisation', name: 'condition_utilisation')]
    public function condition_utilisation(LoggerInterface $logger): Response
    {
        try {
            return $this->render('legal/conditions_utilisation.html.twig', [
                'controller_name' => 'LegalController',
        ]);
        }catch (\Doctrine\DBAL\Exception $e) {
            $logger->error('Erreur BDD dans AnimauxController::index() : ' . $e->getMessage());

            $this->addFlash('error', 'Impossible de charger la liste des animaux pour le moment.');

            return $this->render('errors/database_error.html.twig', [
                'message' => 'Erreur lors du chargement des conditions d\'utilisation',
            ]);
        } catch (\Exception $e) {
            $logger->critical('Erreur inattendue dans AnimauxController::index() : ' . $e->getMessage());

            $this->addFlash('error', 'Une erreur est survenue.');

            return $this->render('errors/general_error.html.twig', [
                'message' => 'Une erreur est survenue lors du chargement des conditions d\'utilisation.',
            ]);
        }
    }

#[Route('/legal/mention_legal', name: 'mention_legal')]
    public function mention_legal(LoggerInterface $logger): Response
    {
        try{
            return $this->render('legal/mentions_legales.html.twig', [
                'controller_name' => 'LegalController',
        ]);
        }catch (\Doctrine\DBAL\Exception $e) {
            $logger->error('Erreur BDD dans AnimauxController::index() : ' . $e->getMessage());

            $this->addFlash('error', 'Impossible de charger la liste des animaux pour le moment.');

            return $this->render('errors/database_error.html.twig', [
                'message' => 'Erreur lors du chargement des mentions légales',
            ]);
        } catch (\Exception $e) {
            $logger->critical('Erreur inattendue dans AnimauxController::index() : ' . $e->getMessage());

            $this->addFlash('error', 'Une erreur est survenue.');

            return $this->render('errors/general_error.html.twig', [
                'message' => 'Une erreur est survenue lors du chargement des mentions légales.',
            ]);
        }
    }

#[Route('/legal/politique_confi', name: 'politique_confi')]
    public function politique_confi(LoggerInterface $logger): Response
    {
        try{
            return $this->render('legal/politique_confidentialite.html.twig', [
                'controller_name' => 'LegalController',
        ]);
        }catch (\Doctrine\DBAL\Exception $e) {
            $logger->error('Erreur BDD dans AnimauxController::index() : ' . $e->getMessage());

            $this->addFlash('error', 'Impossible de charger la liste des animaux pour le moment.');

            return $this->render('errors/database_error.html.twig', [
                'message' => 'Erreur lors du chargement des politiques de confidentialités',
            ]);
        } catch (\Exception $e) {
            $logger->critical('Erreur inattendue dans AnimauxController::index() : ' . $e->getMessage());

            $this->addFlash('error', 'Une erreur est survenue.');

            return $this->render('errors/general_error.html.twig', [
                'message' => 'Une erreur est survenue lors du chargement des politiques de confidentialités.',
            ]);
        }
    }
#[Route('/legal/cookies', name: 'cookies_policy')]
    public function index(LoggerInterface $logger): Response
    {
        try{
            return $this->render('cookies/index.html.twig', [
                'controller_name' => 'LegalController',
    ]);
        } catch (\Doctrine\DBAL\Exception $e) {
            $logger->error('Erreur BDD dans AnimauxController::index() : ' . $e->getMessage());

            $this->addFlash('error', 'Impossible de charger la liste des animaux pour le moment.');

            return $this->render('errors/database_error.html.twig', [
                'message' => 'Erreur lors du chargement des politiques des cookies',
            ]);
        } catch (\Exception $e) {
            $logger->critical('Erreur inattendue dans AnimauxController::index() : ' . $e->getMessage());

            $this->addFlash('error', 'Une erreur est survenue.');

            return $this->render('errors/general_error.html.twig', [
                'message' => 'Une erreur est survenue lors du chargement des politiques des cookies.',
            ]);
        }
    }

}
