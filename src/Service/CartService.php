<?php

namespace App\Service;

use App\Entity\Product;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    private const CART_SESSION_KEY = 'cart';

    public function __construct(private RequestStack $requestStack)
    {
    }

    /**
     * Get all cart items
     * @return array<int, array{'product': Product, 'quantity': int}>
     */
    public function getItems(): array
    {
        return $this->getSession()->get(self::CART_SESSION_KEY, []);
    }

    /**
     * Get total number of items in cart
     */
    public function getTotal(): int
    {
        $total = 0;
        foreach ($this->getItems() as $item) {
            $total += $item['quantity'];
        }
        return $total;
    }

    /**
     * Get cart subtotal (price only)
     */
    public function getSubtotal(): float
    {
        $subtotal = 0.0;
        foreach ($this->getItems() as $item) {
            $subtotal += $item['product']->getPrice() * $item['quantity'];
        }
        return $subtotal;
    }

    /**
     * Add product to cart or increase quantity
     */
    public function addItem(Product $product, int $quantity = 1): void
    {
        $items = $this->getItems();
        $productId = $product->getId();

        if (isset($items[$productId])) {
            $items[$productId]['quantity'] += $quantity;
        } else {
            $items[$productId] = [
                'product' => $product,
                'quantity' => $quantity
            ];
        }

        $this->getSession()->set(self::CART_SESSION_KEY, $items);
    }

    /**
     * Remove product from cart
     */
    public function removeItem(int $productId): void
    {
        $items = $this->getItems();
        unset($items[$productId]);
        $this->getSession()->set(self::CART_SESSION_KEY, $items);
    }

    /**
     * Update quantity of product in cart
     */
    public function updateQuantity(int $productId, int $quantity): void
    {
        $items = $this->getItems();

        if ($quantity <= 0) {
            $this->removeItem($productId);
            return;
        }

        if (isset($items[$productId])) {
            $items[$productId]['quantity'] = $quantity;
            $this->getSession()->set(self::CART_SESSION_KEY, $items);
        }
    }

    /**
     * Clear entire cart
     */
    public function clear(): void
    {
        $this->getSession()->remove(self::CART_SESSION_KEY);
    }

    /**
     * Check if cart is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->getItems());
    }

    /**
     * Get session
     */
    private function getSession()
    {
        return $this->requestStack->getSession();
    }
}
