<?php
namespace ContentBundle\Import\Strategy;

/**
 *
 * @author jean-
 *        
 */
class AccessoryStrategy implements ImportInterface
{
    const _MAIN_CATEGORY_ID             = 3;
    const _FILE_NAME                    = "accessoires.csv";
    const _TOTAL_COLS                   = 9;
    const _MAIN_CATEGORY_EMPTY_COLS     = 8;
    const _SUB_CATEGORY_EMPTY_COLS      = 7;
    const _PRODUCT_EMPTY_COLS           = 2;
    
    public $statusColumn;
    public $referenceColumn;
    public $priceColumn;
    public $vatColumn;
    public $grammageColumn;
    public $imageColumn;
    
    public $titleColumn;
    public $originColumn;
    public $abstractColumn;
    public $descriptionColumn;
    public $conditionnementColumn;
    
    private $decorators;
    
    public function __construct() {
        $this->addDecorator("images-produits", 7, "_parseImages");
        
        // Définition des positions des colonnes
        $this->statusColumn = 8;
        $this->referenceColumn = 6;
        $this->priceColumn = 3;
        $this->vatColumn = 5;
        $this->grammageColumn = 4;
        
        // Données de base
        $this->titleColumn = 0;
        $this->originColumn = null;
        $this->abstractColumn = null;
        $this->descriptionColumn = 2;
        $this->conditionnementColumn = null;
        
    }
    
    /**
     * Ajoute le type de décorateur à traiter lors de l'importation
     * {@inheritDoc}
     * @see \ContentBundle\Import\Strategy\ImportInterface::addDecorator()
     */
    public function addDecorator(string $decorator, int $colNumber, string $method = null): \ContentBundle\Import\Strategy\ImportInterface {
        $this->decorators[] = [
            "decorator" => $decorator, 
            "col" => $colNumber,
            "method" => $method
        ];
        return $this;
    }
    
    /**
     * Retourne les décorateurs définis ou faux si aucun décorateur
     * @return array; ,|boolean
     */
    public function getDecorators() {
        if (count($this->decorators)) {
            return $this->decorators;
        }
        return false;
    }
}

