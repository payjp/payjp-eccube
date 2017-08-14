<?php

namespace Plugin\PayJp;

use Eccube\Entity\PaymentOption;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Entity\Payment;
use Eccube\Repository\PaymentRepository;

class PluginManager extends AbstractPluginManager
{

    public function install($config, $app)
    {
        $this->migrationSchema($app, __DIR__ . '/Migration', $config['code']);

        // アセットを公開ディレクトリ以下にコピーする
        $this->deleteAssets();
        $this->copyAssets();
    }

    public function uninstall($config, $app)
    {
        $this->migrationSchema($app, __DIR__ . '/Migration', $config['code'], 0);
        $this->deleteAssets();
    }

    public function enable($config, $app)
    {
        // クレジットカードという決済手段がなければ登録する
        $payment = $app['eccube.repository.payment']
            ->findOneBy(
                array('method' => 'クレジットカード')
            );

        if (is_null($payment)) {
            $softDeleteFilter = $app['orm.em']->getFilters()->getFilter('soft_delete');
            $softDeleteFilter->setExcludes(array('Eccube\Entity\Member'));
            $defaultCreator = $app['eccube.repository.member']->findOneBy(array('id' => 1));
            $payment = $app['eccube.repository.payment']->findOrCreate(0);
            $payment->setMethod('クレジットカード');
            $payment->setCharge(0);
            $payment->setRuleMin(0);
            $payment->setCreator($defaultCreator);
            $app['orm.em']->persist($payment);
            $app['orm.em']->flush($payment);
        }

        // 全ての配送手段に対して有効にする
        $deliveries = $app['eccube.repository.delivery']->findAll();
        foreach ($deliveries as $delivery) {
            $paymentOption = $app['eccube.repository.payment_option']->findOneBy(array('Payment' => $payment, 'Delivery' => $delivery));
            if (is_null($paymentOption)) {
                $paymentOption = new PaymentOption();
                $paymentOption->setDelivery($delivery);
                $paymentOption->setPayment($payment);
                $paymentOption->setDeliveryId($delivery->getId());
                $paymentOption->setPaymentId($payment->getId());
                $app['orm.em']->persist($paymentOption);
            }
        }

        $app['orm.em']->flush();
    }

    public function disable($config, $app)
    {
        // dtb_payment_optionからクレジットカードを削除する
        $Payment = $app['eccube.repository.payment']
            ->findOneBy(
                array('method' => 'クレジットカード')
            );
        if (! is_null($Payment)) {
            $PaymentOptions = $app['eccube.repository.payment_option']->findAll();
            foreach ($PaymentOptions as $PO) {
                if ($PO->getPaymentId() == $Payment->getId()) {
                    $app['orm.em']->remove($PO);
                }
            }
            $app['orm.em']->flush();
        }
    }

    public function update($config, $app)
    {
        $this->deleteAssets();
        $this->copyAssets();
    }

    /**
     * アセットを削除する
     */
    private function deleteAssets() {
        $pub_image_dir = dirname(dirname(dirname(dirname(__FILE__)))) . '/html/plugin/pay_jp';
        if (file_exists($pub_image_dir)) {
            $dh = opendir($pub_image_dir);
            while (false !== ($entry = readdir($dh))) {
                if ($entry != "." && $entry != "..") {
                    unlink("$pub_image_dir/$entry");
                }
            }
            rmdir($pub_image_dir);
        }
    }

    /**
     * アセットを公開ディレクトリ以下にコピーする
     */
    private function copyAssets() {
        $pub_image_dir = dirname(dirname(dirname(dirname(__FILE__)))) . '/html/plugin/pay_jp';
        $resource_dir = dirname(__FILE__) . '/Resource';
        mkdir($pub_image_dir, 0755, true);
        copy("$resource_dir/js/pay_jp_admin.js", "$pub_image_dir/pay_jp_admin.js");
        copy("$resource_dir/css/pay_jp.css", "$pub_image_dir/pay_jp.css");
    }
}
