---
title: Documentação
description: Paypal integration
---

> **Warning**
> 
> PayPal is discontinuing its legacy Website Payments Standard (WPS)
> integration, with full transaction processing termination scheduled for
> January 2027. This plugin is **not compatible** with any other integration.

Este plugin fornece:

* Possibilidade de associar um valor a um tipo de contribuição,
* Criar um formulário de pagamento do PayPal,
* uma história,
* Armazenamento automático de transações após a validação.

![Paypal plugin preferences](images/preferences.png)

![Paypal payment form](images/form.png)

![Paypal payment form (public)](images/public_form.png)

> **Warning**
> 
> Devido à forma como os pagamentos do PayPal são processados, especialmente a
> confirmação de pagamento, sua instância deve ser acessível publicamente.

## Instalação

Primeiramente, baixe o plugin:

* [Get latest Paypal
  plugin!](https://github.com/galette-plugins/plugin-paypal/releases/latest)
* [Get Paypal plugin nightly
  build!](https://github.com/galette-plugins/plugin-paypal/releases/tag/nightly)

Extraia o arquivo baixado no diretório `plugins` do Galette. Por exemplo, no
Linux (substituindo `{url}` e `{version}` pelos valores corretos):

```bash
$ cd /var/www/html/galette/plugins
$ wget {url}
$ tar xjvf galette-plugin-paypal-{version}.tar.bz2
```

## Inicialização do banco de dados

Para funcionar, este plugin requer várias tabelas no banco de dados. Consulte
[Interface de gerenciamento de plugins do
Galette](https://doc.galette.eu/en/master/plugins/index.html#plugins-managment).

E está concluído; o plugin do PayPal está instalado :)

## Configuração do plugin

Após a instalação do plugin, um grupo `Paypal` é adicionado ao menu, com algumas
novas entradas:

* `Formulário de pagamento`: o próprio formulário de pagamento, que pode ser
  acessado como uma página pública,
* `Preferências`: preferências do plugin, acessíveis para administradores e
  membros da equipe.

Para funcionar corretamente, você precisa preencher um valor muito importante: o
código da sua conta PayPal. Você pode usar o endereço de e-mail associado à sua
conta PayPal (mas precisará alterá-lo no Galette caso ele mude no PayPal) ou o
seu identificador de comerciante. Para encontrar o seu identificador de
comerciante, faça login no PayPal e você o encontrará nas preferências da sua
conta. A alteração do identificador só é permitida para administradores.

A tela de preferências também permite editar os valores relacionados aos tipos
de contribuição e ocultar alguns tipos.

After that, any user can choose the contribution type, adjust the amount and pay
from his Paypal account. If the user is a logged in member, and if the
contribution type is a membership extension, its membership will be recalculated
when the payment will be confirmed.
