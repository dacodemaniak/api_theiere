<?php
namespace ContentBundle\Import\Strategy;

/**
 * @property int $referenceColumn  N° de la colonne référence
 * @property int $priceColumn N° de la colonne prix
 * @property int $vatColumn N° de la colonne TVA
 * @property int $grammageColumn N° de la colonne Grammage
 * @property int $conditionnementColumn N° de la colonne Conditionnement
 * @property int $imageColumn N° de la colonne Image
 * @property int $statusColumn N° de la colonne de statut du produit
 * @property int $titleColumn N° de la colonne Titre
 * @property int $originColumn N° de la colonne Origine
 * @property int $abstractColumn N° de la colonne Résumé
 * @property int $descriptionColumn N° de la colonne Description
 */
interface ImportInterface {
    const _SEP = "\n";
    
    /**
     * Ajoute un décorateur pour le traitement de l'importation
     * @var string $decorator Slug du décorateur à instancier
     * @var int $columnNumber N° de la colonne à partir de laquelle traiter le décorateur
     * @var string $method Méthode à utiliser pour traiter la données (null si pas de traitement)
     * @return ImportInterface
     */
    public function addDecorator(string $decorator, int $colNumber, string $method=null): ImportInterface;
}

