<?php

/**
 * This file is part of Galette Paypal plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2011-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

/** @var \Galette\Core\Plugins $this */
$this->register(
    name: 'Galette Paypal',     //Name
    desc: 'Paypal integration', //Short description
    author: 'Johan Cwiklinski', //Author
    version: '2.2.1',           //Version
    compver: '1.3.0',           //Galette compatible version
    route: 'paypal',            //routing name and translation domain
    date: '2025-12-08',         //Release date
    acls: [                     //Permissions needed
        'paypal_preferences'        => 'staff',
        'store_paypal_preferences'  => 'staff',
        'paypal_history'            => 'staff',
        'filter_paypal_history'     => 'staff'
    ],
    dbver: 1.00
);

$this->setCsrfExclusions([
    '/paypal_success/',
]);
