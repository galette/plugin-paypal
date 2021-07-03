ALTER TABLE `galette_paypal_history` ADD `signature` VARCHAR(255) NOT NULL;
ALTER TABLE `galette_paypal_history` ADD `state` tinyint(4) NOT NULL DEFAULT 0;
