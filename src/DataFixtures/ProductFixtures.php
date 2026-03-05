<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Electronics
        $this->createProduct($manager, 'iPhone 15 Pro', 'High-end smartphone with advanced camera system and A17 Pro chip', 'Electronics', 4999.99);
        $this->createProduct($manager, 'Samsung Galaxy S24', 'Latest flagship Android phone with AI features', 'Electronics', 4299.99);
        $this->createProduct($manager, 'iPad Air', 'Premium tablet with M1 chip and stunning display', 'Electronics', 2999.99);
        $this->createProduct($manager, 'MacBook Pro', 'Powerful laptop for professionals and creatives', 'Electronics', 6999.99);
        $this->createProduct($manager, 'Sony WH-1000XM5', 'Premium noise-canceling wireless headphones', 'Electronics', 1999.99);

        // Clothing
        $this->createProduct($manager, 'Designer Jeans', 'Premium quality denim jeans with perfect fit', 'Clothing', 349.99);
        $this->createProduct($manager, 'Cotton T-Shirt', 'Comfortable and breathable cotton shirt', 'Clothing', 89.99);
        $this->createProduct($manager, 'Leather Jacket', 'Classic leather jacket for style and warmth', 'Clothing', 1299.99);
        $this->createProduct($manager, 'Running Shoes', 'Professional athletic shoes for runners', 'Clothing', 699.99);
        $this->createProduct($manager, 'Winter Coat', 'Warm and stylish coat for cold weather', 'Clothing', 899.99);

        // Home & Kitchen
        $this->createProduct($manager, 'Coffee Maker', 'Automatic coffee maker with thermal carafe', 'Home & Kitchen', 299.99);
        $this->createProduct($manager, 'Blender', 'High-powered blender for smoothies and soups', 'Home & Kitchen', 199.99);
        $this->createProduct($manager, 'Cookware Set', 'Non-stick cookware set with 10 pieces', 'Home & Kitchen', 449.99);
        $this->createProduct($manager, 'Microwave Oven', 'Digital microwave with multiple settings', 'Home & Kitchen', 349.99);
        $this->createProduct($manager, 'Vacuum Cleaner', 'Powerful cordless vacuum cleaner', 'Home & Kitchen', 799.99);

        // Books
        $this->createProduct($manager, 'The Art of War', 'Classic ancient Chinese military strategy book', 'Books', 49.99);
        $this->createProduct($manager, '1984', 'Dystopian novel classic by George Orwell', 'Books', 59.99);
        $this->createProduct($manager, 'Sapiens', 'A brief history of humankind by Yuval Noah Harari', 'Books', 69.99);
        $this->createProduct($manager, 'The Lean Startup', 'Guide to building successful startups', 'Books', 54.99);
        $this->createProduct($manager, 'Atomic Habits', 'Build better habits and break bad ones', 'Books', 64.99);

        // Sports & Outdoors
        $this->createProduct($manager, 'Mountain Bike', 'Durable mountain bike for off-road adventures', 'Sports & Outdoors', 2499.99);
        $this->createProduct($manager, 'Yoga Mat', 'Non-slip yoga mat for comfortable practice', 'Sports & Outdoors', 79.99);
        $this->createProduct($manager, 'Tent', 'Waterproof camping tent for 4 people', 'Sports & Outdoors', 599.99);
        $this->createProduct($manager, 'Sleeping Bag', 'Warm sleeping bag for camping trips', 'Sports & Outdoors', 199.99);
        $this->createProduct($manager, 'Backpack', 'Large capacity hiking backpack', 'Sports & Outdoors', 349.99);

        // Beauty & Wellness
        $this->createProduct($manager, 'Skincare Set', 'Complete skincare routine set with 5 products', 'Beauty & Wellness', 299.99);
        $this->createProduct($manager, 'Hair Dryer', 'Professional ionic hair dryer', 'Beauty & Wellness', 199.99);
        $this->createProduct($manager, 'Massage Gun', 'Electric muscle massage gun for recovery', 'Beauty & Wellness', 399.99);
        $this->createProduct($manager, 'Vitamin Supplements', 'Daily multivitamin supplements', 'Beauty & Wellness', 99.99);
        $this->createProduct($manager, 'Face Mask', 'Hydrating sheet face masks pack of 10', 'Beauty & Wellness', 79.99);

        // Toys & Games
        $this->createProduct($manager, 'LEGO Set', 'Building brick set with 1000+ pieces', 'Toys & Games', 499.99);
        $this->createProduct($manager, 'Board Game', 'Strategy board game for family fun', 'Toys & Games', 89.99);
        $this->createProduct($manager, 'Drone', 'HD camera drone with 30 minute flight time', 'Toys & Games', 1299.99);
        $this->createProduct($manager, 'Action Figures', 'Collectible action figures set', 'Toys & Games', 149.99);
        $this->createProduct($manager, 'Puzzle', '3D puzzle with 500 pieces', 'Toys & Games', 59.99);

        $manager->flush();
    }

    private function createProduct(ObjectManager $manager, string $name, string $description, string $category, float $price): void
    {
        $product = new Product();
        $product->setName($name);
        $product->setDescription($description);
        $product->setCategory($category);
        $product->setPrice($price);
        
        $manager->persist($product);
    }
}
