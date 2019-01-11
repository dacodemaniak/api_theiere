import { ProductService } from './../../services/product.service';
import { BasketService } from './../../services/basket.service';
import { BasketModel } from './models/basket.model';
import { StringToNumberHelper } from '../../helpers/string-to-number.helper';
/**
 * @name BasketListModule
 * @desc Affichage de la liste des produits dans le panier
 * @author IDea Factory - Déc. 2018 (dev-team@ideafactory.fr)
 * @package modules\basket
 * @version 1.0.0
 */
export class BasketListModule {
    private basket: Array<BasketModel>;

    public constructor() {
        this._init().then((panier) => {
            this.basket = panier;

            // Construit le panier
            const tbody: JQuery = $('#basket-list tbody');
            let granTotal: number = 0;
            let fullTaxTotal: number = 0;

            if (this.basket.length) {
                for (let product of this.basket) {
                    let total: number = product.priceHT * product.quantity;
                    granTotal += total;

                    fullTaxTotal += this._getTTC(product, product.product);

                    product.getTableRow().then((row) => {
                        tbody.append(row);
                        
                    });
                }
                

                // Ajouter le total HT au pied de tableau
                $('.gran-total').html(StringToNumberHelper.toCurrency(granTotal.toString()));
                $('.fulltax-total').html(StringToNumberHelper.toCurrency(fullTaxTotal.toString()));
            }

            $('#basket-list').removeClass('hidden');
        });
    }

    /**
     * Récupère les produits du panier
     
    private _init(): Promise<Array<BasketModel>> {
        return new Promise((resolve) => {
            const basketService: BasketService = new BasketService();
            basketService.localBasket().then((panier) => {
                resolve(panier);
            });
        });
    }

    private _getTTC(inBasket: BasketModel, product: any): number {
        const priceList: Array<any> = product.pricing;
        
        let price: any;

        if (priceList.length > 1) {
            const index: number = priceList.findIndex((obj) => { return obj.quantity == inBasket.servingSize});
            price = priceList[index];
        } else {
            price = priceList[0];
        }

        let vat: number = product.vat;
        if (vat === 0.05) {
            vat = 0.055;
        }
        return inBasket.priceHT * (1 + vat);
    }
    */

    private _init() {
        const basketService: BasketService = new BasketService();
        const productService: ProductService = new ProductService();
    
        const promise: Promise<Array<BasketModel>> = new Promise((resolve) => {
            
            basketService.localBasket().then((panier) => {
                panier.forEach((basket: BasketModel) => {
                    productService.getProduct(basket.id).then((product) => {
                        basket.product = product;
                        this.basket.push(basket);
                    })
                });
                resolve();
            });
        });
    }
}