<?php

namespace Plugin\PayJp\Form\Type;

use Eccube\Application\ApplicationTrait;
use Plugin\PayJp\Entity\PayJpConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\Constraints as Assert;

class ApiKeyType extends AbstractType
{

    private $app;

    public function __construct(ApplicationTrait $app)
    {
        $this->app = $app;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $data = $options['data'];
        if (!is_null($data)) {
            $api_key_secret = $data->getApiKeySecret();
        }
        $builder
            ->add('api_key_secret', 'text', array(
                'label' => '秘密キー',
                'required' => true,
                'data' => $api_key_secret,
                'attr' => array(
                    'class' => 'pay_jp_api_key',
                ),
                'mapped' => false,
                'constraints' => array(
                    new Assert\NotBlank(array(
                            'message' => '秘密キーが入力されていません。')
                    ),
                    new Assert\Regex(array(
                            'pattern' => '/^\w+$/',
                            'match' => true,
                            'message' => '秘密キーは半角英数字で入力してください。')
                    ),
                ),
            ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
    }

    public function getName()
    {
        return 'pay_jp_api_key';
    }
}