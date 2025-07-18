<?php

namespace App\DataFixtures;

use App\Entity\PaymentType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PaymentTypeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $types = [
            'Повна передоплата',
            'Накладений платiж з 50% передоплатою',
        ];

        foreach ($types as $typeName) {
            $paymentType = new PaymentType();
            $paymentType->setPaymentTypeName($typeName);
            $manager->persist($paymentType);
        }

        $manager->flush();
    }
}
