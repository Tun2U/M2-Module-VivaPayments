# Tun2U Magento 2 VivaPayments extension

[![Latest Stable Version](https://poser.pugx.org/tun2u/m2-module-vivapayments/v/stable)](https://packagist.org/packages/tun2u/m2-module-vivapayments)
[![Total Downloads](https://poser.pugx.org/tun2u/m2-module-vivapayments/downloads)](https://packagist.org/packages/tun2u/m2-module-vivapayments)
[![License](https://poser.pugx.org/tun2u/m2-module-vivapayments/license)](https://packagist.org/packages/tun2u/m2-module-vivapayments)

## Features

-   Magento Smart Checkout plugin that allows you to accept payments via Viva Smart Checkout in your Magento store.
-   Supports Magento 2.3.x

## Installing

##### Manual Installation

Install Tun2U VivaPayments extension for Magento 2

-   Download the extension
-   Unzip the file
-   Create a folder {Magento root}/app/code/Tun2U/VivaPayments
-   Copy the content from the unzip folder
-   Run following command

```
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy
php bin/magento setup:di:compile
php bin/magento cache:flush
```

-   Flush cache

##### Using Composer (from Magento Root Dir run)

```
composer require tun2u/m2-module-vivapayments
php bin/magento setup:static-content:deploy
php bin/magento setup:di:compile
php bin/magento cache:flush
```

## Requirements

-   PHP >= 7.0.0

## Compatibility

-   Magento >= 2.0

### Setup

-   From admin:
-   Go to stores > Configuration
-   Find Sales in sidebar
-   Open Viva Wallet Smart Checkout tab
-   Set cofigurations

### Settings

-   <strong>Merchant Id</strong>: Enter the Merchant ID you noted in Step 2: Find Account credentials

-   <strong>API Key</strong>: Enter the API Key you noted in Step 2: Find Account credentials

-   <strong>Source Code: Enter the Source Code of the Source you created in Step 3: Create Payment Source

-   <strong>OrderCode URL</strong>:
    <ol>
    <li>Enter https://www.vivapayments.com/api/orders if you are using a live Viva instance</li>
    <li>Enter https://demo.vivapayments.com/api/orders if you are using a demo Viva instance</li>
    </ol>

-   <strong>Gateway URL</strong>:
    <ol>
    <li>Enter https://www.vivapayments.com/web/newtransaction.aspx if you are using a live Viva instance</li>
    <li>Enter https://demo.vivapayments.com/web/newtransaction.aspx if you are using a demo Viva instance</li>
    </ol>

-   <strong>Transaction URL</strong>:
    <ol>
    <li>Enter https://www.vivapayments.com/api/transactions if you are using a live Viva instance</li>
    <li>Enter https://demo.vivapayments.com/api/transactions if you are using a demo Viva instance</li>
    </ol>
-   <strong>Installments</strong>: This is an optional field, only applicable to Greek merchants – you can set the maximum allowed payment card installments and their corresponding order values
-   <strong>Enable ISV mode</strong>: This field determines whether ISV mode is active or not
-   <strong>ISV Checkout URL</strong>: Enter https://api.vivapayments.com/checkout/v2/isv/orders if you are using a ISV mode. It will replace OrderCode URL field.
-   <strong>ISV Amount</strong>: ISV Amount fee

## Support

If you encounter any problems or bugs, please create an issue on [GitHub](https://github.com/Tun2U/M2-Module-VivaPayments/issues).

## Developer

##### Tun2U Team

-   Website: [https://www.tun2u.com](https://www.tun2u.com)
-   Twitter: [@tun2u](https://twitter.com/tun2u)

## Licence

[GNU General Public License, version 3 (GPLv3)](http://opensource.org/licenses/gpl-3.0)

## Copyright

(c) 2024 Tun2U Team
