<?php

namespace UserBundle\Repository;

/**
 * BasketRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BasketRepository extends \Doctrine\ORM\EntityRepository
{
    public function getNextOrderNum(): int {
        $query = $this->createQueryBuilder("b")
            ->select('COUNT(b.id)')
            ->from('UserBundle:Basket', 'commandes')
        ;
       
      try {
           $result = $query
            ->getQuery()
            ->getSingleScalarResult();
           
           return $result;
      } catch(\Exception $e) {
          return 2;
      }
    }
}