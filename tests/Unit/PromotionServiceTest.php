<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Product;
use App\Service\PromotionService;
use PHPUnit\Framework\TestCase;

class PromotionServiceTest extends TestCase
{
    private PromotionService $service;

    protected function setUp(): void
    {
        $this->service = new PromotionService();
    }

    public function testReturnsNormalPriceWhenNoPromotion(): void
    {
        $product = $this->createProduct(50.00);

        $this->assertSame(50.00, $this->service->getCurrentPrice($product));
        $this->assertFalse($this->service->isOnPromotion($product));
    }

    public function testReturnsPromoPriceDuringPeriod(): void
    {
        $product = $this->createProduct(50.00, 25.00);

        $this->assertSame(25.00, $this->service->getCurrentPrice($product, new \DateTimeImmutable('2026-08-05 00:00:00')));
        $this->assertTrue($this->service->isOnPromotion($product, new \DateTimeImmutable('2026-08-05 00:00:00')));
    }

    public function testReturnsNormalPriceBeforePromotionPeriod(): void
    {
        $product = $this->createProduct(50.00, 25.00);

        $this->assertSame(50.00, $this->service->getCurrentPrice($product, new \DateTimeImmutable('2026-07-05 00:00:00')));
        $this->assertFalse($this->service->isOnPromotion($product, new \DateTimeImmutable('2026-07-05 00:00:00')));
    }

    public function testReturnsNormalPriceAfterPromotionPeriod(): void
    {
        $product = $this->createProduct(50.00, 25.00);

        $this->assertSame(50.00, $this->service->getCurrentPrice($product, new \DateTimeImmutable('2026-09-05 00:00:00')));
        $this->assertFalse($this->service->isOnPromotion($product, new \DateTimeImmutable('2026-09-05 00:00:00')));
    }

    public function testPromoPriceEqualToNormalIsNotActive(): void
    {
        $product = $this->createProduct(50.00, 50.00);

        $this->assertFalse($this->service->isOnPromotion($product));
    }

    public function testPromoPriceGreaterThanNormalIsNotActive(): void
    {
        $product = $this->createProduct(50.00, 75.00);

        $this->assertFalse($this->service->isOnPromotion($product));
    }

    public function testInvertedDatesAreNotActive(): void
    {
        $product = $this->createProduct(50.00, 25.00);

        $product->setPromoEndsAt(new \DateTimeImmutable('2026-08-01 00:00:00'));
        $product->setPromoStartsAt(new \DateTimeImmutable('2026-08-31 23:59:59'));

        $this->assertFalse($this->service->isOnPromotion($product));
    }

    public function testBoundaryStartIsIncluded(): void
    {
        $product = $this->createProduct(50.00, 25.00);

        $this->assertSame(25.00, $this->service->getCurrentPrice($product, new \DateTimeImmutable('2026-08-01 00:00:00')));
        $this->assertTrue($this->service->isOnPromotion($product, new \DateTimeImmutable('2026-08-01 00:00:00')));
    }

    public function testBoundaryEndIsIncluded(): void
    {
        $product = $this->createProduct(50.00, 25.00);

        $this->assertSame(25.00, $this->service->getCurrentPrice($product, new \DateTimeImmutable('2026-08-31 00:00:00')));
        $this->assertTrue($this->service->isOnPromotion($product, new \DateTimeImmutable('2026-08-31 00:00:00')));
    }

    private function createProduct(float $price, ?float $promoPrice = null): Product
    {
        $product = new Product();
        $product->setName('Test Product')->setPrice($price);

        if (null !== $promoPrice) {
            $product->setPromoPrice($promoPrice);
            $product->setPromoStartsAt(new \DateTimeImmutable('2026-08-01 00:00:00'));
            $product->setPromoEndsAt(new \DateTimeImmutable('2026-08-31 23:59:59'));
        }

        return $product;
    }
}
