<?php

/**
 * Copyright © 2003-2024 The Galette Team
 *
 * This file is part of Galette (https://galette.eu).
 *
 * Galette is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Galette is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Galette. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

use GalettePaypal\Controllers\PaypalController;

//Constants and classes from plugin
require_once $module['root'] . '/_config.inc.php';

$app->get(
    '/preferences',
    [PaypalController::class, 'preferences']
)->setName('paypal_preferences')->add($authenticate);

$app->post(
    '/preferences',
    [PaypalController::class, 'storePreferences']
)->setName('store_paypal_preferences')->add($authenticate);

$app->get(
    '/form',
    [PaypalController::class, 'form']
)->setName('paypal_form');

$app->get(
    '/cancel',
    [PaypalController::class, 'cancel']
)->setName('paypal_cancelled');

$app->post(
    '/success',
    [PaypalController::class, 'success']
)->setName('paypal_success');

$app->post(
    '/notify',
    [PaypalController::class, 'notify']
)->setName('paypal_notify');

$app->get(
    '/logs[/{option:order|reset|page}/{value}]',
    [PaypalController::class, 'logs']
)->setName('paypal_history')->add($authenticate);

//history filtering
$app->post(
    '/history/filter',
    [PaypalController::class, 'filter']
)->setName('filter_paypal_history')->add($authenticate);
