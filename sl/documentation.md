---
title: Dokumentacija
description: Paypal integration
---

> **Warning**
> 
> PayPal is discontinuing its legacy Website Payments Standard (WPS)
> integration, with full transaction processing termination scheduled for
> January 2027. This plugin is **not compatible** with any other integration.

Ta vtičnik ponuja:

* možnost povezave zneska z vrsto prispevka,
* ustvarite obrazec za plačilo prek PayPala,
* zgodovina,
* samodejno shranjevanje transakcij po potrditvi.

![Paypal plugin preferences](images/preferences.png)

![Paypal payment form](images/form.png)

![Paypal payment form (public)](images/public_form.png)

> **Warning**
> 
> Zaradi načina obdelave plačil prek PayPala, zlasti potrditve plačila, mora
> biti vaš primerek javno dostopen.

## Namestitev

Najprej prenesite vtičnik:

* [Get latest Paypal
  plugin!](https://github.com/galette-plugins/plugin-paypal/releases/latest)
* [Get Paypal plugin nightly
  build!](https://github.com/galette-plugins/plugin-paypal/releases/tag/nightly)

Razširite prenesen arhiv v imenik Galette `plugins`. Na primer v Linuxu
(zamenjajte `{url}` in `{version}` s pravilnimi vrednostmi):

```bash
$ cd /var/www/html/galette/plugins
$ wget {url}
$ tar xjvf galette-plugin-paypal-{version}.tar.bz2
```

## Inicializacija baze podatkov

Za delovanje ta vtičnik potrebuje več tabel v bazi podatkov. Glejte [Vmesnik za
upravljanje vtičnikov
Galette](https://doc.galette.eu/en/master/plugins/index.html#plugins-managment).

In to je končano; vtičnik Paypal je nameščen :)

## Konfiguracija vtičnika

Ko je vtičnik nameščen, se v meni doda skupina »Paypal« z nekaj novimi vnosi:

* „Plačilni obrazec“: sam plačilni obrazec, ki je dostopen kot javna stran,
* `Nastavitve`: nastavitve vtičnikov, dostopne skrbnikom in članom osebja.

Za pravilno delovanje morate vnesti zelo pomembno vrednost: kodo svojega Paypal
računa. Uporabite lahko e-poštni naslov, povezan z vašim Paypal računom (vendar
ga boste morali spremeniti v Galette, če se spremeni v Paypalu), ali pa svojo
identifikacijsko številko trgovca. Če želite najti svojo identifikacijsko
številko trgovca, se prijavite v Paypal in jo boste našli v nastavitvah računa.
Spreminjanje identifikacijske številke je dovoljeno samo skrbnikom.

Zaslon z nastavitvami omogoča tudi urejanje zneskov, povezanih z vrstami
prispevkov, in skrivanje nekaterih vrst.

After that, any user can choose the contribution type, adjust the amount and pay
from his Paypal account. If the user is a logged in member, and if the
contribution type is a membership extension, its membership will be recalculated
when the payment will be confirmed.
