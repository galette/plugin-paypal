<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

use Analog\Analog;
use GalettePaypal\Paypal;
use GalettePaypal\PaypalHistory;
use Galette\Entity\Contribution;
use Galette\Filters\HistoryList;
use Galette\Entity\PaymentType;

/**
 * Maps routes
 *
 * PHP version 5
 *
 * Copyright © 2015 The Galette Team
 *
 * This file is part of Galette (http://galette.tuxfamily.org).
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
 *
 * @category  Plugins
 * @package   GalettePaypal
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2016 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version   SVN: $Id$
 * @link      http://galette.tuxfamily.org
 * @since     0.9dev 2016-11-20
 */

//Constants and classes from plugin
require_once $module['root'] . '/_config.inc.php';

$this->get(
    __('/preferences', 'routes'),
    function ($request, $response, $args) use ($module, $module_id) {
        if ($this->session->paypal !== null) {
            $paypal = $this->session->paypal;
            $this->session->paypal = null;
        } else {
            $paypal = new Paypal($this->zdb);
        }

        $amounts = $paypal->getAllAmounts();
        $params = [
            'page_title'    => _T('Paypal Settings', 'routes'),
            'paypal'        => $paypal,
            'amounts'       => $amounts
        ];

        // display page
        $this->view->render(
            $response,
            'file:[' . $module['route'] . ']paypal_preferences.tpl',
            $params
        );
        return $response;
    }
)->setName('paypal_preferences')->add($authenticate);

$this->post(
    __('/preferences', 'routes'),
    function ($request, $response, $args) use ($module, $module_id) {
        $post = $request->getParsedBody();
        $paypal = new Paypal($this->zdb);

        if (isset($post['amounts'])) {
            if (isset($post['paypal_id']) && $this->login->isAdmin()) {
                $paypal->setId($post['paypal_id']);
            }
            if (isset($post['amount_id']) && isset($post['amounts'])) {
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
                    _T('An error occured saving paypal preferences :(', 'paypal')
                );
            }
        }

        return $response
            ->withStatus(301)
            ->withHeader('Location', $this->router->pathFor('paypal_preferences'));
    }
)->setName('store_paypal_preferences')->add($authenticate);

$this->get(
    __('/form', 'paypal_routes'),
    function ($request, $response) use ($module, $module_id) {
        $paypal = new Paypal($this->zdb);

        $current_url = $this->preferences->getURL();

        $params = [
            'paypal'        => $paypal,
            'amounts'       => $paypal->getAmounts($this->login),
            'page_title'    => _T('Paypal payment', 'paypal'),
            'current_url'   => rtrim($current_url, '/')
        ];

        if ($this->login->isLogged() && !$this->login->isSuperAdmin()) {
            $params['custom'] = $this->login->id;
        }

        // display page
        $this->view->render(
            $response,
            'file:[' . $module['route'] . ']paypal_form.tpl',
            $params
        );
        return $response;
    }
)->setName('paypal_form');

$this->get(
    __('/cancel', 'paypal_routes'),
    function ($request, $response) {
        $this->flash->addMessage(
            'warning_detected',
            _T('Your payment has been aborted!', 'paypal')
        );
        return $response
            ->withStatus(301)
            ->withHeader('Location', $this->router->pathFor('paypal_form'));
    }
)->setName('paypal_cancelled');

$this->post(
    __('/success', 'paypal_routes'),
    function ($request, $response) use ($module) {
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
            'file:[' . $module['route'] . ']paypal_success.tpl',
            $params
        );
        return $response;
    }
)->setName('paypal_success');

$this->post(
    __('/notify', 'paypal_routes'),
    function ($request, $response) {
        $post = $request->getParsedBody();

        //if we've received some informations from paypal website, we can proceed
        if (isset($post['mc_gross']) && isset($post['item_number'])) {
            if (isset($post['charset'])) {
                foreach ($post as $key => $value) {
                    $post[$key] = iconv($post['charset'], 'UTF-8', $value);
                }
            }

            $ph = new PaypalHistory($this->zdb, $this->login);
            $ph->add($post);

            $s = null;
            foreach ($post as $k => $v) {
                if ($s != null) {
                    $s .= ' | ';
                }
                $s .= $k . '=' . $v;
            }

            Analog::log(
                $s,
                Analog::DEBUG
            );

            //are we working on a real contribution?
            $real_contrib = false;
            if (isset($post['custom'])
                && is_numeric($post['custom'])
                && $post['payment_status'] == 'Completed'
            ) {
                $real_contrib = true;
            }

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
                $contrib->amount = $post['mc_gross'];

                //all goes well, we can proceed
                if ($contrib->isCotis() && $real_contrib) {
                    // Check that membership fees does not overlap
                    $overlap = $contrib->checkOverlap();
                    if ($overlap !== true) {
                        if ($overlap === false) {
                            Analog::log(
                                'An eror occured checking overlaping fees :(',
                                Analog::ERROR
                            );
                        } else {
                            //method directly return error message
                            Analog::log(
                                'Error while calculating overlaping fees from paypal payment: ' . $overlap,
                                Analog::ERROR
                            );
                        }
                    }
                }

                if ($real_contrib) {
                    $store = $contrib->store();
                    if ($store === true) {
                        //contribution has been stored :)
                        Analog::log(
                            'Paypal payment has been successfully registered as a contribution',
                            Analog::INFO
                        );
                    } else {
                        //something went wrong :'(
                        Analog::log(
                            'An error occured while storing a new contribution from Paypal payment',
                            Analog::ERROR
                        );
                    }
                }

                //execute post contribution script, if any
                if ($this->preferences->pref_new_contrib_script) {
                    $pp_infos = array();
                    foreach ($post as $k => $v) {
                        $pp_infos['paypal_' . $k] = $v;
                    }
                    $es = new Galette\IO\ExternalScript($this->preferences);
                    $res = $contrib->executePostScript($es, null, $pp_infos);

                    if ($res !== true) {
                        //send admin a mail with all details
                        if ($this->preferences->pref_mail_method > GaletteMail::METHOD_DISABLED) {
                            $mail = new GaletteMail();
                            $mail->setSubject(
                                _T("Post contribution script failed")
                            );
                            /** TODO: only super-admin is contacted here. We should send
                            *  a message to all admins, or propose them a chekbox if
                            *  they don't want to get bored
                            */
                            $mail->setRecipients(
                                array(
                                    $this->preferences->pref_email_newadh => str_replace(
                                        '%asso',
                                        $this->preferences->pref_name,
                                        _T("%asso Galette's admin")
                                    )
                                )
                            );

                            $message = _T("The configured post contribution script has failed.");
                            $message .= "\n" . _T("You can find contribution information and script output below.");
                            $message .= "\n\n";
                            $message .= $res;

                            $mail->setMessage($message);
                            $sent = $mail->send();

                            if (!$sent) {
                                $txt = preg_replace(
                                    array('/%name/', '/%email/'),
                                    array($adh->sname, $adh->email),
                                    _T("A problem happened while sending to admin post contribution notification for user %name (%email) contribution")
                                );
                                $hist->add($txt);

                                $this->flash->addMessage(
                                    'success_detected',
                                    $txt
                                );

                                //Mails are disabled... We log (not safe, but)...
                                Analog::log(
                                    'Post contribution script has failed. Here was the data: ' .
                                    "\n" . print_r($res, true),
                                    Analog::ERROR
                                );
                            }
                        } else {
                            //Mails are disabled... We log (not safe, but)...
                            Analog::log(
                                'Post contribution script has failed. Here was the data: ' .
                                "\n" . print_r($res, true),
                                Analog::ERROR
                            );
                        }
                    }
                }
            } else {
                Analog::log(
                    'A paypal payment notification has been received, but is not completed!',
                    Analog::WARNING
                );
            }
        } else {
            Analog::log(
                'Paypal notify URL call without required arguments!',
                Analog::ERROR
            );
        }
    }
)->setName('paypal_notify');

$this->get(
    __('/logs', 'routes') . '[/{option:' . __('page', 'routes') .'|' .
        __('order', 'routes') .'|' . __('reset', 'routes') .'}/{value}]',
    function ($request, $response, $args) use ($module, $module_id) {
        $paypal_history = new PaypalHistory($this->zdb, $this->login);

        $filters = [];
        if (isset($this->session->filter_paypal_history)) {
            $filters = $this->session->filter_paypal_history;
        } else {
            $filters = new HistoryList();
        }

        $option = null;
        if (isset($args['option'])) {
            $option = $args['option'];
        }
        $value = null;
        if (isset($args['value'])) {
            $value = $args['value'];
        }

        if ($option !== null) {
            switch ($option) {
                case __('page', 'routes'):
                    $filters->current_page = (int)$value;
                    break;
                case __('order', 'routes'):
                    $filters->orderby = $value;
                    break;
                case __('reset', 'routes'):
                    $filters = new HistoryList();
                    break;
            }
        }
        $this->session->filter_paypal_history = $filters;

        //assign pagination variables to the template and add pagination links
        $paypal_history->setFilters($filters);
        $logs = $paypal_history->getPaypalHistory();
        $filters->setSmartyPagination($this->router, $this->view->getSmarty());

        $params = [
            'page_title'        => _T("Paypal History"),
            'paypal_history'    => $paypal_history,
            'logs'              => $logs,
            'module_id'         => $module_id
        ];

        $this->session->filter_paypal_history = $filters;

        // display page
        $this->view->render(
            $response,
            'file:[' . $module['route'] . ']paypal_history.tpl',
            $params
        );
        return $response;
    }
)->setName('paypal_history')->add($authenticate);

//history filtering
$this->post(
    __('/history/filter', 'paypal_routes'),
    function ($request, $response) {
        $post = $request->getParsedBody();

        //reset history
        $filters = [];
        if (isset($post['reset'])) {
        } else {
            //number of rows to show
            if (isset($post['nbshow'])) {
                $filters['show'] = $post['nbshow'];
            }
        }

        $this->session->filter_paypal_history = $filters;

        return $response
            ->withStatus(301)
            ->withHeader('Location', $this->router->pathFor('paypal_history'));
    }
)->setName('filter_paypal_history')->add($authenticate);
