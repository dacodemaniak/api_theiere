<?php
/**
 * @name Article Entité sur les articles
 * @version 1.0.0
 * @version 1.0.1
 *  - Ajout du prix TTC dans la méthode getRawContent()
 */
namespace ContentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * Article
 *
 * @ORM\Table(name="article")
 * @ORM\Entity(repositoryClass="ContentBundle\Repository\ArticleRepository")
 */
class Article
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=75, unique=true)
     */
    private $slug;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_enabled", type="boolean")
     */
    private $isEnabled;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    private $content;
    
    /**
     * @ORM\OneToMany(targetEntity=DecorArticle::class, mappedBy="article")
     */
    private $decors;

    
    /**
     * @ORM\OneToMany(targetEntity=CategorieToArticles::class, mappedBy="article")
     * 
     * @var ArrayCollection
     */
    private $categorieToArticles;
    
    
    public function __construct() {
    	$this->decors = new ArrayCollection();
    	$this->categorieToArticles = new ArrayCollection();
    }
    
    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set slug
     *
     * @param string $slug
     *
     * @return Article
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set isEnabled
     *
     * @param boolean $isEnabled
     *
     * @return Article
     */
    public function setIsEnabled($isEnabled)
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    /**
     * Get isEnabled
     *
     * @return bool
     */
    public function getIsEnabled()
    {
        return $this->isEnabled;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return Article
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return Object | Array
     */
    public function getContent()
    {
    	
    	if ($this->content) {
    		$json = new JsonDecode();
    		return $json->decode($this->content, JsonEncoder::FORMAT);
    	}
    }
    
    /**
     * Retourne le contenu de l'article sous forme de tableau
     * @return array
     */
    public function getRawContent(): array {
        if ($this->content) {
            
            $json = new JsonDecode(true);
            
            $content = $json->decode($this->content, JsonEncoder::FORMAT);
            
            // Recalcul les prix TTC
            for($i = 0; $i <count($content["pricing"]); $i++) {
                $pricing = $content["pricing"][$i];
                $ttc = $pricing["ht"] * (1 + $content["vat"]);
                $pricing["ttc"] = round($ttc, 2);
                // Remplace le prix...
                $content["pricing"][$i] = $pricing;
            }
            
            return $content;
        }
        
        return [];
    }
    
    /**
     * Retourne la liste des décorations de l'article
     * @return ArrayCollection
     */
    public function getDecors() {
    	return $this->decors;
    }
    
    /**
     * Retourne les décorateurs de l'article courant
     * @return array
     */
    public function getDecorators(): array {
        $decorators = [];
        
        foreach($this->decors as $toDecors) {
            $decorators[] = [
                "decorator" => $toDecors->getDecor()->getRawContent(),
                "values" => $toDecors->getRawContent()
            ];
        }
        
        return $decorators;
    }
    
    /**
     * Retourne l'image principale du produit
     * @return array
     */
    public function getMainImage() {
        $images = [];
        $imagePath = "";
        
        foreach ($this->decors as $toDecors) {
            if ($toDecors->getDecor()->getSlug() === "images-produits") {
                $content = $toDecors->getDecor()->getContent();
                $imagePath = $content->root;
                $values = $toDecors->getContent();
                $images[] = $values;
            }
        }
        $image = array_shift($images)[0];
        
        return [
            "src" => $imagePath . $image->src,
            "alt" => $image->alt->fr
        ];
    }
    
    /**
     * Ajoute un élément de décoration à l'article courant
     * @param Decor $decor
     */
    public function addDecor(Decor $decor) {
    	$this->decors[] = $decor;
    }
    
    /**
     * Méthode magique pour la récupération d'un contenu spécifique
     * dans le contenu JSON d'un document
     * @param string $methodName
     * @param array $args
     */
    public function __call(string $methodName, array $args) {
    	if (($content = $this->getContent()) !== null) {
    		preg_match_all('/((?:^|[A-Z])[a-z]+)/', $methodName, $methodComposition);
    		
    		$composition = $methodComposition[0];
    		
    		$isGetter = ($composition[0] === "get") ? true : false;
    		$hasLang = (count($composition) === 3) ? true : false;
    		
    		$property = strtolower($composition[1]);
    		
    		if ($isGetter) {
	    		if (property_exists($content, $property)) {
	    			if ($hasLang) {
	    				if (property_exists($content->{$property}, strtolower($composition[2]))) {
	    					return $content->{$property}->{strtolower($composition[2])};
	    				}
	    			} else {
	    				return $content->{$property};
	    			}
	    		}
    		}
    	}
    }
}

