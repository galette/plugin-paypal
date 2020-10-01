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
 * Copyright © 2011-2014 The Galette Team
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
 * @copyright 2011-2014 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version   SVN: $Id$
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.7dev - 2011-06-03
 */

namespace GalettePaypal;

use Analog\Analog;
use Galette\Core\Db;
use Galette\Core\Login;
use Galette\Entity\ContributionsTypes;

/**
 * Preferences for paypal
 *
 * @category  Classes
 * @name      Paypal
 * @package   Galette
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2011-2014 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.7dev - 2011-06-03
 */

class Paypal
{
    public const TABLE = 'types_cotisation_prices';
    public const PK = ContributionsTypes::PK;
    public const PREFS_TABLE = 'preferences';

    public const PAYMENT_PENDING = 'Pending';
    public const PAYMENT_COMPLETE = 'Complete';

    private $zdb;

    private $prices = array();
    private $id = null;
    private $inactives = array();

    private $loaded = false;
    private $amounts_loaded = false;

    /**
     * Default constructor
     *
     * @param Db $zdb Database instance
     */
    public function __construct(Db $zdb)
    {
        $this->zdb = $zdb;
        $this->loaded = false;
        $this->prices = array();
        $this->inactives = array();
        $this->id = null;
        $this->load();
    }

    /**
     * Load preferences form the database and amounts
     *
     * @return void
     */
    public function load()
    {
        try {
            $results = $this->zdb->selectAll(PAYPAL_PREFIX . self::PREFS_TABLE);

            foreach ($results as $row) {
                switch ($row->nom_pref) {
                    case 'paypal_id':
                        $this->id = $row->val_pref;
                        break;
                    case 'paypal_inactives':
                        $this->inactives = explode(',', $row->val_pref);
                        break;
                    default:
                        //we've got a preference not intended
                        Analog::log(
                            '[' . get_class($this) . '] unknown preference `' .
                            $row->nom_pref . '` in the database.',
                            Analog::WARNING
                        );
                }
            }
            $this->loaded = true;
            return $this->loadAmounts();
        } catch (\Exception $e) {
            Analog::log(
                '[' . get_class($this) . '] Cannot load paypal preferences |' .
                $e->getMessage(),
                Analog::ERROR
            );
            //consider plugin is not loaded when missing the main preferences
            //(that includes paypal id)
            $this->loaded = false;
        }
    }

    /**
     * Load amounts from database
     *
     * @return void
     */
    private function loadAmounts()
    {
        $ct = new ContributionsTypes($this->zdb);
        $this->prices = $ct->getCompleteList();

        try {
            $results = $this->zdb->selectAll(PAYPAL_PREFIX . self::TABLE);

            //check if all types currently exists in paypal table
            if (count($results) != count($this->prices)) {
                Analog::log(
                    '[' . get_class($this) . '] There are missing types in ' .
                    'paypal table, Galette will try to create them.',
                    Analog::INFO
                );
            }

            $queries = array();
            foreach ($this->prices as $k => $v) {
                $_found = false;
                if (count($results) > 0) {
                    //for each entry in types, we want to get the associated amount
                    foreach ($results as $paypal) {
                        if ($paypal->id_type_cotis == $k) {
                            $_found=true;
                            $this->prices[$k]['amount'] = (double)$paypal->amount;
                            break;
                        }
                    }
                }
                if ($_found === false) {
                    Analog::log(
                        'The type `' . $v['name'] . '` (' . $k . ') does not exist' .
                        ', Galette will attempt to create it.',
                        Analog::INFO
                    );
                    $this->prices[$k]['amount'] = null;
                    $queries[] = array(
                          'id'   => $k,
                        'amount' => null
                    );
                }
            }
            if (count($queries) > 0) {
                $this->newEntries($queries);
            }
            //amounts should be loaded here
            $this->amounts_loaded = true;
        } catch (\Exception $e) {
            Analog::log(
                '[' . get_class($this) . '] Cannot load paypal amounts' .
                '` | ' . $e->getMessage(),
                Analog::ERROR
            );
            //amounts are not loaded at this point
            $this->amounts_loaded = false;
        }
    }

    /**
     * Store values in the database
     *
     * @return void
     */
    public function store()
    {
        try {
            //store paypal id
            $values = array(
                'nom_pref' => 'paypal_id',
                'val_pref' => $this->id
            );
            $update = $this->zdb->update(PAYPAL_PREFIX . self::PREFS_TABLE);
            $update->set($values)
                ->where(
                    array(
                        'nom_pref' => 'paypal_id'
                    )
                );

            $edit = $this->zdb->execute($update);

            //store inactives
            $values = array(
                'nom_pref' => 'paypal_inactives',
                'val_pref' => implode(',', $this->inactives)
            );
            $update = $this->zdb->update(PAYPAL_PREFIX . self::PREFS_TABLE);
            $update->set($values)
                ->where(
                    array(
                        'nom_pref' => 'paypal_inactives'
                    )
                );

            $edit = $this->zdb->execute($update);

            Analog::log(
                '[' . get_class($this) .
                '] Paypal preferences were sucessfully stored',
                Analog::INFO
            );

            return $this->storeAmounts();
        } catch (\Exception $e) {
            Analog::log(
                '[' . get_class($this) . '] Cannot store paypal preferences' .
                '` | ' . $e->getMessage(),
                Analog::ERROR
            );
            return false;
        }
    }

    /**
     * Store amounts in the database
     *
     * @return boolean
     */
    public function storeAmounts()
    {
        try {
            $update = $this->zdb->update(PAYPAL_PREFIX . self::TABLE);
            $update->set(
                array(
                    'amount'    => ':amount'
                )
            )->where->equalTo(self::PK, ':id');

            $stmt = $this->zdb->sql->prepareStatementForSqlObject($update);

            foreach ($this->prices as $k => $v) {
                /** Why where parameter is named where1 ?? */
                $stmt->execute(
                    array(
                        'amount'    => (float)$v['amount'],
                        'where1'    => $k
                    )
                );
            }

            Analog::log(
                '[' . get_class($this) . '] Paypal amounts were sucessfully stored',
                Analog::INFO
            );
            return true;
        } catch (\Exception $e) {
            Analog::log(
                '[' . get_class($this) . '] Cannot store paypal amounts' .
                '` | ' . $e->getMessage(),
                Analog::ERROR
            );
            return false;
        }
    }

    /**
    * Add missing types in paypal table
    *
    * @param Array $queries Array of items to insert
    *
    * @return true on success, false on failure
    */
    private function newEntries($queries)
    {
        try {
            $insert = $this->zdb->insert(PAYPAL_PREFIX . self::TABLE);
            $insert->values(
                array(
                    self::PK    => ':' . self::PK,
                    'amount'    => ':amount'
                )
            );
            $stmt = $this->zdb->sql->prepareStatementForSqlObject($insert);

            foreach ($queries as $q) {
                $stmt->execute(
                    array(
                        self::PK    => $q['id'],
                        'amount'    => $q['amount']
                    )
                );
            }

            return true;
        } catch (\Exception $e) {
            Analog::log(
                'Unable to store missing types in paypal table.' .
                $stmt->getMessage() . '(' . $stmt->getDebugInfo() . ')',
                Analog::WARNING
            );
            return false;
        }
    }

    /**
     * Get Paypal identifier
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get loaded and active amounts
     *
     * @param Login $login Login instance
     *
     * @return array
     */
    public function getAmounts(Login $login)
    {
        $prices = array();
        foreach ($this->prices as $k => $v) {
            if (!$this->isInactive($k)) {
                if ($login->isLogged() || $v['extra'] == 0) {
                    $prices[$k] = $v;
                }
            }
        }
        return $prices;
    }

    /**
     * Get loaded amounts
     *
     * @return array
     */
    public function getAllAmounts()
    {
        return $this->prices;
    }

    /**
     * Is the plugin loaded?
     *
     * @return boolean
     */
    public function isLoaded()
    {
        return $this->loaded;
    }

    /**
     * Are amounts loaded?
     *
     * @return boolean
     */
    public function areAmountsLoaded()
    {
        return $this->amounts_loaded;
    }

    /**
     * Set paypal identifier
     *
     * @param string $id identifier
     *
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Set new prices
     *
     * @param array $ids     array of identifier
     * @param array $amounts array of amounts
     *
     * @return void
     */
    public function setPrices($ids, $amounts)
    {
        $this->prices = [];
        foreach ($ids as $k => $id) {
            $this->prices[$id]['amount'] = $amounts[$k];
        }
    }

    /**
     * Check if the specified contribution is active
     *
     * @param int $id type identifier
     *
     * @return boolean
     */
    public function isInactive($id)
    {
        return in_array($id, $this->inactives);
    }

    /**
     * Set inactives types
     *
     * @param array $inactives array of inactives types
     *
     * @return void
     */
    public function setInactives($inactives)
    {
        $this->inactives = $inactives;
    }

    /**
     * Unset inactives types
     *
     * @return void
     */
    public function unsetInactives()
    {
        $this->inactives = array();
    }
}
