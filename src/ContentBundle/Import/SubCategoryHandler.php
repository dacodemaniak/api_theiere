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

class SubCategoryHandler extends ImportHandler {
	/**
	 * Gestionnaire pour le traitement d'une ligne de catégorie
	 * @param array $data
	 */
	public function handle(array $data) {
		echo "Traite une sous-catégorie ? " . $data[0] . "<br>\n";
		if ($this->emptyCols($data) === 6) {
			echo "Sous-categorie traitée : " . $data[0] . "<br>\n";
			// Vérifie l'existence de la catégorie
			$categorie = $this->getCategorie($data, 2);
		} else {
			// Délègue au handler suivant
			parent::handle($data);
		}

	}
}