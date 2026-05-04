--
-- This file is part of Galette Paypal plugin (https://galette.eu).
-- SPDX-FileCopyrightText: Copyright © 2011-2026 The Galette Team
-- SPDX-License-Identifier: GPL-3.0-or-later
--

DROP TABLE IF EXISTS galette_paypal_history;
CREATE TABLE galette_paypal_history (
  id_paypal int(11) NOT NULL auto_increment,
  history_date datetime NOT NULL,
  amount double NOT NULL,
  comments varchar(255)  COLLATE utf8_unicode_ci,
  request text COLLATE utf8_unicode_ci,
  signature varchar(255) NOT NULL,
  state tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_paypal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

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
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

INSERT INTO galette_paypal_preferences (nom_pref, val_pref) VALUES ('paypal_id', '');
INSERT INTO galette_paypal_preferences (nom_pref, val_pref) VALUES ('paypal_inactives', '4,6,7');
