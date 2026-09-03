---
title: Documentación
description: Paypal integration
---

> **Warning**
> 
> PayPal is discontinuing its legacy Website Payments Standard (WPS)
> integration, with full transaction processing termination scheduled for
> January 2027. This plugin is **not compatible** with any other integration.

Este complemento proporciona:

* posibilidad de asociar un importe a un tipo de contribución,
* crear un formulario de pago en Paypal,
* un historial,
* almacenamiento automático de las transacciones una vez validadas.

![Paypal plugin preferences](images/preferences.png)

![Paypal payment form](images/form.png)

![Paypal payment form (public)](images/public_form.png)

> **Warning**
> 
> Debido a la forma en que se gestionan los pagos de Paypal, especialmente la
> confirmación del pago, su instancia debe ser de acceso público.

## Instalación

Antes que todo, descargue el complemento:

* [Get latest Paypal
  plugin!](https://github.com/galette-plugins/plugin-paypal/releases/latest)
* [Get Paypal plugin nightly
  build!](https://github.com/galette-plugins/plugin-paypal/releases/tag/nightly)

Extraer el archivo descargado en el directorio de Galette `plugins`. Por
ejemplo, bajo linux (reemplazar `{url}` y `{version}` con valores correctos):

```bash
$ cd /var/www/html/galette/plugins
$ wget {url}
$ tar xjvf galette-plugin-paypal-{version}.tar.bz2
```

## Inicio de la base de datos

Para funcionar, este plugin requiere varias tablas en la base de datos. Ver
[Interfaz de gestión de plugins
Galette](https://doc.galette.eu/en/master/plugins/index.html#plugins-managment).

Terminado; el plugin PayPal está instalado :)

## Ajustes del plugin

Una vez instalado el plugin, se añade un grupo de `Paypal` al menú, con algunas
entradas nuevas:

* `Formulario de pago`: el formulario de pago en sí, al que se puede acceder
  como página pública,
* `Preferencias`: ajustes del plugin, accesibles para administradores y miembros
  del personal.

Para funcionar correctamente, es necesario rellenar un valor muy importante: el
código de tu cuenta PayPal. Puede utilizar la dirección de correo electrónico
asociada a su cuenta de Paypal (pero tendrá que cambiarla en Galette si cambia
en Paypal) o su identificador de comerciante. Para encontrar su código de
usuario, inicie sesión en PayPal y lo encontrará en las preferencias de su
cuenta. Cambiar el identificador solo está permitido para administradores.

La pantalla de los ajustes también permite editar el importe relacionado con los
tipos de contribuciones, y ocultar algunos tipos.

After that, any user can choose the contribution type, adjust the amount and pay
from his Paypal account. If the user is a logged in member, and if the
contribution type is a membership extension, its membership will be recalculated
when the payment will be confirmed.
