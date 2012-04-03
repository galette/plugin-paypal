<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Paypal form
 *
 * This page can be loaded directly, or via ajax.
 * Via ajax, we do not have a full html page, but only
 * that will be displayed using javascript on another page
 *
 * PHP version 5
 *
 * Copyright © 2011-2012 The Galette Team
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
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2011-2012 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version   SVN: $Id$
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.7dev - 2011-06-08
 */

use Galette\Entity\Contribution as Contribution;

$base_path = '../../';
require_once $base_path . 'includes/galette.inc.php';

//Constants and classes from plugin
require_once '_config.inc.php';

//if we've received some informations from paypal website, we can proceed
require_once 'classes/paypal-history.class.php';
if ( isset($_POST) && isset($_POST['mc_gross'])
    && isset($_POST['item_number'])
) {
    $ph = new PaypalHistory();
    $ph->add($_POST);

    $log->log(
        'An entry has been added in paypal history',
        PEAR_LOG_INFO
    );

    $s = null;
    foreach ( $_POST as $k=>$v ) {
        if ( $s != null ) {
            $s .= ' | ';
        }
        $s .= $k . '=' . $v;
    }

    $log->log(
        $s,
        PEAR_LOG_DEBUG
    );

    //we'll now try to add the relevant cotisation
    if ( isset($_POST['custom'])
        && is_numeric($_POST['custom'])
        && $_POST['payment_status'] == 'Completed'
    ) {
        if ( $_POST['payment_status'] == 'Completed' ) {
            /**
             * We will use the following parameters:
             * - mc_gross: the amount
             * - custom: member id
             * - item_number: contribution type id
             */
            $args = array(
                    'type'  => $_POST['item_number'],
                    'adh'   => $_POST['custom']
            );
            if ( $preferences->pref_membership_ext != '' ) {
                $args['ext'] = $preferences->pref_membership_ext;
            }
            $contrib = new Contribution($args);
            $contrib->amount = $_POST['mc_gross'];

            //all goes well, we can proceed
            if ( $contrib->isCotis() ) {
                // Check that membership fees does not overlap
                $overlap = $contrib->checkOverlap();
                if ( $overlap !== true ) {
                    if ( $overlap === false ) {
                        $log->log(
                            'An eror occured checking overlaping fees :(',
                            PEAR_LOG_ERR
                        );
                    } else {
                        //method directly return erro message
                        $log->log(
                            'Error while calculating overlaping fees from paypal payment: ' . $overlap,
                            PEAR_LOG_ERR
                        );
                    }
                }
            }

            $store = $contrib->store();
            if ( $store === true ) {
                //contribution has been stored :)
                $log->log(
                    'Paypal payment has been successfully registered as a contribution',
                    PEAR_LOG_INFO
                );
            } else {
                //something went wrong :'(
                $log->log(
                    'An error occured while storing a new contribution from Paypal payment',
                    PEAR_LOG_ERR
                );
            }
        } else {
            $log->log(
                'A paypal payment notification has been received, but is not completed!',
                PEAR_LOG_WARNING
            );
        }
    }
} else {
    $log->log(
        'Paypal notify URL call without required arguments!',
        PEAR_LOG_WARNING
    );
}
?>
