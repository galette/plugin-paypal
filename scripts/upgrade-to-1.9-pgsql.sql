ALTER TABLE galette_paypal_history
  ADD COLUMN signature character varying(255) NOT NULL,
  ADD COLUMN state smallint DEFAULT 0 NOT NULL;
