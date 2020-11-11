/**
 * Subscribe Pro Apple Pay button
 **/
define(
    [
        'uiComponent',
        "knockout",
        "jquery",
        'mage/translate'
    ],
    function (
        Component,
        ko,
        jQuery,
        $t
    ) {
        'use strict';

        var that;

        return {
            init: function (element, context) {
                console.log('init apple pay button');
                console.log(element);
                // No element or context
                if (!element || !context) {
                    return;
                }

                // Context must implement these methods
                if (typeof context.getClientToken !== 'function') {
                    console.error("Missing getClientToken method in ApplePay context", context);
                    return;
                }
                if (typeof context.getPaymentRequest !== 'function') {
                    console.error("Missing getPaymentRequest method in ApplePay context", context);
                    return;
                }
                if (typeof context.startPlaceOrder !== 'function') {
                    console.error("Missing startPlaceOrder method in ApplePay context", context);
                    return;
                }

                if (this.deviceSupported() === false) {
                    console.log('device not supported');
                    return;
                }

                console.log('Device supported, create Apple Pay session');

                // Create a button within the KO element, as apple pay can only be instantiated through
                // a valid on click event (ko onclick bind interferes with this).
                var el = document.createElement('div');
                el.className = "sp-apple-pay-button";
                el.title = $t("Pay with Apple Pay");
                el.alt = $t("Pay with Apple Pay");
                el.addEventListener('click', function (e) {
                    e.preventDefault();
                    console.log(context);

                    let applePaySession = new ApplePaySession(1, context.getPaymentRequest());
                    applePaySession.onvalidatemerchant = function (event) {
                        jQuery.ajax({
                            url: context.createSessionUrl,
                            type: "POST",
                            dataType: "json",
                            contentType: "application/json; charset=utf-8",
                            crossDomain: true,
                            headers: {
                                'Authorization': 'Bearer ' + context.clientToken.access_token // sp access token
                            },
                            data: JSON.stringify({
                                url: event.validationURL,
                                merchantDomainName: context.merchantDomainName
                            }),
                            success: function (data, textStatus, jqXHR) {
                                applePaySession.completeMerchantValidation(data);
                            },
                            error: function (jqXHR, textStatus, errorThrown) {
                                console.error('Apple Pay Error: ' + errorThrown);
                                applePaySession.abort();
                            }
                        })
                    };

                    applePaySession.onshippingcontactselected = function (event) {
                        return context.onShippingContactSelect(event, applePaySession);
                    };

                    applePaySession.onshippingmethodselected = function (event) {
                        return context.onShippingMethodSelect(event, applePaySession);
                    };

                    applePaySession.onpaymentauthorized = function (event) {
                        console.log(event);

                        applePaySession.completePayment(ApplePaySession.STATUS_SUCCESS);
                    };

                    applePaySession.begin();
                });
                console.log(element, el);
                element.appendChild(el);
            },

            /**
             * Check the site is using HTTPS & apple pay is supported on this device.
             * @return boolean
             */
            deviceSupported: function () {
                if (location.protocol != 'https:') {
                    console.warn("Apple Pay requires your checkout be served over HTTPS");
                    return false;
                }

                if ((window.ApplePaySession && ApplePaySession.canMakePayments()) !== true) {
                    console.warn("Apple Pay is not supported on this device/browser");
                    return false;
                }

                return true;
            },

            replaceTotalLabel: function (total, label) {
                var newTotal = {
                    label: label,
                    amount: total.amount
                };
                if (total.type) {
                    newTotal.type = total.type;
                }

                return newTotal;
            }
        };
    }
);
