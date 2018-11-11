<?php
/**
* @name CategoryPooler Pool des catégories à traiter
* @author IDea Factory (dev-team@ideafactory.fr) - Oct. 2018
* @package ContentBundle/Controller/Import
* @version 1.0.0
*/
namespace ContentBundle\Import;

use MenuBundle\Entity\Categorie;
use ContentBundle\Entity\Decor;

class CategoryPooler {
	
	/**
	 * Stockage des catégories par niveau
	 * @var Categorie[]
	 */
	private $pooler;
	
	/**
	 * Gestionnaire d'entité Doctrine
	 */
	private $manager;

	/**
	 * Décorateur d'images pour un produit
	 * @var \ContentBundle\Entity\Decor
	 */
	private $imageDecorator;
	
	/**
	 * Décorateurs actifs
	 * @var array
	 */
	private $activeDecorators;
	
	/**
	 * Définit la catégorie racine pour l'importation de données
	 * @param \MenuBundle\Entity\Categorie $rootCategorie
	 */
	public function __construct(\MenuBundle\Entity\Categorie $rootCategorie, $manager) {
		$this->pooler["root"] = $rootCategorie;
		$this->manager = $manager;
		
		/* Instancie le décorateur images
		$repository = $this->manager->getRepository(Decor::class);
		$decors = $repository->findBySlug("images-produits");
		
		$this->imageDecorator = $decors[0];
		*/
		$this->activeDecorators = [];
	}
	
	/**
	 * Retourne le gestionnaire d'entité Doctrine
	 * @return \Doctrine\ORM\EntityManager
	 */
	public function getManager() {
		return $this->manager;
	}
	
	/**
	 * @obsolote getImageDecorator()
	 * @see getDecorator(string $slug)
	 * Retourne le décorateur Images
	 * @return \ContentBundle\Entity\Decor
	 */
	public function getImageDecorator(): Decor {
		return $this->imageDecorator;
	}
	
	/**
	 * Récupère l'instance d'un décorateur produit (et l'instancie si nécessaire)
	 * @param string $slug
	 * @return Decor
	 */
	public function getDecorator(string $slug): Decor {
	    if(!array_key_exists($slug, $this->activeDecorators)) {
	        $repository = $this->manager->getRepository(Decor::class);
	        $decors = $repository->findBySlug($slug);
	        
	        $this->activeDecorators[$slug] = $decors[0];
	    }
	    return $this->activeDecorators[$slug];
	}
	
	/**
	 * Retourne la catégorie racine de l'importation
	 * @return \MenuBundle\Entity\Categorie
	 */
	public function getRootCategory(): \MenuBundle\Entity\Categorie {
		return $this->pooler["root"];
	}
	
	/**
	 * Définit la catégorie courante de l'importation
	 * @param \MenuBundle\Entity\Categorie $category
	 */
	public function setCurrentCategory(Categorie $category, int $level) {
		$this->pooler["level_" . $level] = $category;
	}
	
	/**
	 * Retourne la catégorie courante pour l'importation des données
	 * @return \MenuBundle\Entity\Categorie
	 */
	public function getCurrentCategory(int $level) {
		if (array_key_exists("level_" . $level, $this->pooler))
			return $this->pooler["level_" . $level];
		
		return null;
	}
}