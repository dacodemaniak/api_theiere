<?php
namespace ContentBundle\Import\Strategy;

/**
 *
 * @author jean-
 *        
 */
class CoffeeStrategy implements ImportInterface
{
    const _MAIN_CATEGORY_ID             = 5;
    const _FILE_NAME                    = "cafes.csv";
    const _TOTAL_COLS                   = 13;
    const _MAIN_CATEGORY_EMPTY_COLS     = 12;
    const _SUB_CATEGORY_EMPTY_COLS      = 11;
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
        $this->addDecorator("images-produits", 9, "_parseImages")
            ->addDecorator("conseil", 11);
        
        // Définition des positions des colonnes
        $this->statusColumn = 12;
        $this->referenceColumn = 10;
        $this->priceColumn = 5;
        $this->vatColumn = 8;
        $this->grammageColumn = 6;
        
        // Données de base
        $this->titleColumn = 0;
        $this->originColumn = 2;
        $this->abstractColumn = 3;
        $this->descriptionColumn = 4;
        $this->conditionnementColumn = 7;
        
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

