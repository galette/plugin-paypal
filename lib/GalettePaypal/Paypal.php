<?php

/**
 * This file is part of Galette Paypal plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2011-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GalettePaypal;

use Analog\Analog;
use Galette\Core\Db;
use Galette\Core\Login;
use Galette\Core\Preferences;
use Galette\Entity\ContributionsTypes;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

/**
 * Preferences and Standard Checkout client for Paypal
 *
 * Relies on the Orders v2 REST API. The legacy Website Payments Standard
 * integration (`cmd=_xclick` form and IPN) is discontinued by Paypal.
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Paypal
{
    public const string TABLE = 'preferences';

    public const string API_LIVE = 'https://api-m.paypal.com';
    public const string API_SANDBOX = 'https://api-m.sandbox.paypal.com';

    private Db $zdb;
    private Preferences $preferences;

    /** @var array<int, array<string,mixed>> */
    private array $prices = [];
    /** @var array<int, string> */
    private array $inactives = [];
    /** @var array<int, string> Preferences names actually present in the database */
    private array $stored_prefs = [];

    private string $client_id = '';
    private string $client_secret = '';
    private string $webhook_id = '';
    private bool $sandbox = false;
    private string $currency = 'EUR';

    private bool $loaded = false;
    private bool $amounts_loaded = false;

    private ?ClientInterface $http_client = null;
    private ?string $access_token = null;

    /**
     * Default constructor
     *
     * @param Db          $zdb         Database instance
     * @param Preferences $preferences Galette preferences
     */
    public function __construct(Db $zdb, Preferences $preferences)
    {
        $this->zdb = $zdb;
        $this->preferences = $preferences;
        $this->load();
    }

    /**
     * Load preferences from the database and amounts from core contributions types
     */
    public function load(): void
    {
        try {
            $results = $this->zdb->selectAll(PAYPAL_PREFIX . self::TABLE);

            $this->stored_prefs = [];
            /** @var \ArrayObject<string, mixed> $row */
            foreach ($results as $row) {
                $this->stored_prefs[] = (string)$row->nom_pref;
                switch ($row->nom_pref) {
                    case 'paypal_client_id':
                        $this->client_id = (string)$row->val_pref;
                        break;
                    case 'paypal_client_secret':
                        $this->client_secret = (string)$row->val_pref;
                        break;
                    case 'paypal_webhook_id':
                        $this->webhook_id = (string)$row->val_pref;
                        break;
                    case 'paypal_sandbox':
                        $this->sandbox = (bool)(int)$row->val_pref;
                        break;
                    case 'paypal_currency':
                        if ($row->val_pref != '') {
                            $this->currency = strtoupper((string)$row->val_pref);
                        }
                        break;
                    case 'paypal_inactives':
                        $this->inactives = $row->val_pref == ''
                            ? []
                            : explode(',', (string)$row->val_pref);
                        break;
                    default:
                        //we've got a preference not intended
                        Analog::log(
                            '[' . get_class($this) . '] unknown preference `'
                            . $row->nom_pref . '` in the database.',
                            Analog::WARNING
                        );
                }
            }
            $this->loaded = true;
            $this->loadContributionsTypes();
        } catch (\Exception $e) {
            Analog::log(
                '[' . get_class($this) . '] Cannot load paypal preferences |'
                . $e->getMessage(),
                Analog::ERROR
            );
            //consider plugin is not loaded when missing the main preferences
            $this->loaded = false;
        }
    }

    /**
     * Load amounts from core contributions types
     */
    private function loadContributionsTypes(): void
    {
        try {
            $ct = new ContributionsTypes($this->zdb);
            $this->prices = $ct->getCompleteList();
            //amounts should be loaded here
            $this->amounts_loaded = true;
        } catch (\Exception $e) {
            Analog::log(
                '[' . get_class($this) . '] Cannot load amounts from core contributions types'
                . '` | ' . $e->getMessage(),
                Analog::ERROR
            );
            //amounts are not loaded at this point
            $this->amounts_loaded = false;
        }
    }

    /**
     * Store values in the database
     */
    public function store(): bool
    {
        $values = [
            'paypal_client_id'      => $this->client_id,
            'paypal_client_secret'  => $this->client_secret,
            'paypal_webhook_id'     => $this->webhook_id,
            'paypal_sandbox'        => $this->sandbox ? '1' : '0',
            'paypal_currency'       => $this->currency,
            'paypal_inactives'      => implode(',', $this->inactives)
        ];

        try {
            foreach ($values as $name => $value) {
                if (in_array($name, $this->stored_prefs, true)) {
                    $update = $this->zdb->update(PAYPAL_PREFIX . self::TABLE);
                    $update
                        ->set(['val_pref' => $value])
                        ->where(['nom_pref' => $name]);
                    $this->zdb->execute($update);
                } else {
                    //preference does not exist yet, add it
                    $insert = $this->zdb->insert(PAYPAL_PREFIX . self::TABLE);
                    $insert->values([
                        'nom_pref' => $name,
                        'val_pref' => $value
                    ]);
                    $this->zdb->execute($insert);
                    $this->stored_prefs[] = $name;
                }
            }

            Analog::log(
                '[' . get_class($this)
                . '] Paypal preferences were successfully stored',
                Analog::INFO
            );

            return true;
        } catch (\Exception $e) {
            Analog::log(
                '[' . get_class($this) . '] Cannot store paypal preferences'
                . '` | ' . $e->getMessage(),
                Analog::ERROR
            );
            return false;
        }
    }

    /**
     * Is the plugin loaded?
     */
    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * Are amounts loaded?
     */
    public function areAmountsLoaded(): bool
    {
        return $this->amounts_loaded;
    }

    /**
     * Has the plugin everything it needs to talk to Paypal?
     */
    public function isConfigured(): bool
    {
        return $this->loaded
            && $this->client_id !== ''
            && $this->client_secret !== ''
            && $this->webhook_id !== '';
    }

    /**
     * Get Paypal REST application client identifier
     */
    public function getClientId(): string
    {
        return $this->client_id;
    }

    /**
     * Set Paypal REST application client identifier
     *
     * @param string $client_id Client identifier
     */
    public function setClientId(string $client_id): void
    {
        $this->client_id = trim($client_id);
    }

    /**
     * Get Paypal REST application client secret
     */
    public function getClientSecret(): string
    {
        return $this->client_secret;
    }

    /**
     * Set Paypal REST application client secret
     *
     * @param string $client_secret Client secret
     */
    public function setClientSecret(string $client_secret): void
    {
        $this->client_secret = trim($client_secret);
    }

    /**
     * Get the identifier of the webhook declared on Paypal side
     */
    public function getWebhookId(): string
    {
        return $this->webhook_id;
    }

    /**
     * Set the identifier of the webhook declared on Paypal side
     *
     * @param string $webhook_id Webhook identifier
     */
    public function setWebhookId(string $webhook_id): void
    {
        $this->webhook_id = trim($webhook_id);
    }

    /**
     * Are we running against Paypal sandbox?
     */
    public function isSandbox(): bool
    {
        return $this->sandbox;
    }

    /**
     * Set sandbox mode
     *
     * @param bool $sandbox Sandbox mode
     */
    public function setSandbox(bool $sandbox): void
    {
        $this->sandbox = $sandbox;
    }

    /**
     * Get currency payments are made in
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Set currency payments are made in
     *
     * @param string $currency ISO 4217 currency code
     */
    public function setCurrency(string $currency): void
    {
        $currency = strtoupper(trim($currency));
        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            Analog::log(
                '[' . get_class($this) . '] Invalid currency code `' . $currency . '`',
                Analog::WARNING
            );
            return;
        }
        $this->currency = $currency;
    }

    /**
     * Get loaded and active amounts
     *
     * @param Login $login Login instance
     *
     * @return array<int, array<string,mixed>>
     */
    public function getAmounts(Login $login): array
    {
        $prices = [];
        foreach ($this->prices as $k => $v) {
            if (!$this->isInactive($k)) {
                if ($login->isLogged() || $v['extra'] == ContributionsTypes::DONATION_TYPE) {
                    $prices[$k] = $v;
                }
            }
        }
        return $prices;
    }

    /**
     * Get loaded amounts
     *
     * @return array<int, array<string,mixed>>
     */
    public function getAllAmounts(): array
    {
        return $this->prices;
    }

    /**
     * Check if the specified contribution type is inactive
     *
     * @param int $id type identifier
     */
    public function isInactive(int $id): bool
    {
        return in_array($id, $this->inactives);
    }

    /**
     * Set inactives types
     *
     * @param array<int, string> $inactives array of inactives types
     */
    public function setInactives(array $inactives): void
    {
        $this->inactives = $inactives;
    }

    /**
     * Unset inactives types
     */
    public function unsetInactives(): void
    {
        $this->inactives = [];
    }

    /**
     * Get Paypal REST API base URL
     */
    public function getApiBaseUrl(): string
    {
        return $this->sandbox ? self::API_SANDBOX : self::API_LIVE;
    }

    /**
     * Set the HTTP client to use; mainly intended for tests
     *
     * @param ClientInterface $client HTTP client
     */
    public function setHttpClient(ClientInterface $client): void
    {
        $this->http_client = $client;
    }

    /**
     * Get the HTTP client to use
     */
    private function getHttpClient(): ClientInterface
    {
        if ($this->http_client === null) {
            $this->http_client = new Client(['timeout' => 30]);
        }
        return $this->http_client;
    }

    /**
     * Get an OAuth2 access token, using client credentials
     *
     * The token is kept for the current request only; Galette does not need to
     * store it, as a payment involves a couple of calls at most.
     */
    private function getAccessToken(): ?string
    {
        if ($this->access_token !== null) {
            return $this->access_token;
        }

        if ($this->client_id === '' || $this->client_secret === '') {
            Analog::log(
                '[' . get_class($this) . '] Paypal REST credentials are missing',
                Analog::ERROR
            );
            return null;
        }

        try {
            $response = $this->getHttpClient()->request(
                'POST',
                $this->getApiBaseUrl() . '/v1/oauth2/token',
                [
                    'auth'          => [$this->client_id, $this->client_secret],
                    'form_params'   => ['grant_type' => 'client_credentials'],
                    'headers'       => ['Accept' => 'application/json']
                ]
            );
        } catch (GuzzleException $e) {
            Analog::log(
                '[' . get_class($this) . '] Cannot get Paypal access token | ' . $e->getMessage(),
                Analog::ERROR
            );
            return null;
        }

        $data = $this->decode($response);
        if (!isset($data['access_token'])) {
            Analog::log(
                '[' . get_class($this) . '] Paypal did not return any access token',
                Analog::ERROR
            );
            return null;
        }

        $this->access_token = (string)$data['access_token'];
        return $this->access_token;
    }

    /**
     * Create a Paypal order and get the URL the payer must be redirected to
     *
     * @param array<string, mixed> $metadata   member_id (optional), item_id and item_name
     * @param string               $amount     Amount, as a decimal string
     * @param string               $return_url URL Paypal sends the payer back to
     * @param string               $cancel_url URL Paypal sends the payer to on cancellation
     *
     * @return ?array{id: string, payer_action: string}
     */
    public function createOrder(
        array $metadata,
        string $amount,
        string $return_url,
        string $cancel_url
    ): ?array {
        $token = $this->getAccessToken();
        if ($token === null) {
            return null;
        }

        $body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => $this->currency,
                        'value'         => $this->formatAmount($amount)
                    ],
                    'description'   => mb_substr((string)$metadata['item_name'], 0, 127),
                    'custom_id'     => sprintf(
                        '%s:%s',
                        $metadata['member_id'] ?? '',
                        $metadata['item_id']
                    )
                ]
            ],
            'payment_source' => [
                'paypal' => [
                    'experience_context' => [
                        'brand_name'            => mb_substr($this->preferences->pref_nom, 0, 127),
                        'shipping_preference'   => 'NO_SHIPPING',
                        'user_action'           => 'PAY_NOW',
                        'return_url'            => $return_url,
                        'cancel_url'            => $cancel_url
                    ]
                ]
            ]
        ];

        $order = $this->call('POST', '/v2/checkout/orders', $body, $token);
        if ($order === null || !isset($order['id'])) {
            return null;
        }

        $payer_action = null;
        foreach ($order['links'] ?? [] as $link) {
            if (($link['rel'] ?? '') === 'payer-action') {
                $payer_action = (string)$link['href'];
                break;
            }
        }

        if ($payer_action === null) {
            Analog::log(
                '[' . get_class($this) . '] Paypal order ' . $order['id']
                . ' has no payer-action link',
                Analog::ERROR
            );
            return null;
        }

        return [
            'id'            => (string)$order['id'],
            'payer_action'  => $payer_action
        ];
    }

    /**
     * Retrieve an existing order
     *
     * @param string $order_id Paypal order identifier
     *
     * @return ?array<string, mixed>
     */
    public function getOrder(string $order_id): ?array
    {
        $token = $this->getAccessToken();
        if ($token === null) {
            return null;
        }

        return $this->call('GET', '/v2/checkout/orders/' . rawurlencode($order_id), null, $token);
    }

    /**
     * Capture an approved order
     *
     * When the order has already been captured - which happens when both the
     * payer return and the webhook are processed - the existing order is
     * returned instead of an error.
     *
     * @param string $order_id Paypal order identifier
     *
     * @return ?array<string, mixed>
     */
    public function captureOrder(string $order_id): ?array
    {
        $token = $this->getAccessToken();
        if ($token === null) {
            return null;
        }

        $captured = $this->call(
            'POST',
            '/v2/checkout/orders/' . rawurlencode($order_id) . '/capture',
            [],
            $token,
            ['ORDER_ALREADY_CAPTURED']
        );

        if ($captured === null) {
            return null;
        }

        if (isset($captured['galette_already_captured'])) {
            //order was captured by the concurrent code path, get its current state
            return $this->getOrder($order_id);
        }

        return $captured;
    }

    /**
     * Verify a webhook notification actually comes from Paypal
     *
     * @param array<string, string> $headers Relevant `paypal-*` request headers
     * @param string                $body    Raw request body
     */
    public function verifyWebhookSignature(array $headers, string $body): bool
    {
        if ($this->webhook_id === '') {
            Analog::log(
                '[' . get_class($this) . '] No webhook identifier configured, '
                . 'notification cannot be verified',
                Analog::ERROR
            );
            return false;
        }

        $required = [
            'paypal-auth-algo',
            'paypal-cert-url',
            'paypal-transmission-id',
            'paypal-transmission-sig',
            'paypal-transmission-time'
        ];
        foreach ($required as $header) {
            if (!isset($headers[$header]) || $headers[$header] === '') {
                Analog::log(
                    '[' . get_class($this) . '] Missing `' . $header . '` header on notification',
                    Analog::ERROR
                );
                return false;
            }
        }

        $event = json_decode($body, true);
        if (!is_array($event)) {
            Analog::log(
                '[' . get_class($this) . '] Notification body is not valid JSON',
                Analog::ERROR
            );
            return false;
        }

        $token = $this->getAccessToken();
        if ($token === null) {
            return false;
        }

        $result = $this->call(
            'POST',
            '/v1/notifications/verify-webhook-signature',
            [
                'auth_algo'         => $headers['paypal-auth-algo'],
                'cert_url'          => $headers['paypal-cert-url'],
                'transmission_id'   => $headers['paypal-transmission-id'],
                'transmission_sig'  => $headers['paypal-transmission-sig'],
                'transmission_time' => $headers['paypal-transmission-time'],
                'webhook_id'        => $this->webhook_id,
                'webhook_event'     => $event
            ],
            $token
        );

        return ($result['verification_status'] ?? null) === 'SUCCESS';
    }

    /**
     * Perform an authenticated call on Paypal REST API
     *
     * @param string                $method         HTTP method
     * @param string                $path           Path, relative to the API base URL
     * @param ?array<string, mixed> $body           JSON body to send, if any
     * @param string                $token          OAuth2 access token
     * @param array<int, string>    $accepted_issue Paypal issues that must not be
     *                                              treated as errors
     *
     * @return ?array<string, mixed>
     */
    private function call(
        string $method,
        string $path,
        ?array $body,
        string $token,
        array $accepted_issue = []
    ): ?array {
        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json'
            ],
            'http_errors' => false
        ];
        if ($body !== null) {
            $options['json'] = $body;
        }

        try {
            $response = $this->getHttpClient()->request(
                $method,
                $this->getApiBaseUrl() . $path,
                $options
            );
        } catch (GuzzleException $e) {
            Analog::log(
                '[' . get_class($this) . '] Paypal call to ' . $path . ' failed | '
                . $e->getMessage(),
                Analog::ERROR
            );
            return null;
        }

        $data = $this->decode($response);
        $status = $response->getStatusCode();

        if ($status >= 200 && $status < 300) {
            return $data;
        }

        foreach ($data['details'] ?? [] as $detail) {
            if (in_array($detail['issue'] ?? '', $accepted_issue, true)) {
                return ['galette_already_captured' => true];
            }
        }

        Analog::log(
            '[' . get_class($this) . '] Paypal call to ' . $path . ' returned '
            . $status . ' | ' . json_encode($data),
            Analog::ERROR
        );
        return null;
    }

    /**
     * Decode a JSON response body
     *
     * @param ResponseInterface $response HTTP response
     *
     * @return array<string, mixed>
     */
    private function decode(ResponseInterface $response): array
    {
        $decoded = json_decode((string)$response->getBody(), true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Format an amount the way Paypal expects it
     *
     * Paypal takes decimal strings, there is no minor unit conversion to do.
     *
     * @param string $amount Amount
     */
    public function formatAmount(string $amount): string
    {
        return number_format((float)str_replace(',', '.', $amount), 2, '.', '');
    }
}
