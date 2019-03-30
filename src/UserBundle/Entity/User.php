<?php

namespace UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * User
 *
 * @ORM\Table(name="users")
 * @ORM\Entity(repositoryClass="UserBundle\Repository\UserRepository")
 */
class User
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
     * @ORM\Column(name="login", type="string", length=75, unique=true)
     */
    private $login;

    /**
     * @var string
     *
     * @ORM\Column(name="security_pass", type="string", length=32)
     */
    private $securityPass;

    /**
     * @var string
     *
     * @ORM\Column(name="salt", type="string", length=25)
     */
    private $salt;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_valid", type="boolean")
     */
    private $isValid;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_login", type="datetime", nullable=true)
     */
    private $lastLogin;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="validated_at", type="datetime", nullable=true)
     */
    private $validatedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    private $content;

    /**
     * @var \UserBundle\Groupe
     *
     * @ORM\ManyToOne(targetEntity="Groupe", inversedBy="users", fetch="EAGER")
     * @ORM\JoinColumn(name="groupe_id", referencedColumnName="id")
     */
    private $group;

    /**
     * @ORM\OneToMany(targetEntity=Basket::class, cascade={"persist", "remove"}, mappedBy="user")
     */
    protected $orders;
    
    /**
     * Constructeur de la classe User
     */
    public function __construct() {
        
        $this->orders = new ArrayCollection();
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
     * Set login
     *
     * @param string $login
     *
     * @return User
     */
    public function setLogin($login)
    {
        $this->login = $login;

        return $this;
    }

    /**
     * Get login
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Set securityPass
     *
     * @param string $securityPass
     *
     * @return User
     */
    public function setSecurityPass($securityPass)
    {
        $this->securityPass = $securityPass;

        return $this;
    }

    /**
     * Get securityPass
     *
     * @return string
     */
    public function getSecurityPass()
    {
        return $this->securityPass;
    }

    /**
     * Set salt
     *
     * @param string $salt
     *
     * @return User
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Get salt
     *
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Set isValid
     *
     * @param boolean $isValid
     *
     * @return User
     */
    public function setIsValid($isValid)
    {
        $this->isValid = $isValid;

        return $this;
    }

    /**
     * Get isValid
     *
     * @return bool
     */
    public function getIsValid()
    {
        return $this->isValid;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return User
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set lastLogin
     *
     * @param \DateTime $lastLogin
     *
     * @return User
     */
    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    /**
     * Get lastLogin
     *
     * @return \DateTime
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * Set validatedAt
     *
     * @param \DateTime $validatedAt
     *
     * @return User
     */
    public function setValidatedAt($validatedAt)
    {
        $this->validatedAt = $validatedAt;

        return $this;
    }

    /**
     * Get validatedAt
     *
     * @return \DateTime
     */
    public function getValidatedAt()
    {
        return $this->validatedAt;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return User
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Ajoute une adresse de facturation
     * @param array $address
     */
    public function addBillingAddress(array $address) {
        $content = $this->getRawContent();
        $content["addresses"]["billing"] = $address;
        
        $this->content = json_encode($content);
    }

    /**
     * Ajoute une adresse de facturation
     * @param array $address
     */
    public function addDeliveryAddress(array $address) {
        $content = $this->getRawContent();
        $content["addresses"]["delivery"][] = $address;
        
        $this->content = json_encode($content);
    }
    /**
     * Get content
     *
     * @return JsonDecode
     */
    public function getContent()
    {
    	if ($this->content !== null) {
    		$json = new JsonDecode();
        	return $json->decode($this->content, JsonEncoder::FORMAT);
    	}
    	
    	return null;
    }
    
    public function getRawContent() {
    	if ($this->content !== null) {
    		$json = new JsonDecode(true);
    		return $json->decode($this->content, JsonEncoder::FORMAT);
    	}
    	return null;
    }
    
    /**
     * Retourne une des données de contenu Utilisateur si elle existe
     * @param string $attributeName
     * @return string|NULL
     */
    public function __get(string $attributeName) {
        if (!property_exists($this, $attributeName)) {
            $content = $this->getContent();
            if (property_exists($content, $$attributeName)) {
                return $content->{$attributeName};
            }
        }
        return null;
    }
    
    /**
     * Retourne le numéro de téléphone
     * @return string|NULL
     */
    public function getPhoneNumber() {
        $content = $this->getContent();
        
        if (property_exists($content, "phone")) {
           return $content->phone;
        }
        return null;
    }
    
    /**
     * Retourne le groupe de l'utilisateur courant
     * @return UserBundle\Groupe
     */
    public function getGroup() {
    	return $this->group;
    }
    
    public function setGroup($group) {
    	$this->group = $group;
    }
    
    /**
     * Retourne la liste des commandes de l'utilisateur concerné
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getOrders() {
        return $this->orders;
    }
    
    public function addOrder(Basket $basket) {
        $this->orders[] = $basket;
    }
    
    public function getName(): string {
        $content = $this->getContent();
        
        return $content->civility . " " . $content->lastName . " " . $content->firstName;
    }
}

