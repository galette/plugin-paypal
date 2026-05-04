<?php

/**
 * This file is part of Galette Paypal plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2011-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GalettePaypal;

use DI\Attribute\Inject;
use Galette\Core\Db;
use Galette\Core\Login;
use Galette\Core\Plugins\DashboardProviderInterface;
use Galette\Core\Plugins\MenuProviderInterface;
use Galette\Core\Preferences;
use Galette\Entity\Adherent;
use Galette\Core\GalettePlugin;

/**
 * Galette Paypal plugin
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */

class PluginGalettePaypal extends GalettePlugin implements MenuProviderInterface, DashboardProviderInterface
{
    #[Inject]
    private readonly Db $zdb; //@phpstan-ignore-line injected from DI

    /**
     * Extra menus entries
     *
     * @return array<string, string|array<string,mixed>>
     */
    public function getMenus(): array
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
    public function getPublicMenus(): array
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
    public function getDashboards(): array
    {
        /** @var Login $login */
        global $login;
        /** @var Preferences $preferences */
        global $preferences;
        $contents = [];

        if ($preferences->showPublicPage($login, 'pref_publicpages_visibility_generic')) {
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
     * Get current logged-in user dashboards contents
     *
     * @return array<int, string|array<string,mixed>>
     */
    public function getMyDashboards(): array
    {
        return [];
    }

    /**
     * Is the plugin fully installed (including database, extra configuration, etc.)?
     */
    public function isInstalled(): bool
    {
        return
            $this->zdb->tableExists(PAYPAL_PREFIX . Paypal::TABLE)
            && $this->zdb->tableExists(PAYPAL_PREFIX . PaypalHistory::TABLE);
    }
}
