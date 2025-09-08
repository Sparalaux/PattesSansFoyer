<?php

namespace App\Controller;

use App\Entity\Animaux;
use App\Form\AnimalType;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


final class ProfilController extends AbstractController
{
    #[Route('/profil', name: 'profil')]
    public function index(Security $security, ReservationRepository $reservationRepository, LoggerInterface $logger): Response
    {
        try{
            $user = $security->getUser();
            $reservations = $reservationRepository->findBy(['user' => $user]);

            return $this->render('profil/index.html.twig', [
                'user' => $user,
            'roles' => $user->getRoles(),
            'reservations' => $reservations,
        ]);
        }catch (\Doctrine\DBAL\Exception $e) {
            $logger->error('Erreur BDD dans AnimauxController::index() : ' . $e->getMessage());

            $this->addFlash('error', 'Impossible de charger la liste des animaux pour le moment.');

            return $this->render('errors/database_error.html.twig', [
                'message' => 'Erreur lors du chargement des informations du profil',
            ]);
        } catch (\Exception $e) {
            $logger->critical('Erreur inattendue dans AnimauxController::index() : ' . $e->getMessage());

            $this->addFlash('error', 'Une erreur est survenue.');

            return $this->render('errors/general_error.html.twig', [
                'message' => 'Une erreur est survenue lors du chargement du profil.',
            ]);
        }
    }

#[Route('/profil/agence/ajouter-animal', name: 'agence_ajout_animal')]
public function ajouterAnimal(
    Request $request,
    EntityManagerInterface $em,
    LoggerInterface $logger
): Response {
    try{
        $animal = new Animaux();
        $form = $this->createForm(AnimalType::class, $animal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($animal);
            $em->flush();

            $this->addFlash('success', 'Animal ajouté avec succès !');
            return $this->redirectToRoute('profil');
        }

    return $this->render('profil/ajouter_animal.html.twig', [
        'form' => $form->createView()
    ]);
    }catch (\Doctrine\DBAL\Exception $e) {
            $logger->error('Erreur BDD dans AnimauxController::index() : ' . $e->getMessage());

            $this->addFlash('error', 'Impossible de charger la liste des animaux pour le moment.');

            return $this->render('errors/database_error.html.twig', [
                'message' => 'Erreur lors du chargement de l\'animal a ajoter',
            ]);
        } catch (\Exception $e) {
            $logger->critical('Erreur inattendue dans AnimauxController::index() : ' . $e->getMessage());

            $this->addFlash('error', 'Une erreur est survenue.');

            return $this->render('errors/general_error.html.twig', [
                'message' => 'Une erreur est survenue lors de l\'ajout d\'animal.',
            ]);
        }
}

#[Route('/reservation/{id}/supprimer', name: 'reservation_supprimer', methods: ['POST'])]
public function supprimerReservation(
    int $id,
    ReservationRepository $reservationRepository,
    EntityManagerInterface $em,
    Security $security,
    Request $request,
    LoggerInterface $logger
): Response {
    try{
        $reservation = $reservationRepository->find($id);

        if (!$reservation || $reservation->getUser() !== $security->getUser()) {
            throw $this->createAccessDeniedException('Réservation non valide.');
        }

    // Protection CSRF
    if ($this->isCsrfTokenValid('supprimer_reservation_' . $reservation->getId(), $request->request->get('_token'))) {
        $em->remove($reservation);
        $em->flush();

        $this->addFlash('success', 'Réservation supprimée avec succès.');
    }

    return $this->redirectToRoute('profil');
    }catch (\Doctrine\DBAL\Exception $e) {
            $logger->error('Erreur BDD dans AnimauxController::index() : ' . $e->getMessage());

            $this->addFlash('error', 'Impossible de charger la liste des animaux pour le moment.');

            return $this->render('errors/database_error.html.twig', [
                'message' => 'Erreur lors du chargement des reservations',
            ]);
        } catch (\Exception $e) {
            $logger->critical('Erreur inattendue dans AnimauxController::index() : ' . $e->getMessage());

            $this->addFlash('error', 'Une erreur est survenue.');

            return $this->render('errors/general_error.html.twig', [
                'message' => 'Une erreur est survenue lors de la suppression de la reservation.',
            ]);
        }
}
}
