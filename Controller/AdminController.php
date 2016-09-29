<?php

namespace Plugin\PayJp\Controller;

use Eccube\Application;
use Eccube\Application\ApplicationTrait;
use Payjp\Account;
use Symfony\Component\HttpFoundation\Request;
use Eccube\Controller\AbstractController;
use Plugin\PayJp\Entity\PayJpConfig;
use Plugin\PayJp\Entity;


class AdminController extends AbstractController {
    const PAGE_UNIT = 20;

    private $app;

    private $locale;

    public function api_key(ApplicationTrait $app, Request $request) {
        $this->initCommon($app);

        $PayJpConfig = $this->app['orm.em']->getRepository('Plugin\PayJp\Entity\PayJpConfig')->findOneBy(array('id' => 1));
        if (is_null($PayJpConfig)) {
            $PayJpConfig = new PayJpConfig();
            $PayJpConfig->setApiKeySecret('YOUR_SECRET_KEY');
            $this->app['orm.em']->persist($PayJpConfig);
            $this->app['orm.em']->flush($PayJpConfig);
        }
        $form = $app['form.factory']->createBuilder('pay_jp_api_key', $PayJpConfig)->getForm();
        $form->setData($PayJpConfig);

        return $this->app->render(
            'PayJp/Resource/template/api_key.twig',
            array(
                'form' => $form->createView(),
            )
        );
    }
    
    public function api_key_update(ApplicationTrait $app, Request $request) {
        $this->initCommon($app);

        $form = $this->app['form.factory']->createBuilder('pay_jp_api_key')->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $request->request->get('pay_jp_api_key');
            $PayJpConfig = $this->app['orm.em']->getRepository('Plugin\PayJp\Entity\PayJpConfig')->findOneBy(array('id' => 1));
            $PayJpConfig->setApiKeySecret($data['api_key_secret']);
            $this->app['orm.em']->persist($PayJpConfig);
            $this->app['orm.em']->flush($PayJpConfig);
        }

        return $this->app->render(
            'PayJp/Resource/template/api_key.twig',
            array(
                'form' => $form->createView(),
            )
        );
    }

    public function log(ApplicationTrait $app, Request $request) {
        $this->initCommon($app);

        $page = $request->get('page', 0);

        $qb = $this->app['orm.em']->createQueryBuilder();
        $qb->select($qb->expr()->count('c'))->from('Plugin\PayJp\Entity\PayJpLog', 'c');
        $count = $qb->getQuery()->getSingleScalarResult();
        $last_page = floor(($count - 1) / self::PAGE_UNIT);

        $logs = $this->app['orm.em']->getRepository('Plugin\PayJp\Entity\PayJpLog')->findBy(array(), array('id' =>  'DESC'), self::PAGE_UNIT, self::PAGE_UNIT * $page);

        return $this->app->render(
            'PayJp/Resource/template/log.twig',
            array(
                'page' => $page,
                'logs' => $logs,
                'count' => $count,
                'last_page' => $last_page,
            )
        );
    }

    private function initCommon(ApplicationTrait $app) {
        $this->app = $app;
        if (preg_match('/^ja/', $this->app['config']['locale'])) {
            $this->locale = 'ja';
        }
    }
}