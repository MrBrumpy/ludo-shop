<?php

declare(strict_types=1);

namespace App\Tests\Functional;

class LoginTest extends FunctionalTestCase
{
    public function testLoginPageIsAccessible(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $crawler->filter('form')->count());
    }

    public function testLogoutRedirectsToHome(): void
    {
         $crawler = $this->client->request('GET', '/login');
         $csrfToken = $crawler->filter('input[name="_csrf_token"]')->attr('value');

         $this->client->request('POST', '/login', [
             '_username' => 'client@example.com',
             '_password' => 'Client123!',
             '_csrf_token' => $csrfToken,
         ]);

         $this->client->request('GET', '/logout');

         $this->assertResponseRedirects();
         $this->client->followRedirect();
         $this->assertRouteSame('app_home');
     }

    public function testSuccessfulLoginRedirectsToCatalog(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $csrfToken = $crawler->filter('input[name="_csrf_token"]')->attr('value');

        $this->client->request('POST', '/login', [
            '_username' => 'client@example.com',
            '_password' => 'Client123!',
            '_csrf_token' => $csrfToken,
        ]);

        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertRouteSame('app_home');
    }

    public function testFailedLoginShowsError(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $csrfToken = $crawler->filter('input[name="_csrf_token"]')->attr('value');

        $this->client->request('POST', '/login', [
            '_username' => 'client@example.com',
            '_password' => 'wrong-password',
            '_csrf_token' => $csrfToken,
        ]);

        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-danger', 'Invalid credentials');
    }
}
