<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $categories = [
            'Milk',
            'Dark',
            'Milk Strawberry',
            'Milk Vanila',
            'Milk Oreo',
            'Milk Popcorn',
            'Alpine Milk Hazelnuts',
            'Yoghurt-Strawberry',
            'Crunchy Peanut Butter'
        ];

        foreach ($categories as $name) {
            $category = new Category();
            $category->setCategoryName($name);
            $manager->persist($category);
        }

        $manager->flush();
    }
}
