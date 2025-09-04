<?php

namespace App\Controller;

use App\Repository\AnimauxRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{

    #[Route('/', name: 'home')]
    public function index(Request $request,AnimauxRepository $animauxRepo): Response
    {
        $user = $this->getUser();
        $animaux = $animauxRepo->findBy([], null, 4);

        if ($user) {
            $prenom = $user->getUserIdentifier();
        }
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'animaux' => $animaux,
        ]);
    }
}
