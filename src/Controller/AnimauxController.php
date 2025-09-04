<?php

namespace App\Controller;


use App\Entity\Reservation;
use App\Repository\AnimauxRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AnimauxController extends AbstractController
{
    #[Route('/animaux', name: 'animaux')]
    public function index(Request $request,AnimauxRepository $animauxRepository): Response
    {

        $filters = [
            'espece' => $request->query->get('espece'),
            'race' => $request->query->get('race'),
            'age' => $request->query->get('age'),
            'urgence' => $request->query->get('urgence'),
        ];

        $animaux = $animauxRepository->findByFilters($filters);

        return $this->render('animaux/index.html.twig', [
            'animaux' => $animaux,
        ]);
    }

#[Route('/animaux/{id}', name: 'animaux_details', requirements: ['id' => '\d+'])]
    public function animal(AnimauxRepository $animauxRepository, int $id): Response
    {
        $animal = $animauxRepository->find($id);

        if (!$animal) {
            throw $this->createNotFoundException('Animal non trouvé');
        }

        return $this->render('animaux/animal.html.twig', [
            'animal' => $animal,
        ]);
    }

#[Route('/animaux/{id}/reserver', name: 'animal_reserver')]
#[IsGranted('ROLE_USER')]
public function reserver(
    int $id,
    Request $request,
    AnimauxRepository $animauxRepository,
    EntityManagerInterface $em,
    Security $security
): Response {
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
}
}
