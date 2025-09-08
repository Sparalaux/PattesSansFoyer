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


        self::assertResponseStatusCodeSame(Response::HTTP_OK);

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


        $nbAnimaux = count($repo->findAll());

        $client->request('GET', '/');


        self::assertResponseIsSuccessful();


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


        $mockRepo = $this->createMock(AnimauxRepository::class);
        $mockRepo->method('findBy')->willThrowException(new \Doctrine\DBAL\Exception('Erreur DB simulée'));


        $container->set(AnimauxRepository::class, $mockRepo);

        $client->request('GET', '/');


        self::assertResponseStatusCodeSame(Response::HTTP_OK);


        self::assertSelectorTextContains('.error-message', 'Erreur lors de la récupération des animaux');
    }

    /**
     * Teste que la page d'accueil contient le lien "Voir tous les animaux".
     */
    public function testHomePageHasLinkToAllAnimals(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        
        self::assertSelectorExists('a[href="/animaux"]');
    }
}
