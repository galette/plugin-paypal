--
-- This file is part of Galette Paypal plugin (https://galette.eu).
-- SPDX-FileCopyrightText: Copyright © 2011-2026 The Galette Team
-- SPDX-License-Identifier: GPL-3.0-or-later
--

-- Website Payments Standard is discontinued; the plugin now relies on
-- Standard Checkout (Orders v2 REST API) and webhooks.

ALTER TABLE galette_paypal_preferences ENGINE=InnoDB;
ALTER TABLE galette_paypal_preferences CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE galette_paypal_history CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- The business email is not a REST API credential, it cannot be reused.
DELETE FROM galette_paypal_preferences WHERE nom_pref = 'paypal_id';

INSERT INTO galette_paypal_preferences (nom_pref, val_pref) VALUES ('paypal_client_id', '');
INSERT INTO galette_paypal_preferences (nom_pref, val_pref) VALUES ('paypal_client_secret', '');
INSERT INTO galette_paypal_preferences (nom_pref, val_pref) VALUES ('paypal_webhook_id', '');
INSERT INTO galette_paypal_preferences (nom_pref, val_pref) VALUES ('paypal_sandbox', '0');
INSERT INTO galette_paypal_preferences (nom_pref, val_pref) VALUES ('paypal_currency', 'EUR');

-- IPN `verify_sign` is replaced by the order identifier.
ALTER TABLE galette_paypal_history DROP COLUMN signature;
ALTER TABLE galette_paypal_history ADD COLUMN order_id varchar(50) NOT NULL DEFAULT '' AFTER history_date;
ALTER TABLE galette_paypal_history ADD COLUMN capture_id varchar(50) DEFAULT NULL AFTER order_id;
ALTER TABLE galette_paypal_history ADD COLUMN currency varchar(3) NOT NULL DEFAULT 'EUR' AFTER amount;
ALTER TABLE galette_paypal_history ADD COLUMN id_adh int(10) unsigned DEFAULT NULL AFTER currency;
ALTER TABLE galette_paypal_history ADD COLUMN id_type_cotis int(10) unsigned DEFAULT NULL AFTER id_adh;
ALTER TABLE galette_paypal_history ADD COLUMN id_cotis int(10) unsigned DEFAULT NULL AFTER id_type_cotis;

-- Legacy IPN entries have no order identifier; they get a synthetic one so the
-- unique index below can be created and idempotency checks stay reliable.
UPDATE galette_paypal_history SET order_id = CONCAT('legacy-', id_paypal) WHERE order_id = '';

ALTER TABLE galette_paypal_history ADD UNIQUE KEY `galette_paypal_history_order_idx` (`order_id`);
ALTER TABLE galette_paypal_history ALTER COLUMN order_id DROP DEFAULT;
