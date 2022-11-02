<?php

namespace App\DataFixtures;

use App\Entity\Brand;
use App\Entity\Customer;
use App\Entity\Phone;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AllFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $brand = new Brand();
        $brand->setName('Première marque');
        $manager->persist($brand);

        $phone = new Phone();
        $phone
            ->setName('Phone 1')
            ->setBrand($brand)
            ->setColor('white')
            ->setCreated(new \DateTime())
            ->setDualSim(false)
            ->setMemory(4)
            ->setDescription("bonne occasion en entrée de gamme !")
            ->setSellingPrice(79.90);
        $manager->persist($phone);

        $phone = new Phone();
        $phone
            ->setName('Phone 2')
            ->setBrand($brand)
            ->setColor('black')
            ->setCreated(new \DateTime())
            ->setDualSim(true)
            ->setMemory(16)
            ->setDescription("un modèle fiable")
            ->setSellingPrice(149.99);
        $manager->persist($phone);
        
        $customer = new Customer();
        $customer
            ->setName('Premier client')
            ->setCreatedAt(new \DateTime());
        $manager->persist($customer);

        $user = new User();
        $user
            ->setCustomer($customer)
            ->setUsername('Utilisateur 1')
            ->setCreatedAt(new \DateTime())
            ->setEmail('contact.demosaf@gmail.com')
            ->setRoles(['ADMIN']);
        $manager->persist($user);

        $user = new User();
        $user
            ->setCustomer($customer)
            ->setUsername('Utilisateur 2')
            ->setCreatedAt(new \DateTime())
            ->setEmail('contact2.demosaf@gmail.com')
            ->setRoles(['USER']);
        $manager->persist($user);
        
        $manager->flush();
    }

}