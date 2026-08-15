<?php

/**
 * This file is part of Galette Paypal plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2011-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GalettePaypal;

use Analog\Analog;
use Galette\Core\Db;
use Galette\Core\Galette;
use Galette\Core\Login;
use Galette\Core\History;
use Galette\Core\Preferences;
use Galette\Entity\Adherent;
use Galette\Filters\HistoryList;
use Laminas\Db\Adapter\Driver\Pdo\Result;

/**
 * Paypal payments history
 *
 * Each entry is created as soon as an order is created on Paypal side, and is
 * then used as the idempotency guard between the payer return and the webhook
 * notification: both may report the very same payment.
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class PaypalHistory extends History
{
    public const string TABLE = 'history';
    public const string PK = 'id_paypal';

    public const int STATE_NONE = 0;
    public const int STATE_PROCESSED = 1;
    /** @deprecated Legacy IPN state, kept so existing entries stay readable */
    public const int STATE_DONE = 2;
    public const int STATE_ERROR = 3;
    /** @deprecated Legacy IPN state, kept so existing entries stay readable */
    public const int STATE_INCOMPLETE = 4;
    public const int STATE_ALREADYDONE = 5;
    /** Order created on Paypal side, payment not completed yet */
    public const int STATE_PENDING = 6;
    /** Capture in progress; acts as a lock between return and webhook */
    public const int STATE_CAPTURING = 7;
    /** Payment received from someone who is not a member: no contribution */
    public const int STATE_PUBLIC = 8;

    private int $id;

    /**
     * Default constructor.
     *
     * @param Db           $zdb         Database
     * @param Login        $login       Login
     * @param Preferences  $preferences Preferences
     * @param ?HistoryList $filters     Filtering
     */
    public function __construct(Db $zdb, Login $login, Preferences $preferences, ?HistoryList $filters = null)
    {
        $this->with_lists = false;
        parent::__construct($zdb, $login, $preferences, $filters);
    }

    /**
     * Add a new entry
     *
     * Not used by the plugin, which relies on addPending() instead; kept to
     * honour the parent signature.
     *
     * @param array<string>|string $action   the action to log
     * @param string               $argument the argument
     * @param string               $query    the query (if relevant)
     *
     * @return bool true if entry was successfully added, false otherwise
     */
    public function add(array|string $action, string $argument = '', string $query = ''): bool
    {
        Analog::log(
            'Paypal history entries are created from addPending(), not from add().',
            Analog::ERROR
        );
        return false;
    }

    /**
     * Register a newly created Paypal order
     *
     * @param string $order_id  Paypal order identifier
     * @param float  $amount    Amount to be paid
     * @param string $currency  Currency
     * @param ?int   $member_id Member identifier, when the payer is a known member
     * @param int    $type_id   Contribution type identifier
     * @param string $item_name Contribution type label
     */
    public function addPending(
        string $order_id,
        float $amount,
        string $currency,
        ?int $member_id,
        int $type_id,
        string $item_name
    ): bool {
        if ($this->orderExists($order_id)) {
            //the unique index on `order_id` is the last resort guard; checking
            //first keeps a failed insert from poisoning a running transaction
            Analog::log(
                'Paypal order ' . $order_id . ' is already registered',
                Analog::WARNING
            );
            return false;
        }

        try {
            $values = [
                'history_date'  => date('Y-m-d H:i:s'),
                'order_id'      => $order_id,
                'amount'        => $amount,
                'currency'      => $currency,
                'id_adh'        => $member_id,
                'id_type_cotis' => $type_id,
                'comments'      => $item_name,
                'state'         => self::STATE_PENDING
            ];

            $insert = $this->zdb->insert($this->getTableName());
            $insert->values($values);
            $this->zdb->execute($insert);

            //the sequence name does not follow core conventions on PostgreSQL;
            //`order_id` is unique, reading the entry back is both portable and cheap
            $entry = $this->loadByOrderId($order_id);
            if ($entry === null) {
                return false;
            }
            $this->id = (int)$entry[self::PK];

            Analog::log(
                'A Paypal order has been registered in paypal history',
                Analog::INFO
            );
        } catch (\Exception $e) {
            Analog::log(
                'An error occurred trying to add log entry. ' . $e->getMessage(),
                Analog::ERROR
            );
            return false;
        }

        return true;
    }

    /**
     * Is an order already registered?
     *
     * @param string $order_id Paypal order identifier
     */
    private function orderExists(string $order_id): bool
    {
        try {
            $select = $this->zdb->select($this->getTableName());
            $select->columns([self::PK])->where(['order_id' => $order_id])->limit(1);
            $results = $this->zdb->execute($select);

            return $results->count() > 0;
        } catch (\Exception $e) {
            Analog::log(
                'An error occurred looking for Paypal order ' . $order_id . ' | ' . $e->getMessage(),
                Analog::ERROR
            );
            //we cannot tell; the unique index still guards the insert
            return false;
        }
    }

    /**
     * Take the exclusive right to process an order
     *
     * The payer return and the webhook notification both try to capture the
     * very same order. The conditional update below is atomic on both MySQL
     * and PostgreSQL: only one caller can move the entry out of the pending
     * state, and it is the only one allowed to capture and to create the
     * contribution.
     *
     * @param string $order_id Paypal order identifier
     *
     * @return bool true when the caller took the lock
     */
    public function claim(string $order_id): bool
    {
        try {
            $update = $this->zdb->update($this->getTableName());
            $update
                ->set(['state' => self::STATE_CAPTURING])
                ->where([
                    'order_id'  => $order_id,
                    'state'     => self::STATE_PENDING
                ]);
            $result = $this->zdb->execute($update);

            if (!$result instanceof Result || $result->getAffectedRows() !== 1) {
                return false;
            }
        } catch (\Exception $e) {
            Analog::log(
                'An error occurred claiming Paypal order ' . $order_id . ' | ' . $e->getMessage(),
                Analog::ERROR
            );
            return false;
        }

        $entry = $this->loadByOrderId($order_id);
        if ($entry === null) {
            return false;
        }
        $this->id = (int)$entry[self::PK];

        return true;
    }

    /**
     * Get an history entry from a Paypal order identifier
     *
     * @param string $order_id Paypal order identifier
     *
     * @return ?array<string, mixed>
     */
    public function loadByOrderId(string $order_id): ?array
    {
        try {
            $select = $this->zdb->select($this->getTableName());
            $select->where(['order_id' => $order_id])->limit(1);
            $results = $this->zdb->execute($select);
            $row = $results->current();

            if ($row === false || $row === null) {
                return null;
            }

            return (array)$row;
        } catch (\Exception $e) {
            Analog::log(
                'An error occurred loading Paypal order ' . $order_id . ' | ' . $e->getMessage(),
                Analog::ERROR
            );
            return null;
        }
    }

    /**
     * Store the outcome of a Paypal capture
     *
     * @param array<string, mixed> $request    Raw Paypal payload
     * @param ?string              $capture_id Paypal capture identifier
     */
    public function setOutcome(array $request, ?string $capture_id): bool
    {
        try {
            $update = $this->zdb->update($this->getTableName());
            $update
                ->set([
                    'capture_id'    => $capture_id,
                    'request'       => Galette::jsonEncode($request)
                ])
                ->where([self::PK => $this->id]);
            $this->zdb->execute($update);
            return true;
        } catch (\Exception $e) {
            Analog::log(
                'An error occurred when storing Paypal outcome | ' . $e->getMessage(),
                Analog::ERROR
            );
        }
        return false;
    }

    /**
     * Link the entry to the contribution that has been created
     *
     * @param int $id_cotis Contribution identifier
     */
    public function setContribution(int $id_cotis): bool
    {
        try {
            $update = $this->zdb->update($this->getTableName());
            $update
                ->set(['id_cotis' => $id_cotis])
                ->where([self::PK => $this->id]);
            $this->zdb->execute($update);
            return true;
        } catch (\Exception $e) {
            Analog::log(
                'An error occurred when linking contribution | ' . $e->getMessage(),
                Analog::ERROR
            );
        }
        return false;
    }

    /**
     * Get table's name
     *
     * @param bool $prefixed Whether table name should be prefixed
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
        $new = [];
        if (count($orig) > 0) {
            foreach ($orig as $o) {
                try {
                    if ($o['request'] === null || $o['request'] === '') {
                        $oa = [];
                    } elseif (Galette::isSerialized($o['request'])) {
                        $oa = unserialize($o['request']);
                    } else {
                        $oa = Galette::jsonDecode($o['request']);
                    }

                    $o['raw_request'] = print_r($oa, true);
                    $o['request'] = $oa;
                    $o['member_fullname'] = $this->getMemberFullName(
                        empty($o['id_adh']) ? null : (int)$o['id_adh']
                    );

                    $new[] = $o;
                } catch (\Exception $e) {
                    Analog::log(
                        'Error loading Paypal history entry #' . $o[$this->getPk()]
                        . ' ' . $e->getMessage(),
                        Analog::WARNING
                    );
                }
            }
        }
        return $new;
    }

    /**
     * Get member full name, for display purposes
     *
     * @param ?int $id Member identifier
     */
    private function getMemberFullName(?int $id): string
    {
        if ($id === null) {
            return '';
        }

        try {
            $adh = new Adherent($this->zdb, $id, ['dynamics' => false, 'groups' => false]);
            return $adh->sfullname;
        } catch (\Exception $e) {
            Analog::log(
                'Cannot load member #' . $id . ' | ' . $e->getMessage(),
                Analog::WARNING
            );
            return '';
        }
    }

    /**
     * Builds the order clause
     *
     * @return array<int, string> SQL ORDER clause
     */
    protected function buildOrderClause(): array
    {
        $order = [];

        if ($this->filters->orderby == HistoryList::ORDERBY_DATE) {
            $order[] = 'history_date ' . $this->filters->getDirection();
        }

        return $order;
    }

    /**
     * Set payment state
     *
     * @param int $state State, one of self::STATE_ constants
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
