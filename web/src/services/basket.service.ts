/**
 * @name BasketService
 * @desc Service de gestion du panier de commande
 * @author IDea Factory - Déc. 2018
 * @package services
 * @version 1.0.0
 */

import * as $ from 'jquery';
import { BasketModel } from './../modules/basket/models/basket.model';

export class BasketService {
    /**
     * Tableau des produits du panier courant
     * @var Array<BasketModel>
     */
    private basket: Array<BasketModel>;

    public constructor(){
        this.basket = new Array<BasketModel>();
    }

    /**
     * Promesse de panier local
     */
    public localBasket(): Promise<Array<BasketModel>> {
        return new Promise((resolve) => {
            const basket = JSON.parse(localStorage.getItem('eshop-basket'));
            const panier: Array<BasketModel> = new Array<BasketModel>();
            if (basket) {
                console.log('Panier: ' + JSON.stringify(basket));
                for (let element of basket) {
                    const product = new BasketModel();
                    product.deserialize(element);
                }
                console.log('Résolution du panier');
                resolve(panier);
            }
        });
    }

    /**
     * Ajoute ou met à jour un produit dans le panier courant
     * @return Promise<Array<BasketModel>>
     */
    public addProduct(product: BasketModel): Promise<Array<BasketModel>> {
        console.log('Ajoute un produit au panier');
        return new Promise((resolve) => {
            this.localBasket().then((panier) => {
                if (panier.length) {
                    const index: number = panier.findIndex((obj) => { return obj.id == product.id});
                    if (index !== -1) {
                        const updateProduct: BasketModel = panier[index];
                        updateProduct.quantity += product.quantity;
                        panier[index] = updateProduct;
                    } else {
                        panier.push(product);
                    }
                } else {
                    panier.push(product);
                }
                // Met à jour le stockage local
                localStorage.setItem('eshop-basket', JSON.stringify(panier));
                resolve(panier);
            });
        });

    }
}