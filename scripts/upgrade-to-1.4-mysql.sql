--
-- This file is part of Galette Paypal plugin (https://galette.eu).
-- SPDX-FileCopyrightText: Copyright © 2011-2026 The Galette Team
-- SPDX-License-Identifier: GPL-3.0-or-later
--

ALTER TABLE galette_paypal_types_cotisation_prices 
  DROP FOREIGN KEY galette_cotisation_price;
ALTER TABLE galette_paypal_types_cotisation_prices 
  ADD CONSTRAINT galette_cotisation_price 
    FOREIGN KEY (id_type_cotis) REFERENCES galette_types_cotisation(id_type_cotis) ON DELETE CASCADE;
