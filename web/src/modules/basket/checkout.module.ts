import { CreditCardHelper } from './../../helpers/credit-card.helper';
import { StringToNumberHelper } from './../../helpers/string-to-number.helper';
import { RouterModule } from './../router/router.module';
import { ProductBasketModel } from "./models/product-basket.model";
import { UserService } from "../../services/user.service";
import { StepComponent } from "./step-component";
import { BasketService } from "../../services/basket.service";
import { Constants } from "../../shared/constants";
import { ToastModule } from "../toast/toast.module";

import * as $ from 'jquery';

/**
 * @name CheckoutModule
 * @desc Validation du panier
 * @author IDea Factory - Jan. 2019 (dev-team@ideafactory.fr)
 * @package modules/basket
 * @version 1.0.0
 */
export class CheckoutModule {
    private basketService: BasketService;

    private basket: Array<ProductBasketModel>;
    private userService: UserService;
    private stepComponent: StepComponent;
    private deliveryAddressLabel: string;
    private billingAddress: any;
    private deliveryAddress: any;
    

    private fullTaxBasket: number = 0;
    private carryingCharge: number = 0;
    private totalBasket: number = 0;

    private button: JQuery = $('#confirm-payment');
    private buttonEnableState: boolean = false;

    private cardNumber: JQuery = $('#cardnumber-content');
    
    private formContent: Array<JQuery> = new Array<JQuery>();

    public constructor(deliveryAddress: string) {

        this.deliveryAddressLabel = deliveryAddress;

        this.userService = new UserService();

        // Définit les éléments du formulaire
        this.formContent.push($('#owner-content'));
        this.formContent.push($('#cardnumber-content'));
        this.formContent.push($('#expirationmonth-content'));
        this.formContent.push($('#expirationyear-content'));
        this.formContent.push($('#cvv-content'));

        this.userService.hasUser().then((has) => {
            this._init().then((panier) => {
                this.basket = panier;
                
                // Calcule les totaux : prix TTC et Poids totaux
                this._getBasketTotals();
                $('#total-basket .amount').html(StringToNumberHelper.toCurrency(this.fullTaxBasket.toString()));

                // Récupère les frais de port associés
                this.carryingCharge = this.basketService.getBasket().getCharge();
                $('#total-basket .carrying-charge').html(
                    StringToNumberHelper.toCurrency(this.carryingCharge.toString())
                );

                // Total à payer
                this.totalBasket = this.fullTaxBasket + this.carryingCharge;
                $('#total-basket .full-amount').html(
                    StringToNumberHelper.toCurrency(this.totalBasket.toString())
                );

                // Années valides pour la date d'expiration de la carte
                this._populateCardYears();

                // Instancie le gestionnaire de progression
                this.stepComponent = new StepComponent(this.userService, this.basket);
                this.stepComponent.markAsComplete('signin');
                this.stepComponent.markAsComplete('basket-checkin');
                this.stepComponent.markAsActive('payment');
            });

            // Récupérer l'adresse de facturation
            this.billingAddress = this.userService.getUser().getBillingAddressContent();

            // Récupérer l'adresse de livraison
            const deliveryAddresses: Map<string, any> = this.userService.getUser().getDeliveryAddresses();
            this.deliveryAddress = deliveryAddresses.get(this.deliveryAddressLabel);

            // Alimente les adresses de facturation et livraison
            this._hydrateBilling($('#billing-address ul'));
            this._hydrateDelivery($('#delivery-address ul'));

            // Définit les listeners
            this._setListeners();

        });

    }

    /**
     * Récupère les produits du panier
     */
    private _init(): Promise<Array<ProductBasketModel>> {
        return new Promise((resolve) => {
            this.basketService = new BasketService();
            this.basketService.localBasket().then((panier) => {
                resolve(panier);
            });
        });
    }

    private _setListeners(): void {
        $('#credit-card-form').on(
            'keyup',
            (event: any): void => this._validForm(event)
        );

        
        $('#expirationmonth-content, #expirationyear-content').on(
            'change',
            (event: any): void => this._validForm(event)
        );

        this.cardNumber.on(
            'keyup',
            (event: any): void => this._checkCardNumber(event)
        );
    }

    private _hydrateBilling(address: JQuery): void {
        console.log('Facturation : ' + this.billingAddress.address);
        address.children('.address').eq(0).html(this.billingAddress.address);
        address.children('.city').eq(0).html(
            this.billingAddress.zipcode + ' ' + this.billingAddress.city
        );
        address.children('.country').eq(0).html(this.billingAddress.country);
    }

    private _hydrateDelivery(address: JQuery): void {
        address.children('.address').eq(0).html(this.deliveryAddress.address);
        address.children('.city').eq(0).html(
            this.deliveryAddress.zipcode + ' ' + this.deliveryAddress.city
        );
        address.children('.country').eq(0).html(this.deliveryAddress.country);
    }

    private _validForm(event: any): void {
        const element: JQuery = $(event.target);

        if (this._checkForm()) {
            this.button.removeAttr('disabled');
        } else {
            this.button.attr('disabled', 'disabled');
        }
    }

    private _checkForm(): boolean {
        let buttonEnableState: boolean = true;
        this.formContent.forEach((element: JQuery) => {
            if (element.is('input')) {
                if (element.attr('id') === 'cardnumber-content') {
                    const creditCardValidator: any = CreditCardHelper.validation(element.val().toString());
                    if (creditCardValidator[0].code !== 1000) {
                        buttonEnableState = false;
                    }
                } else {
                    if (element.attr('id') === 'cvv-content') {
                        if (element.val().toString().length < 3) {
                            buttonEnableState = false;
                        }
                    } else {
                        if (element.val() === '') {
                            buttonEnableState = false;
                        }
                    }
                }
            } else {
                if (element.val() === '') {
                    buttonEnableState = false;
                }
            }
        });
        // Tous les contrôles sont passés...
        return buttonEnableState;
    }

    private _checkCardNumber(event: any): void {
        const cardNumber: JQuery = $(event.target);
        const validationState: any = CreditCardHelper.validation(cardNumber.val().toString());

        const alert: JQuery = $('#cardnumber-alert');

        console.log('Trace : ' + validationState[0].code + ' <=> -1000');

        if (validationState[0].code < -1000) {
            
            alert
                .removeClass('hidden')
                .html(validationState[0].message);
        } else if (validationState[0].code === 1001) {
            const logo: JQuery = $('#' + validationState[0].message);
            $('#cards-logo img').removeClass('active');
            logo.addClass('active');
        } else if (validationState[0].code === 1000) {
            const logo: JQuery = $('#' + validationState[0].message);
            $('#cards-logo img').removeClass('active');
            logo.addClass('active');
            
            alert.addClass('hidden');
        }
    }

    private _getBasketTotals(): void {
        for (let basket of this.basket) {
            this.fullTaxBasket = this.fullTaxBasket + basket.getFullTaxTotal();
        }
    }

    /**
     * Alimente la liste des années valides pour la carte
     */
    private _populateCardYears(): void {

        const yearList: JQuery = $('.expiration-date #expirationyear-content');

        console.warn('Années : ' + yearList.attr('data-rel'));

        const today: Date = new Date();
        let currentYear: number = today.getFullYear();

        for(let i: number = 0; i < 5; i++) {
            const option: JQuery = $('<option>');
            option
                .attr('value', currentYear)
                .html(currentYear.toString());
            yearList.append(option);
            currentYear++;
        }
    }
}