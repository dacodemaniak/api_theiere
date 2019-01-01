import { Constants } from './../../shared/constants';
import { EmailHelper } from './../../helpers/email.helper';
import { ToastModule } from '../toast/toast.module';

/**
 * @name ContactModule
 * @desc Gestionnaire du formulaire de contact
 * @author IDea Factory - Déc. 2018 (dev-team@ideafactory.fr)
 * @package modules/contact
 * @version 1.0.0
 */
export class ContactModule {
    private apiRoot: string;

    private messageField: JQuery = $('#message-content');
    private nameField: JQuery = $('#name-content');
    private emailField: JQuery = $('#email-content');

    private buttonField: JQuery = $('#send');

    public constructor() {
        this.apiRoot = Constants.apiRoot;
        this._setListeners();
    }

    private _setListeners(): void {
        $('#contact-form').on(
            'keyup',
            (event: any): void => this.formControl()
        );

        $('#contact-form').on(
            'submit',
            (event: any): void => this.send(event)
        )
        this.nameField.on(
            'blur',
            (event: any): void => this.isDirty(this.nameField)
        );
    }

    /**
     * Contrôle le formulaire de contact
     */
    private formControl() {

        if (this.nameField.val() !== '' && this.messageField.val() !== '' && this.emailField.val() !== '') {
            const emailVal: string = this.emailField.val() as string;
            if (EmailHelper.validate(emailVal)) {
                this.buttonField.removeAttr('disabled');
                return;
            }

        }

        this.buttonField.attr('disabled', 'disabled');
    }

    private isDirty(field: JQuery): void {
        const message: JQuery = $('[data-rel="' + field.attr('id') + '"]');
        if (field.val() === '') {
            message.toggleClass('hidden');
        } else {
            if (field.attr('id') === 'email-content') {
                if (!EmailHelper.validate(this.emailField.val() as string)) {
                    message.toggleClass('hidden');
                }
            }
        }
    }

    private send(event: any) {
        event.preventDefault();

        const datas: any = {
            name: this.nameField.val(),
            email: this.emailField.val(),
            message: this.messageField.val()
        };

        console.log('Envoie le formulaire');

        $.ajax({
            url: this.apiRoot + 'contact',
            method: 'put',
            dataType: 'json',
            success: (datas) => {
                console.log(datas.status);
                if (datas.status === 200) {
                    const toast = new ToastModule(
                        {
                            position: 'top-center',
                            type: 'success',
                            title: 'Votre message a été envoyé',
                            message: datas.message
                        }
                    );
                    toast.show();
                    // Réinitialise le formulaire
                    this.buttonField.attr('disabled', 'disabled');
                    this.nameField.val('');
                    this.emailField.val('');
                    this.messageField.val('');
                } else {
                    const toast = new ToastModule(
                        {
                            position: 'top-center',
                            type: 'warning',
                            title: 'Une erreur est survenue',
                            message: datas.message
                        }
                    );
                    toast.show();                    
                }
            },
            error: (xhr, error, datas) => {
                console.log(datas);
                const toast = new ToastModule(
                    {
                        position: 'top-center',
                        type: 'danger',
                        title: 'Une erreur est survenue',
                        message: datas
                    }
                );
                toast.show();  
            }
        })
    }

}