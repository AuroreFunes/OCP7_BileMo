<?php

namespace App\DataFixtures;

use App\Entity\Brand;
use App\Entity\Customer;
use App\Entity\Phone;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // brands creation
        $brandList = [];
        for ($i=1; $i < 4; $i++) { 
            $brand = new Brand();
            $brand->setName("Marque " . $i);
            $manager->persist($brand);

            $brandList[] = $brand;
        }
        
        // phones creation
        $colors = ['black', 'white', 'gold', 'silver', 'blue', 'green', 'red', 'pink'];
        $dualSim = [true, false];
        for ($i=1; $i < 21; $i++) { 
            $phone = new Phone();
            $phone
                ->setName("Phone " . $i)
                ->setBrand($brandList[array_rand($brandList)])
                ->setColor($colors[array_rand($colors)])
                ->setCreatedAt(new \DateTime())
                ->setDualSim($dualSim[array_rand($dualSim)])
                ->setMemory(rand(4,100))
                ->setDescription("Description n° " . $i)
                ->setSellingPrice(rand(2000,50000)/100);
            $manager->persist($phone);
        }

        // customers creation
        $customerList = [];
        for ($i=1; $i < 3; $i++) { 
            $customer = new Customer();
            $customer
                ->setName("Customer " . $i)
                ->setCreatedAt(new \DateTime());
            $manager->persist($customer);

            $customerList[] = $customer;
        }

        // users creation
        $tokenValidity = new \DateTime();
        $tokenValidity->add(new \DateInterval('P1Y'));

        $user = new User();
        $user
            ->setUsername('User 1')
            ->setCustomer($customerList[0])
            ->setCreatedAt(new \DateTime())
            ->setEmail('contact1@testmail.com')
            ->setRoles(['ROLE_ADMIN'])
            ->setToken('token1')
            ->setTokenValidity($tokenValidity);
        $manager->persist($user);

        $user = new User();
        $user
            ->setUsername('User 2')
            ->setCustomer($customerList[0])
            ->setCreatedAt(new \DateTime())
            ->setEmail('contact2@testmail.com')
            ->setRoles(['ROLE_USER'])
            ->setToken('token2')
            ->setTokenValidity($tokenValidity);
        $manager->persist($user);

        $user = new User();
        $user
            ->setUsername('User 3')
            ->setCustomer($customerList[1])
            ->setCreatedAt(new \DateTime())
            ->setEmail('contact3@testmail.com')
            ->setRoles(['ROLE_ADMIN'])
            ->setToken('token3')
            ->setTokenValidity($tokenValidity);
        $manager->persist($user);

        $user = new User();
        $user
            ->setUsername('User 4')
            ->setCustomer($customerList[1])
            ->setCreatedAt(new \DateTime())
            ->setEmail('contact4@testmail.com')
            ->setRoles(['ROLE_USER'])
            ->setToken('token4')
            ->setTokenValidity($tokenValidity);
        $manager->persist($user);

        $expiredToken = new \DateTime('1900-01-01 00:00:00');
        $user = new User();
        $user
            ->setUsername('User 5')
            ->setCustomer($customerList[0])
            ->setCreatedAt(new \DateTime())
            ->setEmail('contact5@testmail.com')
            ->setRoles(['ROLE_ADMIN'])
            ->setToken('expiredToken!')
            ->setTokenValidity($expiredToken);
        $manager->persist($user);

        $user = new User();
        $user
            ->setUsername('User 6')
            ->setCustomer($customerList[0])
            ->setCreatedAt(new \DateTime())
            ->setEmail('contac6@testmail.com')
            ->setRoles(['ROLE_USER']);
        $manager->persist($user);
        
        $manager->flush();
    }

}