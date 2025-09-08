<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mailer\EventListener\MessageLoggerListener;
use Symfony\Component\Mailer\Transport\InMemoryTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\Response;

final class ContactControllerTest extends WebTestCase
{
    /**
     * Teste que la page Contact se charge correctement.
     */
    public function testContactPageLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $client->request('GET', '/contact');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form'); // Vérifie qu'il y a bien un formulaire
        self::assertSelectorTextContains('h1', 'Contact'); // Vérifie le titre de la page (à adapter)
    }

    /**
     * Teste l'envoi d'un formulaire de contact valide.
     */
    public function testSendValidContactForm(): void
    {
        $client = static::createClient();

        // Activer le transport mail en mémoire
        $container = static::getContainer();
        $transport = $container->get('mailer.transport');
        $logger = new MessageLoggerListener();
        $transport->registerPlugin($logger);

        // Charger la page Contact
        $crawler = $client->request('GET', '/contact');

        // Remplir le formulaire
        $form = $crawler->selectButton('Envoyer')->form([
            'contact[nom]' => 'Testeur',
            'contact[email]' => 'test@example.com',
            'contact[message]' => 'Ceci est un message de test',
        ]);

        // Soumettre le formulaire
        $client->submit($form);

        // Vérifier la redirection après soumission
        self::assertResponseRedirects('/contact');

        // Suivre la redirection
        $client->followRedirect();

        // Vérifie qu'un message de succès est affiché
        self::assertSelectorExists('.flash-success');
        self::assertSelectorTextContains('.flash-success', 'Votre message a bien été envoyé');

        // Vérifie qu'un email a bien été envoyé
        /** @var InMemoryTransport $transport */
        $emails = $transport->getSent();
        self::assertCount(1, $emails, 'Un email doit être envoyé.');
        self::assertInstanceOf(Email::class, $emails[0]);
        self::assertSame('Nouveau message de contact', $emails[0]->getSubject());
        self::assertSame('test@example.com', $emails[0]->getFrom()[0]->getAddress());
    }

    /**
     * Teste la soumission d'un formulaire invalide.
     */
    public function testSendInvalidContactForm(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/contact');

        // Remplir le formulaire avec un email invalide
        $form = $crawler->selectButton('Envoyer')->form([
            'contact[nom]' => 'Testeur',
            'contact[email]' => 'invalid-email',
            'contact[message]' => 'Ceci est un message test',
        ]);

        // Soumettre le formulaire
        $client->submit($form);

        // Vérifier qu'on reste sur la page Contact (pas de redirection)
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        // Vérifier qu'une erreur de validation s'affiche
        self::assertSelectorExists('.form-error-message');
    }

    /**
     * Teste la gestion d'une erreur serveur.
     */
    public function testContactPageHandlesServerError(): void
    {
        $client = static::createClient();

        // On simule une URL incorrecte pour déclencher une erreur
        $client->request('POST', '/contact', [
            'contact[nom]' => 'Testeur',
            'contact[email]' => 'test@example.com',
            'contact[message]' => null, // Message manquant pour déclencher une exception
        ]);

        // La page doit gérer l'erreur et afficher la page d'erreur générale
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSelectorExists('.error-message');
        self::assertSelectorTextContains('.error-message', 'Une erreur est survenue');
    }
}
