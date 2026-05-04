--
-- This file is part of Galette Paypal plugin (https://galette.eu).
-- SPDX-FileCopyrightText: Copyright © 2011-2026 The Galette Team
-- SPDX-License-Identifier: GPL-3.0-or-later
--

ALTER TABLE `galette_paypal_history` ADD `signature` VARCHAR(255) NOT NULL;
ALTER TABLE `galette_paypal_history` ADD `state` tinyint(4) NOT NULL DEFAULT 0;
