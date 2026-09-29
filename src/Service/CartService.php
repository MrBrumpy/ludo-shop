<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class CartService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function getOrCreateCart(User $user): Cart
    {
        $cart = $this->entityManager->getRepository(Cart::class)->findOneBy(['user' => $user]);
        if (null === $cart) {
            $cart = new Cart($user);
            $this->entityManager->persist($cart);
            $this->entityManager->flush();
        }

        return $cart;
    }

    public function addProduct(Cart $cart, Product $product, int $quantity = 1): void
    {
        foreach ($cart->getItems() as $item) {
            if ($item->getProduct() === $product) {
                $this->setQuantity($cart, $item, $item->getQuantity() + $quantity);

                return;
            }
        }

        if ($quantity > $product->getStock()) {
            throw new \DomainException(sprintf('Stock insuffisant pour le produit « %s » (%d disponible(s)).', $product->getName(), $product->getStock()));
        }

        $item = new CartItem($product);
        $item->setQuantity($quantity);
        $item->setUnitPrice($product->getPrice());
        $cart->addItem($item);
        $cart->touch();
        $this->entityManager->persist($item);
        $this->entityManager->flush();
    }

    public function setQuantity(Cart $cart, CartItem $item, int $quantity): void
    {
        $product = $item->getProduct();
        if ($quantity > $product->getStock()) {
            throw new \DomainException(sprintf('Stock insuffisant pour le produit « %s » (%d disponible(s)).', $product->getName(), $product->getStock()));
        }

        if ($quantity <= 0) {
            $this->removeItem($cart, $item);

            return;
        }

        $item->setQuantity($quantity);
        $cart->touch();
        $this->entityManager->flush();
    }

    public function removeItem(Cart $cart, CartItem $item): void
    {
        $cart->removeItem($item);
        $cart->touch();
        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }

    public function clear(Cart $cart): void
    {
        foreach ($cart->getItems() as $item) {
            $cart->removeItem($item);
            $this->entityManager->remove($item);
        }
        $cart->touch();
        $this->entityManager->flush();
    }

    public function getTotal(Cart $cart): float
    {
        $total = 0.0;
        foreach ($cart->getItems() as $item) {
            $total += $item->getLineTotal();
        }

        return round($total, 2);
    }

    public function getItemCount(Cart $cart): int
    {
        $count = 0;
        foreach ($cart->getItems() as $item) {
            $count += $item->getQuantity();
        }

        return $count;
    }

    public function countForUser(User $user): int
    {
        $cart = $user->getCart();
        if (null === $cart) {
            return 0;
        }

        return $this->getItemCount($cart);
    }
}
