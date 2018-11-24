<?php

namespace ContentBundle\Repository;

/**
 * CategorieToArticlesRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CategorieToArticlesRepository extends \Doctrine\ORM\EntityRepository {
	
	public function count(int $categoryId) {
		$query = $this->getEntityManager()
			->createQuery(
				'
					SELECT COUNT(a.article_id) AS amount
					FROM MenuBundle:Categorie c INNER JOIN ContentBundle:CategorieToArticles ca
					INNER JOIN ContentBundle:Article a
					WHERE c.id = :id
				'
			)->setParameter("id", $categoryId);
		
		$result = $query
			->getSingleScalarResult();
		
		
		return $result->amount + 1;
	}
}