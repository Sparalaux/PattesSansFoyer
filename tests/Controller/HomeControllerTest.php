<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\AnimauxRepository;

final class HomeControllerTest extends WebTestCase
{
    /**
     * Teste si la page d'accueil est accessible.
     */
    public function testHomePageLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        // Vérifie que la réponse est OK (code 200)
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        // Vérifie que le contenu HTML contient un élément attendu
        self::assertSelectorExists('h1', 'Accueil');
    }

    /**
     * Teste que la page d'accueil affiche bien les animaux.
     */
    public function testHomePageDisplaysAnimals(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var AnimauxRepository $repo */
        $repo = $container->get(AnimauxRepository::class);

        // On compte combien d'animaux sont présents en base
        $nbAnimaux = count($repo->findAll());

        $client->request('GET', '/');

        // Vérifie que la réponse est OK
        self::assertResponseIsSuccessful();

        // Si la base contient des animaux, on vérifie qu'ils apparaissent bien
        if ($nbAnimaux > 0) {
            self::assertSelectorExists('.animal-card', 'Les animaux devraient être visibles');
        }
    }

    /**
     * Teste le comportement en cas d'erreur de base de données.
     */
    public function testHomePageHandlesDatabaseError(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        // On "simule" un repository qui renvoie une exception
        $mockRepo = $this->createMock(AnimauxRepository::class);
        $mockRepo->method('findBy')->willThrowException(new \Doctrine\DBAL\Exception('Erreur DB simulée'));

        // Remplace le repository dans le container
        $container->set(AnimauxRepository::class, $mockRepo);

        $client->request('GET', '/');

        // Vérifie que l'application renvoie un code 500 ou affiche la page d'erreur personnalisée
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        // Vérifie que le message d'erreur personnalisé est affiché
        self::assertSelectorTextContains('.error-message', 'Erreur lors de la récupération des animaux');
    }

    /**
     * Teste que la page d'accueil contient le lien "Voir tous les animaux".
     */
    public function testHomePageHasLinkToAllAnimals(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        // Vérifie que le lien existe
        self::assertSelectorExists('a[href="/animaux"]');
    }
}
