<?php

/**
 * Copyright © 2003-2026 The Galette Team
 *
 * This file is part of Galette (https://galette.eu).
 *
 * Galette is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Galette is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Galette. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace GalettePaypal\tests\units;

use Galette\Tests\GaletteTestCase;

/**
 * Paypal tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Paypal extends GaletteTestCase
{
    protected int $seed = 20240518135530;

    /**
     * Test empty
     */
    public function testEmpty(): void
    {
        $paypal = new \GalettePaypal\Paypal($this->zdb);
        $this->assertSame('', $paypal->getId());

        $amounts = $paypal->getAmounts($this->login);
        $this->assertCount(1, $amounts);

        $ctype = new \Galette\Entity\ContributionsTypes($this->zdb);
        $ctype_id = $ctype->getIdByLabel('donation in money');
        $this->assertEquals(
            [
                $ctype_id => [
                    'name' => 'donation in money',
                    'amount' => null,
                    'extra' => '0',
                    'text_orig' => 'donation in money',
                ]
            ],
            $amounts
        );
        $this->assertCount(7, $paypal->getAllAmounts());
        $this->assertTrue($paypal->areAmountsLoaded());
        $this->assertTrue($paypal->isLoaded());
    }

    /**
     * Test getFormURL method
     */
    public function testGetFormURL(): void
    {
        $paypal = new \GalettePaypal\Paypal($this->zdb);
        $this->assertStringContainsString(
            'paypal.com',
            $paypal->getFormURL()
        );
    }

    /**
     * Test IPNValidationURL method
     */
    public function testGetIPNValidationURL(): void
    {
        $paypal = new \GalettePaypal\Paypal($this->zdb);
        $this->assertStringContainsString(
            'paypal.com',
            $paypal->getIPNValidationURL()
        );
    }

    /**
     * Test validateRequest method
     */
    public function testValidateRequest(): void
    {
        $paypal = new \GalettePaypal\Paypal($this->zdb);
        $this->assertFalse($paypal->validateRequest([]));
        $this->assertFalse($paypal->validateRequest(['mc_gross' => 10.0]));
        $this->assertFalse($paypal->validateRequest(['item_number' => 42]));
        $this->assertTrue($paypal->validateRequest(['mc_gross' => 10.0, 'item_number' => 42]));
    }
}
