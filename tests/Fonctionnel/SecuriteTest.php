<?php

namespace App\Tests\Fonctionnel;

use App\Controller\Admin\CartonCrudController;
use App\Controller\Admin\CategorieObjetCrudController;
use App\Controller\Admin\MouvementCrudController;
use App\Controller\Admin\ObjetCrudController;
use App\Controller\Admin\PleinCrudController;
use App\Controller\Admin\StationCrudController;
use App\Controller\Admin\UtilisateurCrudController;
use App\Controller\Admin\VoitureCrudController;
use App\Tests\FonctionnelCase;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use PHPUnit\Framework\Attributes\DataProvider;

class SecuriteTest extends FonctionnelCase
{
    public function testLoginAffiche(): void
    {
        $this->client->request('GET', '/login');
        $this->assertResponseIsSuccessful();
    }

    public function testConnexion(): void
    {
        $this->creerUtilisateur('admin@exemple.fr', ['ROLE_ADMIN'], 'secret123');

        $this->client->request('GET', '/login');
        $this->client->submitForm('Connexion', ['email' => 'admin@exemple.fr', 'password' => 'secret123']);

        $this->assertResponseRedirects('/');
        $this->client->followRedirect();
        $this->assertSelectorExists('a[href="/admin"]');
    }

    public function testMauvaisMotDePasse(): void
    {
        $this->creerUtilisateur('admin@exemple.fr', ['ROLE_ADMIN'], 'secret123');

        $this->client->request('GET', '/login');
        $this->client->submitForm('Connexion', ['email' => 'admin@exemple.fr', 'password' => 'faux']);

        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-danger');
    }

    public function testAdminAnonymeRedirigeVersLogin(): void
    {
        $this->client->request('GET', '/admin');
        $this->assertResponseRedirects('http://localhost/login');
    }

    public function testAdminInterditAuRoleUser(): void
    {
        $this->client->loginUser($this->creerUtilisateur('user@exemple.fr', []));
        $this->client->request('GET', '/admin');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminOuvertAuRoleAdmin(): void
    {
        $this->client->loginUser($this->creerUtilisateur('admin@exemple.fr', ['ROLE_ADMIN']));
        $this->client->request('GET', '/admin');
        $this->assertResponseIsSuccessful();
    }

    /** @return iterable<string, array{string}> */
    public static function crudControllers(): iterable
    {
        foreach ([PleinCrudController::class, VoitureCrudController::class, StationCrudController::class, ObjetCrudController::class,
            CategorieObjetCrudController::class, CartonCrudController::class, MouvementCrudController::class, UtilisateurCrudController::class] as $c) {
            yield $c => [$c];
        }
    }

    #[DataProvider('crudControllers')]
    public function testChaqueCrudEasyAdminAfficheSonIndex(string $controller): void
    {
        $this->client->loginUser($this->creerUtilisateur('admin@exemple.fr', ['ROLE_ADMIN']));
        $url = static::getContainer()->get(AdminUrlGenerator::class)->setController($controller)->setAction('index')->generateUrl();

        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
    }

    public function testBoxEtVoitures(): void
    {
        $this->client->loginUser($this->creerUtilisateur('admin@exemple.fr', ['ROLE_ADMIN']));

        $this->client->request('GET', '/admin/box/');
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/admin/voiture/');
        $this->assertResponseIsSuccessful();
    }

    public function testInscriptionFermee(): void
    {
        $this->client->request('GET', '/register');
        $this->assertResponseStatusCodeSame(404);
    }
}
