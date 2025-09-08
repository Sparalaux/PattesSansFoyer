<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class InscriptionControllerTest extends WebTestCase
{
    /**
     * Teste que la page d'inscription se charge correctement.
     */
    public function testInscriptionPageLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $client->request('GET', '/inscription');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form');
        self::assertSelectorTextContains('h1', 'Inscription');
    }

    /**
     * Teste l'inscription avec des données valides.
     */
    public function testValidRegistrationCreatesUser(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/inscription');

        // On récupère le formulaire et on le remplit
        $form = $crawler->selectButton('S\'inscrire')->form([
            'user[nom]' => 'Dupont',
            'user[prenom]' => 'Jean',
            'user[email]' => 'jean.dupont@example.com',
            'user[password]' => 'MonSuperMotDePasse123!',
        ]);

        $client->submit($form);

        // Vérifie que la redirection se fait vers la page de login
        self::assertResponseRedirects('/login');

        // On suit la redirection
        $client->followRedirect();

        // Vérifie qu'un utilisateur a bien été créé en base
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'jean.dupont@example.com']);

        self::assertNotNull($user, 'Un utilisateur doit être créé.');
        self::assertSame('jean.dupont@example.com', $user->getEmail());
    }

    /**
     * Teste l'inscription avec un email déjà existant.
     */
    public function testRegistrationWithExistingEmailFails(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/inscription');

        // On crée un utilisateur existant
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = new \App\Entity\User();
        $user->setNom('Test');
        $user->setPrenom('Testeur');
        $user->setEmail('existant@example.com');
        $user->setPassword(password_hash('password', PASSWORD_BCRYPT));

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($user);
        $em->flush();

        // On soumet un formulaire avec le même email
        $form = $crawler->selectButton('S\'inscrire')->form([
            'user[nom]' => 'Dupont',
            'user[prenom]' => 'Jean',
            'user[email]' => 'existant@example.com',
            'user[password]' => 'MotDePasse123!',
        ]);

        $client->submit($form);

        // Vérifie que l'utilisateur reste sur la page inscription
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        // Vérifie que le message d'erreur de validation s'affiche
        self::assertSelectorExists('.form-error-message');
    }

    /**
     * Teste l'inscription avec un formulaire invalide.
     */
    public function testInvalidRegistrationShowsErrors(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/inscription');

        // Soumission d'un formulaire avec un email invalide et un mot de passe vide
        $form = $crawler->selectButton('S\'inscrire')->form([
            'user[nom]' => '',
            'user[prenom]' => '',
            'user[email]' => 'invalid-email',
            'user[password]' => '',
        ]);

        $client->submit($form);

        // On reste sur la page d'inscription
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        // Vérifie que les messages d'erreurs sont présents
        self::assertSelectorExists('.form-error-message');
    }

    /**
     * Teste la gestion d'une erreur serveur.
     */
    public function testInscriptionPageHandlesServerError(): void
    {
        $client = static::createClient();

        // On simule une soumission incorrecte pour déclencher une exception
        $client->request('POST', '/inscription', [
            'user[email]' => null,
            'user[password]' => null,
        ]);

        // Vérifie que la page d'erreur s'affiche correctement
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSelectorExists('.error-message');
        self::assertSelectorTextContains('.error-message', 'Une erreur est survenue');
    }
}