<?php

namespace Plugin\PayJp\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Eccube\Entity\Customer;
use Eccube\Util\EntityUtil;

class PayJpConfig extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var int
     */
    private $id = 1;

    /**
     * @var string
     */
    private $api_key_secret;

    /**
     * @var created_at
     */
    private $created_at;

    /**
     * PayJpConfig constructor.
     */
    public function __construct()
    {
        $this->created_at = new DateTime();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getApiKeySecret()
    {
        return $this->api_key_secret;
    }

    /**
     * @param string $api_key_secret
     */
    public function setApiKeySecret($api_key_secret)
    {
        $this->api_key_secret = $api_key_secret;
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