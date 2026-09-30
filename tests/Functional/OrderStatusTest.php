<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Order;
use App\Entity\OrderStatusHistory;
use App\Entity\Product;
use App\Service\CartService;

class OrderStatusTest extends FunctionalTestCase
{
    private function createPaidOrder(): Order
    {
        $this->login('client@example.com');
        $user = $this->findUser('client@example.com');

        $product = $this->repository(Product::class)->findOneBy(['reference' => 'CAT-001']);
        $cartService = $this->client->getContainer()->get(CartService::class);
        $cart = $cartService->getOrCreateCart($user);
        $cartService->addProduct($cart, $product, 1);

        $crawler = $this->client->request('GET', '/checkout');
        $token = $crawler->filter('input[name="checkout_form[_token]"]')->attr('value');

        $this->client->request('POST', '/checkout', [
            'checkout_form' => [
                'addressLine' => '123 Rue Test',
                'postalCode' => '75001',
                'city' => 'Paris',
                'country' => 'FR',
                '_token' => $token,
            ],
        ]);

        $order = $this->repository(Order::class)->findOneBy(['user' => $user]);
        $this->client->request('POST', '/orders/'.$order->getId().'/pay');
        $this->entityManager()->clear();

        return $this->repository(Order::class)->find($order->getId());
    }

    private function changeStatus(Order $order, string $status): void
    {
        $crawler = $this->client->request('GET', '/admin/orders/'.$order->getId());
        $form = $crawler->filter('form[action*="status"]');
        $this->assertGreaterThan(0, $form->count(), 'Le formulaire de changement de statut est introuvable.');

        $this->client->submit($form->form(), [
            'order_status_form[status]' => $status,
        ]);

        $this->assertResponseRedirects();
    }

    public function testAdminCanChangeStatus(): void
    {
        $order = $this->createPaidOrder();

        $this->login('admin@example.com');
        $this->changeStatus($order, 'shipped');

        $this->entityManager()->clear();

        $updated = $this->repository(Order::class)->find($order->getId());
        $this->assertSame('shipped', $updated->getStatus()->value);
    }

    public function testClientCannotChangeStatus(): void
    {
        $order = $this->createPaidOrder();

        $this->login('client@example.com');

        $this->client->request('POST', '/admin/orders/'.$order->getId().'/status', [
            'order_status_form[status]' => 'shipped',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testStatusChangeCreatesHistoryEntry(): void
    {
        $order = $this->createPaidOrder();

        $this->login('admin@example.com');
        $this->changeStatus($order, 'shipped');

        $this->entityManager()->clear();

        $history = $this->repository(OrderStatusHistory::class)->findBy(
            ['order' => $this->repository(Order::class)->find($order->getId())],
            ['changedAt' => 'ASC'],
        );

        $this->assertGreaterThanOrEqual(1, count($history));
    }

    public function testStatusChangeSendsEmail(): void
    {
        $order = $this->createPaidOrder();

        $this->login('admin@example.com');
        $this->changeStatus($order, 'shipped');

        $profile = $this->client->getProfile();
        $this->assertNotNull($profile);
        $collector = $profile->getCollector('mailer');
        $messages = $collector->getEvents()->getMessages();

        $this->assertGreaterThanOrEqual(1, count($messages));
    }
}
