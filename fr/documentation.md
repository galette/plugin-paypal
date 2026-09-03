---
title: Documentation
description: Paypal integration
---

> **Warning**
> 
> PayPal met fin à son intégration historique de paiement (WPS), la fermeture
> complète est prévue pour janvier 2027. Ce plugin est **non compatible** avec
> toute autre intégration.

Ce plugin fournit :

* la possibilité d'associer un montant à un type de contribution,
* la création d'un formulaire de paiement,
* un historique,
* le stockage des transactions une fois validées.

![Paypal plugin preferences](images/preferences.png)

![Paypal payment form](images/form.png)

![Paypal payment form (public)](images/public_form.png)

> **Warning**
> 
> En raison de la façon dont les paiements Paypal sont gérés, notamment la
> confirmation de paiement, votre instance devra être accessible publiquement.

## Installation

Tout d'abord, téléchargez le plugin :

* [Get latest Paypal
  plugin!](https://github.com/galette-plugins/plugin-paypal/releases/latest)
* [Get Paypal plugin nightly
  build!](https://github.com/galette-plugins/plugin-paypal/releases/tag/nightly)

Extrayez l'archive téléchargée dans le dossier `plugins` de Galette. Par
exemple, sous linux (en remplaçant `{url}` et `{version}` par les valeurs
adéquates) :

```bash
$ cd /var/www/html/galette/plugins
$ wget {url}
$ tar xjvf galette-plugin-paypal-{version}.tar.bz2
```

## Initialisation de la base de données

Pour fonctionner, ce plugin requiert des tables dans la base de données.
Référez-vous [à l'interface de gestion des plugins de
Galette](https://doc.galette.eu/en/master/plugins/index.html#plugins-managment).

Et c'est terminé, le plugin Paypal est installé :)

## Configuration du plugin

Une fois le plugin installé, un groupe `Paypal` est ajouté au menu, avec
quelques nouvelles entrées :

* `Formulaire de paiement` : le formulaire de paiement lui même, qui est
  accessible en tant que page publique,
* `Préférences` : les préférences du plugin , accessible aux administrateurs et
  membres du bureau.

Pour que tout fonctionne correctement, vous devrez renseigner une valeur très
importante : votre code de compte Paypal. Vous pouvez soit utiliser l'adresse de
courriel associée à votre compte Paypal (mais vous devriez la changer dans
Galette aussi si elle est modifiée chez Paypal) soit votre identifiant marchand.
Pour trouver cet identifiant, connectez vous à Paypal et vous le trouverez dans
les préférences de votre compte. La modification de l'identifiant est accessible
aux administrateurs seulement.

L'écran des préférences permet également d'associer des montant aux types de
contributions, et de masquer certains types.

Après cela, tout utilisateur pourra choisir un type de contribution, ajuster le
montant et payer depuis son compte Paypal. Si l'utilisateur était connecté à
Galette, et que le type de contribution choisi amène une extension de
l'adhésion, son adhésion sera recalculée lorsque le paiement sera confirmé.
