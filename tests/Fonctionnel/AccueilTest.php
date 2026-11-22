<?php

namespace App\Tests\Fonctionnel;

use App\Tests\FonctionnelCase;
use Symfony\Component\DomCrawler\Crawler;

class AccueilTest extends FonctionnelCase
{
    public function testAccueilAvecGraphique(): void
    {
        [$voiture, $station] = $this->creerVoitureEtStation();
        $this->creerPlein($voiture, $station);

        $crawler = $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('canvas'));
    }

    /**
     * Régression du 21/11/2026 : bootstrap.bundle.min.js absent du serveur (404), menu mobile inopérant.
     */
    public function testChaqueScriptEtFeuilleDeStyleLocalRepond200(): void
    {
        $crawler = $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $urls = array_merge(
            $crawler->filter('script[src]')->each(fn (Crawler $n) => $n->attr('src')),
            $crawler->filter('link[rel=stylesheet][href]')->each(fn (Crawler $n) => $n->attr('href')),
            $crawler->filter('link[rel=modulepreload][href]')->each(fn (Crawler $n) => $n->attr('href')),
        );
        $urls = array_filter($urls, fn (string $u) => str_starts_with($u, '/') && !str_starts_with($u, '//'));
        $this->assertNotEmpty($urls);
        $this->assertContains('/bootstrap/js/bootstrap.bundle.min.js', $urls);

        foreach ($urls as $url) {
            $chemin = parse_url($url, PHP_URL_PATH);
            // fichiers servis tels quels par le serveur web (public/) : ils doivent exister ; le reste (AssetMapper) passe par le noyau
            if (is_file(dirname(__DIR__, 2).'/public'.$chemin)) {
                $this->addToAssertionCount(1);
                continue;
            }
            $this->client->request('GET', $url);
            $this->assertSame(200, $this->client->getInternalResponse()->getStatusCode(), $url.' doit répondre 200');
        }
    }
}
