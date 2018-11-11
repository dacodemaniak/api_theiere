<?php
/**
 * @name ImportHandler Abstraction de gestionnaires d'importation
 * @author IDea Factory (dev-team@ideafactory.fr) - Oct. 2018
 * @package ContentBundle\Import
 * @version 1.0.0
 */
namespace ContentBundle\Import;

use ContentBundle\Import\HandlerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Doctrine\ORM\EntityManager;

use MenuBundle\Entity\Categorie;
use ContentBundle\Entity\Article;
use ContentBundle\Entity\Decor;

abstract class ImportHandler extends ContainerAwareCommand implements HandlerInterface {
	
	private static $replacements = [
			"à"=>"a","á"=>"a","â"=>"a","ã"=>"a","ä"=>"a","å"=>"a",
			"ò"=>"o","ó"=>"o","ô"=>"o","õ"=>"o","ö"=>"o",
			"è"=>"e","é"=>"e","ê"=>"e","ë"=>"e",
			"ì"=>"i","í"=>"i","î"=>"i","ï"=>"i",
			"ù"=>"u","ú"=>"u","û"=>"u","ü"=>"u",
			"ÿ"=>"y",
			"ñ"=>"n",
			"ç"=>"c",
			"ø"=>"o",
			"À" => "A","Á"=>"A","Â" => "A","Ã" => "a","Ä" => "A","Å" => "A",
			"Ò"  => "O","Ó"  => "O","Ô"  => "O","Õ"  => "O","Ö"  => "O","Ø" => "O",
			"È" => "E", "É" => "E","Ê" => "E","Ë" => "E",
			"Ç" => "C",
			"Ì" => "I","Í" => "I","Î" => "I","Ï" => "I",
			"Ù" => "U","Ú" => "U","Û" => "U","Ü" => "U",
			"Ñ" => "N",
			" " => "_",
			"&" => "",
			"/" => "_",
			"?" => "_",
			":" => "_",
			"'" => "_"
		];
	/**
	 * Instance du gestionnaire suivant
	 * @var ImportHandler
	 */
	private $nextHandler;
	
	/**
	 * Instance du manager Doctrine
	 * @var \Doctrine\ORM\EntityManager
	 */
	protected $manager;
	
	/**
	 * Pool de catégories
	 * @var \ContentBundle\Import\CategoryPooler
	 */
	protected $pooler;
	

	
	public function __construct(\ContentBundle\Import\CategoryPooler $pooler) {
		$this->pooler = $pooler;
		$this->manager = $this->pooler->getManager();
	}
	
	/**
	 * Gère l'importation des données elles-mêmes et / ou délègue au handler suivant
	 * @param array $data
	 */
	public function handle(array $data) {
		if ($this->nextHandler) {
			return $this->nextHandler->handle($data);
		}
	}
	
	/**
	 * Définit le handler suivant dans la chaîne de responsabilités
	 * @param HandlerInterface $handler
	 * @return HandlerInterface
	 */
	public function setNext(HandlerInterface $handler): HandlerInterface{
		$this->nextHandler = $handler;
		
		return $handler;
	}
	
	/**
	 * @obsolete getImageDecorator
	 * @see getDecorator(string $slug): Decor
	 * Retourne le décorateur "images" pour la génération d'un produit
	 * @return \ContentBundle\Entity\Decor
	 */
	protected function getImageDecorator(): Decor {
		return $this->pooler->getImageDecorator();	
	}
	
	/**
	 * Retourne une instance de décorateur en fonction du slug
	 * @param string $slug Slug du décorateur à retourner
	 * @return Decor
	 */
	protected function getDecorator(string $slug): Decor {
	    return $this->pooler->getDecorator($slug);
	}
	
	/**
	 * Retourne un slug basé sur la chaîne en entrée, débarrassée des caractères nuisibles
	 * @param string $input
	 * @return string
	 */
	protected static function toSlug(string $input): string {
		return strtr(trim($input), self::$replacements);
	}
	
	/**
	 * Retourne une catégorie existante
	 * @param string $slug
	 * @return \MenuBundle\Entity\Categorie
	 */
	protected function getCategorie(array $data, int $level) {
		
		if (($categorie = $this->pooler->getCurrentCategory($level)) !== null){
			if ($categorie->getSlug() === strtolower(self::toSlug($data[0]))) {
				return $categorie;
			}
		}
		
		$categorieRepository = $this->manager->getRepository(Categorie::class);
		
		$slug = strtolower(self::toSlug($data[0]));
		$categories = $categorieRepository->findBySlug($slug);
		
		if (count($categories)){
			$this->pooler->setCurrentCategory($categories[0], $level);
			return $categories[0];
		}
		
		// Crée la nouvelle catégorie
		$categorie = new Categorie();
		$categorie->setSlug($slug);
		$categorie->setIsEnabled(1);
		$categorie->setRoute("/Category");
		if ($level === 1) {
			$content = ["title" => [["fr" => $data[0]]]];
		} else {
			$content = [
				"title" => [["fr" => $data[0]]],
				"description" => ["fr" => $data[3]]
			];
		}
		$categorie->setContent(
			json_encode($content)
		);
		
		$parentCategory = null;
		if ($level === 1) {
			$categorie->setParent($this->pooler->getRootCategory());
			$parentCategory = $this->pooler->getRootCategory();
		} else {
			$categorie->setParent($this->pooler->getCurrentCategory(1));
			$parentCategory = $this->pooler->getCurrentCategory(1);
		}
		
		$this->manager->persist($categorie);
		
		$parentCategory->addChildren($categorie);
		
		$this->manager->persist($parentCategory);
		
		
		// Ecrit les données
		$this->manager->flush();
		
		$this->pooler->setCurrentCategory($categorie, $level);
		
		return $categorie;
		
	}
	
	/**
	 * Retourne un produit à partir de son slug
	 * @param array $data
	 * @return NULL|\ContentBundle\Entity\Article
	 */
	protected function getProduct(array $data) {
		$slug = strtolower(self::toSlug($data[0]));
		
		$articleRepository = $this->manager->getRepository(Article::class);
		
		$articles = $articleRepository->findBySlug($slug);
		
		if (count($articles) === 0) {
			return null;
		}
		
		return $articles[0];
	}
	
	/**
	 * Détermine le nombre de colonnes vides du tableau de données
	 * @param array $data
	 * @return int
	 */
	protected function emptyCols(array $data): int {
		$emptyCols = 0;
		
		for($i = 0; $i < count($data); $i++) {
			if (trim($data[$i]) === "") {
				$emptyCols++;
			}
		}
		
		return $emptyCols;
	}
}
