<?php
namespace App\Service;

class ProductService
{
    private array $products = [
        1 => ['id' => 1, 'name' => 'Laptop', 'price' => 1500, 'description' => 'Powerful laptop for work and gaming', 'category' => 'Electronics'],
        2 => ['id' => 2, 'name' => 'Smartphone', 'price' => 800, 'description' => 'Latest model with great camera', 'category' => 'Electronics'],
        3 => ['id' => 3, 'name' => 'Tablet', 'price' => 500, 'description' => 'Lightweight tablet for everyday use', 'category' => 'Electronics'],
        4 => ['id' => 4, 'name' => 'Headphones', 'price' => 150, 'description' => 'Noise-cancelling wireless headphones', 'category' => 'Accessories'],
        5 => ['id' => 5, 'name' => 'Keyboard', 'price' => 80, 'description' => 'Mechanical keyboard with RGB lighting', 'category' => 'Accessories'],
    ];

    public function getAll(): array
    {
        return $this->products;
    }

    public function getById(int $id): ?array
    {
        return $this->products[$id] ?? null;
    }
}