<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Animaux;
use App\Entity\Reservation;
use App\Repository\UserRepository;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ProfilControllerTest extends WebTestCase
{
    /**
     * Création d’un utilisateur de test
     */
    private function createUser(string $email = 'test@example.com'): User
    {
        $em = static::getContainer()->get('doctrine')->getManager();

        $user = new User();
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setEmail($email);
        $user->setPassword(password_hash('password123', PASSWORD_BCRYPT));

        $em->persist($user);
        $em->flush();

        return $user;
    }

    /**
     * Teste que la page profil est accessible pour un utilisateur connecté
     */
    public function testProfilPageAccessibleForLoggedUser(): void
    {
        $client = static::createClient();

        $user = $this->createUser();
        $client->loginUser($user);

        $client->request('GET', '/profil');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('h1'); // Exemple : le titre de la page
    }

    /**
     * Teste que l'ajout d'un animal fonctionne
     */
    public function testAjouterAnimal(): void
    {
        $client = static::createClient();

        $user = $this->createUser('agence@example.com');
        $client->loginUser($user);

        $crawler = $client->request('GET', '/profil/agence/ajouter-animal');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form');

        $form = $crawler->selectButton('Ajouter')->form([
            'animal[nom]' => 'Rex',
            'animal[espece]' => 'Chien',
            'animal[race]' => 'Berger Allemand',
            'animal[age]' => 3,
            'animal[urgence]' => false,
        ]);

        $client->submit($form);

        self::assertResponseRedirects('/profil');
        $client->followRedirect();

        self::assertSelectorExists('.flash-success');
        self::assertSelectorTextContains('.flash-success', 'Animal ajouté avec succès');
    }

    /**
     * Teste la suppression d'une réservation valide
     */
    public function testSupprimerReservation(): void
    {
        $client = static::createClient();

        $user = $this->createUser('resa@example.com');
        $client->loginUser($user);

        $em = static::getContainer()->get('doctrine')->getManager();

        // Création d'un animal et d'une réservation
        $animal = new Animaux();
        $animal->setNom('Minou');
        $animal->setEspece('Chat');
        $animal->setRace('Siamois');
        $animal->setAge(2);
        $animal->setUrgence(false);

        $em->persist($animal);

        $reservation = new Reservation();
        $reservation->setAnimal($animal);
        $reservation->setUser($user);
        $reservation->setDateReservation(new \DateTime());

        $em->persist($reservation);
        $em->flush();

        // Envoi du POST avec un token CSRF valide
        $crawler = $client->request('GET', '/profil');
        $csrfToken = $client->getContainer()->get('security.csrf.token_manager')
            ->getToken('supprimer_reservation_' . $reservation->getId());

        $client->request('POST', '/reservation/' . $reservation->getId() . '/supprimer', [
            '_token' => $csrfToken,
        ]);

        self::assertResponseRedirects('/profil');
        $client->followRedirect();

        self::assertSelectorExists('.flash-success');
        self::assertSelectorTextContains('.flash-success', 'Réservation supprimée avec succès');
    }

    /**
     * Teste la suppression d'une réservation avec un mauvais utilisateur
     */
    public function testSupprimerReservationAccessDenied(): void
    {
        $client = static::createClient();

        $user1 = $this->createUser('user1@example.com');
        $user2 = $this->createUser('user2@example.com');
        $client->loginUser($user2);

        $em = static::getContainer()->get('doctrine')->getManager();

        // Création d'un animal et d'une réservation pour user1
        $animal = new Animaux();
        $animal->setNom('Chouchou');
        $animal->setEspece('Chien');
        $animal->setRace('Bulldog');
        $animal->setAge(5);
        $animal->setUrgence(false);

        $em->persist($animal);

        $reservation = new Reservation();
        $reservation->setAnimal($animal);
        $reservation->setUser($user1);
        $reservation->setDateReservation(new \DateTime());

        $em->persist($reservation);
        $em->flush();

        // Tentative de suppression par un autre utilisateur
        $client->request('POST', '/reservation/' . $reservation->getId() . '/supprimer', [
            '_token' => 'fake_token',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
