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
    version: '3.0.0',           //Version
    compver: '1.3.0',           //Galette compatible version
    route: 'paypal',            //routing name and translation domain
    date: '2026-08-15',         //Release date
    acls: [                     //Permissions needed
        'paypal_preferences'        => 'staff',
        'store_paypal_preferences'  => 'staff',
        'paypal_history'            => 'staff',
        'filter_paypal_history'     => 'staff'
    ],
    dbver: 3.00
);
