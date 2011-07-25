<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Paypal history management
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
 * @package   Galette
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2011 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version   SVN: $Id$
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.7dev - 2011-07-25
 */

/** @ignore */
require_once WEB_ROOT . 'classes/history.class.php';
require_once 'paypal.class.php';

/**
 * This class stores and serve the logo.
 * If no custom logo is found, we take galette's default one.
 *
 * @category  Classes
 * @name      Logo
 * @package   Galette
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2009-2011 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.7dev - 2009-09-13
 */
class PaypalHistory extends History
{
    const TABLE = 'history';
    const PK = 'id_paypal';

    protected $_types = array(
        'text',
        'date',
        'float',
        'text',
        'text'
    );

    protected $_fields = array(
        'id_paypal',
        'history_date',
        'amount',
        'comments',
        'request'
    );

    /**
    * Default constructor.
    */
    public function __construct()
    {
        parent::__construct();
    }

   /**
    * Returns the field we want to default set order to
    *
    * @return string field name
    */
    protected function getDefaultOrder()
    {
        return 'history_date';
    }

    /**
    * Add a new entry
    *
    * @param string $action   the action to log
    * @param string $argument the arguemnt
    * @param string $query    the query (if relevant)
    *
    * @return bool true if entry was successfully added, false otherwise
    */
    public function add($request)
    {
        global $mdb, $log, $login;

        MDB2::loadFile('Date');

        $requete = 'INSERT INTO ' .
            $mdb->quoteIdentifier($this->getTableName()) . ' (';
        $requete .= implode(', ', $this->_fields);
        $requete .= ') VALUES (:id, :date, :amount, :comment, :request)';

        $stmt = $mdb->prepare($requete, $this->_types, MDB2_PREPARE_MANIP);

        if (MDB2::isError($stmt)) {
            $log->log(
                'Unable to initialize add log entry into database.' .
                $stmt->getMessage() . '(' . $stmt->getDebugInfo() . ')',
                PEAR_LOG_WARNING
            );
            return false;
        }

        $stmt->execute(
            array(
                'id'      => 'NULL',
                'date'    => MDB2_Date::mdbNow(),
                'amount'  => $request['mc_gross'],
                'comment' => $request['item_name'],
                'request' => serialize($request)
            )
        );

        $log->log($stmt, PEAR_LOG_DEBUG);

        if (MDB2::isError($stmt)) {
            $log->log(
                "An error occured trying to add log entry. " . $stmt->getMessage(),
                PEAR_LOG_ERR
            );
            return false;
        } else {
            $log->log('Log entry added', PEAR_LOG_DEBUG);
        }

        $stmt->free();

        return true;
    }

    /**
     * Get table's name
     *
     * @return string
     */
    protected function getTableName()
    {
        return PREFIX_DB . PAYPAL_PREFIX . self::TABLE;
    }

    /**
     * Get table's PK
     *
     * @return string
     */
    protected function getPk()
    {
        return self::PK;
    }

    public function getPaypalHistory()
    {
        $orig = $this->getHistory();
        $new = array();
        foreach ( $orig as $o ) {
            $o['request'] = print_r(unserialize($o['request']), true);
            $new[] = $o;
        }
        return $new;
    }
}
?>
