<?php

/**
 * This file is part of Galette Paypal plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2011-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

use Galette\Middleware\Authenticate;
use GalettePaypal\Controllers\PaypalController;

//Constants and classes from plugin
require_once $module['root'] . '/_config.inc.php';

$app->get(
    '/preferences',
    [PaypalController::class, 'preferences']
)->setName('paypal_preferences')->add(Authenticate::class);

$app->post(
    '/preferences',
    [PaypalController::class, 'storePreferences']
)->setName('store_paypal_preferences')->add(Authenticate::class);

$app->get(
    '/form',
    [PaypalController::class, 'form']
)->setName('paypal_form');

$app->post(
    '/form',
    [PaypalController::class, 'formCheckout']
)->setName('paypal_formCheckout');

//Paypal sends the payer back here once the payment is approved
$app->get(
    '/return',
    [PaypalController::class, 'returnUrl']
)->setName('paypal_return');

$app->get(
    '/success',
    [PaypalController::class, 'success']
)->setName('paypal_success');

$app->get(
    '/cancel',
    [PaypalController::class, 'cancel']
)->setName('paypal_cancelled');

//server to server notification, used when the payer never comes back
$app->post(
    '/webhook',
    [PaypalController::class, 'webhook']
)->setName('paypal_webhook');

$app->get(
    '/logs[/{option:order|reset|page}/{value}]',
    [PaypalController::class, 'logs']
)->setName('paypal_history')->add(Authenticate::class);

//history filtering
$app->post(
    '/history/filter',
    [PaypalController::class, 'filter']
)->setName('filter_paypal_history')->add(Authenticate::class);
