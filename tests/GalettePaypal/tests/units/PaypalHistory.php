<?php

/**
 * This file is part of Galette Paypal plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2011-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GalettePaypal\tests\units;

use Analog\Analog;
use Galette\Filters\HistoryList;
use Galette\Tests\GaletteTestCase;

/**
 * Paypal history tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class PaypalHistory extends GaletteTestCase
{
    protected int $seed = 20240518135530;

    /**
     * Cleanup after each test
     */
    public function tearDown(): void
    {
        $delete = $this->zdb->delete(PAYPAL_PREFIX . \GalettePaypal\PaypalHistory::TABLE);
        $this->zdb->execute($delete);

        parent::tearDown();
    }

    /**
     * Get an history instance
     */
    private function getHistory(): \GalettePaypal\PaypalHistory
    {
        return new \GalettePaypal\PaypalHistory($this->zdb, $this->login, $this->preferences);
    }

    /**
     * Test a pending order is registered, and can be read back
     */
    public function testAddPending(): void
    {
        $ph = $this->getHistory();
        $this->assertTrue($ph->addPending('ORDER-1', 25.0, 'EUR', 42, 3, 'annual fee'));

        $entry = $ph->loadByOrderId('ORDER-1');
        $this->assertIsArray($entry);
        $this->assertSame('ORDER-1', $entry['order_id']);
        $this->assertEquals(25.0, (float)$entry['amount']);
        $this->assertSame('EUR', $entry['currency']);
        $this->assertEquals(42, (int)$entry['id_adh']);
        $this->assertEquals(3, (int)$entry['id_type_cotis']);
        $this->assertSame('annual fee', $entry['comments']);
        $this->assertEquals(
            \GalettePaypal\PaypalHistory::STATE_PENDING,
            (int)$entry['state']
        );
    }

    /**
     * Test an unknown order is reported as such
     */
    public function testLoadUnknownOrder(): void
    {
        $this->assertNull($this->getHistory()->loadByOrderId('NOPE'));
    }

    /**
     * Test the very same order cannot be registered twice
     */
    public function testOrderIsUnique(): void
    {
        $ph = $this->getHistory();
        $this->assertTrue($ph->addPending('ORDER-2', 25.0, 'EUR', 42, 3, 'annual fee'));
        $this->assertFalse($ph->addPending('ORDER-2', 25.0, 'EUR', 42, 3, 'annual fee'));
        $this->expectLogEntry(Analog::WARNING, 'ORDER-2 is already registered');
    }

    /**
     * Test only one caller can claim an order
     *
     * This is what keeps the payer return and the webhook notification from
     * both creating a contribution for the same payment.
     */
    public function testClaimIsExclusive(): void
    {
        $this->getHistory()->addPending('ORDER-3', 25.0, 'EUR', 42, 3, 'annual fee');

        //two independent instances, as the payer return and the webhook are
        //two distinct requests
        $first = $this->getHistory();
        $second = $this->getHistory();

        $this->assertTrue($first->claim('ORDER-3'));
        $this->assertFalse($second->claim('ORDER-3'));

        $entry = $first->loadByOrderId('ORDER-3');
        $this->assertIsArray($entry);
        $this->assertEquals(
            \GalettePaypal\PaypalHistory::STATE_CAPTURING,
            (int)$entry['state']
        );
    }

    /**
     * Test an unknown order cannot be claimed
     */
    public function testClaimUnknownOrder(): void
    {
        $this->assertFalse($this->getHistory()->claim('NOPE'));
    }

    /**
     * Test state, outcome and contribution are stored on the claimed entry
     */
    public function testSetters(): void
    {
        $ph = $this->getHistory();
        $ph->addPending('ORDER-4', 25.0, 'EUR', 42, 3, 'annual fee');
        $this->assertTrue($ph->claim('ORDER-4'));

        $this->assertTrue($ph->setOutcome(['id' => 'ORDER-4', 'status' => 'COMPLETED'], 'CAPTURE-4'));
        $this->assertTrue($ph->setContribution(1234));
        $this->assertTrue($ph->setState(\GalettePaypal\PaypalHistory::STATE_PROCESSED));

        $entry = $ph->loadByOrderId('ORDER-4');
        $this->assertIsArray($entry);
        $this->assertSame('CAPTURE-4', $entry['capture_id']);
        $this->assertEquals(1234, (int)$entry['id_cotis']);
        $this->assertEquals(
            \GalettePaypal\PaypalHistory::STATE_PROCESSED,
            (int)$entry['state']
        );
        $this->assertStringContainsString('COMPLETED', (string)$entry['request']);
    }

    /**
     * Test entries can be listed, even when no request has been stored yet
     */
    public function testGetPaypalHistory(): void
    {
        $ph = $this->getHistory();
        $ph->addPending('ORDER-5', 25.0, 'EUR', null, 3, 'donation in money');

        $listing = $this->getHistory();
        $listing->setFilters(new HistoryList());
        $logs = $listing->getPaypalHistory();

        //entries left over from a previous version are still listed, so we look
        //for ours rather than assuming the table is empty
        $ours = array_values(
            array_filter($logs, static fn(object $log): bool => $log['order_id'] === 'ORDER-5')
        );
        $this->assertCount(1, $ours);
        $this->assertSame('donation in money', $ours[0]['comments']);
        $this->assertSame('', $ours[0]['member_fullname']);
        $this->assertSame([], $ours[0]['request']);
    }

    /**
     * Test add() is not the way to create entries
     */
    public function testAddIsRefused(): void
    {
        $this->assertFalse($this->getHistory()->add(['whatever']));
        $this->expectLogEntry(Analog::ERROR, 'entries are created from addPending()');
    }
}
