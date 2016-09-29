<?php

namespace Plugin\PayJp\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Eccube\Entity\Customer;
use Eccube\Util\EntityUtil;

class PayJpCustomer extends \Eccube\Entity\AbstractEntity
{

    /**
     * @var $id
     */
    private $id;

    /**
     * @var $pay_jp_customer_id
     */
    private $pay_jp_customer_id;


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

