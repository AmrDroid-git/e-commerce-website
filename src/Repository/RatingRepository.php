<?php
namespace App\Repository;

use App\Entity\Product;
use App\Entity\Rating;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }

    public function findOneByUserAndProduct(User $user, Product $product): ?Rating
    {
        return $this->findOneBy([
            'user'    => $user,
            'product' => $product,
        ]);
    }
    public function findAverageRatingForProduct(Product $product): ?float
    {
        $qb = $this->createQueryBuilder('r')
            ->select('AVG(r.value) as avgRating')
            ->andWhere('r.product = :product')
            ->setParameter('product', $product)
            ->getQuery()
            ->getSingleScalarResult();

        return $qb !== null ? floatval($qb) : null;
    }
    public function findAverageRatingByUser(User $user): ?float
    {
        $qb = $this->createQueryBuilder('r')
            ->select('AVG(r.value) as avgRating')
            ->where('r.user = :user')
            ->setParameter('user', $user);

        return $qb->getQuery()->getSingleScalarResult();
    }
}

