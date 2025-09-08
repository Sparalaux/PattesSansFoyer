<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\AnimauxRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Animaux;
use App\Entity\User;
use App\Entity\Reservation;

final class AnimauxControllerTest extends WebTestCase
{
    public function testAnimauxIndexLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $client->request('GET', '/animaux');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('h1', 'Liste des animaux'); // à adapter selon ton template
    }

    public function testAnimauxIndexWithFilters(): void
    {
        $client = static::createClient();
        $client->request('GET', '/animaux?espece=chien&age=2');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSelectorExists('.animal-card');
    }

    public function testAnimauxDetailsPage(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var AnimauxRepository $repo */
        $repo = $container->get(AnimauxRepository::class);
        $animal = $repo->findOneBy([]);

        if (!$animal) {
            self::markTestSkipped('Aucun animal en base pour tester la page détails.');
        }

        $client->request('GET', '/animaux/' . $animal->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('h1', $animal->getNom()); // adapte selon ton template
    }

    public function testAnimauxDetailsNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/animaux/999999'); // ID qui n’existe pas

        self::assertResponseStatusCodeSame(Response::HTTP_OK); // tu affiches ta page 404 custom
        self::assertSelectorExists('.error-message'); // classe CSS de ton template `errors/404.html.twig`
    }

    public function testReservationRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/animaux/1/reserver');

        // Symfony redirige vers la page de login si pas connecté
        self::assertResponseRedirects('/login');
    }

    public function testReservationWhenLoggedIn(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);

        // On récupère un animal
        $animal = $em->getRepository(Animaux::class)->findOneBy([]);
        if (!$animal) {
            self::markTestSkipped('Aucun animal en base pour tester la réservation.');
        }

        // On crée un utilisateur fictif
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER']);
        $em->persist($user);
        $em->flush();

        // On connecte cet utilisateur
        $client->loginUser($user);

        // On fait une réservation
        $client->request('GET', '/animaux/' . $animal->getId() . '/reserver');

        // Vérifie que ça redirige bien vers la page détails
        self::assertResponseRedirects('/animaux/' . $animal->getId());

        // Vérifie en BDD qu’une réservation a bien été créée
        $reservation = $em->getRepository(Reservation::class)->findOneBy([
            'animal' => $animal,
            'user'   => $user,
        ]);
        self::assertNotNull($reservation, 'La réservation doit être enregistrée.');
    }

    public function testReservationWithInvalidAnimal(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        // On crée un utilisateur fictif
        $user = new User();
        $user->setEmail('test2@example.com');
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER']);
        $em->persist($user);
        $em->flush();

        $client->loginUser($user);

        // On essaye de réserver un animal inexistant
        $client->request('GET', '/animaux/999999/reserver');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSelectorExists('.error-message'); // vérifie ta page d’erreur personnalisée
    }
}