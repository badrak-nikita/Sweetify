<?php

namespace App\Service;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;

class Filters
{
    public function getFilteredProducts(EntityManagerInterface $em, string $searchTerm, array $categoryIds): array
    {
        $qb = $em->createQueryBuilder()
            ->select('p', 'c', 'i')
            ->from(Product::class, 'p')
            ->leftJoin('p.categories', 'c')
            ->leftJoin('p.images', 'i');

        if (!empty($searchTerm)) {
            $qb->andWhere('LOWER(p.name) LIKE :search')
                ->setParameter('search', '%' . strtolower($searchTerm) . '%');
        }

        if (!empty($categoryIds)) {
            $qb->andWhere('c.id IN (:categoryIds)')
                ->setParameter('categoryIds', $categoryIds);
        }

        return $qb->getQuery()->getResult();
    }
}
