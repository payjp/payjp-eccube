<?php

namespace Plugin\PayJp\Entity;

use Doctrine\ORM\Mapping as ORM;

use DateTime;
use Eccube\Entity\Customer;
use Eccube\Util\EntityUtil;

class PayJpOrder extends \Eccube\Entity\AbstractEntity
{

    /**
     * @var $id
     */
    private $id;

    /**
     * @var $order_id
     */
    private $order_id;

    /**
     * @var $pay_jp_customer_id
     */
    private $pay_jp_customer_id;

    /**
     * @var $pay_jp_token
     */
    private $pay_jp_token;

    /**
     * @var $pay_jp_charge_id
     */
    private $pay_jp_charge_id;

    /**
     * @var created_at
     */
    private $created_at;

    /**
     * PayJpLog constructor.
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
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * @param mixed $order_id
     */
    public function setOrderId($order_id)
    {
        $this->order_id = $order_id;
    }

    /**
     * @return mixed
     */
    public function getPayJpCustomerId()
    {
        return $this->pay_jp_customer_id;
    }

    /**
     * @param mixed $pay_jp_customer_id
     */
    public function setPayJpCustomerId($pay_jp_customer_id)
    {
        $this->pay_jp_customer_id = $pay_jp_customer_id;
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
     * @return mixed
     */
    public function getPayJpChargeId()
    {
        return $this->pay_jp_charge_id;
    }

    /**
     * @param mixed $pay_jp_charge_id
     */
    public function setPayJpChargeId($pay_jp_charge_id)
    {
        $this->pay_jp_charge_id = $pay_jp_charge_id;
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

