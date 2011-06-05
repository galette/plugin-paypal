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
 * Copyright © 2011 The Galette Team
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
 * @category  Classes
 * @package   GalettePaypal
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2011 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version   SVN: $Id$
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.7dev - 2011-06-03
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
 * @copyright 2011 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.7dev - 2011-06-03
 */

class Paypal
{

    const TABLE = 'types_cotisation_prices';
    const PK = ContributionsTypes::PK;

    private $_prices = array();

    /**
    * Default constructor
    */
    public function __construct()
    {
        $this->load();
    }

    /**
     * Load amounts from database
     *
     * @return void
     */
    public function load()
    {
        global $mdb, $log;

        $ct = new ContributionsTypes();
        $this->_prices = $ct->getCompleteList();

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

        if ( $result->numRows() == 0 ) {
            $log->log('No contribution type defined in database.', PEAR_LOG_INFO);
        } else {
            //check if all types currently exists in paypal table
            if ( $result->numRows() != count($this->_prices) ) {
                $log->log(
                    '[' . get_class($this) . '] There are missing types in ' .
                    'paypal table, Galette will try to create them.',
                    PEAR_LOG_INFO
                );
            }
            $paypals = $result->fetchAll(MDB2_FETCHMODE_ASSOC);
            $queries = array();
            foreach ( $this->_prices as $k=>$v ) {
                $_found = false;
                //for each entry in types, we want to get the associated amount
                foreach ( $paypals as $paypal ) {
                    if ( $paypal['id_type_cotis'] == $k ) {
                        $_found=true;
                        $this->_prices[$k][] = (double)$paypal['amount'];
                        break;
                    }
                }
                if ( $_found === false ) {
                    $log->log(
                        'The type `' . $v[0] . '` (' . $k . ') does not exist' .
                        ', Galette will attempt to create it.',
                        PEAR_LOG_INFO
                    );
                    $this->_prices[$k][] = 0;
                    $queries[] = array(
                          'id'      => $k,
                        'amount'  => (double)0
                    );
                }
            }
            if ( count($queries) > 0 ) {
                $this->_newEntries($queries);
            }
        }
    }

    /**
    * Add missing types in paypal table
    *
    * @param Array $queries Array of items to insert
    *
    * @return true on success, false on failure
    */
    private function _newEntries($queries)
    {
        global $mdb, $log;

        $stmt = $mdb->prepare(
            'INSERT INTO ' . PREFIX_DB . PAYPAL_PREFIX . self::TABLE . ' (' .
            self::PK . ', amount) VALUES (:id, :amount)',
            array('integer', 'double'),
            MDB2_PREPARE_MANIP
        );

        $mdb->getDb()->loadModule('Extended', null, false);
        $mdb->getDb()->extended->executeMultiple($stmt, $queries);

        if ( MDB2::isError($stmt) ) {
            $this->_error = $stmt;
            $log->log(
                'Unable to store missing types in paypal table.' .
                $stmt->getMessage() . '(' . $stmt->getDebugInfo() . ')',
                PEAR_LOG_WARNING
            );
            return false;
        }

        $stmt->free();
        return true;
    }

    /**
     * Get loaded amounts
     *
     * @return array
     */
    public function getAmounts()
    {
        return $this->_prices;
    }

}
?>