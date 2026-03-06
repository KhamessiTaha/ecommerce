<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // Create Admin User
        $admin = new User();
        $admin->setFullName('Admin User');
        $admin->setEmail('admin@saroukh.tn');
        $admin->setRoles(['ROLE_ADMIN']);
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'password123');
        $admin->setPassword($hashedPassword);

        $manager->persist($admin);

        // Create Demo Customer User
        $customer = new User();
        $customer->setFullName('John Doe');
        $customer->setEmail('user@saroukh.tn');
        $customer->setRoles(['ROLE_USER']);
        $hashedPassword = $this->passwordHasher->hashPassword($customer, 'password123');
        $customer->setPassword($hashedPassword);

        $manager->persist($customer);

        // Create Another Customer
        $customer2 = new User();
        $customer2->setFullName('Jane Smith');
        $customer2->setEmail('jane@saroukh.tn');
        $customer2->setRoles(['ROLE_USER']);
        $hashedPassword = $this->passwordHasher->hashPassword($customer2, 'password123');
        $customer2->setPassword($hashedPassword);

        $manager->persist($customer2);

        $manager->flush();
    }
}
