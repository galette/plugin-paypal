---
title: Documentazione
description: Paypal integration
---

> **Warning**
> 
> PayPal is discontinuing its legacy Website Payments Standard (WPS)
> integration, with full transaction processing termination scheduled for
> January 2027. This plugin is **not compatible** with any other integration.

Questo componente aggiuntivo fornisce:

* possibilità di associare un ammontare ad un tipo di contribuzione,
* creare un modulo di pagamento Paypal,
* una cronologia,
* salvataggio delle transazioni automatico una volta convalidato.

![Paypal plugin preferences](images/preferences.png)

![Paypal payment form](images/form.png)

![Paypal payment form (public)](images/public_form.png)

> **Warning**
> 
> Per il modo in cui i pagamenti Paypal sono gestiti, specialmente la conferma
> di pagamento, la tua istanza deve essere accessibile pubblicamente.

## Installazione

Prima di tutto, scaricare il plugin:

* [Get latest Paypal
  plugin!](https://github.com/galette-plugins/plugin-paypal/releases/latest)
* [Get Paypal plugin nightly
  build!](https://github.com/galette-plugins/plugin-paypal/releases/tag/nightly)

Estrarre l'archivio scaricato nella directory `plugins` di Galette. Ad esempio,
su Linux (sostituendo `{url}` e `{version}` con i valori corretti):

```bash
$ cd /var/www/html/galette/plugins
$ wget {url}
$ tar xjvf galette-plugin-paypal-{version}.tar.bz2
```

## Inizializzazione database

Per funzionare, questo plugin richiede diverse tabelle nel database. Vedere
[Interfaccia di gestione dei plugin
Galette](https://doc.galette.eu/en/master/plugins/index.html#plugins-managment).

Completato; il componente aggiuntivo Paypal è installato :)

## Configurazione componente aggiuntivo

Una volta che il componente aggiuntivo è stato installato, verrà aggiunto un
gruppo `Paypal` al menu, con alcune nuove voci:

* `Forma di pagamento`: la forma di pagamento, accessibile da una pagina
  pubblica,
* `Preferenze`: le preferenze relative ai componenti aggiuntivi, accessibile
  agli amministratori e membri dello staff.

Per poter funzionare correttamente, devi inserire un dato molto importante: il
tuo codice account Paypal. Puoi usare l'indirizzo email associato all'account
Paypal (ma dovrai cambiarlo in Galette se lo cambi su Paypal) o il tuo
identificatore commerciante. Per trovare il tuo identificatore, accedi a Paypal
e lo troverai nelle preferenze dell'account. Solo gli amministratori possono
cambiare l'identificatore.

Nelle preferenze è possibile modificare gli ammontare delle contribuzioni, e
nasconderne alcuni tipologie.

After that, any user can choose the contribution type, adjust the amount and pay
from his Paypal account. If the user is a logged in member, and if the
contribution type is a membership extension, its membership will be recalculated
when the payment will be confirmed.
