--
-- This file is part of Galette Paypal plugin (https://galette.eu).
-- SPDX-FileCopyrightText: Copyright © 2011-2026 The Galette Team
-- SPDX-License-Identifier: GPL-3.0-or-later
--

ALTER TABLE galette_paypal_history
  ADD COLUMN signature character varying(255) NOT NULL,
  ADD COLUMN state smallint DEFAULT 0 NOT NULL;
