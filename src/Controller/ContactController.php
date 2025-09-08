<?php

namespace App\Controller;

use App\Form\ContactType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact')]
    public function index(Request $request, MailerInterface $mailer, LoggerInterface $logger): Response
    {
        try{
            $form = $this->createForm(ContactType::class);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();

                $email = (new Email())
                ->from($data['email'])
                ->to('tonadresse@email.com') // ⚠️ Mets ici ta vraie adresse
                ->subject('Nouveau message de contact')
                ->text(
                    "Nom : " . $data['nom'] . "\n" .
                    "Email : " . $data['email'] . "\n\n" .
                    "Message : \n" . $data['message']
                );

                $mailer->send($email);

                $this->addFlash('success', 'Votre message a bien été envoyé !');

                return $this->redirectToRoute('contact');
            }

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(),
        ]);
        }catch (\Doctrine\DBAL\Exception $e) {
            $logger->error('Erreur BDD dans AnimauxController::index() : ' . $e->getMessage());

            $this->addFlash('error', 'Impossible de charger la liste des animaux pour le moment.');

            return $this->render('errors/database_error.html.twig', [
                'message' => 'Erreur lors du chargement des contacts',
            ]);
        } catch (\Exception $e) {
            $logger->critical('Erreur inattendue dans AnimauxController::index() : ' . $e->getMessage());

            $this->addFlash('error', 'Une erreur est survenue.');

            return $this->render('errors/general_error.html.twig', [
                'message' => 'Une erreur est survenue lors du chargement des contacts.',
            ]);
        }
    }
}