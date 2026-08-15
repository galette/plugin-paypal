--
-- This file is part of Galette Paypal plugin (https://galette.eu).
-- SPDX-FileCopyrightText: Copyright © 2011-2026 The Galette Team
-- SPDX-License-Identifier: GPL-3.0-or-later
--

DROP TABLE IF EXISTS galette_paypal_history;
CREATE TABLE galette_paypal_history (
  id_paypal int(11) NOT NULL auto_increment,
  history_date datetime NOT NULL,
  order_id varchar(50) NOT NULL,
  capture_id varchar(50) DEFAULT NULL,
  amount double NOT NULL,
  currency varchar(3) NOT NULL DEFAULT 'EUR',
  id_adh int(10) unsigned DEFAULT NULL,
  id_type_cotis int(10) unsigned DEFAULT NULL,
  id_cotis int(10) unsigned DEFAULT NULL,
  comments varchar(255) COLLATE utf8mb4_unicode_ci,
  request text COLLATE utf8mb4_unicode_ci,
  state tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_paypal`),
  UNIQUE KEY `galette_paypal_history_order_idx` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `galette_paypal_preferences`
--
DROP TABLE IF EXISTS galette_paypal_preferences;
CREATE TABLE galette_paypal_preferences (
  id_pref int(10) unsigned NOT NULL auto_increment,
  nom_pref varchar(100) NOT NULL default '',
  val_pref varchar(200) NOT NULL default '',
  PRIMARY KEY  (id_pref),
  UNIQUE KEY(nom_pref)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO galette_paypal_preferences (nom_pref, val_pref) VALUES ('paypal_client_id', '');
INSERT INTO galette_paypal_preferences (nom_pref, val_pref) VALUES ('paypal_client_secret', '');
INSERT INTO galette_paypal_preferences (nom_pref, val_pref) VALUES ('paypal_webhook_id', '');
INSERT INTO galette_paypal_preferences (nom_pref, val_pref) VALUES ('paypal_sandbox', '0');
INSERT INTO galette_paypal_preferences (nom_pref, val_pref) VALUES ('paypal_currency', 'EUR');
INSERT INTO galette_paypal_preferences (nom_pref, val_pref) VALUES ('paypal_inactives', '4,6,7');
