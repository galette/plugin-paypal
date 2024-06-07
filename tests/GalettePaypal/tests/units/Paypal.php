<?php

/**
 * Copyright © 2003-2024 The Galette Team
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

namespace GalettePaypal\tests\units;

use Galette\GaletteTestCase;

/**
 * Paypal tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Paypal extends GaletteTestCase
{
    protected int $seed = 20240518135530;

    /**
     * Cleanup after each test method
     *
     * @return void
     */
    public function tearDown(): void
    {
        $delete = $this->zdb->delete(PAYPAL_PREFIX . \GalettePaypal\Paypal::TABLE);
        $this->zdb->execute($delete);
        parent::tearDown();
    }

    /**
     * Test empty
     *
     * @return void
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
                    'extra' => 0
                ]
            ],
            $amounts
        );
        $this->assertCount(7, $paypal->getAllAmounts());
        $this->assertTrue($paypal->areAmountsLoaded());
        $this->assertTrue($paypal->isLoaded());
    }
}
