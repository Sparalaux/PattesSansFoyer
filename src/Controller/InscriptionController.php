<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class InscriptionController extends AbstractController
{
    #[Route('/inscription', name: 'inscription')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        LoggerInterface $logger
    ): Response
    {
        try{
            $user = new User();
            $form = $this->createForm(UserType::class, $user);
            $form->handleRequest($request);

            if($form->isSubmitted() && $form->isValid()){
                $password = $form->get('password')->getData();
                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
                $em->persist($user);
                $em->flush();

                return $this->redirectToRoute(('app_login'));
            }

        return $this->render('inscription/index.html.twig', [
            'form' => $form->createView()
        ]);
        }catch (\Doctrine\DBAL\Exception $e) {
            $logger->error('Erreur BDD dans AnimauxController::index() : ' . $e->getMessage());

            $this->addFlash('error', 'Impossible de charger la liste des animaux pour le moment.');

            return $this->render('errors/database_error.html.twig', [
                'message' => 'Erreur lors du chargement des inscriptions',
            ]);
        } catch (\Exception $e) {
            $logger->critical('Erreur inattendue dans AnimauxController::index() : ' . $e->getMessage());

            $this->addFlash('error', 'Une erreur est survenue.');

            return $this->render('errors/general_error.html.twig', [
                'message' => 'Une erreur est survenue lors du chargement des inscriptions.',
            ]);
        }
    }
}
