--
-- This file is part of Galette Paypal plugin (https://galette.eu).
-- SPDX-FileCopyrightText: Copyright © 2011-2026 The Galette Team
-- SPDX-License-Identifier: GPL-3.0-or-later
--

DROP SEQUENCE IF EXISTS galette_paypal_history_id_seq;
CREATE SEQUENCE galette_paypal_history_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 2147483647
    MINVALUE 1
    CACHE 1;

DROP TABLE IF EXISTS galette_paypal_history;
CREATE TABLE galette_paypal_history (
  id_paypal integer DEFAULT nextval('galette_paypal_history_id_seq'::text) NOT NULL,
  history_date timestamp NOT NULL,
  order_id character varying(50) NOT NULL,
  capture_id character varying(50) DEFAULT NULL,
  amount real NOT NULL,
  currency character varying(3) NOT NULL DEFAULT 'EUR',
  id_adh integer DEFAULT NULL,
  id_type_cotis integer DEFAULT NULL,
  id_cotis integer DEFAULT NULL,
  comments character varying(255),
  request text,
  state smallint DEFAULT 0 NOT NULL,
  PRIMARY KEY (id_paypal)
);

CREATE UNIQUE INDEX galette_paypal_history_order_idx ON galette_paypal_history (order_id);

--
-- Table structure for table `galette_paypal_preferences`
--
DROP SEQUENCE IF EXISTS galette_paypal_preferences_id_seq;
CREATE SEQUENCE galette_paypal_preferences_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 2147483647
    MINVALUE 1
    CACHE 1;

DROP TABLE IF EXISTS galette_paypal_preferences;
CREATE TABLE galette_paypal_preferences (
  id_pref integer DEFAULT nextval('galette_paypal_preferences_id_seq'::text) NOT NULL,
  nom_pref character varying(100) NOT NULL default '',
  val_pref character varying(200) NOT NULL default '',
  PRIMARY KEY  (id_pref)
);

CREATE UNIQUE INDEX galette_paypal_preferences_unique_idx ON galette_paypal_preferences (nom_pref);

INSERT INTO galette_paypal_preferences (nom_pref, val_pref) VALUES ('paypal_client_id', '');
INSERT INTO galette_paypal_preferences (nom_pref, val_pref) VALUES ('paypal_client_secret', '');
INSERT INTO galette_paypal_preferences (nom_pref, val_pref) VALUES ('paypal_webhook_id', '');
INSERT INTO galette_paypal_preferences (nom_pref, val_pref) VALUES ('paypal_sandbox', '0');
INSERT INTO galette_paypal_preferences (nom_pref, val_pref) VALUES ('paypal_currency', 'EUR');
INSERT INTO galette_paypal_preferences (nom_pref, val_pref) VALUES ('paypal_inactives', '4,6,7');
