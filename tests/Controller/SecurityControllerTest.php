<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class SecurityControllerTest extends WebTestCase
{
    /**
     * Crée un utilisateur de test dans la base
     */
    private function createUser(string $email = 'test@example.com', string $password = 'password123'): User
    {
        $em = static::getContainer()->get('doctrine')->getManager();

        $user = new User();
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setEmail($email);
        $user->setPassword(password_hash($password, PASSWORD_BCRYPT));

        $em->persist($user);
        $em->flush();

        return $user;
    }

    /**
     * Teste que la page login s'affiche correctement
     */
    public function testLoginPageAccessible(): void
    {
        $client = static::createClient();

        $client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form'); // Vérifie que le formulaire est présent
        self::assertSelectorExists('input[name="email"]');
        self::assertSelectorExists('input[name="password"]');
    }

    /**
     * Teste une tentative de connexion avec de bons identifiants
     */
    public function testSuccessfulLogin(): void
    {
        $client = static::createClient();

        $this->createUser('success@example.com', 'password123');

        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Valider')->form([
            'email' => 'success@example.com',
            'password' => 'password123',
        ]);

        $client->submit($form);

        self::assertResponseRedirects('/');
        $client->followRedirect();
        self::assertSelectorExists('.flash-success, nav'); // Vérifie qu'on est bien connecté
    }

    /**
     * Teste une tentative de connexion avec de mauvais identifiants
     */
    public function testFailedLogin(): void
    {
        $client = static::createClient();

        $this->createUser('fail@example.com', 'password123');

        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Valider')->form([
            'email' => 'fail@example.com',
            'password' => 'wrongpassword',
        ]);

        $client->submit($form);

        self::assertResponseRedirects('/login');
        $client->followRedirect();

        self::assertSelectorExists('.alert-danger');
        self::assertSelectorTextContains('.alert-danger', 'Identifiants invalides');
    }

    /**
     * Teste le blocage après plusieurs mauvaises tentatives (Rate Limiter)
     */
    public function testRateLimiterBlocksAfterFiveAttempts(): void
    {
        $client = static::createClient();

        $this->createUser('block@example.com', 'password123');

        for ($i = 0; $i < 6; $i++) {
            $crawler = $client->request('GET', '/login');
            $form = $crawler->selectButton('Valider')->form([
                'email' => 'block@example.com',
                'password' => 'wrongpassword',
            ]);
            $client->submit($form);
        }

        $client->followRedirect();

        self::assertSelectorExists('.alert-danger');
        self::assertSelectorTextContains('.alert-danger', 'Trop de tentatives. Réessayez plus tard.');
    }

    /**
     * Teste que la déconnexion fonctionne correctement
     */
    public function testLogout(): void
    {
        $client = static::createClient();

        $user = $this->createUser('logout@example.com');
        $client->loginUser($user);

        $client->request('GET', '/logout');

        self::assertResponseRedirects('/'); // Symfony gère automatiquement la redirection après logout
    }

    /**
     * Teste la gestion des erreurs dans la page de login
     */
    public function testLoginPageHandlesExceptionGracefully(): void
    {
        $client = static::createClient();

        // On simule une exception en désactivant temporairement la base de données
        static::getContainer()->get('doctrine')->getConnection()->close();

        $client->request('GET', '/login');

        self::assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
        self::assertSelectorExists('h1');
        self::assertSelectorTextContains('h1', 'Une erreur est survenue');
    }
}
