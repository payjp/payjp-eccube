<?php

namespace Plugin\PayJp\ServiceProvider;

use Eccube\Application;
use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;

class PayJpServiceProvider implements ServiceProviderInterface
{
    public function register(BaseApplication $app)
    {
        // Repository
        $app['pay_jp.repository.pay_jp_config'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\PayJp\Entity\PayJpConfig');
        });
        $app['pay_jp.repository.pay_jp_customer'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\PayJp\Entity\PayJpCustomer');
        });
        $app['pay_jp.repository.pay_jp_token'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\PayJp\Entity\PayJpToken');
        });
        $app['pay_jp.repository.pay_jp_order'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\PayJp\Entity\PayJpOrder');
        });
        $app['pay_jp.repository.pay_jp_log'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\PayJp\Entity\PayJpLog');
        });

        // 新規ページ
        $app->match('/' . $app["config"]["admin_route"]  . '/plugin/pay_jp/api_key', '\Plugin\PayJp\Controller\AdminController::api_key')->bind('pay_jp_api_key');
        $app->match('/' . $app["config"]["admin_route"]  . '/plugin/pay_jp/api_key_update', '\Plugin\PayJp\Controller\AdminController::api_key_update')->bind('pay_jp_api_key_update');
        $app->match('/' . $app["config"]["admin_route"]  . '/plugin/pay_jp/log', '\Plugin\PayJp\Controller\AdminController::log')->bind('pay_jp_log');
        $app->match('/' . $app["config"]["admin_route"]  . '/plugin/pay_jp/log-{page}', '\Plugin\PayJp\Controller\AdminController::log')->assert('page', '\d+')->bind('pay_jp_log_list');
        $app->match('/' . $app["config"]["admin_route"]  . '/plugin/pay_jp/api/charge/{order_id}', '\Plugin\PayJp\Controller\ApiController::charge')->bind('pay_jp_api_charge');
        $app->match('/' . $app["config"]["admin_route"]  . '/plugin/pay_jp/api/key_check', '\Plugin\PayJp\Controller\ApiController::key_check')->bind('pay_jp_api_key_check');

        // 管理メニュー
        $app['config'] = $app->share($app->extend('config', function ($config) {
            $head = array_slice($config['nav'], 0, 4);
            $tail = array_slice($config['nav'], 4);
            $append = array(array(
                'id' => 'pay_jp',
                'name' => 'PAY.JP 管理',
                'has_child' => 'true',
                'icon' => "cb-point",
                'child' => array(
                    array(
                        'id' => 'pay_jp_api_key',
                        'name' => 'APIキー',
                        'url' => 'pay_jp_api_key'
                    ),
                    array(
                        'id' => 'pay_jp_log',
                        'name' => 'ログ',
                        'url' => 'pay_jp_log',
                    ),

                )
            ));
            $config['nav'] = array_merge($head, $append, $tail);
            return $config;
        }));
        
        // Form
        $app['form.types'] = $app->share($app->extend('form.types', function ($types) use ($app) {
            $types[] = new \Plugin\PayJp\Form\Type\ApiKeyType($app);
            return $types;
        }));

    }

    public function boot(BaseApplication $app)
    {
    }
}