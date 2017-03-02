<?php

namespace Omnipay\Cybersource\Message;
use Omnipay\Common\Message\RequestInterface;

/**
 * AbstractRequest
 */
abstract class AbstractRequest extends \Omnipay\Common\Message\AbstractRequest{

    protected $testEndpoint = 'https://testsecureacceptance.cybersource.com/silent';
    protected $liveEndpoint = 'https://secureacceptance.cybersource.com/silent';

    public function getLocale()
    {
        return 'en-us';
    }
    
    public function getEndpoint()
    {
        return $this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint;
    }

    public function getAccessKey()
    {
        return $this->getParameter('accessKey');
    }

    public function getSecretKey()
    {
        return $this->getParameter('secretKey');
    }

    public function getProfileId()
    {
        return $this->getParameter('profileId');
    }

    public function setAccessKey($value)
    {
        return $this->setParameter('accessKey', $value);
    }

    public function setSecretKey($value)
    {
        return $this->setParameter('secretKey', $value);
    }

    public function setProfileId($value)
    {
        return $this->setParameter('profileId', $value);
    }

    protected function getBrandCode($brand)
    {
        $brands = array(
            '001' => 'visa',
            '002' => 'mastercard',
            '003' => 'amex',
            '004' => 'discover',
            '005' => 'diners_club',
            '006' => 'cart_blanche',
            '007' => 'jcb',
            '014' => 'enroute',
            '021' => 'jal',
            '024' => 'maestro_uk',
            '031' => 'delta',
            '034' => 'dankort',
            '036' => 'carte_bleue',
            '037' => 'carta_si',
            '042' => 'maestro_int',
            '043' => 'ge_money_uk_card'
        );

        foreach($brands as $code => $brandOption)
        {
            $brand = str_replace($brandOption, $code, $brand);
        }

        return $brand;
    }

    protected function sign($params) {
        $signedFieldNames = explode(",",$params["signed_field_names"]);
        foreach ($signedFieldNames as $field) {
           $dataToSign[] = $field . "=" . $params[$field];
        }
        
        return base64_encode(hash_hmac('sha256', implode(",",$dataToSign), $this->getSecretKey(), true));
    }
    
}
