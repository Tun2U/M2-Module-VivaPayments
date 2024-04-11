/**
 * @category    Tun2U
 * @package     Tun2U_VivaPayments
 * @author      Tun2U Team <dev@tun2u.com>
 * @copyright   Copyright (c) 2024 Tun2U (https://www.tun2u.com)
 * @license     https://opensource.org/licenses/gpl-3.0.html  GNU General Public License (GPL 3.0)
 */
/*browser:true*/
/*global define*/
define(["Magento_Checkout/js/view/payment/default", "mage/url"], function (
    Component,
    url
) {
    "use strict";

    return Component.extend({
        defaults: {
            template: "Tun2U_VivaPayments/payment/paymentmethod",
        },
        redirectAfterPlaceOrder: false,
        /**
         * After place order callback
         */
        afterPlaceOrder: function () {
            window.location.replace(url.build("vivapayments/checkout/start"));
        },
    });
});
