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

namespace GalettePaypal;

use Analog\Analog;
use Galette\Core\Db;
use Galette\Core\Galette;
use Galette\Core\Login;
use Galette\Core\History;
use Galette\Core\Preferences;
use Galette\Filters\HistoryList;

/**
 * This class stores and serve the logo.
 * If no custom logo is found, we take galette's default one.
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class PaypalHistory extends History
{
    public const TABLE = 'history';
    public const PK = 'id_paypal';

    public const STATE_NONE = 0;
    public const STATE_PROCESSED = 1;
    public const STATE_DONE = 2;
    public const STATE_ERROR = 3;
    public const STATE_INCOMPLETE = 4;
    public const STATE_ALREADYDONE = 5;

    private int $id;

    /**
     * Default constructor.
     *
     * @param Db           $zdb         Database
     * @param Login        $login       Login
     * @param Preferences  $preferences Preferences
     * @param ?HistoryList $filters     Filtering
     */
    public function __construct(Db $zdb, Login $login, Preferences $preferences, HistoryList $filters = null)
    {
        $this->with_lists = false;
        parent::__construct($zdb, $login, $preferences, $filters);
    }

    /**
     * Add a new entry
     *
     * @param array<string>|string $action   the action to log
     * @param string               $argument the argument
     * @param string               $query    the query (if relevant)
     *
     * @return bool true if entry was successfully added, false otherwise
     */
    public function add(array|string $action, string $argument = '', string $query = ''): bool
    {
        $request = $action;
        try {
            $values = array(
                'history_date'  => date('Y-m-d H:i:s'),
                'amount'        => $request['mc_gross'],
                'comments'      => $request['item_name'],
                'request'       => Galette::jsonEncode($request),
                'signature'     => $request['verify_sign'],
                'state'         => self::STATE_NONE
            );

            $insert = $this->zdb->insert($this->getTableName());
            $insert->values($values);
            $this->zdb->execute($insert);
            $this->id = $this->zdb->getLastGeneratedValue($this);

            Analog::log(
                'An entry has been added in paypal history',
                Analog::INFO
            );
        } catch (\Exception $e) {
            Analog::log(
                "An error occured trying to add log entry. " . $e->getMessage(),
                Analog::ERROR
            );
            return false;
        }

        return true;
    }

    /**
     * Get table's name
     *
     * @param boolean $prefixed Whether table name should be prefixed
     *
     * @return string
     */
    protected function getTableName(bool $prefixed = false): string
    {
        if ($prefixed === true) {
            return PREFIX_DB . PAYPAL_PREFIX . self::TABLE;
        } else {
            return PAYPAL_PREFIX . self::TABLE;
        }
    }

    /**
     * Get table's PK
     *
     * @return string
     */
    protected function getPk(): string
    {
        return self::PK;
    }

    /**
     * Gets Paypal history
     *
     * @return array<int, object>
     */
    public function getPaypalHistory(): array
    {
        $orig = $this->getHistory();
        $new = array();
        $dedup = array();
        if (count($orig) > 0) {
            foreach ($orig as $o) {
                try {
                    if (Galette::isSerialized($o['request'])) {
                        $oa = unserialize($o['request']);
                    } else {
                        $oa = Galette::jsonDecode($o['request']);
                    }

                    $o['raw_request'] = print_r($oa, true);
                    $o['request'] = $oa;
                    if (in_array($oa['verify_sign'], $dedup)) {
                        $o['duplicate'] = true;
                    } else {
                        $dedup[] = $oa['verify_sign'];
                    }

                    $new[] = $o;
                } catch (\Exception $e) {
                    Analog::log(
                        'Error loading Paypal history entry #' . $o[$this->getPk()] .
                        ' ' . $e->getMessage(),
                        Analog::WARNING
                    );
                }
            }
        }
        return $new;
    }

    /**
     * Builds the order clause
     *
     * @return array<int, string> SQL ORDER clause
     */
    protected function buildOrderClause(): array
    {
        $order = array();

        switch ($this->filters->orderby) {
            case HistoryList::ORDERBY_DATE:
                $order[] = 'history_date ' . $this->filters->ordered;
                break;
        }

        return $order;
    }

    /**
     * Is payment already processed?
     *
     * @param string $sign Verify sign paypal parameter
     *
     * @return boolean
     */
    public function isProcessed(string $sign): bool
    {
        $select = $this->zdb->select($this->getTableName());
        $select->where([
            'signature' => $sign,
            'state'     => self::STATE_PROCESSED
        ]);
        $results = $this->zdb->execute($select);

        return (count($results) > 0);
    }

    /**
     * Set payment state
     *
     * @param integer $state State, one of self::STATE_ constants
     *
     * @return boolean
     */
    public function setState(int $state): bool
    {
        try {
            $update = $this->zdb->update($this->getTableName());
            $update
                ->set(['state' => $state])
                ->where([self::PK => $this->id]);
            $this->zdb->execute($update);
            return true;
        } catch (\Exception $e) {
            Analog::log(
                'An error occurred when updating state field | ' . $e->getMessage(),
                Analog::ERROR
            );
        }
        return false;
    }
}
