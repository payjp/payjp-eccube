<?php

namespace Plugin\PayJp;

use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Exception;
use Payjp\Charge;
use Payjp\Payjp;
use Payjp\Token;
use Plugin\PayJp\Entity;
use Plugin\PayJp\Entity\PayJpCustomer;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Plugin\PayJp\Entity\PayJpConfig;
use Symfony\Component\HttpFoundation\ParameterBag;

use Eccube\Entity\Payment;

class PayJpClient
{
    public static $errorMessages = array(
        'invalid_number' => '不正なカード番号',
        'invalid_cvc' => '不正なCVC',
        'invalid_expiry_month' => '不正な有効期限月',
        'invalid_expiry_year' => '不正な有効期限年',
        'expired_card' => '有効期限切れ',
        'card_declined' => 'カード会社によって拒否されたカード',
        'processing_error' => '決済ネットワーク上で生じたエラー',
        'missing_card' => '顧客がカードを保持していない',
        'invalid_id' => '不正なID',
        'no_api_key' => 'APIキーがセットされていない',
        'invalid_api_key' => '不正なAPIキー',
        'invalid_plan' => '不正なプラン',
        'invalid_expiry_days' => '不正な失効日数',
        'unnecessary_expiry_days' => '失効日数が不要なパラメーターである場合',
        'invalid_flexible_id' => '不正なID指定',
        'invalid_timestamp' => '不正なUnixタイムスタンプ',
        'invalid_trial_end' => '不正なトライアル終了日',
        'invalid_string_length' => '不正な文字列長',
        'invalid_country' => '不正な国名コード',
        'invalid_currency' => '不正な通貨コード',
        'invalid_address_zip' => '不正な郵便番号',
        'invalid_amount' => '不正な支払い金額',
        'invalid_plan_amount' => '不正なプラン金額',
        'invalid_card' => '不正なカード',
        'invalid_customer' => '不正な顧客',
        'invalid_boolean' => '不正な論理値',
        'invalid_email' => '不正なメールアドレス',
        'no_allowed_param' => 'パラメーターが許可されていない場合',
        'no_param' => 'パラメーターが何もセットされていない',
        'invalid_querystring' => '不正なクエリー文字列',
        'missing_param' => '必要なパラメーターがセットされていない',
        'invalid_param_key' => '指定できない不正なパラメーターがある',
        'no_payment_method' => '支払い手段がセットされていない',
        'payment_method_duplicate' => '支払い手段が重複してセットされている',
        'payment_method_duplicate_including_customer' => '支払い手段が重複してセットされている(顧客IDを含む)',
        'failed_payment' => '指定した支払いが失敗している場合',
        'invalid_refund_amount' => '不正な返金額',
        'already_refunded' => 'すでに返金済み',
        'cannot_refund_by_amount' => '返金済みの支払いに対して部分返金ができない',
        'invalid_amount_to_not_captured' => '確定されていない支払いに対して部分返金ができない',
        'refund_amount_gt_net' => '返金額が元の支払い額より大きい',
        'capture_amount_gt_net' => '支払い確定額が元の支払い額より大きい',
        'invalid_refund_reason' => '不正な返金理由',
        'already_captured' => 'すでに支払いが確定済み',
        'cant_capture_refunded_charge' => '返金済みの支払いに対して支払い確定ができない',
        'charge_expired' => '認証が失効している支払い',
        'alerady_exist_id' => 'すでに存在しているID',
        'token_already_used' => 'すでに使用済みのトークン',
        'already_have_card' => '指定した顧客がすでに保持しているカード',
        'dont_has_this_card' => '顧客が指定したカードを保持していない',
        'doesnt_have_card' => '顧客がカードを何も保持していない',
        'invalid_interval' => '不正な課金周期',
        'invalid_trial_days' => '不正なトライアル日数',
        'invalid_billing_day' => '不正な支払い実行日',
        'exist_subscribers' => '購入者が存在するプランは削除できない',
        'already_subscribed' => 'すでに定期課金済みの顧客',
        'already_canceled' => 'すでにキャンセル済みの定期課金',
        'already_pasued' => 'すでに停止済みの定期課金',
        'subscription_worked' => 'すでに稼働している定期課金',
        'test_card_on_livemode' => '本番モードのリクエストにテストカードが使用されている',
        'not_activated_account' => '本番モードが許可されていないアカウント',
        'too_many_test_request' => 'テストモードのリクエストリミットを超過している',
        'invalid_access' => '不正なアクセス',
        'payjp_wrong' => 'PAY.JPのサーバー側でエラーが発生している',
        'pg_wrong' => '決済代行会社のサーバー側でエラーが発生している',
        'not_found' => 'リクエスト先が存在しないことを示す',
        'not_allowed_method' => '許可されていないHTTPメソッド',
    );

    public function __construct($secret_key)
    {
        Payjp::setApiKey($secret_key);
    }

    public function createCustomer(ParameterBag $queryParams, Customer $customer) {
        $params = self::convertQueryParameters($queryParams);
        $params['email'] = $customer->getEmail();
        $params['metadata'] = array('customer' => $customer->getId());
        try {
            return \Payjp\Customer::create($params);
        } catch (Exception $e) {
            return $e->getJsonBody();
        }
    }

    public function retrieveCustomer($customerId) {
        try {
            return \Payjp\Customer::retrieve($customerId);
        } catch (Exception $e) {
            return $e->getJsonBody();
        }
    }

    public function createToken(ParameterBag $queryParams) {
        $params = self::convertQueryParameters($queryParams);
        try {
            return Token::create($params);
        } catch (Exception $e) {
            return $e->getJsonBody();
        }
    }

    public function retrieveToken($tokenId) {
        return Token::retrieve($tokenId);
    }

    public function createChargeWithCustomer($amount, $payJpCustomerId, $orderId, $capture, $expiry_days = 1) {
        $params = array(
            'amount' => $amount,
            'currency' => 'jpy',
            'customer' => $payJpCustomerId,
            'metadata' => array(
                'order' => $orderId

            ),
            'capture' => $capture,
        );
        if (! $capture) {
            $params['expiry_days'] = $expiry_days;
        }
        try {
            return Charge::create($params);
        } catch (Exception $e) {
            return $e->getJsonBody();
        }
    }

    public function createChargeWithToken($amount, $payJpTokenId, $orderId, $capture, $expiry_days = 1) {
        $params = array(
            'amount' => $amount,
            'currency' => 'jpy',
            'card' => $payJpTokenId,
            'metadata' => array(
                'order' => $orderId
            ),
            'capture' => $capture,
        );
        if (! $capture) {
            $params['expiry_days'] = $expiry_days;
        }
        try {
            return Charge::create($params);
        } catch (Exception $e) {
            return $e->getJsonBody();
        }
    }

    public function retrieveCharge($chargeId) {
        try {
            return Charge::retrieve($chargeId);
        } catch (Exception $e) {
            return $e->getJsonBody();
        }
    }

    /**
     * GET引数をPAY.JP送信用のパラメータに変換する
     *
     * @param ParameterBag $params
     * @return array
     */
    public static function convertQueryParameters(ParameterBag $params) {
        $ret = array('card' => array());

        if ($params->has('shopping')) {
            $shopping = $params->get('shopping');
            if (is_array($shopping)) {
                if (isset($shopping['pay_jp_card_name'])) {
                    $pay_jp_card_name = $shopping['pay_jp_card_name'];
                    if (0 < strlen($pay_jp_card_name) && strlen($pay_jp_card_name) < 255) {
                        $ret['card']['name'] = $pay_jp_card_name;
                    }
                }
                if (isset($shopping['pay_jp_card_number1']) && isset($shopping['pay_jp_card_number1']) && isset($shopping['pay_jp_card_number1']) && isset($shopping['pay_jp_card_number4'])) {
                    $num1 = $shopping['pay_jp_card_number1'];
                    $num2 = $shopping['pay_jp_card_number2'];
                    $num3 = $shopping['pay_jp_card_number3'];
                    $num4 = $shopping['pay_jp_card_number4'];
                    if (preg_match('/^\d{4}$/', $num1) && preg_match('/^\d{4}$/', $num2) && preg_match('/^\d{4}$/', $num3) && preg_match('/^\d{4}$/', $num4)) {
                        $ret['card']['number'] = $num1 . $num2 . $num3 . $num4;
                    }
                }
                if (isset($shopping['pay_jp_card_exp_year'])) {
                    $year = $shopping['pay_jp_card_exp_year'];
                    if (preg_match('/^\d{2}$/', $year)) {
                        $ret['card']['exp_year'] = '20' . $year;
                    }
                }
                if (isset($shopping['pay_jp_card_exp_month'])) {
                    $month = $shopping['pay_jp_card_exp_month'];
                    if (preg_match('/^\d{2}$/', $month)) {
                        $ret['card']['exp_month'] = (int)$month;
                    }
                }
                if (isset($shopping['pay_jp_card_cvv'])) {
                    $cvv = $shopping['pay_jp_card_cvv'];
                    if (preg_match('/^\d{3,4}$/', $cvv)) {
                        $ret['card']['cvc'] = $cvv;
                    }
                    $ret['card']['cvc'] = $shopping['pay_jp_card_cvv'];
                }
            }
        }

        return $ret;
    }

    public static function getErrorMessageFromCode($error, $locale) {
        if (isset(self::$errorMessages[$error['code']])) {
            $message = self::$errorMessages[$error['code']];
            $out_message = $locale == 'ja' ? $message : str_replace('_', ' ', $error['code']);
            return $out_message;
        }
        return PayJpEvent::getLocalizedString('unexpected_error', $locale);
    }
}