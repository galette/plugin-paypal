<?php

/**
 * Copyright © 2003-2025 The Galette Team
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
use Galette\Entity\ContributionsTypes;

/**
 * Preferences for Paypal
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Paypal
{
    public const TABLE = 'preferences';

    public const PAYMENT_PENDING = 'Pending';
    public const PAYMENT_COMPLETE = 'Complete';

    private Db $zdb;

    /** @var array<int, array<string,mixed>> */
    private array $prices;
    private ?string $id;
    /** @var array<int, string> */
    private array $inactives;

    private bool $loaded;
    private bool $amounts_loaded = false;

    /**
     * Default constructor
     *
     * @param Db $zdb Database instance
     */
    public function __construct(Db $zdb)
    {
        $this->zdb = $zdb;
        $this->loaded = false;
        $this->prices = [];
        $this->inactives = [];
        $this->id = null;
        $this->load();
    }

    /**
     * Load preferences form the database and amounts from core contributions types
     *
     * @return void
     */
    public function load(): void
    {
        try {
            $results = $this->zdb->selectAll(PAYPAL_PREFIX . self::TABLE);

            /** @var \ArrayObject<string, mixed> $row */
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
            $this->loadContributionsTypes();
        } catch (\Exception $e) {
            Analog::log(
                '[' . get_class($this) . '] Cannot load paypal preferences |' .
                $e->getMessage(),
                Analog::ERROR
            );
            //consider plugin is not loaded when missing the main preferences
            //(that includes Paypal id)
            $this->loaded = false;
        }
    }

    /**
     * Load amounts from core contributions types
     *
     * @return void
     */
    private function loadContributionsTypes(): void
    {
        try {
            $ct = new ContributionsTypes($this->zdb);
            $this->prices = $ct->getCompleteList();
            //amounts should be loaded here
            $this->amounts_loaded = true;
        } catch (\Exception $e) {
            Analog::log(
                '[' . get_class($this) . '] Cannot load amounts from core contributions types' .
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
     * @return bool
     */
    public function store(): bool
    {
        try {
            //store paypal id
            $values = [
                'nom_pref' => 'paypal_id',
                'val_pref' => $this->id
            ];
            $update = $this->zdb->update(PAYPAL_PREFIX . self::TABLE);
            $update->set($values)
                ->where(
                    [
                        'nom_pref' => 'paypal_id'
                    ]
                );

            $edit = $this->zdb->execute($update);

            //store inactives
            $values = [
                'nom_pref' => 'paypal_inactives',
                'val_pref' => implode(',', $this->inactives)
            ];
            $update = $this->zdb->update(PAYPAL_PREFIX . self::TABLE);
            $update->set($values)
                ->where(
                    [
                        'nom_pref' => 'paypal_inactives'
                    ]
                );

            $edit = $this->zdb->execute($update);

            Analog::log(
                '[' . get_class($this) .
                '] Paypal preferences were successfully stored',
                Analog::INFO
            );

            return true;
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
     * Get Paypal identifier
     *
     * @return string
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Get loaded and active amounts
     *
     * @param Login $login Login instance
     *
     * @return array<int, array<string,mixed>>
     */
    public function getAmounts(Login $login): array
    {
        $prices = [];
        foreach ($this->prices as $k => $v) {
            if (!$this->isInactive($k)) {
                if ($login->isLogged() || $v['extra'] == ContributionsTypes::DONATION_TYPE) {
                    $prices[$k] = $v;
                }
            }
        }
        return $prices;
    }

    /**
     * Get loaded amounts
     *
     * @return array<int, array<string,mixed>>
     */
    public function getAllAmounts(): array
    {
        return $this->prices;
    }

    /**
     * Is the plugin loaded?
     *
     * @return boolean
     */
    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * Are amounts loaded?
     *
     * @return boolean
     */
    public function areAmountsLoaded(): bool
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
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * Set new prices
     *
     * @param array<int, string> $ids     array of identifier
     * @param array<int, string> $amounts array of amounts
     *
     * @return void
     */
    public function setPrices(array $ids, array $amounts): void
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
    public function isInactive(int $id): bool
    {
        return in_array($id, $this->inactives);
    }

    /**
     * Set inactives types
     *
     * @param array<int, string> $inactives array of inactives types
     *
     * @return void
     */
    public function setInactives(array $inactives): void
    {
        $this->inactives = $inactives;
    }

    /**
     * Unset inactives types
     *
     * @return void
     */
    public function unsetInactives(): void
    {
        $this->inactives = [];
    }

    /**
     * Get the URL to use for Paypal
     *
     * @return string
     */
    public function getFormURL(): string
    {
        return Galette::isDebugEnabled()
            ? 'https://www.sandbox.paypal.com/cgi-bin/webscr'
            : 'https://www.paypal.com/cgi-bin/webscr';
    }
}
