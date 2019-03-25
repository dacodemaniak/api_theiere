<?php

namespace MenuBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * Menu
 *
 * @ORM\Table(name="menu")
 * @ORM\Entity(repositoryClass="MenuBundle\Repository\MenuRepository")
 */
class Menu
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
     * @ORM\Column(name="slug", type="string", length=50, unique=true)
     */
    private $slug;

    /**
     * @var string
     *
     * @ORM\Column(name="region", type="string", length=50)
     */
    private $region;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    private $content;

    /**
     * @ORM\OneToMany(targetEntity=MenuToCategories::class, mappedBy="menu")
     */
    private $categories;
    
    public function __construct() {
    	$this->categories = new ArrayCollection();
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
     * @return Menu
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
     * Set region
     *
     * @param string $region
     *
     * @return Menu
     */
    public function setRegion($region)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * Get region
     *
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return Menu
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return JsonDecode
     */
    public function getContent()
    {
    	if ($this->content) {
    		$json = new JsonDecode(true);
    	
        	return $json->decode($this->content, JsonEncoder::FORMAT);
    	}
    }
    
    /**
     * Ajoute une catégorie
     * @param \MenuBundle\Entity\Categorie $categorie
     * @return Menu
     */
    public function addCategorie($categorie): Menu {
    	$this->categories[] = $categorie;
    	
    	return $this;
    }
    
    /**
     * Retourne la liste des catégories du menu courant
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getCategories() {
    	return $this->categories;
    }
    
    public function categoriesToArray() {
    	$options = [];
    	
    	
    	if ($this->categories)
    	{
    		foreach ($this->categories as $option) {
    			$categorie = $option->getCategorie();
    			if ($categorie->getIsEnabled()) {
	    			$options[] = [
	    				"id" => $categorie->getId(),
	    				"ordre" => $option->getPlace(),
	    				"slug" => $categorie->getSlug(),
	    				"route" => $categorie->getRoute(),
	    				"content" => $categorie->getRawContent(),
	    				"nodes" => $categorie->childrenToArray()
	    			];
    			}
    		}
    	}
    	
		return $options;
    }
}

