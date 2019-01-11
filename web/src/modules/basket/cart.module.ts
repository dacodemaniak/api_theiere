import { ProductService } from './../../services/product.service';
import { ToastModule } from './../toast/toast.module';
import { BasketModel } from './models/basket.model';
import { BasketService } from './../../services/basket.service';
/**
 * @name CartModule
 * @desc Gestion du formulaire d'ajout au panier
 * @author IDea Factory - Déc. 2018 (dev-team@ideafactory.fr)
 * @package modules/basket
 * @version 1.0.0
 */

import { StringToNumberHelper } from './../../helpers/string-to-number.helper';

 export class CartModule {
    /**
     * Formulaire sur lequel gérer les listeners
     */
    private cartForm: JQuery;

    public constructor() {
        console.log('Instanciation de CartModule');
        this.cartForm = $('.formCart');

        // Gère les listeners
        this._setListeners();
    }

    /**
     * Définit les listeners sur les formulaires de sélection de produit
     */
    private _setListeners() {
        // Listener sur le changement d'état du sélecteur de quantité
        $('.quantity-selector').on(
            'change',
            (event: any): void => this._handleQuantitySelector(event)
        );

        console.log('Définit les listeners sur les boutons + et -');
        
        $('.increase').on(
            'click',
            (event: any): void => this.increase(event)
        );

        $('.decrease').on(
            'click',
            (event: any): any => this.decrease(event)
        );

        $('.add-to-cart').on(
            'click',
            (event: any): void => this._addToCart(event)
        );
    }

    /**
     * Gère la sélection de quantité
     */
    private _handleQuantitySelector(event: any) {
        const quantitySelector = $(event.target);

        const selectedLine = quantitySelector.find('option:selected');
        const htPrice: number = parseFloat(selectedLine.data('price'));
        const vat: number = parseFloat(selectedLine.data('vat'));
        const ttcPrice: number = htPrice * (1 + vat);

        console.log('Prix HT ' + htPrice.toString() + ' vat : ' + vat);

        const priceHolder: JQuery = $('span[data-rel="' + quantitySelector.attr("id") + '"]');

        priceHolder.html(new Intl.NumberFormat('fr-FR', {style: 'currency', currency: 'EUR'}).format(ttcPrice));

        // Mise à jour des quantités max...
        const stock: number = parseInt(selectedLine.data('stock'));
        const maxPerOrder: number = parseInt(selectedLine.data('maxperorder'));
        const thresold: number = parseInt(selectedLine.data('thresold'));
        let max: number = 0;
        if ((stock - thresold) < maxPerOrder) {
            max = stock - thresold;
        } else {
            max = maxPerOrder;
        }
        // Met à jour le champ de saisie en conséquence
        const form: JQuery = quantitySelector.parents('form');
        const input: JQuery = $('input[data-rel="' + form.attr('id') + ']"');
        input.attr('max', max);
    }

    /**
     * Incrémente la quantité de produit
     */
    private increase(event: any): void {

        const button: JQuery = $(event.target);
        const form: JQuery = button.parents('form');
        const input: JQuery = $('input[data-rel="' + form.attr('id') + '"]');

        console.log('Incrémente la quantité pour le formulaire : ' + form.attr('id'));
        
        const max: number = parseInt(input.attr("max"));
        const currentVal: number = parseInt(input.val().toString());

        if (currentVal + 1 < max) {
            input.val(currentVal + 1);
        }
    }

    /**
     * Décrémente la quantité de produit
     */
    private decrease(event: any) {
        console.log('Décrémente la quantité');

        const button: JQuery = $(event.target);
        const form: JQuery = button.parents('form');
        const input: JQuery = $('input[data-rel="' + form.attr('id') + '"]');

        const min: number = parseInt(input.attr("min"));
        const currentVal: number = parseInt(input.val().toString());

        if (currentVal - 1 >= min) {
            input.val(currentVal - 1);
        }
    }


    /**
     * Ajoute un produit dans le panier local
     */
    private _addToCart(event: any): void {
        const button: JQuery = $(event.target);
        const form: JQuery = button.parents('form');
        const input: JQuery = $('input[data-rel="' + form.attr('id') + '"]');
        const price: number = StringToNumberHelper.toNumber(form.find('.price').eq(0).html());
        const quantity: number = parseInt(input.val().toString());
        let servingSize: string = null;

        // Gérer la quantité servie
        const quantitySelector: JQuery = form.find('.quantity-selector');
        if (quantitySelector.is('select')) {
            // Récupère la quantité servie dans la liste
            const selectedLine = quantitySelector.find('option:selected');
            servingSize = selectedLine.text().trim();
        }
        console.log('Ajouter un produit dans le panier : \n' + button.data('id') + '\nPrix : ' + price + '\nQuantité : ' + quantity + '\nService : ' + servingSize);

        const basketModel: BasketModel = new BasketModel();
        basketModel.id = button.data('id');
        basketModel.priceHT = price;
        basketModel.quantity = quantity;
        basketModel.servingSize = servingSize;

        const productService: ProductService = new ProductService();

        productService.getProduct(basketModel.id).then((product) => {
            basketModel.product = product.product;
            const basketService: BasketService = new BasketService();
            basketService.addProduct(basketModel).then((panier) => {
                const userBasketQuantity: JQuery = $('#user-basket').find('span').eq(0);
                userBasketQuantity.html(panier.length.toString());
    
                // Plus le toast pour indiquer que ça s'est bien passé
                const toast: ToastModule = new ToastModule({
                    title: 'Produit ajouté',
                    message: 'Le produit a bien été ajouté au panier.',
                    position: 'middle-center'
                });
                toast.show();
            });
        });
    }
}