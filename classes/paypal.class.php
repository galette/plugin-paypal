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
    const PREFS_TABLE = 'preferences';

    private $_prices = array();
    private $_id = null;
    private $_inactives = array();

    private $_loaded = false;
    private $_error = null;
    private $_amounts_loaded = false;

    /**
    * Default constructor
    */
    public function __construct()
    {
        $this->_loaded = false;
        $this->_error = array();
        $this->_prices = array();
        $this->_inactives = array();
        $this->_id = null;
        $this->_load();
    }

    /**
     * Load preferences form the database and amounts
     *
     * @return void
     */
    private function _load(){
        global $mdb, $log;

        $requete = 'SELECT * FROM ' . PREFIX_DB . PAYPAL_PREFIX . self::PREFS_TABLE;
        $result = $mdb->query($requete);

        if (MDB2::isError($result)) {
            $log->log(
                '[' . get_class($this) . '] Cannot load paypal preferences' .
                '` | ' . $result->getMessage() . '(' . $result->getDebugInfo() . ')',
                PEAR_LOG_ERR
            );
            //consider plugin is not loaded when missing the main preferences (that includes paypal id)
            $this->_loaded = false;
            $this->_error = array(
                'message'   => $result->getMessage(),
                'debug'     => $result->getDebugInfo()
            );
        } else {
            $r = $result->fetchAll();
            foreach ( $r as $row ) {
                switch ( $row->nom_pref ) {
                case 'paypal_id':
                    $this->_id = $row->val_pref;
                    break;
                case 'paypal_inactives':
                    $this->_inactives = explode(',', $row->val_pref);
                    break;
                default:
                    //we've got a preference not intended
                    $log->log(
                        '[' . get_class($this) . '] unknown preference `' .
                        $row->nom_pref . '` in the database.',
                        PEAR_LOG_WARNING
                    );
                }
            }
            $this->_loaded = true;
            $this->_loadAmounts();
        }
    }

    /**
     * Load amounts from database
     *
     * @return void
     */
    private function _loadAmounts()
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
                PEAR_LOG_ERR
            );
            //missing amounts is not a critical error, user can enter the amount manually :)
            $this->_error = array(
                'message'   => $result->getMessage(),
                'debug'     => $result->getDebugInfo()
            );
        } else {
            //check if all types currently exists in paypal table
            if ( $result->numRows() != count($this->_prices) ) {
                $log->log(
                    '[' . get_class($this) . '] There are missing types in ' .
                    'paypal table, Galette will try to create them.',
                    PEAR_LOG_INFO
                );
            }
            if ( $result->numRows() > 0 ) {
                $paypals = $result->fetchAll(MDB2_FETCHMODE_ASSOC);
            } else {
                $log->log(
                    'No paypal type amounts defined in database.',
                    PEAR_LOG_INFO
                );
            }
            $queries = array();
            foreach ( $this->_prices as $k=>$v ) {
                $_found = false;
                if ( $result->numRows() > 0 ) {
                    //for each entry in types, we want to get the associated amount
                    foreach ( $paypals as $paypal ) {
                        if ( $paypal['id_type_cotis'] == $k ) {
                            $_found=true;
                            $this->_prices[$k][] = (double)$paypal['amount'];
                            break;
                        }
                    }
                }
                if ( $_found === false ) {
                    $log->log(
                        'The type `' . $v[0] . '` (' . $k . ') does not exist' .
                        ', Galette will attempt to create it.',
                        PEAR_LOG_INFO
                    );
                    $this->_prices[$k][] = null;
                    $queries[] = array(
                          'id'      => $k,
                        'amount'  => null
                    );
                }
            }
            if ( count($queries) > 0 ) {
                $this->_newEntries($queries);
            }
            //amounts should be loaded here
            $this->_amounts_loaded = true;
        }
    }

    /**
     * Store values in the database
     */
    public function store(){
        global $mdb, $log;

        $requete = 'UPDATE ' . PREFIX_DB . PAYPAL_PREFIX . self::PREFS_TABLE . ' SET nom_pref=:nom_pref, val_pref=:val_pref WHERE nom_pref=:nom_pref';
        $stmt = $mdb->prepare(
            $requete,
            array('text', 'text'),
            MDB2_PREPARE_MANIP
        );

        $query = array();
        $query[] = array(
            'nom_pref'  => 'paypal_id',
            'val_pref'  => $this->_id
        );
        $query[] = array(
            'nom_pref'  => 'paypal_inactives',
            'val_pref'  => implode($this->_inactives, ',') //check
        );

        $mdb->getDb()->loadModule('Extended', null, false);
        $mdb->getDb()->extended->executeMultiple($stmt, $query);

        if (MDB2::isError($stmt)) {
            $log->log(
                '[' . get_class($this) . '] Cannot store paypal preferences' .
                '` | ' . $stmt->getMessage() . '(' . $stmt->getDebugInfo() . ')',
                PEAR_LOG_ERR
            );
            return false;
        } else {
            $log->log(
                '[' . get_class($this) . '] Paypal preferences were sucessfully stored',
                PEAR_LOG_INFO
            );
        }

        $stmt->free();
        $this->storeAmounts();
        return true;
    }

    public function storeAmounts()
    {
        global $mdb, $log;

        $requete = "UPDATE " . PREFIX_DB . PAYPAL_PREFIX . self::TABLE . ' SET amount=:amount WHERE ' . self::PK . '=:id';

        $stmt = $mdb->prepare(
            $requete,
            array('double', 'int'),
            MDB2_PREPARE_MANIP
        );

        $query = array();
        foreach ( $this->_prices as $k=>$v ) {
            $query[] = array(
                'amount'    => $v[2],
                'id'        => $k
            );
        }

        $mdb->getDb()->loadModule('Extended', null, false);
        $mdb->getDb()->extended->executeMultiple($stmt, $query);

        if (MDB2::isError($stmt)) {
            $log->log(
                '[' . get_class($this) . '] Cannot store paypal amounts' .
                '` | ' . $stmt->getMessage() . '(' . $stmt->getDebugInfo() . ')',
                PEAR_LOG_ERR
            );
            return false;
        } else {
            $log->log(
                '[' . get_class($this) . '] Paypal amounts were sucessfully stored',
                PEAR_LOG_INFO
            );
            return true;
        }

        $stmt->free();
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
     * Get Paypal identifier
     *
     * @return string
     */
    public function getId()
    {
        return $this->_id;
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

    /**
     * Is the plugin loaded?
     *
     * @return boolean
     */
    public function isLoaded()
    {
        return $this->_loaded;
    }

    /**
     * Retrieve informations on error
     *
     * @return array
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * Are amounts loaded?
     *
     * @return boolean
     */
    public function areAmountsLoaded()
    {
        return $this->_amounts_loaded;
    }

    /**
     * Set paypal identifier
     *
     * @param string $id
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * Set new prices
     *
     * @param array $ids
     * @param array $amounts
     */
    public function setPrices($ids, $amounts)
    {
        foreach ( $ids as $k=>$id) {
            $this->_prices[$id][2] = $amounts[$k];
        }
    }

    /**
     * Check if the specified contribution is active
     * @param int $id
     */
    public function isInactive($id)
    {
        return in_array($id, $this->_inactives);
    }

    /**
     * Set inactives types
     *
     * @param array $inactives
     */
    public function setInactives($inactives) {
        $this->_inactives = $inactives;
    }

}
?>