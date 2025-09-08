<?php

namespace App\Controller;

use App\Repository\AnimauxRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(
        Request $request,
        AnimauxRepository $animauxRepo,
        LoggerInterface $logger
    ): Response {
        try {
            // Récupération de l'utilisateur connecté
            $user = $this->getUser();

            // Tentative de récupération des animaux
            $animaux = $animauxRepo->findBy([], null, 4);

            // Récupération du prénom de l'utilisateur s'il est connecté
            $prenom = $user ? $user->getUserIdentifier() : null;

            // Rendu de la page d'accueil
            return $this->render('home/index.html.twig', [
                'controller_name' => 'HomeController',
                'animaux' => $animaux,
                'prenom' => $prenom,
            ]);

        } catch (\Doctrine\DBAL\Exception $e) {
            // Erreur spécifique liée à la base de données
            $logger->error('Erreur base de données sur la page Home : ' . $e->getMessage());

            // Message flash pour l'utilisateur
            $this->addFlash('error', 'Impossible de charger les données des animaux. Réessayez plus tard.');

            // Redirection vers une page d'erreur personnalisée
            return $this->render('errors/database_error.html.twig', [
                'message' => 'Erreur lors de la récupération des animaux',
            ]);

        } catch (\Exception $e) {
            // Gestion des autres erreurs générales
            $logger->critical('Erreur inattendue sur la page Home : ' . $e->getMessage());

            // Message pour l'utilisateur
            $this->addFlash('error', 'Une erreur est survenue, veuillez réessayer plus tard.');

            // Affiche une page d'erreur générique
            return $this->render('errors/general_error.html.twig', [
                'message' => 'Une erreur est survenue',
            ]);
        }
    }
}
