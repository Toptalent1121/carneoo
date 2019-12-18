<?php

namespace App\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Entity\Admin;

class AdminFixture extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    private $container;
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $admin    = new Admin();
        $password = $this->encoder->encodePassword($admin, 'Nimd@');
        $admin->setEmail('admin_autobroker@websky.pl')
            ->setPassword($password)
            ->setRoles(['ROLE_SUPER_ADMIN'])
            ->setName('John')
            ->setLastName('Admin')
            ->setActive(1);
        
        $manager->persist($admin);

        $user = new Admin();
        $password = $this->encoder->encodePassword($admin, 'TesT37');
        $user->setEmail('test_autobroker@websky.pl')
            ->setPassword($password)
            ->setRoles(['ROLE_USER'])
            ->setName('Bill')
            ->setLastName('Cornson')
            ->setActive(1);

        $manager->persist($user);
        $manager->flush();
    }

    public function getOrder()
    {
        return 55;
    }
}