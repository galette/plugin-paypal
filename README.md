# Galette Paypal plugin

> [!IMPORTANT]
> Since version 3.0.0, this plugin relies on **PayPal Standard Checkout** (Orders v2 REST API and
> webhooks). The legacy Website Payments Standard (WPS) integration, which PayPal stops processing
> in January 2027, has been removed.
>
> Upgrading from an earlier version requires a new configuration: the PayPal account email is no
> longer enough. From your [PayPal developer dashboard](https://developer.paypal.com/dashboard/),
> create a REST application and report its **client ID** and **secret** in the plugin settings, then
> declare a **webhook** on the URL displayed on that same page, subscribed to the
> `CHECKOUT.ORDER.APPROVED` and `PAYMENT.CAPTURE.COMPLETED` events, and report its identifier as
> well. Payments are refused until those are filled in.


[![GitHub license](https://img.shields.io/github/license/galette/galette.svg)](https://github.com/galette/plugin-paypal/blob/master/COPYING)

### English

A [Galette](https://galette.eu) plugin to handle paypal payments.

* website: https://galette.eu - https://doc.galette.eu/en/master/plugins/paypal.html
* bugs and features: http://bugs.galette.eu/projects/galette-plugin-paypa
* mailing lists:
  * users: https://listengine.tuxfamily.org/lists.galette.eu/users/
  * developpers: https://listengine.tuxfamily.org/lists.galette.eu/devel/
* documentation: https://doc.galette.eu/en/master/plugins/paypal.html

To use Galette Paypal plugin, you'll need a reliable Galette version, and of course the plugin itself by either:

* download latest stable version available from [Galette Paypal plugin page](https://doc.galette.eu/en/master/plugins/paypal.html)
* use [Galette Paypal plugin soure code from repository](https://doc.galette.eu/en/develop/development/git.html) (make sure you install third party dependencies), this solution requires some technical skills

### Français

Un plugin [Galette](https://galette.eu) pour gérer paiments de cotisation et de dons via Paypal.

* site web : https://galette.eu - https://doc.galette.eu/fr/master/plugins/paypal.html
* bogues et fonctionnalités : http://bugs.galette.eu/projects/galette-plugin-paypa
* liste de diffusion :
  * utilisateurs : https://listengine.tuxfamily.org/lists.galette.eu/users/
  * développeurs : https://listengine.tuxfamily.org/lists.galette.eu/devel/
* documentation : https://doc.galette.eu/fr/master/plugins/paypal.html

Pour utiliser le plugin Paypal pour Galette, vous aurez besoin d'une version adéquate de Galette, ainsi que du plugin lui même :

* télécharger la dernière version stable depuis la [page du  plugin Paypal pour Galette](https://doc.galette.eu/en/master/plugins/paypal.html)
* utiliser [le code source du plugin Paypal pour Galette depuis le dépôt](https://doc.galette.eu/en/develop/development/git.html) (assurez-vous d'installer les biliothèques tierces), cette solution requiert quelques compétences techniques
