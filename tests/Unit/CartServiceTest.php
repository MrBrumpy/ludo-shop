<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CartServiceTest extends TestCase
{
    private CartService $service;

    protected function setUp(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $this->service = new CartService($em);
    }

    public function testEmptyCartReturnsZero(): void
    {
        $user = $this->createStub(User::class);
        $cart = new Cart($user);

        $this->assertSame(0.0, $this->service->getTotal($cart));
    }

    public function testSingleItemReturnsCorrectTotal(): void
    {
        $user = $this->createStub(User::class);
        $cart = new Cart($user);

        $product = new Product();
        $product->setName('Catan');
        $product->setPrice(25.00);

        $item = new CartItem($product);
        $item->setQuantity(1);
        $item->setUnitPrice(25.00);
        $cart->addItem($item);

        $this->assertSame(25.00, $this->service->getTotal($cart));
    }

    public function testMultipleItems(): void
    {
        $user = $this->createStub(User::class);
        $cart = new Cart($user);

        $product = new Product();
        $product->setName('Catan');
        $product->setPrice(25.00);

        $product1 = new Product();
        $product1->setName('Catan');
        $product1->setPrice(50.00);

        $item = new CartItem($product);
        $item->setQuantity(1);
        $item->setUnitPrice(25.00);

        $item1 = new CartItem($product1);
        $item1->setQuantity(1);
        $item1->setUnitPrice(50.00);

        $cart->addItem($item);
        $cart->addItem($item1);

        $this->assertSame(75.00, $this->service->getTotal($cart));
    }

    public function testQuantityMultiplier(): void
    {
        $user = $this->createStub(User::class);
        $cart = new Cart($user);

        $product = new Product();
        $product->setName('Catan');
        $product->setPrice(25.00);

        $item = new CartItem($product);
        $item->setQuantity(3);
        $item->setUnitPrice(25.00);
        $cart->addItem($item);

        $this->assertSame(75.00, $this->service->getTotal($cart));
    }

    public function testPromotionalPriceIsUsed(): void
    {
        $product = new Product();
        $product->setPrice(50.00);
        $product->setPromoPrice(35.00);
        $product->setPromoStartsAt(new \DateTimeImmutable('2026-01-01'));
        $product->setPromoEndsAt(new \DateTimeImmutable('2099-01-01'));
        $product->setStock(10);

        $user = $this->createStub(User::class);
        $cart = new Cart($user);

        $this->service->addProduct($cart, $product, 2);

        $this->assertSame(70.00, $this->service->getTotal($cart));
    }
}
