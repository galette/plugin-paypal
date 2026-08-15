<?php

/**
 * This file is part of Galette Paypal plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2011-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GalettePaypal\Controllers;

use Analog\Analog;
use DI\Attribute\Inject;
use Galette\Controllers\AbstractPluginController;
use Galette\Entity\Adherent;
use Galette\Entity\Contribution;
use Galette\Entity\ContributionsTypes;
use Galette\Entity\PaymentType;
use Galette\Filters\HistoryList;
use GalettePaypal\Paypal;
use GalettePaypal\PaypalHistory;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

/**
 * Galette paypal plugin controller
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */

class PaypalController extends AbstractPluginController
{
    /**
     * @var array<string, mixed>
     */
    #[Inject("Plugin Galette Paypal")]
    protected array $module_info;

    /**
     * Main route
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     */
    public function form(Request $request, Response $response): Response
    {
        $paypal = new Paypal($this->zdb, $this->preferences);

        $params = [
            'paypal' => $paypal,
            'amounts' => $paypal->getAmounts($this->login),
            'page_title' => _T('Paypal payment', 'paypal')
        ];

        if (!$paypal->isLoaded()) {
            $this->flash->addMessageNow(
                'error',
                _T("<strong>Payment could not work</strong>: An error occurred (that has been logged) while loading Paypal preferences from database.<br/>Please report the issue to the staff.", "paypal")
                . '<br/>' . _T("Our apologies for the annoyance :(", "paypal")
            );
        } elseif (!$paypal->isConfigured()) {
            $this->flash->addMessageNow(
                'error',
                _T("Paypal has not been configured yet. Please ask an administrator to fill in the API credentials from plugin preferences.", "paypal")
            );
        }

        // display page
        $this->view->render(
            $response,
            $this->getTemplate('paypal_form'),
            $params
        );
        return $response;
    }

    /**
     * Create the Paypal order and send the payer to Paypal
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     */
    public function formCheckout(Request $request, Response $response): Response
    {
        $post = $request->getParsedBody();
        $paypal = new Paypal($this->zdb, $this->preferences);

        if (!$paypal->isConfigured()) {
            return $this->backToForm(
                $response,
                _T("Paypal has not been configured yet. Please ask an administrator to fill in the API credentials from plugin preferences.", "paypal")
            );
        }

        $amounts = $paypal->getAmounts($this->login);
        $item_id = (int)($post['item_id'] ?? 0);

        if (!isset($amounts[$item_id])) {
            return $this->backToForm(
                $response,
                _T("You have to select an option", "paypal")
            );
        }

        //the amount is never trusted from the browser alone: it must at least
        //match the amount defined for the selected contribution type
        $amount = (float)str_replace(',', '.', (string)($post['amount'] ?? ''));
        $minimum = (float)$amounts[$item_id]['amount'];

        if ($amount <= 0) {
            return $this->backToForm(
                $response,
                _T("Please enter an amount.", "paypal")
            );
        }

        if ($amount < $minimum) {
            return $this->backToForm(
                $response,
                _T("The amount you've entered is lower than the minimum amount for the selected option. Please choose another option or change the amount.", "paypal")
            );
        }

        $member_id = null;
        if ($this->login->isLogged() && !$this->login->isSuperAdmin()) {
            $member_id = (int)$this->login->id;
        }

        $order = $paypal->createOrder(
            [
                'member_id' => $member_id,
                'item_id'   => $item_id,
                'item_name' => $amounts[$item_id]['name']
            ],
            (string)$amount,
            $this->getAbsoluteUrl('paypal_return'),
            $this->getAbsoluteUrl('paypal_cancelled')
        );

        if ($order === null) {
            return $this->backToForm(
                $response,
                _T("An error occurred creating the payment. Please report the issue to the staff.", "paypal")
            );
        }

        $ph = new PaypalHistory($this->zdb, $this->login, $this->preferences);
        if (
            !$ph->addPending(
                $order['id'],
                (float)$paypal->formatAmount((string)$amount),
                $paypal->getCurrency(),
                $member_id,
                $item_id,
                (string)$amounts[$item_id]['name']
            )
        ) {
            return $this->backToForm(
                $response,
                _T("An error occurred creating the payment. Please report the issue to the staff.", "paypal")
            );
        }

        return $response
            ->withStatus(301)
            ->withHeader('Location', $order['payer_action']);
    }

    /**
     * Paypal sends the payer back here once the payment has been approved
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     */
    public function returnUrl(Request $request, Response $response): Response
    {
        $order_id = (string)($request->getQueryParams()['token'] ?? '');

        if ($order_id === '') {
            return $this->backToForm(
                $response,
                _T("Paypal did not provide any payment reference.", "paypal")
            );
        }

        $paypal = new Paypal($this->zdb, $this->preferences);
        $ph = new PaypalHistory($this->zdb, $this->login, $this->preferences);
        $entry = $ph->loadByOrderId($order_id);

        if ($entry === null) {
            Analog::log(
                'Paypal payer came back with an unknown order: ' . $order_id,
                Analog::ERROR
            );
            return $this->backToForm(
                $response,
                _T("This payment is unknown to Galette. Please report the issue to the staff.", "paypal")
            );
        }

        if (!$ph->claim($order_id)) {
            //the webhook got there first; nothing left to do
            return $response
                ->withStatus(301)
                ->withHeader('Location', $this->routeparser->urlFor('paypal_success'));
        }

        if ($this->processOrder($paypal, $ph, $entry, $order_id)) {
            $this->flash->addMessage(
                'success_detected',
                _T('Your payment has been proceeded!', 'paypal')
            );
            return $response
                ->withStatus(301)
                ->withHeader('Location', $this->routeparser->urlFor('paypal_success'));
        }

        return $this->backToForm(
            $response,
            _T("Your payment could not be registered. Please report the issue to the staff.", "paypal")
        );
    }

    /**
     * Webhook; acts as a safety net when the payer never comes back
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     */
    public function webhook(Request $request, Response $response): Response
    {
        $body = (string)$request->getBody();
        $paypal = new Paypal($this->zdb, $this->preferences);

        $headers = [];
        foreach (
            [
                'paypal-auth-algo',
                'paypal-cert-url',
                'paypal-transmission-id',
                'paypal-transmission-sig',
                'paypal-transmission-time'
            ] as $header
        ) {
            $headers[$header] = $request->getHeaderLine($header);
        }

        if (!$paypal->verifyWebhookSignature($headers, $body)) {
            Analog::log(
                'Paypal notification signature could not be verified!',
                Analog::ERROR
            );
            return $response->withStatus(403);
        }

        $event = json_decode($body, true);
        if (!is_array($event)) {
            return $response->withStatus(400);
        }

        $order_id = $this->getOrderId($event);
        if ($order_id === null) {
            Analog::log(
                'Paypal event ignored: ' . ($event['event_type'] ?? 'unknown type'),
                Analog::DEBUG
            );
            return $response->withStatus(200);
        }

        $ph = new PaypalHistory($this->zdb, $this->login, $this->preferences);
        $entry = $ph->loadByOrderId($order_id);

        if ($entry === null) {
            //we never trust the payload alone: no local order, no contribution
            Analog::log(
                'Paypal notification received for an unknown order: ' . $order_id,
                Analog::WARNING
            );
            return $response->withStatus(200);
        }

        if (!$ph->claim($order_id)) {
            Analog::log(
                'A Paypal notification has been received, but the order is already handled!',
                Analog::INFO
            );
            return $response->withStatus(200);
        }

        if (!$this->processOrder($paypal, $ph, $entry, $order_id)) {
            return $response->withStatus(500, 'Internal error');
        }

        return $response->withStatus(200);
    }

    /**
     * Capture an order and register the matching contribution
     *
     * Called both from the payer return and from the webhook; the caller must
     * hold the lock taken by PaypalHistory::claim().
     *
     * @param Paypal               $paypal   Paypal instance
     * @param PaypalHistory        $ph       History entry, already claimed
     * @param array<string, mixed> $entry    History entry values
     * @param string               $order_id Paypal order identifier
     */
    private function processOrder(
        Paypal $paypal,
        PaypalHistory $ph,
        array $entry,
        string $order_id
    ): bool {
        $order = $paypal->captureOrder($order_id);

        if ($order === null) {
            Analog::log(
                'Unable to capture Paypal order ' . $order_id,
                Analog::ERROR
            );
            $ph->setState(PaypalHistory::STATE_ERROR);
            return false;
        }

        $capture = $this->getCapture($order);
        $ph->setOutcome($order, $capture['id'] ?? null);

        if (($capture['status'] ?? null) !== 'COMPLETED') {
            Analog::log(
                'Paypal order ' . $order_id . ' has not been captured: '
                . ($capture['status'] ?? 'no capture found'),
                Analog::WARNING
            );
            $ph->setState(PaypalHistory::STATE_ERROR);
            return false;
        }

        $member_id = $entry['id_adh'] === null ? null : (int)$entry['id_adh'];
        if ($member_id === null) {
            /**
             * Galette does not handle anonymous contributions: the payment is
             * kept in the history, but no contribution is created.
             */
            Analog::log(
                'A Paypal payment has been successfully stored as a public donation',
                Analog::INFO
            );
            $ph->setState(PaypalHistory::STATE_PUBLIC);
            return true;
        }

        return $this->recordContribution($ph, $member_id, (int)$entry['id_type_cotis'], (float)$entry['amount']);
    }

    /**
     * Create the contribution matching a completed payment
     *
     * @param PaypalHistory $ph        History entry, already claimed
     * @param int           $member_id Member identifier
     * @param int           $type_id   Contribution type identifier
     * @param float         $amount    Paid amount
     */
    private function recordContribution(
        PaypalHistory $ph,
        int $member_id,
        int $type_id,
        float $amount
    ): bool {
        $args = [
            'type'          => $type_id,
            'adh'           => $member_id,
            'payment_type'  => PaymentType::PAYPAL
        ];
        if ($this->preferences->pref_membership_ext != '') { //@phpstan-ignore-line
            $args['ext'] = $this->preferences->pref_membership_ext;
        }
        $contrib = new Contribution($this->zdb, $this->login, $args);

        $values = [
            ContributionsTypes::PK  => $type_id,
            Adherent::PK            => $member_id,
            'type_paiement_cotis'   => PaymentType::PAYPAL,
            'montant_cotis'         => $amount
        ];

        $valid = $contrib->setNoCheckLogin()->check($values, [], []);
        if ($valid !== true) {
            Analog::log(
                'An error occurred while storing a new contribution from Paypal payment: '
                . implode("\n   ", $valid),
                Analog::ERROR
            );
            $ph->setState(PaypalHistory::STATE_ERROR);
            return false;
        }

        if (!$contrib->store()) {
            Analog::log(
                'An error occurred while storing a new contribution from Paypal payment',
                Analog::ERROR
            );
            $ph->setState(PaypalHistory::STATE_ERROR);
            return false;
        }

        Analog::log(
            'Paypal payment has been successfully registered as a contribution',
            Analog::INFO
        );
        $ph->setContribution((int)$contrib->id);
        $ph->setState(PaypalHistory::STATE_PROCESSED);

        return true;
    }

    /**
     * Get the order identifier carried by a webhook event, if it is one we handle
     *
     * @param array<string, mixed> $event Decoded webhook event
     */
    private function getOrderId(array $event): ?string
    {
        $resource = $event['resource'] ?? [];

        switch ($event['event_type'] ?? '') {
            case 'CHECKOUT.ORDER.APPROVED':
                return isset($resource['id']) ? (string)$resource['id'] : null;
            case 'PAYMENT.CAPTURE.COMPLETED':
                //a capture links back to its order through its `up` link
                foreach ($resource['links'] ?? [] as $link) {
                    if (($link['rel'] ?? '') === 'up' && isset($link['href'])) {
                        $parts = explode('/', rtrim((string)$link['href'], '/'));
                        return end($parts) ?: null;
                    }
                }
                return null;
            default:
                return null;
        }
    }

    /**
     * Extract the capture out of a captured order
     *
     * @param array<string, mixed> $order Paypal order
     *
     * @return array<string, mixed>
     */
    private function getCapture(array $order): array
    {
        foreach ($order['purchase_units'] ?? [] as $unit) {
            foreach ($unit['payments']['captures'] ?? [] as $capture) {
                return $capture;
            }
        }
        return [];
    }

    /**
     * Build an absolute URL for a plugin route
     *
     * @param string $route_name Route name
     */
    private function getAbsoluteUrl(string $route_name): string
    {
        return rtrim($this->preferences->getURL(), '/')
            . $this->routeparser->urlFor($route_name);
    }

    /**
     * Send the payer back to the payment form, with an explanation
     *
     * @param Response $response PSR Response
     * @param string   $message  Message to display
     */
    private function backToForm(Response $response, string $message): Response
    {
        $this->flash->addMessage('error_detected', $message);

        return $response
            ->withStatus(301)
            ->withHeader('Location', $this->routeparser->urlFor('paypal_form'));
    }

    /**
     * Logs page
     *
     * @param Request         $request  PSR Request
     * @param Response        $response PSR Response
     * @param string|null     $option   Either order, reset or page
     * @param string|int|null $value    Option value
     */
    public function logs(
        Request $request,
        Response $response,
        ?string $option = null,
        string|int|null $value = null
    ): Response {
        $paypal_history = new PaypalHistory($this->zdb, $this->login, $this->preferences);

        $filters = $this->session->filter_paypal_history ?? new HistoryList();

        if ($option !== null) {
            switch ($option) {
                case 'page':
                    $filters->current_page = (int)$value;
                    break;
                case 'order':
                    $filters->orderby = $value;
                    break;
                case 'reset':
                    $filters = new HistoryList();
                    break;
            }
        }
        $this->session->filter_paypal_history = $filters;

        //assign pagination variables to the template and add pagination links
        $paypal_history->setFilters($filters);
        $logs = $paypal_history->getPaypalHistory();
        $logs_count = $paypal_history->getCount();
        $filters->setViewPagination($this->routeparser, $this->view);

        $params = [
            'page_title' => _T("Paypal History", "paypal"),
            'paypal_history' => $paypal_history,
            'logs' => $logs,
            'nb' => $logs_count,
            'module_id' => $this->getModuleId()
        ];

        $this->session->filter_paypal_history = $filters;

        // display page
        $this->view->render(
            $response,
            $this->getTemplate('paypal_history'),
            $params
        );
        return $response;
    }

    /**
     * Filter
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     */
    public function filter(Request $request, Response $response): Response
    {
        $post = $request->getParsedBody();

        //reset history
        $filters = $this->session->filter_paypal_history ?? new HistoryList();
        if (!isset($post['reset']) && isset($post['nbshow'])) {
            //number of rows to show
            $filters->show = $post['nbshow'];
        }

        $this->session->filter_paypal_history = $filters;

        return $response
            ->withStatus(301)
            ->withHeader('Location', $this->routeparser->urlFor('paypal_history'));
    }

    /**
     * Preferences
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     */
    public function preferences(Request $request, Response $response): Response
    {
        if ($this->session->paypal !== null) {
            $paypal = $this->session->paypal;
            $this->session->paypal = null;
        } else {
            $paypal = new Paypal($this->zdb, $this->preferences);
        }

        $params = [
            'page_title'    => _T('Paypal Settings', 'paypal'),
            'paypal'        => $paypal,
            'amounts'       => $paypal->getAllAmounts(),
            'webhook_url'   => $this->getAbsoluteUrl('paypal_webhook')
        ];

        // display page
        $this->view->render(
            $response,
            $this->getTemplate('paypal_preferences'),
            $params
        );
        return $response;
    }

    /**
     * Store Preferences
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     */
    public function storePreferences(Request $request, Response $response): Response
    {
        $post = $request->getParsedBody();
        $paypal = new Paypal($this->zdb, $this->preferences);

        if ($this->login->isAdmin()) {
            if (isset($post['paypal_client_id'])) {
                $paypal->setClientId($post['paypal_client_id']);
            }
            if (isset($post['paypal_client_secret'])) {
                $paypal->setClientSecret($post['paypal_client_secret']);
            }
            if (isset($post['paypal_webhook_id'])) {
                $paypal->setWebhookId($post['paypal_webhook_id']);
            }
            if (isset($post['paypal_currency'])) {
                $paypal->setCurrency($post['paypal_currency']);
            }
            $paypal->setSandbox(isset($post['paypal_sandbox']));
        }

        if (isset($post['inactives'])) {
            $paypal->setInactives($post['inactives']);
        } else {
            $paypal->unsetInactives();
        }

        $stored = $paypal->store();
        if ($stored) {
            $this->flash->addMessage(
                'success_detected',
                _T('Paypal preferences has been saved.', 'paypal')
            );
        } else {
            $this->session->paypal = $paypal;
            $this->flash->addMessage(
                'error_detected',
                _T('An error occurred saving paypal preferences :(', 'paypal')
            );
        }

        return $response
            ->withStatus(301)
            ->withHeader('Location', $this->routeparser->urlFor('paypal_preferences'));
    }

    /**
     * Cancel
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     */
    public function cancel(Request $request, Response $response): Response
    {
        $this->flash->addMessage(
            'warning_detected',
            _T('Your payment has been aborted!', 'paypal')
        );
        return $response
            ->withStatus(301)
            ->withHeader('Location', $this->routeparser->urlFor('paypal_form'));
    }

    /**
     * Success
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     */
    public function success(Request $request, Response $response): Response
    {
        $params = [
            'page_title'    => _T('Paypal payment success', 'paypal')
        ];

        // display page
        $this->view->render(
            $response,
            $this->getTemplate('paypal_success'),
            $params
        );
        return $response;
    }
}
