<?php

namespace Plugin\PayJp\Entity;

use Doctrine\ORM\Mapping as ORM;

use DateTime;
use Eccube\Entity\Customer;
use Eccube\Util\EntityUtil;

class PayJpToken extends \Eccube\Entity\AbstractEntity
{

    /**
     * @var $id
     */
    private $id;

    /**
     * @var $pay_jp_token
     */
    private $pay_jp_token;


    /**
     * @var created_at
     */
    private $created_at;

    /**
     * PayJpCustomer constructor.
     */
    public function __construct()
    {
        $this->created_at = new DateTime();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getPayJpToken()
    {
        return $this->pay_jp_token;
    }

    /**
     * @param mixed $pay_jp_token
     */
    public function setPayJpToken($pay_jp_token)
    {
        $this->pay_jp_token = $pay_jp_token;
    }


    /**
     * @return created_at
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @param created_at $created_at
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
    }
}

