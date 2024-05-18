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
     *
     * @return Response
     */
    public function form(Request $request, Response $response): Response
    {
        $paypal = new Paypal($this->zdb);

        $current_url = $this->preferences->getURL();

        $params = [
            'paypal' => $paypal,
            'amounts' => $paypal->getAmounts($this->login),
            'page_title' => _T('Paypal payment', 'paypal'),
            'current_url' => rtrim($current_url, '/')
        ];

        if ($this->login->isLogged() && !$this->login->isSuperAdmin()) {
            $params['custom'] = $this->login->id;
        }

        if (!$paypal->isLoaded()) {
            $this->flash->addMessageNow(
                'error',
                _T("<strong>Payment could not work</strong>: An error occurred (that has been logged) while loading Paypal preferences from database.<br/>Please report the issue to the staff.", "paypal") .
                '<br/>' . _T("Our apologies for the annoyance :(", "paypal")
            );
        }

        if ($paypal->getId() == null) {
            $this->flash->addMessageNow(
                'error',
                _T("Paypal id has not been defined. Please ask an administrator to add it from plugin preferences.", "paypal")
            );
        }

        if (!$paypal->areAmountsLoaded()) {
            $this->flash->addMessageNow(
                'warning',
                _T("Predefined amounts cannot be loaded, that is not a critical error.", "paypal")
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
     * Logs page
     *
     * @param Request         $request  PSR Request
     * @param Response        $response PSR Response
     * @param string|null     $option   Either order, reset or page
     * @param string|int|null $value    Option value
     *
     * @return Response
     */
    public function logs(
        Request $request,
        Response $response,
        string $option = null,
        string|int $value = null
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
        $filters->setViewPagination($this->routeparser, $this->view);

        $params = [
            'page_title' => _T("Paypal History", "paypal"),
            'paypal_history' => $paypal_history,
            'logs' => $logs,
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
     *
     * @return Response
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
     *
     * @return Response
     */
    public function preferences(Request $request, Response $response): Response
    {
        if ($this->session->paypal !== null) {
            $paypal = $this->session->paypal;
            $this->session->paypal = null;
        } else {
            $paypal = new Paypal($this->zdb);
        }

        $amounts = $paypal->getAllAmounts();
        $params = [
            'page_title'    => _T('Paypal Settings', 'paypal'),
            'paypal'        => $paypal,
            'amounts'       => $amounts
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
     *
     * @return Response
     */
    public function storePreferences(Request $request, Response $response): Response
    {
        $post = $request->getParsedBody();
        $paypal = new Paypal($this->zdb);

        if (isset($post['amounts'])) {
            if (isset($post['paypal_id']) && $this->login->isAdmin()) {
                $paypal->setId($post['paypal_id']);
            }
            if (isset($post['amount_id'])) {
                $paypal->setPrices($post['amount_id'], $post['amounts']);
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
     *
     * @return Response
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
     *
     * @return Response
     */
    public function success(Request $request, Response $response): Response
    {
        $paypal_request = $request->getParsedBody();
        if (isset($paypal_request['charset'])) {
            foreach ($paypal_request as $key => $value) {
                $paypal_request[$key] = iconv($paypal_request['charset'], 'UTF-8', $value);
            }
        }

        $params = [
            'page_title'    => _T('Paypal payment success', 'paypal'),
            'post'          => $paypal_request,
        ];

        $this->flash->addMessage(
            'success_detected',
            _T('Your payment has been proceeded!', 'paypal')
        );

        /*print_r($paypal_request);
        Array
        (
            [mc_gross] => 10.00
            [protection_eligibility] => Ineligible
            [payer_id] => 9EQBXB6VP6TQS
            [tax] => 0.00
            [payment_date] => 14:53:16 Jun 08, 2011 PDT
            [payment_status] => Pending
            [charset] => windows-1252
            [first_name] => Test
            [mc_fee] => 0.64
            [notify_version] => 3.1
            [custom] =>
            [payer_status] => verified
            [business] => asso_1307082004_biz@x-tnd.be
            [quantity] => 1
            [payer_email] => member_1307082133_per@x-tnd.be
            [verify_sign] => AGpFW7lEeJ4C3fJFmc0C7AHLr-I2AOJDPv4h16f.LTWzTPmEMGaw-Z.K
            [txn_id] => 37S45593SX696710D
            [payment_type] => instant
            [last_name] => User
            [receiver_email] => asso_1307082004_biz@x-tnd.be
            [payment_fee] =>
            [receiver_id] => 7ZPFDK9375A6C
            [pending_reason] => paymentreview
            [txn_type] => web_accept
            [item_name] => cotisation annuelle réduite
            [mc_currency] => EUR
            [item_number] =>
            [residence_country] => US
            [test_ipn] => 1
            [handling_amount] => 0.00
            [transaction_subject] => cotisation annuelle réduite
            [payment_gross] =>
            [shipping] => 0.00
            [merchant_return_link] => Go back to %s Website to complete your inscription. (not tra
        )
        */

        // display page
        $this->view->render(
            $response,
            $this->getTemplate('paypal_success'),
            $params
        );
        return $response;
    }

    /**
     * Notify
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     *
     * @return Response
     */
    public function notify(Request $request, Response $response): Response
    {
        $post = $request->getParsedBody();

        //if we've received some informations from paypal website, we can proceed
        if (isset($post['mc_gross'], $post['item_number'])) {
            if (isset($post['charset'])) {
                foreach ($post as $key => $value) {
                    $post[$key] = iconv($post['charset'], 'UTF-8', $value);
                }
            }

            $ph = new PaypalHistory($this->zdb, $this->login, $this->preferences);
            $ph->add($post);

            $s = null;
            foreach ($post as $k => $v) {
                if ($s !== null) {
                    $s .= ' | ';
                }
                $s .= $k . '=' . $v;
            }

            Analog::log($s, Analog::DEBUG);

            //are we working on a real contribution?
            $real_contrib = false;
            if (
                isset($post['custom'])
                && is_numeric($post['custom'])
                && $post['payment_status'] == 'Completed'
            ) {
                $real_contrib = true;
            }

            if ($ph->isProcessed($post['verify_sign'])) {
                Analog::log(
                    'A paypal payment notification has been received, but it is already processed!',
                    Analog::WARNING
                );
                $ph->setState(PaypalHistory::STATE_ALREADYDONE);
            } else {
                //we'll now try to add the relevant cotisation
                if ($post['payment_status'] == 'Completed') {
                    /**
                     * We will use the following parameters:
                     * - mc_gross: the amount
                     * - custom: member id
                     * - item_number: contribution type id
                     *
                     * If no member id is provided, we only send to post contribution
                     * script, Galette does not handle anonymous contributions
                     */
                    $args = array(
                        'type'          => $post['item_number'],
                        'adh'           => $post['custom'],
                        'payment_type'  => PaymentType::PAYPAL
                    );
                    if ($this->preferences->pref_membership_ext != '') {
                        $args['ext'] = $this->preferences->pref_membership_ext;
                    }
                    $contrib = new Contribution($this->zdb, $this->login, $args);
                    $post = [
                        ContributionsTypes::PK  => $post['item_number'],
                        Adherent::PK            => $post['custom'],
                        'type_paiement_cotis'   => PaymentType::PAYPAL,
                        'montant_cotis'         => $post['mc_gross']
                    ];

                    //all goes well, we can proceed
                    if ($real_contrib) {
                        $store = false;
                        $valid = $contrib->check($post, [], []);
                        if ($valid !== true) {
                            Analog::log(
                                'An error occurred while storing a new contribution from Paypal payment:' .
                                implode("\n   ", $valid),
                                Analog::ERROR
                            );
                            $ph->setState(PaypalHistory::STATE_ERROR);
                            return $response->withStatus(500, 'Internal error');
                        }

                        $store = $contrib->store();
                        if ($store === true) {
                            //contribution has been stored :)
                            Analog::log(
                                'Paypal payment has been successfully registered as a contribution',
                                Analog::INFO
                            );
                            $ph->setState(PaypalHistory::STATE_PROCESSED);
                        } else {
                            //something went wrong :'(
                            Analog::log(
                                'An error occurred while storing a new contribution from Paypal payment',
                                Analog::ERROR
                            );
                            $ph->setState(PaypalHistory::STATE_ERROR);
                            return $response->withStatus(500, 'Internal error');
                        }
                    }
                    return $response->withStatus(200);
                } else {
                    Analog::log(
                        'A paypal payment notification has been received, but is not completed!',
                        Analog::WARNING
                    );
                    $ph->setState(PaypalHistory::STATE_INCOMPLETE);
                    return $response->withStatus(500, 'Incomplete request');
                }
            }
            return $response->withStatus(200);
        } else {
            Analog::log(
                'Paypal notify URL call without required arguments!',
                Analog::ERROR
            );
            return $response->withStatus(500, 'Missing required arguments');
        }
    }
}
