<?php

namespace App\Controller;

use App\Entity\Animaux;
use App\Form\AnimalType;
use App\Form\ModifierType;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


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


#[Route('/profil/modifier', name: 'profil_modifier')]
    #[IsGranted('ROLE_USER')]
    public function modifierProfil(
        Request $request,
        Security $security,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        LoggerInterface $logger
    ): Response {
        try {
            $user = $security->getUser();
            $form = $this->createForm(ModifierType::class, $user);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // Si l'utilisateur modifie son mot de passe
                $plainPassword = $form->get('password')->getData();
                if ($plainPassword) {
                    $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                    $user->setPassword($hashedPassword);
                }

                $em->flush();

                $this->addFlash('success', 'Profil mis à jour avec succès !');
                return $this->redirectToRoute('profil');
            }

            return $this->render('profil/modifier.html.twig', [
                'form' => $form->createView(),
            ]);
        } catch (\Doctrine\DBAL\Exception $e) {
            $logger->error('Erreur BDD dans ProfilController::modifierProfil() : ' . $e->getMessage());

            $this->addFlash('error', 'Impossible de modifier le profil pour le moment.');

            return $this->render('errors/database_error.html.twig', [
                'message' => 'Erreur lors de la modification du profil',
            ]);
        } catch (\Exception $e) {
            $logger->critical('Erreur inattendue dans ProfilController::modifierProfil() : ' . $e->getMessage());

            $this->addFlash('error', 'Une erreur est survenue.');

            return $this->render('errors/general_error.html.twig', [
                'message' => 'Une erreur est survenue lors de la modification du profil.',
            ]);
        }
    }

#[Route('/profil/supprimer', name: 'profil_supprimer', methods: ['POST'])]
public function supprimerProfil(
    EntityManagerInterface $em,
    Security $security,
    TokenStorageInterface $tokenStorage,
    Request $request,
    LoggerInterface $logger
): Response {
    try {
        $user = $security->getUser();

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        // Vérification CSRF
        if ($this->isCsrfTokenValid('supprimer_profil_' . $user->getId(), $request->request->get('_token'))) {
            $session = $request->getSession();
            $session->invalidate();

            $tokenStorage->setToken(null);

            $em->remove($user);
            $em->flush();

            $this->addFlash('success', 'Votre compte a été supprimé avec succès.');

            return $this->redirectToRoute('home');
        }

        $this->addFlash('error', 'La suppression du compte a échoué.');
        return $this->redirectToRoute('profil');

    } catch (\Exception $e) {
        $logger->error('Erreur suppression compte : ' . $e->getMessage());

        $this->addFlash('error', 'Impossible de supprimer votre profil.');

        return $this->redirectToRoute('profil');
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
