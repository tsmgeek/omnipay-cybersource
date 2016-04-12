<?php

namespace Omnipay\Cybersource;

use Omnipay\Common\AbstractGateway;
use Omnipay\Cybersource\Message\CompletePurchaseRequest;
use Omnipay\Cybersource\Message\PurchaseRequest;

/**
 * Cybersource Gateway
 *
 */
class Gateway extends AbstractGateway
{
    public function getName()
    {
        return 'Cybersource';
    }

    public function getDefaultParameters()
    {
        return array(
            'profileId' => '',
            'accessKey' => '',
            'secretKey' => ''
        );
    }
    
    public function setSecretKey($value)
    {
        return $this->setParameter('secretKey', $value);
    }
    
    public function setAccessKey($value)
    {
        return $this->setParameter('accessKey', $value);
    }
    
    public function getSecretKey()
    {
        return $this->getParameter('secretKey');
    }
    
    public function getAccessKey()
    {
        return $this->getParameter('accessKey');
    }
    
    public function setProfileId($value)
    {
        return $this->setParameter('profileId', $value);
    }
    
    public function getProfileId()
    {
        return $this->getParameter('profileId');
    }
    
    public function getReference()
    {
        return $this->getParameter('reference');
    }
    
    public function setReference($value)
    {
        return $this->setParameter('reference', $value);
    }

    public function purchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Cybersource\Message\PurchaseRequest', $parameters);
    }

    public function completePurchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Cybersource\Message\CompletePurchaseRequest', $parameters);
    }
}
