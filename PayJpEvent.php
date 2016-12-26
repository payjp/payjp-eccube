<?php

namespace Plugin\PayJp;

use Eccube\Application;
use Eccube\Application\ApplicationTrait;
use Eccube\Common\Constant;
use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Plugin\PayJp\Entity;
use Plugin\PayJp\Entity\PayJpCustomer;
use Plugin\PayJp\Entity\PayJpLog;
use Plugin\PayJp\Entity\PayJpOrder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Validator\Constraints;

use Eccube\Entity\Payment;

class PayJpEvent
{
    const COOKIE_NAME_ECCUBE = 'eccube';

    /** @var ApplicationTrait $app */
    private $app;

    /** @var bool does the customer need PAY.JP or not */
    private $willBePaidByCreditCard = false;

    /** @var int dtb_paymentにおけるクレジットカードのid。
     * 選択されている決済手段がクレジットカードかどうかの判定に用いる。
     */
    private $creditCardPaymentId = 0;

    /**
     * @var string Cookieを使って保持する非会員用のトークン
     */
    private $nonMemberToken = null;

    /**
     * @var \Eccube\Entity\Customer
     */
    private $eccubeCustomer = null;

    /**
     * @var Plugin\PayJp\Entity\PayJpCustomer
     */
    private $payJpCustomer = null;

    /**
     * @var エラーメッセージ
     */
    private $errorMessage = null;

    /**
     * @var 国際化
     */
    private static $i18n = array();

    /**
     * @var string ロケール（jaかenのいずれか）
     */
    private $locale = 'en';

    public function __construct($app)
    {
        $this->app = $app;

        if (preg_match('/^ja/', $app['config']['locale'])) {
            $this->locale = 'ja';
        }
    }

    public function onFrontShoppingIndexInitialize(EventArgs $event)
    {
        // 共通初期化
        $this->initForEventArgs($event);

        // クレジットカード以外ならば何もしない
        if (! $this->willBePaidByCreditCard) {
            return;
        }

        // フォームの追加
        $builder = $event->getArgument('builder');
        $this->makeFormBuilder($builder);
    }


    public function onRenderShippingIndex(TemplateEvent $event)
    {
        // クレジットカード以外ならば何もしない
        if (! $this->willBePaidByCreditCard) {
            return;
        }

        // JSファイルがなければオンデマンドで生成
        if (! file_exists($this->getScriptDiskPath())) {
            $this->makeScript();
        }

        $src = $event->getSource();

        // <div id="payment_list" を探す
        // その次に現れる<h2 class="heading02"> を探して、その手前にクレジットカード情報入力フォームを挿入する

        $pos1 = strpos($src, '<div id="payment_list"');
        assert(0 < $pos1);
        $pos2 = strpos($src, '<h2 class="heading02">', $pos1);
        assert(0 < $pos2);

        $YY = strftime('%y');
        $MM = strftime('%m');
        $YEAR = strftime('%Y');
        $MONTH = 0 + $MM;
        $MONTH_EN = strftime('%h');

        // JavaScriptとCSSを挿入
        $buff = '<link rel="stylesheet" href="/plugin/pay_jp/pay_jp.css" />';
        $buff .= '<script type="text/javascript" src="/plugin/pay_jp/pay_jp_' . $this->locale . '.js"></script>';

        // JavaScriptのグローバルスコープで値を渡す
        $buff .= '<script type="text/javascript">' . "\n";
        $buff .= 'var payJpCreditCardPaymentId = ' . $this->creditCardPaymentId . ";\n";
        $buff .= 'var payJpHasToken = ' . (is_null($this->nonMemberToken) ? 'false' : 'true') . ";\n";
        $buff .= 'var payJpHasCard = ' . (is_null($this->payJpCustomer) ? 'false' : 'true') . ";\n";
        $buff .= '</script>';

        // フォームを挿入
        $buff .= '<h2 class="heading02">' . self::getLocalizedString('card_info', $this->locale) . '</h2>';
        $buff .= '<div id="pay_jp_credit_card_info" class="column">';
        $buff .= '<div id="pay_jp_credit_card_info_body" class="form-group">';
        $buff .= '{{ form_widget(form.pay_jp_error) }}';
        if (!is_null($this->errorMessage)) {
            $buff .= '<div class="pay_jp_error_message" role="alert">' . $this->errorMessage . '</div>';
        }
        $buff .= '<table id="pay_jp_form_table"';
        if (!(is_null($this->nonMemberToken) && is_null($this->payJpCustomer))) {
            $buff .= ' style="display: none"';
        }
        $buff .= '>';
        $buff .= '<tr><th><label>' . self::getLocalizedString('card_holder', $this->locale) . '</label></th><td>';
        $buff .= '{{ form_widget(form.pay_jp_card_name) }}';
        $buff .= '{{ form_errors(form.pay_jp_card_name) }}';
        $buff .= '</td></tr>';
        $buff .= '<tr><th><label>' . self::getLocalizedString('card_number', $this->locale) . '</label></th><td>';
        $buff .= '{{ form_widget(form.pay_jp_card_number1) }}';
        $buff .= '-';
        $buff .= '{{ form_widget(form.pay_jp_card_number2) }}';
        $buff .= '-';
        $buff .= '{{ form_widget(form.pay_jp_card_number3) }}';
        $buff .= '-';
        $buff .= '{{ form_widget(form.pay_jp_card_number4) }}';
        $buff .= '{{ form_errors(form.pay_jp_card_number1) }}';
        $buff .= '</td></tr>';
        $buff .= '<tr><th><label>' . self::getLocalizedString('security_code', $this->locale) . '</label></th><td>';
        $buff .= '{{ form_widget(form.pay_jp_card_cvv) }}';
        $buff .= '<br />' . self::getLocalizedString('security_code_notice', $this->locale) . '<br />';
        $buff .= '{{ form_errors(form.pay_jp_card_cvv) }}';
        $buff .= '</td></tr>';
        $buff .= '<tr><th><label>' . self::getLocalizedString('expiration', $this->locale) . '</label></th><td>';
        $buff .= '{{ form_widget(form.pay_jp_card_exp_month) }}';
        $buff .= '/';
        $buff .= '{{ form_widget(form.pay_jp_card_exp_year) }}';
        if ($this->locale == 'ja') {
            $buff .= "<br />（例） $YEAR 年 $MONTH 月なら $MM/$YY";
        } else {
            $buff .= "<br />ex. $MONTH_EN. $YEAR => $MM/$YY";
        }
        $buff .= '{{ form_errors(form.pay_jp_card_exp_year) }}';
        $buff .= '{{ form_errors(form.pay_jp_card_exp_month) }}';
        $buff .= '</td></tr>';
        $buff .= '</table>';
        if (!(is_null($this->nonMemberToken) && is_null($this->payJpCustomer))) {
            $buff .= '<div id="reset_credit_card_block">';
            $buff .= '<input type="hidden" id="ignore_credit_card_information" name="ignore_credit_card_information" value="1" />';
            $buff .= '<p>' . self::getLocalizedString('card_accept', $this->locale) . '</p>';
            $buff .= '<a href="#" class="btn btn-default" id="reset_credit_card">' . self::getLocalizedString('use_another_card', $this->locale) . '</a>';
            $buff .= '</div>';
        }
        $buff .= '</div>';
        $buff .= '</div>';

        $out = substr($src, 0, $pos2) . $buff . substr($src, $pos2);

        $event->setSource($out);
    }

    public function onFrontShoppingConfirmInitialize(EventArgs $event)
    {

        // 共通初期化
        $this->initForEventArgs($event);

        // クレジットカード以外ならば何もしない
        if (! $this->willBePaidByCreditCard) {
            $this->app->log('skipped. not credit card.');
            return;
        }

        $request = $event->getRequest();
        $order = $event->getArgument('Order');

        // フォームの追加
        $builder = $event->getArgument('builder');
        $this->makeFormBuilder($builder);

        // 入力を受け取る
        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $config = $this->app['orm.em']->getRepository('Plugin\PayJp\Entity\PayJpConfig')->findOneBy(array('id' => 1));

            $client = new PayJpClient($config->getApiKeySecret());

            // ECCUBE側に会員登録しているか
            if (! is_null($this->eccubeCustomer)) {
                $this->app->log('ECCUBE Customer');

                // 会員なので顧客として扱う
                if (is_null($this->payJpCustomer)) {
                    $this->app->log('PAY.JP Customer');

                    // PAY.JPの顧客登録がまだなので登録する
                    $this->writeRequestLog($order, 'createCustomer');
                    $createResult = $client->createCustomer($request->request, $this->eccubeCustomer);
                    $this->writeResponseLog($order, 'createCustomer', $createResult);

                    // 顧客登録を登録できなかったらエラー
                    if (is_array($createResult) && isset($createResult['error'])) {
                        $this->setErrorOnRequest($request, PayJpClient::getErrorMessageFromCode($createResult['error'], $this->locale));
                        return;
                    }

                    // plg_pay_jp_customerにレコードを登録する
                    $this->payJpCustomer = new PayJpCustomer();
                    $this->payJpCustomer->setId($this->eccubeCustomer->getId());
                    $this->payJpCustomer->setPayJpCustomerId($createResult['id']);
                    $this->app['orm.em']->persist($this->payJpCustomer);
                    $this->app['orm.em']->flush($this->payJpCustomer);
                }

                // 支払いを作成する
                $this->writeRequestLog($order, 'createChargeWithCustomer');
                $chargeResult = $client->createChargeWithCustomer($order->getTotal(), $this->payJpCustomer->getPayJpCustomerId(), $order->getId(), true);
                $this->writeResponseLog($order, 'createChargeWithCustomer', $chargeResult);

                // 支払いを作成できなかったらエラー
                if (is_array($chargeResult) && isset($chargeResult['error'])) {
                    $this->setErrorOnRequest($request, PayJpClient::getErrorMessageFromCode($chargeResult['error'], $this->locale));
                    return;
                }

                // 支払いはここで確定している

                // 注文と関連付ける
                $payJpOrder = new PayJpOrder();
                $payJpOrder->setOrderId($order->getId());
                $payJpOrder->setPayJpCustomerId($this->payJpCustomer->getId());
                $this->app['orm.em']->persist($payJpOrder);
                $this->app['orm.em']->flush($payJpOrder);
            } else {

                // 一時的な購入なのでトークンで購入する
                $this->app->log('not ECCUBE Customer, using Token');

                // 既にトークンを保有しているか
                if (is_null($this->nonMemberToken)) {

                    // トークンを作成する
                    $this->writeRequestLog($order, 'createToken');
                    $createResult = $client->createToken($request->request);
                    $this->writeResponseLog($order, 'createToken', $createResult);

                    // トークンを作成できなかったらエラー
                    if (is_array($createResult) && isset($createResult['error'])) {
                        $this->setErrorOnRequest($request, PayJpClient::getErrorMessageFromCode($createResult['error'], $this->locale));
                        return;
                    }

                    $this->nonMemberToken = $createResult['id'];
                }

                // 支払いを作成する
                $this->writeRequestLog($order, 'createChargeWithToken');
                $chargeResult = $client->createChargeWithToken($order->getTotal(), $this->nonMemberToken, $order->getId(), true);
                $this->writeResponseLog($order, 'createChargeWithToken', $chargeResult);

                // 支払いを作成できなかったらエラー
                if (is_array($chargeResult) && isset($chargeResult['error'])) {
                    $this->setErrorOnRequest($request, PayJpClient::getErrorMessageFromCode($chargeResult['error'], $this->locale));
                    return;
                }

                // 支払いはここで確定している

                // 注文と関連付ける
                $payJpOrder = new PayJpOrder();
                $payJpOrder->setOrderId($order->getId());
                $payJpOrder->setPayJpToken($this->nonMemberToken);
                $payJpOrder->setPayJpChargeId($chargeResult->__get('id'));
                $this->app['orm.em']->persist($payJpOrder);
                $this->app['orm.em']->flush($payJpOrder);
            }
        }
    }

    public function onRenderAdminOrderEdit(TemplateEvent $event) {
        $this->app->log('onRenderAdminOrderEdit');

        // JSファイルがなければオンデマンドで生成
        if (! file_exists($this->getScriptDiskPath())) {
            $this->makeScript();
        }

        $src = $event->getSource();

        // {% endblock javascript %} を探す
        // 手前にJavaScript読み込みタグを追加
        $pos1 = strpos($src, '{% endblock javascript %}');
        assert(0 < $pos1);
        $buff = substr($src, 0, $pos1) . '<script type="text/javascript" src="/plugin/pay_jp/pay_jp_admin.js"></script>' . substr($src, $pos1);

        // {{ form.vars.value.payment_method }} を置き換える
        $replaced = "{{ form.vars.value.payment_method }}";
        $replaced .= "{% if Order.payment_method == 'クレジットカード' %}";
        $replaced .= '<p style="font-size: 85%;">支払いID: <span class="show_charge_id" id="show_charge_id_{{ Order.id }}"></span></p>';
        $replaced .= "{% endif %}";
        $buff = str_replace('{{ form.vars.value.payment_method }}', $replaced, $buff);

        $event->setSource($buff);
    }

    public function onRenderAdminOrderIndex(TemplateEvent $event) {

        // JSファイルがなければオンデマンドで生成
        if (! file_exists($this->getScriptDiskPath())) {
            $this->makeScript();
        }

        $src = $event->getSource();

        // {% endblock javascript %} を探す
        // 手前にJavaScript読み込みタグを追加
        $pos1 = strpos($src, '{% endblock javascript %}');
        assert(0 < $pos1);
        $buff = substr($src, 0, $pos1) . '<script type="text/javascript" src="/plugin/pay_jp/pay_jp_admin.js"></script>' . substr($src, $pos1);

        // {{ Order.payment_method }} を置き換える
        $replaced = "{{ Order.payment_method }}";
        $replaced .= "{% if Order.payment_method == 'クレジットカード' %}";
        $replaced .= '<p style="font-size: 85%;">支払いID: <span class="show_charge_id" id="show_charge_id_{{ Order.id }}"></span></p>';
        $replaced .= "{% endif %}";
        $buff = str_replace('{{ Order.payment_method }}', $replaced, $buff);

        $event->setSource($buff);
    }


    private function setErrorOnRequest($request, $message) {
        $query = $request->request;
        if ($query->has('shopping')) {
            $shopping = $query->get('shopping');
            if (is_array($shopping)) {
                if (isset($shopping['pay_jp_error'])) {
                    $shopping['pay_jp_error'] = 'invalid_plan_amount';
                    $query->set('shopping', $shopping);
                    $this->errorMessage = $message;
                }
            }
        }
    }

    private function initForEventArgs(EventArgs $event) {
        
        // dtb_orderから決済種別、会員かどうかを取得する
        if ($event->hasArgument('Order')) {
            $this->willBePaidByCreditCard = $event->getArgument('Order')->getPayment()->getMethod() == 'クレジットカード';
            $this->eccubeCustomer = $event->getArgument('Order')->getCustomer();
            $this->creditCardPaymentId = $event->getArgument('Order')->getPayment()->getId();
        }

        // 会員ならPAY.JPの会員情報かトークン、非会員ならトークンの取得を試みる
        if (! is_null($this->eccubeCustomer)) {
            $this->payJpCustomer = $this->app['pay_jp.repository.pay_jp_customer']->find($event->getArgument('Order')->getCustomer()->getId());
        }
        if (is_null($this->payJpCustomer)) {
            $this->nonMemberToken = $this->app['pay_jp.repository.pay_jp_token']->find($event->getRequest()->cookies->get(self::COOKIE_NAME_ECCUBE));
        }
    }

    private function makeFormBuilder(&$builder) {
        $builder->add("pay_jp_card_name", 'text', array(
            'required' => false,
            'label' => false,
            'mapped' => false,
            'attr' => array(
                'placeholder' => '',
                'class' => 'pay_jp_card_name',
                'maxlength' => '40',
            )));

        $builder->add("pay_jp_card_exp_year", 'text', array(
            'required' => false,
            'label' => false,
            'mapped' => false,
            'attr' => array(
                'placeholder' => '',
                'class' => 'pay_jp_card_exp',
                'maxlength' => '2',
            )));

        $builder->add("pay_jp_card_exp_month", 'text', array(
            'required' => false,
            'label' => false,
            'mapped' => false,
            'attr' => array(
                'placeholder' => '',
                'class' => 'pay_jp_card_exp',
                'maxlength' => '2',
            )));

        for ($i = 1; $i <= 4; $i++) {
            $builder->add("pay_jp_card_number$i", 'text', array(
                'required' => false,
                'label' => false,
                'mapped' => false,
                'attr' => array(
                    'placeholder' => '',
                    'class' => 'pay_jp_card_number',
                    'maxlength' => '4',
                )));
        }

        $builder->add("pay_jp_card_cvv", 'text', array(
            'required' => false,
            'label' => false,
            'mapped' => false,
            'attr' => array(
                'placeholder' => '',
                'class' => 'pay_jp_card_cvv',
                'maxlength' => '3',
            )));

        $constraints = array();
        foreach (PayJpClient::$errorMessages as $code => $message) {
            $out_message = $this->locale == 'ja' ? $message : str_replace('_', ' ', $code);
            array_push($constraints, new Constraints\NotEqualTo(array(
                'value' => $code,
                'message' => $out_message
            )));
        }
        $builder->add("pay_jp_error", 'hidden', array(
            'required' => false,
            'label' => false,
            'mapped' => false,
            'constraints' => $constraints,
            'attr' => array(
                'value' => 'ok',
            )));
    }

    private function writeRequestLog(Order $order, $api) {
        $logMessage = '[Order' . $order->getId() . '][' . $api . '] リクエスト実行';
        $this->app->log($logMessage);

        $payJpLog = new PayJpLog();
        $payJpLog->setMessage($logMessage);
        $this->app['orm.em']->persist($payJpLog);
        $this->app['orm.em']->flush($payJpLog);
    }

    private function writeResponseLog(Order $order, $api, $result) {
        $logMessage = '[Order' . $order->getId() . '][' . $api . '] ';
        if (is_object($result)) {
            $logMessage .= '成功';
        } elseif (! is_array($result)) {
            $logMessage .= print_r($result, true);
        } elseif (isset($result['error'])) {
            $logMessage .= self::getLocalizedString($result['error']['code'], $this->locale);
        } else {
            $logMessage .= '成功';
        }

        $this->app->log($logMessage);

        $payJpLog = new PayJpLog();
        $payJpLog->setMessage($logMessage);
        $this->app['orm.em']->persist($payJpLog);
        $this->app['orm.em']->flush($payJpLog);
    }

    private function getScriptDiskPath() {
        return dirname(dirname(dirname(dirname(__FILE__)))) . '/html/plugin/pay_jp/pay_jp_' . $this->locale . '.js';
    }

    private function makeScript() {
        $buff = file_get_contents(dirname(__FILE__) . '/Resource/js/pay_jp.js');
        $out_path = $this->getScriptDiskPath();

        $m = array();
        preg_match_all('/\{\{ (\w+) \}\}/', $buff, $m);
        for ($i = 0; $i < sizeof($m[0]); $i++) {
            $buff = str_replace($m[0][$i], self::getLocalizedString($m[1][$i], $this->locale), $buff);
        }

        file_put_contents($out_path, $buff);
    }

    public static function getLocalizedString($id, $locale) {
        if (! isset(self::$i18n[$locale])) {
            $tmp_loader = new \Symfony\Component\Translation\Loader\YamlFileLoader();
            $catalogue = $tmp_loader->load(dirname(__FILE__) . "/locale/pay_jp.$locale.yml", 'ja', 'pay_jp');
            self::$i18n[$locale] = $catalogue->all('pay_jp');
        }
        if (isset(self::$i18n[$locale][$id])) {
            return self::$i18n[$locale][$id];
        }
        return '--';
    }
}