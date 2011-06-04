<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Paypal Galette plugin
 *
 * This page can be loaded directly, or via ajax.
 * Via ajax, we do not have a full html page, but only
 * that will be displayed using javascript on another page
 *
 * PHP version 5
 *
 * Copyright © ${year} The Galette Team
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
 * @category
 * @package
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright ${year} The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version   SVN: $$Id$$
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.7dev - ${date}
 */

/** @ignore */
require_once WEB_ROOT . 'classes/contributions_types.class.php';

 /**
 * Preferences for galette
 *
 * @category  Classes
 * @name      Paypal
 * @package   Galette
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright ${year} The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.7dev - ${date}
 */

class Paypal {

    const TABLE = 'types_cotisation_prices';
    const PK = ContributionsTypes::PK;

    private $_prices = array();

    public function __construct()
    {
        $this->load();
        $this->_checkUpdate();
        /*$ct = new ContributionsTypes();
        foreach ( $ct->getCompleteList() as $k=>$v ) {
            $this->_prices[$k] = null;
        }*/
    }

    public function load()
    {
        global $mdb, $log;

        $ct = new ContributionsTypes();
        $this->_prices = $ct->getCompleteList();
        /*foreach ( $ct->getCompleteList() as $k=>$v ) {
            $this->_prices[$k] = null;
        }*/

        $requete = 'SELECT * FROM ' . PREFIX_DB . PAYPAL_PREFIX . self::TABLE;

        $result = $mdb->query($requete);

        if (MDB2::isError($result)) {
            $log->log(
                '[' . get_class($this) . '] Cannot load paypal amounts' .
                '` | ' . $result->getMessage() . '(' . $result->getDebugInfo() . ')',
                PEAR_LOG_WARNING
            );
            return false;
        }

        print_($result->fetchAll());

    }

    public function _checkUpdate()
    {
        global $mdb, $log;
    }

}
?>