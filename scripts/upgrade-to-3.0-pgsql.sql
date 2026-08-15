--
-- This file is part of Galette Paypal plugin (https://galette.eu).
-- SPDX-FileCopyrightText: Copyright © 2011-2026 The Galette Team
-- SPDX-License-Identifier: GPL-3.0-or-later
--

-- Website Payments Standard is discontinued; the plugin now relies on
-- Standard Checkout (Orders v2 REST API) and webhooks.

-- The business email is not a REST API credential, it cannot be reused.
DELETE FROM galette_paypal_preferences WHERE nom_pref = 'paypal_id';

INSERT INTO galette_paypal_preferences (nom_pref, val_pref) VALUES ('paypal_client_id', '');
INSERT INTO galette_paypal_preferences (nom_pref, val_pref) VALUES ('paypal_client_secret', '');
INSERT INTO galette_paypal_preferences (nom_pref, val_pref) VALUES ('paypal_webhook_id', '');
INSERT INTO galette_paypal_preferences (nom_pref, val_pref) VALUES ('paypal_sandbox', '0');
INSERT INTO galette_paypal_preferences (nom_pref, val_pref) VALUES ('paypal_currency', 'EUR');

-- MySQL side stores a full timestamp, PostgreSQL used to keep the date only.
ALTER TABLE galette_paypal_history ALTER COLUMN history_date TYPE timestamp;

-- IPN `verify_sign` is replaced by the order identifier.
ALTER TABLE galette_paypal_history DROP COLUMN signature;
ALTER TABLE galette_paypal_history ADD COLUMN order_id character varying(50) NOT NULL DEFAULT '';
ALTER TABLE galette_paypal_history ADD COLUMN capture_id character varying(50) DEFAULT NULL;
ALTER TABLE galette_paypal_history ADD COLUMN currency character varying(3) NOT NULL DEFAULT 'EUR';
ALTER TABLE galette_paypal_history ADD COLUMN id_adh integer DEFAULT NULL;
ALTER TABLE galette_paypal_history ADD COLUMN id_type_cotis integer DEFAULT NULL;
ALTER TABLE galette_paypal_history ADD COLUMN id_cotis integer DEFAULT NULL;

-- Legacy IPN entries have no order identifier; they get a synthetic one so the
-- unique index below can be created and idempotency checks stay reliable.
UPDATE galette_paypal_history SET order_id = 'legacy-' || id_paypal WHERE order_id = '';

CREATE UNIQUE INDEX galette_paypal_history_order_idx ON galette_paypal_history (order_id);
ALTER TABLE galette_paypal_history ALTER COLUMN order_id DROP DEFAULT;
