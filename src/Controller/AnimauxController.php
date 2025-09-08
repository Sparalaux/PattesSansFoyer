<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Repository\AnimauxRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AnimauxController extends AbstractController
{
    #[Route('/animaux', name: 'animaux')]
    public function index(
        Request $request,
        AnimauxRepository $animauxRepository,
        LoggerInterface $logger
    ): Response {
        try {
            $filters = [
                'espece'  => $request->query->get('espece'),
                'race'    => $request->query->get('race'),
                'age'     => $request->query->get('age'),
                'urgence' => $request->query->get('urgence'),
            ];

            $animaux = $animauxRepository->findByFilters($filters);

            return $this->render('animaux/index.html.twig', [
                'animaux' => $animaux,
            ]);

        } catch (\Doctrine\DBAL\Exception $e) {
            $logger->error('Erreur BDD dans AnimauxController::index() : ' . $e->getMessage());

            $this->addFlash('error', 'Impossible de charger la liste des animaux pour le moment.');

            return $this->render('errors/database_error.html.twig', [
                'message' => 'Erreur lors du chargement des animaux',
            ]);
        } catch (\Exception $e) {
            $logger->critical('Erreur inattendue dans AnimauxController::index() : ' . $e->getMessage());

            $this->addFlash('error', 'Une erreur est survenue.');

            return $this->render('errors/general_error.html.twig', [
                'message' => 'Une erreur est survenue lors du chargement des animaux.',
            ]);
        }
    }

    #[Route('/animaux/{id}', name: 'animaux_details', requirements: ['id' => '\d+'])]
    public function animal(
        AnimauxRepository $animauxRepository,
        LoggerInterface $logger,
        int $id
    ): Response {
        try {
            $animal = $animauxRepository->find($id);

            if (!$animal) {
                throw $this->createNotFoundException('Animal non trouvé.');
            }

            return $this->render('animaux/animal.html.twig', [
                'animal' => $animal,
            ]);

        } catch (\Doctrine\DBAL\Exception $e) {
            $logger->error('Erreur BDD dans AnimauxController::animal() : ' . $e->getMessage());

            $this->addFlash('error', 'Impossible d’accéder à cet animal pour le moment.');

            return $this->render('errors/database_error.html.twig', [
                'message' => 'Erreur lors de la récupération des détails de l’animal.',
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            // Gestion spécifique pour les 404
            $logger->warning('Animal introuvable (id=' . $id . ') : ' . $e->getMessage());

            return $this->render('errors/404.html.twig', [
                'message' => 'Cet animal n’existe pas.',
            ]);
        } catch (\Exception $e) {
            $logger->critical('Erreur inattendue dans AnimauxController::animal() : ' . $e->getMessage());

            $this->addFlash('error', 'Une erreur est survenue.');

            return $this->render('errors/general_error.html.twig', [
                'message' => 'Une erreur est survenue lors du chargement de l’animal.',
            ]);
        }
    }

    #[Route('/animaux/{id}/reserver', name: 'animal_reserver')]
    #[IsGranted('ROLE_USER')]
    public function reserver(
        int $id,
        Request $request,
        AnimauxRepository $animauxRepository,
        EntityManagerInterface $em,
        Security $security,
        LoggerInterface $logger
    ): Response {
        try {
            $animal = $animauxRepository->find($id);

            if (!$animal) {
                throw $this->createNotFoundException('Animal non trouvé.');
            }

            $reservation = new Reservation();
            $reservation->setAnimal($animal);
            $reservation->setUser($security->getUser());
            $reservation->setDateReservation(new \DateTime());

            $em->persist($reservation);
            $em->flush();

            $this->addFlash('success', 'Réservation enregistrée avec succès !');

            return $this->redirectToRoute('animaux_details', ['id' => $id]);

        } catch (\Doctrine\DBAL\Exception $e) {
            $logger->error('Erreur BDD dans AnimauxController::reserver() : ' . $e->getMessage());

            $this->addFlash('error', 'Impossible de réserver cet animal pour le moment.');

            return $this->render('errors/database_error.html.twig', [
                'message' => 'Erreur lors de l’enregistrement de la réservation.',
            ]);
        } catch (\Exception $e) {
            $logger->critical('Erreur inattendue dans AnimauxController::reserver() : ' . $e->getMessage());

            $this->addFlash('error', 'Une erreur est survenue.');

            return $this->render('errors/general_error.html.twig', [
                'message' => 'Impossible de réserver cet animal.',
            ]);
        }
    }
}
