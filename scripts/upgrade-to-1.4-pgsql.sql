--
-- This file is part of Galette Paypal plugin (https://galette.eu).
-- SPDX-FileCopyrightText: Copyright © 2011-2026 The Galette Team
-- SPDX-License-Identifier: GPL-3.0-or-later
--

ALTER TABLE galette_paypal_types_cotisation_prices
  DROP CONSTRAINT galette_paypal_types_cotisation_prices_id_type_cotis_fkey,
  ADD CONSTRAINT galette_paypal_types_cotisation_prices_id_type_cotis_fkey
    FOREIGN KEY (id_type_cotis) REFERENCES galette_types_cotisation ON DELETE CASCADE;
