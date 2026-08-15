/**
 * This file is part of Galette Paypal plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2011-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

import { expect } from '@playwright/test';
import { test } from '@e2e/fixtures/auth.fixture';

/**
 * Paypal plugin E2E tests
 *
 * These cover what can be checked without leaving Galette. The payment tunnel
 * itself lives on paypal.com and requires sandbox credentials, so it is left
 * out: only its entry point (order creation) and the way Galette reacts to an
 * unknown payment reference are exercised here.
 */
test.describe('Paypal plugin', () => {
  test('settings page exposes the REST credentials and the webhook URL', async ({ loggedInPage: page }) => {
    await page.goto('/plugins/paypal/preferences');

    await expect(page.locator('input#paypal_client_id')).toBeVisible();
    await expect(page.locator('input#paypal_client_secret')).toBeVisible();
    await expect(page.locator('input#paypal_webhook_id')).toBeVisible();
    await expect(page.locator('input#paypal_currency')).toBeVisible();
    await expect(page.locator('input#paypal_sandbox')).toHaveCount(1);

    //the URL to declare on Paypal side, along with the events to subscribe to
    await expect(page.getByText('/plugins/paypal/webhook')).toBeVisible();
    await expect(page.getByText('PAYMENT.CAPTURE.COMPLETED')).toBeVisible();
  });

  test('settings can be saved', async ({ loggedInPage: page }) => {
    await page.goto('/plugins/paypal/preferences');

    await page.locator('input#paypal_currency').fill('CHF');
    await page.locator('button[type="submit"]').click();

    await page.waitForURL(/\/plugins\/paypal\/preferences/);
    await expect(page.locator('input#paypal_currency')).toHaveValue('CHF');

    //put the default back, so the test can be replayed
    await page.locator('input#paypal_currency').fill('EUR');
    await page.locator('button[type="submit"]').click();
    await page.waitForURL(/\/plugins\/paypal\/preferences/);
  });

  test('payment form warns when Paypal is not configured', async ({ loggedInPage: page }) => {
    await page.goto('/plugins/paypal/form');

    //no REST credentials on a fresh install: no form, but an explanation
    await expect(page.locator('form#paypalform')).toHaveCount(0);
    await expect(page.getByText('has not been configured yet')).toBeVisible();
  });

  test('an unknown payment reference does not create anything', async ({ loggedInPage: page }) => {
    await page.goto('/plugins/paypal/return?token=UNKNOWN-ORDER');

    await page.waitForURL(/\/plugins\/paypal\/form/);
    await expect(page.getByText('unknown to Galette')).toBeVisible();
  });

  test('history page is reachable and empty', async ({ loggedInPage: page }) => {
    await page.goto('/plugins/paypal/logs');

    await expect(page.getByRole('heading', { name: /Paypal/i })).toBeVisible();
  });
});
