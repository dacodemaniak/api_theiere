<?php
/**
* @name ProductHandler Gestionnaire pour une ligne de produit du fichier
* @author IDea Factory (dev-team@ideafactory.fr) - Oct. 2018
* @package ContentBundle\Controller\Import
* @version 1.0.0
*/
namespace ContentBundle\Import;

use ContentBundle\Import\ImportHandler;
use ContentBundle\Entity\Article;
use ContentBundle\Entity\CategorieToArticles;
use ContentBundle\Entity\DecorArticle;
use ContentBundle\Import\Strategy\ImportInterface;

class ProductHandler extends ImportHandler {
	
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
	    echo "Traite un produit ? " . $data[$this->strategy->titleColumn] . "<br>\n";
		if ($this->emptyCols($data) <= $this->strategy::_PRODUCT_EMPTY_COLS) {
		    echo "Produit traité : " . $data[$this->strategy->titleColumn] . "<br>\n";
			// Vérifie l'existence de la catégorie
			if (($product = $this->getProduct($data)) === null) {
				$product = new Article();
				$product->setSlug(strtolower(self::toSlug($data[$this->strategy->titleColumn])));
				
				// Détermination du statut du produit (actif ou non)
				if ((count($data) - 1) <= $this->strategy->statusColumn) {
				    $status = $data[$this->strategy->statusColumn] !== "" ? 0 : 1;
				} else {
				    $status = 1;
				}
				
				$product->setIsEnabled($status);
			}
			
			$content = [
				"title" => ["fr" => $data[$this->strategy->titleColumn]],
			    "origin" => $this->strategy->originColumn !== null ? ["fr" => $data[$this->strategy->originColumn]] : null,
			    "abstract" => $this->strategy->abstractColum !== null ? ["fr" => $data[$this->strategy->abstractColumn]] : null,
			    "description" => $this->strategy->descriptionColumn !== null ? ["fr" => $data[$this->strategy->descriptionColumn]] : null,
			    "conditionnement" => $this->strategy->conditionnementColumn !== null ? ["fr" => $data[$this->strategy->conditionnementColumn]] : null,
			    "pricing" => $this->_parse($data[$this->strategy->priceColumn], $this->_vat($data[$this->strategy->vatColumn]), $data[$this->strategy->grammageColumn]),
			    "vat" => $this->_vat($data[$this->strategy->vatColumn])
			];
			
			$product->setContent(json_encode($content));
			
			// Persiste la donnée
			$this->manager->persist($product);
			
			// Gère les décorateurs
			if( ($decorators = $this->strategy->getDecorators()) !== false) {
    			foreach($decorators as $decorator) {
    			    echo "Traite le décorateur : " . $decorator["decorator"] . "<br>\n";
    			    if ($decorator["method"] !== null) {
    			        $importedDatas = $this->{$decorator["method"]}($data[$decorator["col"]]);
    			    } else {
    			        $importedDatas = $data[$decorator["col"]];
    			    }
    			    
    			    echo "Données de décor :<br>\n"; 
    			    var_dump($importedDatas);
    			    echo "<br>\n";

    			    if (is_array($importedDatas) && count($importedDatas)) {
    			        for ($i=0; $i < count($importedDatas); $i++) {
    			            $decor = new DecorArticle();
    			            $decor->setArticle($product);
    			            $decor->setDecor($this->getDecorator($decorator["decorator"]));
    			            
    			            
    			            // Traitement spécifique pour le décorateur "image"
    			            if ($decorator["decorator"] === "images-produits") {
    			                $decorContent [] = [
    			                        "src" => $importedDatas[$i],
    			                        "alt" => $product->getTitle()
    			                ];
    			            } else {
    			                $decorContent[] = ["fr" => $importedDatas[$i]];
    			            }
    			        }
    			        $decor->setContent(json_encode($decorContent));
    			        $this->manager->persist($decor);
    			        $this->manager->flush();
    			        
    			        $decorContent = [];

    			    } else {
    			        // Donnée à plat
    			        $decor = new DecorArticle();
    			        $decor->setArticle($product);
    			        $decor->setDecor($this->getDecorator($decorator["decorator"]));
    			        
    			        $content = [
    			            ["fr" => $importedDatas]
    			        ];
    			        $decor->setContent(json_encode($content));
    			        $this->manager->persist($decor);
    			        $this->manager->flush();
    			    }
    			}
			}
			/* Gère le décorateur "images"
			$images  = $this->_parseImages($data[$this->strategy->imageColumn]);
			if (count($images) > 0) {
				for ($i = 0; $i < count($images); $i++) {
					
					$image = new DecorArticle();
					$image->setArticle($product);
					$image->setDecor($this->getImageDecorator());
					
					// Définit le contenu
					$content = [
						[
							"src" => $images[$i],
							"alt" => $product->getTitle()
						]
					];
					$image->setContent(json_encode($content));
					$this->manager->persist($image);
				}
			}
			*/
			
			// Ajoute le produit à la catégorie courante
			$categoryLevel = 2;
			
			$category = $this->pooler->getCurrentCategory(2);
			if ($category === null) {
				$category = $this->pooler->getCurrentCategory(1);
				$categoryLevel = 1;
			}
			
			$this->manager->persist($category);
			$this->pooler->setCurrentCategory($category, $categoryLevel);
			
			// Ajoute le produit à la catégorie
			$productToCategory = new CategorieToArticles();
			$productToCategory->setArticle($product);
			$productToCategory->setCategorie($category);
			//$place = $this->manager->getRepository(CategorieToArticles::class)->count($category->getId());
			$place = 1;
			$productToCategory->setPlace($place);
			
			$this->manager->persist($productToCategory);
			
			$this->manager->flush();
			
		}
		return null; // Arrête la chaîne à ce niveau là
	}
	
	/**
	 * Parse les prix TTC pour retourner la gamme de prix HT selon les quantités
	 * @param string $pricing
	 * @param float $vat
	 */
	private function _parse(string $price, float $vat, string $conditionnement) {
		$pricing = [];
		
		$prices = explode($this->strategy::_SEP, $price);
		$packages = explode($this->strategy::_SEP, $conditionnement);
		
		for ($i=0; $i < count($prices); $i++) {
			$perQuantities = explode("€", trim($prices[$i]));
			$pricing[] = [
					"ht" => (float) str_replace(",", ".", trim($perQuantities[0])) / (1 + $vat),
					"quantity" => trim($packages[$i]),
					"stock" => 100,
					"maxPerOrder" => 10,
					"thresold" => 10
			];
		}

		
		return $pricing;
	}
	
	/**
	 * Retourne le taux de TVA à partir de la chaîne en entrée
	 * @param string $vat
	 * @return float
	 */
	private function _vat(string $vat): float {
		
		
		if (trim($vat === "")) {
			return 0.2;
		}
		
		$vat = str_replace("%", "", trim($vat));
		
		# begin_debug
		# echo "Traite le taux de TVA : " . $vat . "\n";
		# end_debug
		
		return (float) $vat / 100;
	}
	
	/**
	 * Parse les images importées et retourne un tableau de données
	 * @param string $image
	 * @return array
	 */
	private function _parseImages(string $image): array {
		$images = [];
		
		if (strlen(trim($image)) > 0) {
		    $images = explode($this->strategy::_SEP, $image);
			for ($i=0; $i < count($images); $i++) {
				$images[$i] = self::toSlug($images[$i]);
			}
		}
		
		return $images;
	}
}