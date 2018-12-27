<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use ContentBundle\ContentBundle;

class DefaultController extends Controller
{
    /**
     * Objet contenant les données de l'éditorial
     * @var unknown
     */
    public $editorial;
    
	/**
	 * @Route("/", defaults={"_format"="html"})
	 */
	public function indexAction(Request $request) {
	    $request->setRequestFormat("html");
	    
	    $this->editorial = $this->getEditorial();
	    
	    $promotion = $this->getBySlug("promotions");
	    $products = [];
	    
	    if ($promotion) {
	        $catToArticles = $promotion->getArticles();
	        foreach($catToArticles as $catToArticle) {
	            $products[] = [
	                "product" => $catToArticle->getArticle(),
	                "image" => $catToArticle->getArticle()->getMainImage()
	            ];
	        }
	    }
	    
		return $this->render("@App/Default/index.html.twig",
		  [
		      "editorial" => $this->editorial,
		      "promotions" => $products
		  ]
		);
	}
	
	/**
	 * Retourne l'article spécifique éditorial
	 * 
	 */
	private function getEditorial() {
	    $content = null;
	    $editorial = $this->getDoctrine()
	       ->getManager()
	       ->getRepository("ContentBundle:Article")
	       ->findOneBySlug("editorial");
	   
	   if ($editorial) {
	       $content = $editorial->getContent();        
	   }
	   
	   return $content;
	    
	}
	
	/**
	 * Retourne la catégorie par l'intermédiaire du slug
	 * @param string $slug
	 * @return \MenuBundle\Entity\Categorie|NULL
	 */
	private function getBySlug(string $slug) {
	    $category = $this->getDoctrine()
	    ->getManager()
	    ->getRepository(\MenuBundle\Entity\Categorie::class)
	    ->findOneBy(["slug" => $slug]);
	    
	    return $category;
	}
    
}