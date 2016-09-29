<?php

namespace Plugin\PayJp\Entity;

use Doctrine\ORM\Mapping as ORM;

use DateTime;
use Eccube\Entity\Customer;
use Eccube\Util\EntityUtil;

class PayJpLog extends \Eccube\Entity\AbstractEntity
{

    /**
     * @var $id
     */
    private $id;

    /**
     * @var $message
     */
    private $message;


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
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
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

