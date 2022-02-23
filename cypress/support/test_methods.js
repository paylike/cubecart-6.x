/// <reference types="cypress" />

'use strict';

import { PaylikeTestHelper } from './test_helper.js';

export var TestMethods = {

    /** Admin & frontend user credentials. */
    StoreUrl: (Cypress.env('ENV_ADMIN_URL').match(/^(?:http(?:s?):\/\/)?(?:[^@\n]+@)?(?:www\.)?([^:\/\n?]+)/im))[0],
    AdminUrl: Cypress.env('ENV_ADMIN_URL'),
    RemoteVersionLogUrl: Cypress.env('REMOTE_LOG_URL'),

    /** Construct some variables to be used bellow. */
    ShopName: 'cubecart',
    PaylikeName: 'Paylike', // with first capital
    CheckoutUrl: '/index.php?_a=checkout',
    PaymentMethodsAdminUrl: '?_g=plugins&type=plugins&module=Paylike_Payments',
    OrdersPageAdminUrl: '?_g=orders',
    PluginsPageAdminUrl: '?_g=plugins',

    /**
     * Login to admin backend account
     */
    loginIntoAdminBackend() {
        cy.loginIntoAccount('input[name=username]', 'input[name=password]', 'admin');
    },
    /**
     * Login to client|user frontend account
     */
    loginIntoClientAccount() {
        cy.loginIntoAccount('input[name=username]', 'input[name=password]', 'client');
    },

    /**
     * Modify Paylike settings
     * @param {String} captureMode
     */
    changePaylikeCaptureMode(captureMode) {
        /** Go to Paylike payment method. */
        cy.goToPage(this.PaymentMethodsAdminUrl);

        /** Select capture mode. */
        cy.selectOptionContaining('select[name="module[capturemode]"]', captureMode)

        cy.get('.form_control > input[name=save]').click();
    },

    /**
     * Make payment with specified currency and process order
     *
     * @param {String} currency
     * @param {String} paylikeAction
     * @param {Boolean} partialAmount
     */
     payWithSelectedCurrency(currency, paylikeAction, partialAmount = false) {
        /** Make an instant payment. */
        it(`makes a Paylike payment with "${currency}"`, () => {
            this.makePaymentFromFrontend(currency);
        });

        /** Process last order from admin panel. */
        it(`process (${paylikeAction}) an order from admin panel`, () => {
            this.processOrderFromAdmin(paylikeAction, partialAmount);
        });
    },

    /**
     * Make an instant payment
     * @param {String} currency
     */
    makePaymentFromFrontend(currency) {
        /** Go to store frontend. */
        cy.goToPage(this.StoreUrl);

        /** Change currency. */
        this.changeShopCurrency(currency);

        /** Add to cart random product. */
        var randomInt = PaylikeTestHelper.getRandomInt(/*max*/ 1);
        cy.get('button[value="Add to Basket"]').eq(randomInt).click();

        /** Go to checkout. */
        cy.goToPage(this.StoreUrl + this.CheckoutUrl);

        /** Choose Paylike. */
        cy.get(`input[id*=${this.PaylikeName}]`).click();

        /** Get & Verify amount. */
        cy.get('#content_checkout_medium_up td').contains('Grand Total').next().then($grandTotal => {
            cy.window().then(win => {
                var expectedAmount = PaylikeTestHelper.filterAndGetAmountInMinor($grandTotal, currency);
                var orderTotalAmount = Number(win.cc_paylike_params.amount);
                expect(expectedAmount).to.eq(orderTotalAmount);
            });
        });

        /** Show paylike popup. */
        cy.get('#checkout_proceed').click();

        /**
         * Fill in Paylike popup.
         */
         PaylikeTestHelper.fillAndSubmitPaylikePopup();

        cy.wait(500);

        cy.get('.alert-box.success').should('contain', 'Payment has been received');
    },

    /**
     * Process last order from admin panel
     * @param {String} paylikeAction
     * @param {Boolean} partialAmount
     */
    processOrderFromAdmin(paylikeAction, partialAmount = false) {
        /** Go to admin orders page. */
        cy.goToPage(this.OrdersPageAdminUrl);

        /** Click on first (latest in time) order from orders table. */
        cy.get('#orders tbody a').first().click();

        /**
         * Take specific action on order
         */
        this.paylikeActionOnOrderAmount(paylikeAction, partialAmount);
    },

    /**
     * Capture an order amount
     * @param {String} paylikeAction
     * @param {Boolean} partialAmount
     */
     paylikeActionOnOrderAmount(paylikeAction, partialAmount = false) {
        switch (paylikeAction) {
            case 'capture':
                /** Change order status and submit. */
                cy.selectOptionContaining('select[name="order[status]"]', 'Order Complete');
                break;
            case 'refund':
                /** Select refund tab. */
                cy.get('#tab_plrefund').click();
                /** Refund transaction. */
                cy.get('img[rel="#confirmplrefund"]').click();
                break;
            case 'void':
                /** Select void tab. */
                cy.get('#tab_plvoid').click();
                /** Void transaction. */
                cy.get('img[rel="#confirmplvoid"]').click();
                break;
        }

        /** Trigger selected action. */
        cy.get('input[value=Save]').click();

        /** Check if success message. */
        cy.get('#gui_message .success:nth-child(2)').should('be.visible');
    },

    /**
     * Change shop currency in frontend
     */
    changeShopCurrency(currency) {
        cy.get('a[data-dropdown=currency-switch]').then($dropDownCurrencyButton => {
            var currencyAlreadySelected = $dropDownCurrencyButton.text().includes(currency);
            if (!currencyAlreadySelected) {
                $dropDownCurrencyButton.trigger('click');
                cy.get('#currency-switch a').contains(currency).click();
            }
        });
    },

    /**
     * Get Shop & Paylike versions and send log data.
     */
    logVersions() {
        /** From admin dashboard click on "Store Overview" tab. */
        cy.get('a[href="#advanced"]').click();

        /** Get framework version. */
        cy.get('dt').contains('CubeCart').closest('dl').then($frameworkVersion => {
            var frameworkVersion = $frameworkVersion.children('dd:nth-child(2)').text();
            cy.wrap(frameworkVersion).as('frameworkVersion');
        });

        /** Go to plugins/modules page. */
        cy.goToPage(this.PluginsPageAdminUrl);

        /** Get paylike version. */
        cy.get(`input[id*=${this.PaylikeName}]`).closest('tr').then($paylikeVersion => {
            var paylikeVersion = $paylikeVersion.children('td:nth-child(3)').text();
            cy.wrap(paylikeVersion).as('paylikeVersion');
        });

        /** Get global variables and make log data request to remote url. */
        cy.get('@frameworkVersion').then(frameworkVersion => {
            cy.get('@paylikeVersion').then(paylikeVersion => {

                cy.request('GET', this.RemoteVersionLogUrl, {
                    key: frameworkVersion,
                    tag: this.ShopName,
                    view: 'html',
                    // framework: frameworkVersion,
                    ecommerce: frameworkVersion,
                    plugin: paylikeVersion
                }).then((resp) => {
                    expect(resp.status).to.eq(200);
                });
            });
        });
    },
}