<?php
/**
* @name CategoryController Service REST pour la récupération des produits par catégories
* @author IDea Factory (dev-team@ideafactory.fr)
* @package ContentBundle\Controller
* @version 1.0.0
* @version 1.0.1
*   Modification du typage pour la récupération des produits pour une catégorie
*/
namespace ContentBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\View\View;
use MenuBundle\Entity\Categorie;
use ContentBundle\Entity\Article;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\Common\Collections\ArrayCollection;


class CategoryController extends FOSRestController {
	
    /**
     * Catégorie traitée
     * @var Categorie
     */
    private $category;
    
    /**
     * Catégorie ancêtre de la catégorie courante
     * @var Categorie
     */
    private $ancestor;
    
    /**
     * Produits de la catégorie courante
     * @var \ContentBundle\Entity\Article[]
     */
    private $categoryProducts;
    
    /**
     * Produits des catégories enfants de la catégorie courante
     * @var \MenuBundle\Entity\Categorie[]
     */
    private $childrenProducts;
    
    /**
     * Catégories parentes de la catégorie courante
     * @var \MenuBundle\Entity\Categorie[]
     */
    private $parentCategories;
    
	/**
	 * Constructeur du contrôleur
	 */
	public function __construct() {}
	
	/**
	 * @Route("/products/category/{slug}", methods={"GET","HEAD"}, name="products_category")
	 * 
	 * @param Request $request
	 * 
	 * Retourne les produits et éventuellement les sous-catégories à partir d'un identifiant de catégorie
	 */
	public function productFromCategory(Request $request) {
	    $request->setRequestFormat("html");
	    
	    $routeComponent = $request->get("slug");

	    if (filter_var($routeComponent, FILTER_VALIDATE_INT)) {
	        $id = (int) $routeComponent;
	        $slug = "";
	    } else {
	        $slug = $routeComponent;
	        $id = 0;
	    }
	    
	    // Récupère la catégorie
	    if ($id !== 0) {
	        // Récupère la catégorie par son id
	        $this->category = $this->getById($id);
	    } else {
	        // Récupère la catégorie par son slug
	        $this->category = $this->getBySlug($slug);
	    }
	    
	    // Récupère le fil d'ariane de la catégorie courante
	    if ($this->category->hasParent()) {
	        $ancestors = $this->category->getBreadcrumb();
	    }
	    $ancestors[] = $this->category; // Catégorie courante dans le fil d'ariane
	    
	    /**
	    $products = $this->getCategoryProductsCollection();
	    foreach ($products as $product) {
	        //if ($product->getId() === 4) {
	            var_dump($product->getMainImage());
	        //}
	    }
	    **/
	    
	    return $this->render(
	        "@Content/category/products.html.twig",
	        [
	            "currentCategory" => $this->category,
	            "ancestors" => $ancestors,
	            "products" => $this->getCategoryProductsCollection(),
	            "childrenProducts" => $this->getChildrenProductsCollection()
	        ]
	    );
	}
	
	/**
	 * @Rest\Get("/category/{slug}")
	 */
	public function byCategoryAction(Request $request) {
		
		$routeComponent = $request->get("slug");
		
		if (filter_var($routeComponent, FILTER_VALIDATE_INT)) {
		    $id = (int) $routeComponent;
		    $slug = "";
		} else {
		    $slug = $routeComponent;
		    $id = 0;
		}
		
		// Récupère la catégorie
		if ($id !== 0) {
		    // Récupère la catégorie par son id
		    $this->category = $this->getById($id);
		} else {
		    // Récupère la catégorie par son slug
		    $this->category = $this->getBySlug($slug);
		}
		
		if ($this->category) {
		    if ($this->category->hasParent()) {
		        // Récupère le noeud le plus haut dans l'arborescence
		        $this->ancestor = $this->category->getAncestor();
		    } else {
		        $this->ancestor = $this->category;
		    }
		    
		    // Produits de la catégorie courante
		    $this->categoryProducts = $this->getCategoryProducts();
		    
		    // Produits des catégories descendantes de la catégorie courante
		    $this->childrenProducts = $this->getChildrenProducts();
		    
		    // Catégories parentes de la catégorie courante
		    $this->parentCategories = $this->getParentCategories();
		    
		    $taxonomy = [
		        "currentCategory" => [
		            "id" => $this->category->getId(),
		            "slug" => $this->category->getSlug(),
		            "content" => $this->category->getRawContent()
		        ],
		        "categoryProducts" => $this->categoryProducts,
		        "childrenProducts" => $this->childrenProducts,
		        "ancestors" => $this->parentCategories
		    ];
		    return new View($taxonomy, Response::HTTP_OK);
		}
		
		return new View("La catégorie n'existe pas ou n'est pas disponible !", Response::HTTP_NOT_FOUND);
		
	}
	
	/**
	 * @Rest\Get("/promotions")
	 */
	public function promotions() {
	    $this->category = $this->getBySlug("promotions");
	    
	    // Produits de la catégorie courante
	    $this->categoryProducts = $this->getCategoryProducts();
	    
	    if (count($this->categoryProducts)) {
	        // @todo Récupérer la lead image de la promotion pour ce produit
	        return new View($this->categoryProducts, Response::HTTP_OK);
	    }
	    
	    return new View("Aucune promotion en cours", Response::HTTP_NOT_FOUND);
	}
	
	/**
	 * @Rest\Get("/news")
	 */
	public function nouveautes() {
	    $this->category = $this->getBySlug("news");
	    
	    // Produits de la catégorie courante
	    $this->categoryProducts = $this->getCategoryProducts();
	    
	    if (count($this->categoryProducts)) {
	        // @todo Récupérer la lead image de la promotion pour ce produit
	        return new View($this->categoryProducts, Response::HTTP_OK);
	    }
	    
	    return new View("Aucune nouveauté", Response::HTTP_NOT_FOUND);
	}
	
	
	/**
	 * @Rest\Get("/topsales")
	 */
	public function topSales() {
	    $repository = $this->getDoctrine()
	       ->getManager()
	       ->getRepository(Categorie::class);
	    
	    $topSales = $this->getTopSelling($repository->getTopSelling());
	    
	    if (count($topSales)) {
	        // @todo Récupérer la lead image de la promotion pour ce produit
	        return new View($topSales, Response::HTTP_OK);
	    }
	    
	    return new View("Meilleures ventes non disponibles pour l'instant", Response::HTTP_NOT_FOUND);
	}
	
	/**
	 * Récupère la catégorie par l'intermédiaire de son ID
	 * @param int $id
	 * @return Categorie | null
	 */
	private function getById(int $id) {
	    
	    $category = $this->getDoctrine()
	       ->getManager()
	       ->find(Categorie::class, $id);
	    
	    return $category;
	}
	
	/**
	 * Retourne la catégorie par l'intermédiaire du slug
	 * @param string $slug
	 * @return Categorie|NULL
	 */
	private function getBySlug(string $slug) {
	    $category = $this->getDoctrine()
	       ->getManager()
	       ->getRepository(Categorie::class)
	       ->findOneBy(["slug" => $slug]);
	    
	    return $category;
	}
	
	/**
	 * Récupère les produits de la catégorie courante uniquement
	 * @return array
	 */
	private function getCategoryProducts(): array {
	    $products = [];
	    
	    if ($this->category->hasArticles()) {
	        $products = $this->category->getRawArticles();
	    }
	    
	    return $products;
	}
	
	/**
	 * Récupère la liste des produits de la catégorie courante
	 * @return array
	 */
	private function getCategoryProductsCollection(\MenuBundle\Entity\Categorie $category = null): array {
	   $products = [];
	   
	   if ($category === null) {
	       $category = $this->category;
	   }
	   
	   $catToArticles = $category->getArticles();
	    
	    foreach ($catToArticles as $catToArticle) {
	        if ($catToArticle->getArticle()->getIsEnabled()) {
	            $products[] = $catToArticle->getArticle();
	        }
	    }
	    
	    return $products;
	}
	
	/**
	 * Retourne les produits des catégories filles de la catégorie courante
	 * @return array
	 */
	private function getChildrenProducts(\MenuBundle\Entity\Categorie $category = null, array $childrenProducts = null): array {
	    if ($childrenProducts === null) {
	       $childrenProducts = [];
	    }
	    
	    if ($category === null) {
	        $category = $this->category;
	    }
	    
	    if ($category->hasChildren()) {
	        foreach($category->getChildren() as $child) {
	            $childrenProducts[] = [
	                "category" => [
	                    "id" => $child->getId(),
	                    "slug" => $child->getSlug(),
	                    "content" => $child->getRawContent()
	                ],
	                //"category" => $child->getRawContent(),
	                "products" => $child->getRawArticles(),
	                "childrenCategory" => $this->getChildrenProducts($child)
	            ];
	            $this->getChildrenProducts($child, $childrenProducts);
	        }
	    }
	    
	    return $childrenProducts;
	}
	
	private function getChildrenProductsCollection(\MenuBundle\Entity\Categorie $category = null, array $childrenProducts = null): array {
	    if ($childrenProducts === null) {
	        $childrenProducts = [];
	    }
	    
	    if ($category === null) {
	        $category = $this->category;
	    }
	    
	    if ($category->hasChildren()) {
	        foreach($category->getChildren() as $child) {
	            $childrenProducts[] = [
	                "category" => $child,
	                "products" => $this->getCategoryProductsCollection($child),
	                "childrenCategory" => $this->getChildrenProducts($child)
	            ];
	            $this->getChildrenProducts($child, $childrenProducts);
	        }
	    }
	    
	    return $childrenProducts;
	}
	
	/**
	 * Retourne les catégories parentes de la catégorie courante
	 * @return array
	 */
	private function getParentCategories(): array {
	    return $this->category->getAncestorsAsArray();
	}
	
	/**
	 * Retourne le top 10 des ventes
	 * @param ArrayCollection $articles
	 * @return array
	 */
	private function getTopSelling(array $articles): array {
	    $soldedArticles = [];
	    
	    foreach ($articles as $article) {
	        $content = $article->getContent();
	        
	        if (property_exists($content, "sold")) {
	            $soldedArticles[] = [
	                "product" => $article->getRawContent(),
	                "soldQuantity" => $content->sold
	            ];
	        }
	    }
	    
	    // Tri le résultat dans l'odre des quantités vendues
	    uasort($soldedArticles, function($first, $second) {
	        return $first["soldQuantity"] <=> $second["soldQuantity"];
	    });
	        
	    // Récupère les 10 dernières meilleures ventes
	    return array_slice($soldedArticles, 0, 9, true);
	}
}
