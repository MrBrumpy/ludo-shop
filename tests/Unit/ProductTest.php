<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Product;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testIsMatureReturnsTrueWhenFlagged(): void
    {
        $product = new Product();
        $product->setIsMature(true);

        $this->assertTrue($product->isMature());
    }

    public function testIsMatureReturnsFalseByDefault(): void
    {
        $product = new Product();
        $this->assertFalse($product->isMature());
    }

    public function testIsNotAvailableWhenInactive(): void
    {
        $product = new Product();
        $product->setIsActive(false);
        $product->setStock(10);

        $this->assertFalse($product->isAvailable());
    }

    public function testIsAvailableWhenActiveAndInStock(): void
    {
        $product = new Product();
        $product->setIsActive(true);
        $product->setStock(10);

        $this->assertTrue($product->isAvailable());
    }

    public function testIsNotAvailableWhenOutOfStock(): void
    {
        $product = new Product();
        $product->setIsActive(true);
        $product->setStock(0);

        $this->assertFalse($product->isAvailable());
    }
}
