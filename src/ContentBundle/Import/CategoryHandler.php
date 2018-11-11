<?php
/**
* @name CategoryHandler Gestionnaire pour une ligne de catégorie du fichier
* @author IDea Factory (dev-team@ideafactory.fr) - Oct. 2018
* @package ContentBundle\Controller\Import
* @version 1.0.0
*/
namespace ContentBundle\Import;

use ContentBundle\Import\ImportHandler;
use MenuBundle\Entity\Categorie;
use ContentBundle\Import\Strategy\ImportInterface;

class CategoryHandler extends ImportHandler {
    
    /**
     * Stratégie d'importation du produit
     * @var ImportHandler
     */
    private $strategy;
    
    public function setStrategy(ImportInterface $strategy) {
        $this->strategy = $strategy;
    }
    
	/**
	 * Gestionnaire pour le traitement d'une ligne de catégorie
	 * @param array $data
	 */
	public function handle(array $data) {
		echo "Traite une catégorie ? " . $data[0] . "<br>\n";
		if ($this->emptyCols($data) === $this->strategy::_MAIN_CATEGORY_EMPTY_COLS) {
			echo "Categorie traitée : " . $data[0] . "<br>\n";
			// Vérifie l'existence de la catégorie
			$categorie = $this->getCategorie($data, 1);
		} else {
			// Délègue au handler suivant
			parent::handle($data);
		}
	}
}