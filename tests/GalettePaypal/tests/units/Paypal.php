<?php

/**
 * This file is part of Galette Paypal plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2011-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GalettePaypal\tests\units;

use Analog\Analog;
use Galette\Tests\GaletteTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

/**
 * Paypal tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Paypal extends GaletteTestCase
{
    protected int $seed = 20240518135530;

    /**
     * Cleanup after each test
     */
    public function tearDown(): void
    {
        $paypal = $this->getPaypal();
        $paypal->setClientId('');
        $paypal->setClientSecret('');
        $paypal->setWebhookId('');
        $paypal->setSandbox(false);
        $paypal->setCurrency('EUR');
        $paypal->unsetInactives();
        $paypal->store();

        parent::tearDown();
    }

    /**
     * Get a Paypal instance
     */
    private function getPaypal(): \GalettePaypal\Paypal
    {
        return new \GalettePaypal\Paypal($this->zdb, $this->preferences);
    }

    /**
     * Get a Paypal instance whose HTTP calls are answered by provided responses
     *
     * @param array<int, Response|\Throwable> $responses Queued responses
     */
    private function getMockedPaypal(array $responses): \GalettePaypal\Paypal
    {
        $paypal = $this->getPaypal();
        $paypal->setClientId('client');
        $paypal->setClientSecret('secret');
        $paypal->setWebhookId('WH-TEST');

        $paypal->setHttpClient(
            new Client(['handler' => HandlerStack::create(new MockHandler($responses))])
        );

        return $paypal;
    }

    /**
     * A token response, always needed as the first queued response
     */
    private function tokenResponse(): Response
    {
        return new Response(200, [], (string)json_encode([
            'access_token'  => 'A21AA',
            'expires_in'    => 32400
        ]));
    }

    /**
     * Test empty
     */
    public function testEmpty(): void
    {
        $paypal = $this->getPaypal();

        $this->assertTrue($paypal->isLoaded());
        $this->assertTrue($paypal->areAmountsLoaded());
        $this->assertFalse($paypal->isConfigured());
        $this->assertSame('', $paypal->getClientId());
        $this->assertSame('', $paypal->getClientSecret());
        $this->assertSame('', $paypal->getWebhookId());
        $this->assertFalse($paypal->isSandbox());
        $this->assertSame('EUR', $paypal->getCurrency());

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
                    'description' => '',
                ]
            ],
            $amounts
        );
        $this->assertCount(7, $paypal->getAllAmounts());
    }

    /**
     * Test preferences are stored, and read back
     */
    public function testStore(): void
    {
        $paypal = $this->getPaypal();
        $paypal->setClientId('  a-client-id  ');
        $paypal->setClientSecret('a-client-secret');
        $paypal->setWebhookId('WH-1234');
        $paypal->setSandbox(true);
        $paypal->setCurrency('chf');
        $paypal->setInactives(['1', '2']);

        $this->assertTrue($paypal->store());

        $stored = $this->getPaypal();
        $this->assertSame('a-client-id', $stored->getClientId());
        $this->assertSame('a-client-secret', $stored->getClientSecret());
        $this->assertSame('WH-1234', $stored->getWebhookId());
        $this->assertTrue($stored->isSandbox());
        $this->assertSame('CHF', $stored->getCurrency());
        $this->assertTrue($stored->isInactive(1));
        $this->assertTrue($stored->isInactive(2));
        $this->assertFalse($stored->isInactive(3));
        $this->assertTrue($stored->isConfigured());
    }

    /**
     * Test an invalid currency is refused, and the previous one kept
     */
    public function testInvalidCurrency(): void
    {
        $paypal = $this->getPaypal();
        $paypal->setCurrency('EURO');
        $this->assertSame('EUR', $paypal->getCurrency());
        $this->expectLogEntry(Analog::WARNING, 'Invalid currency code `EURO`');
    }

    /**
     * Test API base URL depends on sandbox mode
     */
    public function testGetApiBaseUrl(): void
    {
        $paypal = $this->getPaypal();
        $this->assertSame(\GalettePaypal\Paypal::API_LIVE, $paypal->getApiBaseUrl());

        $paypal->setSandbox(true);
        $this->assertSame(\GalettePaypal\Paypal::API_SANDBOX, $paypal->getApiBaseUrl());
        $this->assertStringContainsString('sandbox', $paypal->getApiBaseUrl());
    }

    /**
     * Test amounts are formatted the way Paypal expects them
     */
    public function testFormatAmount(): void
    {
        $paypal = $this->getPaypal();
        $this->assertSame('10.00', $paypal->formatAmount('10'));
        $this->assertSame('10.50', $paypal->formatAmount('10.5'));
        $this->assertSame('10.50', $paypal->formatAmount('10,5'));
        $this->assertSame('1234.57', $paypal->formatAmount('1234.567'));
    }

    /**
     * Test order creation
     */
    public function testCreateOrder(): void
    {
        $paypal = $this->getMockedPaypal([
            $this->tokenResponse(),
            new Response(201, [], (string)json_encode([
                'id'    => '5O190127TN364715T',
                'links' => [
                    ['rel' => 'self', 'href' => 'https://api-m.paypal.com/v2/checkout/orders/5O190127TN364715T'],
                    ['rel' => 'payer-action', 'href' => 'https://www.paypal.com/checkoutnow?token=5O190127TN364715T']
                ]
            ]))
        ]);

        $order = $paypal->createOrder(
            ['member_id' => 1, 'item_id' => 3, 'item_name' => 'annual fee'],
            '25',
            'https://example.org/plugins/paypal/return',
            'https://example.org/plugins/paypal/cancel'
        );

        $this->assertIsArray($order);
        $this->assertSame('5O190127TN364715T', $order['id']);
        $this->assertSame(
            'https://www.paypal.com/checkoutnow?token=5O190127TN364715T',
            $order['payer_action']
        );
    }

    /**
     * Test order creation without any payer-action link
     */
    public function testCreateOrderWithoutPayerAction(): void
    {
        $paypal = $this->getMockedPaypal([
            $this->tokenResponse(),
            new Response(201, [], (string)json_encode([
                'id'    => '5O190127TN364715T',
                'links' => [
                    ['rel' => 'self', 'href' => 'https://api-m.paypal.com/v2/checkout/orders/5O190127TN364715T']
                ]
            ]))
        ]);

        $this->assertNull(
            $paypal->createOrder(
                ['member_id' => null, 'item_id' => 3, 'item_name' => 'donation'],
                '25',
                'https://example.org/return',
                'https://example.org/cancel'
            )
        );
        $this->expectLogEntry(Analog::ERROR, 'has no payer-action link');
    }

    /**
     * Test order creation is refused without credentials
     */
    public function testCreateOrderWithoutCredentials(): void
    {
        $paypal = $this->getPaypal();

        $this->assertNull(
            $paypal->createOrder(
                ['member_id' => null, 'item_id' => 3, 'item_name' => 'donation'],
                '25',
                'https://example.org/return',
                'https://example.org/cancel'
            )
        );
        $this->expectLogEntry(Analog::ERROR, 'Paypal REST credentials are missing');
    }

    /**
     * Test order capture
     */
    public function testCaptureOrder(): void
    {
        $paypal = $this->getMockedPaypal([
            $this->tokenResponse(),
            new Response(201, [], (string)json_encode([
                'id'                => '5O190127TN364715T',
                'status'            => 'COMPLETED',
                'purchase_units'    => [
                    [
                        'payments' => [
                            'captures' => [
                                ['id' => '3C679366HH908993F', 'status' => 'COMPLETED']
                            ]
                        ]
                    ]
                ]
            ]))
        ]);

        $order = $paypal->captureOrder('5O190127TN364715T');
        $this->assertIsArray($order);
        $this->assertSame('COMPLETED', $order['status']);
    }

    /**
     * Test an already captured order does not raise an error
     */
    public function testCaptureAlreadyCapturedOrder(): void
    {
        $paypal = $this->getMockedPaypal([
            $this->tokenResponse(),
            //capture attempt, Paypal complains the order is already captured
            new Response(422, [], (string)json_encode([
                'name'      => 'UNPROCESSABLE_ENTITY',
                'details'   => [['issue' => 'ORDER_ALREADY_CAPTURED']]
            ])),
            //the current state of the order is then fetched
            new Response(200, [], (string)json_encode([
                'id'        => '5O190127TN364715T',
                'status'    => 'COMPLETED'
            ]))
        ]);

        $order = $paypal->captureOrder('5O190127TN364715T');
        $this->assertIsArray($order);
        $this->assertSame('COMPLETED', $order['status']);
    }

    /**
     * Test a failing capture
     */
    public function testCaptureOrderFailure(): void
    {
        $paypal = $this->getMockedPaypal([
            $this->tokenResponse(),
            new Response(422, [], (string)json_encode([
                'name'      => 'UNPROCESSABLE_ENTITY',
                'details'   => [['issue' => 'INSTRUMENT_DECLINED']]
            ]))
        ]);

        $this->assertNull($paypal->captureOrder('5O190127TN364715T'));
        $this->expectLogEntry(Analog::ERROR, 'INSTRUMENT_DECLINED');
    }

    /**
     * Test webhook signature verification
     */
    public function testVerifyWebhookSignature(): void
    {
        $headers = [
            'paypal-auth-algo'          => 'SHA256withRSA',
            'paypal-cert-url'           => 'https://api.paypal.com/cert.pem',
            'paypal-transmission-id'    => 'transmission-id',
            'paypal-transmission-sig'   => 'signature',
            'paypal-transmission-time'  => '2026-08-15T10:00:00Z'
        ];
        $body = (string)json_encode(['event_type' => 'PAYMENT.CAPTURE.COMPLETED']);

        $paypal = $this->getMockedPaypal([
            $this->tokenResponse(),
            new Response(200, [], (string)json_encode(['verification_status' => 'SUCCESS']))
        ]);
        $this->assertTrue($paypal->verifyWebhookSignature($headers, $body));

        $paypal = $this->getMockedPaypal([
            $this->tokenResponse(),
            new Response(200, [], (string)json_encode(['verification_status' => 'FAILURE']))
        ]);
        $this->assertFalse($paypal->verifyWebhookSignature($headers, $body));
    }

    /**
     * Test webhook signature verification refuses incomplete requests
     */
    public function testVerifyWebhookSignatureRefusesIncompleteRequests(): void
    {
        $body = (string)json_encode(['event_type' => 'PAYMENT.CAPTURE.COMPLETED']);

        //no webhook identifier configured
        $paypal = $this->getPaypal();
        $this->assertFalse($paypal->verifyWebhookSignature([], $body));
        $this->expectLogEntry(Analog::ERROR, 'No webhook identifier configured');

        //no header at all
        $paypal = $this->getMockedPaypal([]);
        $this->assertFalse($paypal->verifyWebhookSignature([], $body));
        $this->expectLogEntry(Analog::ERROR, 'Missing `paypal-auth-algo` header');

        //a header is missing
        $paypal = $this->getMockedPaypal([]);
        $this->assertFalse(
            $paypal->verifyWebhookSignature(
                [
                    'paypal-auth-algo'          => 'SHA256withRSA',
                    'paypal-cert-url'           => 'https://api.paypal.com/cert.pem',
                    'paypal-transmission-id'    => 'transmission-id',
                    'paypal-transmission-sig'   => '',
                    'paypal-transmission-time'  => '2026-08-15T10:00:00Z'
                ],
                $body
            )
        );
        $this->expectLogEntry(Analog::ERROR, 'Missing `paypal-transmission-sig` header');
    }
}
