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

use Galette\Core\Login;
use Galette\Core\Preferences;
use Galette\Entity\Adherent;
use Galette\Core\GalettePlugin;

/**
 * Galette Paypal plugin
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */

class PluginGalettePaypal extends GalettePlugin
{
    /**
     * Extra menus entries
     *
     * @return array<string, string|array<string,mixed>>
     */
    public static function getMenusContents(): array
    {
        /** @var Login $login */
        global $login;
        $menus = [];

        if ($login->isAdmin() || $login->isStaff()) {
            $menus['plugin_paypal'] = [
                'title' => _T("Paypal", "paypal"),
                'icon' => 'paypal',
                'items' => [
                    [
                        'label' => _T("Paypal History", "paypal"),
                        'route' => [
                            'name' => 'paypal_history'
                        ]
                    ],
                    [
                        'label' => _T("Settings"),
                        'route' => [
                            'name' => 'paypal_preferences'
                        ]
                    ]
                ]
            ];
        }

        return $menus;
    }

    /**
     * Extra public menus entries
     *
     * @return array<int, string|array<string,mixed>>
     */
    public static function getPublicMenusItemsList(): array
    {
        return [
            [
                'label' => _T("Payment form", "paypal"),
                'route' => [
                    'name' => 'paypal_form'
                ],
                'icon' => 'paypal'
            ]
        ];
    }

    /**
     * Get dashboards contents
     *
     * @return array<int, string|array<string,mixed>>
     */
    public static function getDashboardsContents(): array
    {
        /** @var Login $login */
        global $login;
        /** @var Preferences $preferences */
        global $preferences;
        $contents = [];

        if ($preferences->showPublicPages($login)) {
            $contents[] = [
                'label' => _T("Payment form", "paypal"),
                'route' => [
                    'name' => 'paypal_form'
                ],
                'icon' => 'paypal'
            ];
        }
        return $contents;
    }

    /**
     * Get actions contents
     *
     * @param Adherent $member Member instance
     *
     * @return array<int, string|array<string,mixed>>
     */
    public static function getListActionsContents(Adherent $member): array
    {
        return [];
    }

    /**
     * Get detailed actions contents
     *
     * @param Adherent $member Member instance
     *
     * @return array<int, string|array<string,mixed>>
     */
    public static function getDetailedActionsContents(Adherent $member): array
    {
        return static::getListActionsContents($member);
    }

    /**
     * Get batch actions contents
     *
     * @return array<int, string|array<string,mixed>>
     */
    public static function getBatchActionsContents(): array
    {
        return [];
    }
}
