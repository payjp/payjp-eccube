<?php

namespace Plugin\PayJp\Controller;

use Eccube\Application;
use Eccube\Application\ApplicationTrait;
use Exception;
use Payjp\Account;
use Plugin\PayJp\PayJpClient;
use Plugin\PayJp\PayJpEvent;
use Symfony\Component\HttpFoundation\Request;
use Eccube\Controller\AbstractController;
use Plugin\PayJp\Entity\PayJpConfig;
use Plugin\PayJp\Entity;


class ApiController extends AbstractController {

    private $app;

    private $locale;

    public function charge(ApplicationTrait $app, Request $request) {
        $this->initCommon($app);

        $order_id = $request->get('order_id', null);

        $PayJpOrder = $this->app['orm.em']->getRepository('Plugin\PayJp\Entity\PayJpOrder')->findOneBy(array('order_id' => $order_id));
        if (is_null($PayJpOrder)) {
            return '';
        }

        $chargeId = $PayJpOrder->getPayJpChargeId();
        if (is_null($chargeId)) {
            return '';
        }
        return $chargeId;
    }

    public function key_check(ApplicationTrait $app, Request $request) {
        $this->initCommon($app);

        $sk = $request->request->get('sk');
        \Payjp\Payjp::setApiKey($sk);
        try {
            $res = Account::retrieve();
        } catch (\Payjp\Error\Authentication $e) {
            return PayJpEvent::getLocalizedString('failed_invalid_api_key', $this->locale);
        } catch (Exception $e) {
            $body = $e->getJsonBody();
            return PayJpEvent::getLocalizedString('error_occur', $this->locale) . PayJpClient::getErrorMessageFromCode($body['error'], $this->locale);
        }

        if (isset($res['created'])) {
            $ret = PayJpEvent::getLocalizedString('succeed', $this->locale) . '<br />';
            $ret .= 'ID: ' . $res['id'] . '<br />';
            $ret .= PayJpEvent::getLocalizedString('key_specification', $this->locale) . ': ' . PayJpEvent::getLocalizedString(((preg_match('/sk_test_/', $sk) ? 'for_test' : 'for_production')), $this->locale) . '<br />';
            return $ret;
        }
        return PayJpEvent::getLocalizedString('failed', $this->locale);
    }


    private function initCommon(ApplicationTrait $app) {
        $this->app = $app;
        if (preg_match('/^ja/', $this->app['config']['locale'])) {
            $this->locale = 'ja';
        }
    }
}