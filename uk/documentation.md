---
title: Документація
description: Paypal integration
---

> **Warning**
> 
> PayPal is discontinuing its legacy Website Payments Standard (WPS)
> integration, with full transaction processing termination scheduled for
> January 2027. This plugin is **not compatible** with any other integration.

Це розширення надає:

* можливість пов'язати суму з типом внеску,
* створити форму оплати Paypal,
* історія,
* автоматичне зберігання переказів після перевірки.

![Paypal plugin preferences](images/preferences.png)

![Paypal payment form](images/form.png)

![Paypal payment form (public)](images/public_form.png)

> **Warning**
> 
> Через спосіб обробки платежів Paypal, особливо підтвердження платежу, ваш
> зразок повинен бути прилюдним.

## Встановлення

Перш за все, завантажте плагін:

* [Get latest Paypal
  plugin!](https://github.com/galette-plugins/plugin-paypal/releases/latest)
* [Get Paypal plugin nightly
  build!](https://github.com/galette-plugins/plugin-paypal/releases/tag/nightly)

Розпакуйте завантажений архів у каталог Galette `plugins`. Наприклад, під Linux
(замінивши `{url}` і `{version}` на правильні значення):

```bash
$ cd /var/www/html/galette/plugins
$ wget {url}
$ tar xjvf galette-plugin-paypal-{version}.tar.bz2
```

## Ініціалізація бази даних

Для роботи цього плагіна потрібно кілька таблиць у базі даних. Перегляньте
[Інтерфейс керування плагінами
Galette](https://doc.galette.eu/en/master/plugins/index.html#plugins-managment).

Усе завершено. Розширення "PayPal" установлено :)

## Конфігурація розширення

Після встановлення розширення до меню додається група `Paypal` з деякими новими
записами:

* `Форма оплати`: сама форма оплати, яка доступна як загальнодоступна сторінка,
* `Налаштування`: налаштування розширення, доступні для адміністраторів та
  співробітників.

Для правильної роботи вам необхідно заповнити дуже важливе значення: код вашого
Paypal-рахунку. Ви можете використовувати або адресу електронної пошти,
пов'язаний з вашим обліковим записом Paypal (але ви повинні будете змінити його
в Galette, якщо він зміниться на Paypal) або ваш ідентифікатор торговця. Щоб
знайти ідентифікатор продавця, увійдіть в систему Paypal, і Ви знайдете його в
налаштуваннях вашого облікового запису. Зміна ідентифікатора дозволено тільки
адміністраторам.

Екран налаштувань також дозволяє редагувати суму, пов’язану з видами внесків, і
приховувати деякі види.

After that, any user can choose the contribution type, adjust the amount and pay
from his Paypal account. If the user is a logged in member, and if the
contribution type is a membership extension, its membership will be recalculated
when the payment will be confirmed.
