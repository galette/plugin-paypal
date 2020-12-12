<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Paypal routes
 *
 * PHP version 5
 *
 * Copyright © 2016-2020 The Galette Team
 *
 * This file is part of Galette (http://galette.tuxfamily.org).
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
 *
 * @category  Plugins
 * @package   GalettePaypal
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2016-2020 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version   SVN: $Id$
 * @link      http://galette.tuxfamily.org
 * @since     0.9dev 2016-11-20
 */

use GalettePaypal\Controllers\PaypalController;

//Constants and classes from plugin
require_once $module['root'] . '/_config.inc.php';

$this->get(
    '/preferences',
    [PaypalController::class, 'preferences']
)->setName('paypal_preferences')->add($authenticate);

$this->post(
    '/preferences',
    [PaypalController::class, 'storePreferences']
)->setName('store_paypal_preferences')->add($authenticate);

$this->get(
    '/form',
    [PaypalController::class, 'form']
)->setName('paypal_form');

$this->get(
    '/cancel',
    [PaypalController::class, 'cancel']
)->setName('paypal_cancelled');

$this->post(
    '/success',
    [PaypalController::class, 'success']
)->setName('paypal_success');

$this->post(
    '/notify',
    [PaypalController::class, 'notify']
)->setName('paypal_notify');

$this->get(
    '/logs[/{option:order|reset|page}/{value}]',
    [PaypalController::class, 'logs']
)->setName('paypal_history')->add($authenticate);

//history filtering
$this->post(
    '/history/filter',
    [PaypalController::class, 'filter']
)->setName('filter_paypal_history')->add($authenticate);
