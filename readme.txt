=== BoaCompra Payment for WooCommerce ===
Contributors: ipag
Tags: woocommerce, boacompra, payment
Requires at least: 4.0
Requires PHP: 5.5
Tested up to: 5.2
WC requires at least: 3.0
WC tested up to: 3.7
Stable tag: 1.0.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

BoaCompra Payment plugin is a gateway plugin that allows you to take online payments directly on your site.

* WooCommerce 3.0+.
* Works with PHP 5.4.x until 7.x.x;
* Required lib cURL.
* Required SSL TLS 1.2;

== Installation ==

* Install using the WordPress's "Add New Plugin" tool. In the WordPress plugin area, enable BoaCompra.

= 1 - SIGNUP =

MerchantID & SecretKey

Getting your MerchantID & SecretKey are the first steps to create a functional integration. After the registration and the formalization of the contract with BoaCompra, you will receive a SecretKey which will be used to reference your account and validate the processed payments.
With the data in hand just change the BoaCompra Environment to Production and then copy and paste your MerchantID and your SecretKey in the fields indicated below.

WooCommerce -> Settings -> Payments -> BoaCompra Payment (Manage) 

= 2 - PAYMENT OPTIONS  =

The module offers 3 payment options via Direct Checkout [BRAZIL]:

* Credit Card
* Boleto
* E-Wallet

For the following countries, we offer payment options via Redirect Checkout:

* ARS - Peso (Argentina)
* BRL - Real (Brazil)
* CLP - Peso (Chile)
* COP - Peso (Colombia)
* CRC - Colon (Costa Rica)
* EUR - Euro
* MXN - Peso (Mexico)
* PEN - Nuevos Soles (Peru)
* TRY - Liras (Turkey)
* USD - Dolar
* UYU - Peso (Uruguai)

With the following payment methods: [click here](http://developers.boacompra.com/available-payments/#by-country/).

= 3 - INSTALLMENT =

Set the maximum installments accepted by the store, select between 2 and 12 installments.

* Note:
The interest rate may vary depending on the store's billing ceiling or on your contractual negotiation with BoaCompra.

= 4 - BOLETO  =
Boleto Checkout Message, define the message which will be displayed to the customer when paying via Boleto, for example: "After the order confirmation, remember to pay off the Boleto as soon as possible."

= 5 - Order Status =

To facilitate the order management, we offer the option of Status mapping. The available statuses are:

* Started Status (The order will automatically change to this status when the transaction is marked as Payment Pending on BoaCompra).
* Approved Status (The order will automatically change to this status in case of approval in BoaCompra).
* Cancelled Status (The order will automatically change to this status when the transaction is cancelled in BoaCompra).
* Awaiting Status (The order will automatically change to this status when the transaction is in on hold status in BoaCompra).

Attention:

There are 02 ways to cancel an order:
a) Access the desired order in the WooCommerce panel and click on Refund button. Then set the Total Refunded and click Refund R$ X, XX for Payment via BoaCompra" button and the module will transmit the request to BoaCompra in real time.

b) Instantly in your BoaCompra account under Transactions. To cancel the order BoaCompra will transmit to your store the cancellation request [click here](http://billing-partner.boacompra.com/transactions.php/).

= 6 - ENABLE LOGS =

Enable the option for the module to record everything sent and received between your store and BoaCompra.

To view the logs, click the Logs link or go to "WooCommerce> System Status> Logs> select log boacompra-payment-xxxxx.log" and click "View" to review details of what has been sent and received between your store and BoaCompra.

= Suggestion =

BoaCompra recommends use of the plugin WooCommerce Extra Checkout Fields for Brazil

* [WooCommerce Extra Checkout Fields for Brazil](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/).

== Screenshots ==

1. Setting - Activation, MerchantID & SecretKey
2. Setting - Country Payment Checkout (First Part) 
3. Setting - Setting - Country Payment Checkout (Second Part) 
4. Direct Checkout View 
5. Boleto Checkout View using Direct Checkout
6. e-Wallet Checkout View using Direct Checkout


== Changelog ==

= 1.0.0 =
* First release.
= 1.0.3 =
* Logging feature implemented.


== Upgrade Notice ==

= 1.0.0 =
* First release.
= 1.0.3 =
* Logging feature implemented.