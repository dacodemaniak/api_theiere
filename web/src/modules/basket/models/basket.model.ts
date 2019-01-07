import { ProductService } from './../../../services/product.service';
import { StringToNumberHelper } from '../../../helpers/string-to-number.helper';
/**
 * @name BasketModel
 * @desc Définition d'un élément du panier
 * @author IDea Factory - Déc. 2018
 * @package modules/basket/models
 * @version 1.0.0
 */
export class BasketModel {
    /**
     * Identifiant du produit dans le panier
     * @var number
     */
    public id: number;

    /**
     * Prix unitaire du produit
     * @var number
     */
    public priceHT: number;

    /**
     * Quantité commandée
     * @var number
     */
    public quantity: number;

    /**
     * Taux de TVA pour le produit courant
     */
    public vat: number;

    /**
     * Prix TTC du produit
     */
    public priceTTC: number;

    /**
     * Quantité unitaire
     * @var string
     */
    public servingSize?: string;

    /**
     * Produit complet retourné
     */
    public product: any;

    private productService: ProductService;

    public constructor() {
        this.productService = new ProductService();
    }

    public getTableRow(): Promise<JQuery> {
        return new Promise((resolve) => {
            const _tr: JQuery = $('<tr>');

            const _productTD: JQuery = $('<td>');
            _productTD.attr('data-rel', this.id);
    
            this.vat = this.product.product.vat;
            if (this.vat === 0.05) {
                this.vat = 0.055;
            }
            this.priceTTC = (this.priceHT * this.quantity) * (1 + this.vat);

            _productTD.html(this.product.product.title.fr);
            _productTD.appendTo(_tr);
    
            const _servingTD: JQuery = $('<td>');
            _servingTD.html(this.servingSize);
            _servingTD.appendTo(_tr);
    

            const _quantityTD: JQuery = this._input($('<td>'));
            _quantityTD.appendTo(_tr);
    
            const _priceTD: JQuery = $('<td>');
            _priceTD.html(StringToNumberHelper.toCurrency(this.priceHT.toString()));
            _priceTD.appendTo(_tr);
    
            const _totalTD: JQuery = $('<td>');
            const total: number = this.priceHT * this.quantity;
            _totalTD.html(StringToNumberHelper.toCurrency(total.toString()));
            _totalTD.appendTo(_tr);
    
            const _removeTD: JQuery = this._removeElement($('<td>'));
            _removeTD.appendTo(_tr);
    
            resolve(_tr);
        });
    }

    private _removeElement(col: JQuery): JQuery {
        const icon: JQuery = $('<i>');
        icon
            .addClass('icon-cross')
            .addClass('remove-product')
            .attr('data-rel', this.id);
        icon.appendTo(col);

        return col;
    }

    public _input(col: JQuery): JQuery {
        const group: JQuery = $('<div>');
        group
            .addClass('input-group')
            .addClass('mb-3');
        
        const prepend: JQuery = $('<div>');
        prepend
            .addClass('input-group-prepend');
        
        const downButton: JQuery = $('<button>');
        downButton
            .addClass('btn')
            .addClass('btn-outline-secondary')
            .addClass('decrease')
            .attr('type', 'button')
            .html('-')
            .attr('data-rel', 'input-quantity-' + this.id);
        downButton.appendTo(prepend);

        prepend.appendTo(group);

        const input: JQuery = $('<input>');
        input
            .addClass('form-control')
            .attr('id', 'input-quantity-' + this.id)
            .attr('type', 'number')
            .attr('min', 1)
            .attr('max', this._getMax())
            .attr('readonly', 'readonly')
            .val(this.quantity);
        input.appendTo(group);

        const append: JQuery = $('<div>');
        append
            .addClass('input-group-append');

        const upButton: JQuery = $('<button>');
        upButton
            .addClass('btn')
            .addClass('btn-outline-secondary')
            .addClass('increase')
            .attr('type', 'button')
            .html('+')
            .attr('data-rel', 'input-quantity-' + this.id);
        upButton.appendTo(append);

        append.appendTo(group);

        group.appendTo(col);

        return col;

    }

    private _getMax(): number {
        const pricing: Array<any> = this.product.product.pricing;

        if (pricing.length === 1) {
            const stock: number = pricing[0].stock;
            const thresold: number = pricing[0].thresold;
            const maxPerOrder: number = pricing[0].maxPerOrder;

            if ((stock - thresold) < maxPerOrder) {
                return stock - thresold;
            }
            return maxPerOrder;
        } else {
            const index: number = pricing.findIndex((obj: any) => { return obj.quantity == this.servingSize});
            const price: any = pricing[index];

            const stock: number = price.stock;
            const thresold: number = price.thresold;
            const maxPerOrder: number = price.maxPerOrder;

            if ((stock - thresold) < maxPerOrder) {
                return stock - thresold;
            }
            return maxPerOrder;
        }
    }

    public deserialize(basket: any): BasketModel {
        Object.assign(this, basket);
    
        return this;
    }
}