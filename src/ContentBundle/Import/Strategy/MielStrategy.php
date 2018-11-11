<?php
namespace ContentBundle\Import\Strategy;

/**
 *
 * @author jean-
 *        
 */
class MielStrategy implements ImportInterface
{
    const _MAIN_CATEGORY_ID             = 4;
    const _FILE_NAME                    = "miels.csv";
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
        $this->addDecorator("images-produits", 10, "_parseImages")
            ->addDecorator("proprietes", 5);
        
        // Définition des positions des colonnes
        $this->statusColumn = 12;
        $this->referenceColumn = 11;
        $this->priceColumn = 6;
        $this->vatColumn = 7;
        $this->grammageColumn = 9;
        
        // Données de base
        $this->titleColumn = 0;
        $this->originColumn = 2;
        $this->abstractColumn = 3;
        $this->descriptionColumn = 4;
        $this->conditionnementColumn = 8;
        
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

