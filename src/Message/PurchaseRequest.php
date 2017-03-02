<?php

namespace Omnipay\Cybersource\Message;
use Omnipay\Common\Message\AbstractRequest;

/**
 * Cybersource Purchase Request
 */
class PurchaseRequest extends AbstractRequest
{
    protected $testEndpoint = 'https://testsecureacceptance.cybersource.com/silent/pay';
    protected $liveEndpoint = 'https://secureacceptance.cybersource.com/silent/pay';

    public function getLocale()
    {
        return 'en-us';
    }

    public function getEndPoint()
    {
        return $this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint;
    }

    public function getReference()
    {
        return $this->getParameter('reference');
    }

    public function setReference($value)
    {
        return $this->setParameter('reference', $value);
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

    private function getBrandCode($brand)
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

    private function sign ($params) {
        return $this->signData($this->buildDataToSign($params), $this->getSecretKey());
    }

    private function signData($data, $secretKey) {
        return base64_encode(hash_hmac('sha256', $data, $secretKey, true));
    }

    private function buildDataToSign($params)
    {
        $signedFieldNames = explode(",",$params["signed_field_names"]);
        foreach ($signedFieldNames as $field) {
           $dataToSign[] = $field . "=" . $params[$field];
        }
        return $this->commaSeparate($dataToSign);
    }

    private function commaSeparate ($dataToSign)
    {
        return implode(",",$dataToSign);
    }

    public function getData()
    {
        $data = array();

        // signed fields
        $data['access_key'] = $this->getAccessKey();
        $data['profile_id'] = $this->getProfileId();
        $data['transaction_uuid'] = sha1(microtime());
        $data['signed_field_names'] = '';
        $data['unsigned_field_names'] = '';
        $data['signed_date_time'] = gmdate("Y-m-d\TH:i:s\Z");
        $data['locale'] = $this->getLocale();
        $data['transaction_type'] = 'sale';
        $data['reference_number'] = $this->getReference();
        $data['amount'] = $this->getAmount();
        $data['currency'] = $this->getCurrency();
        $data['payment_method'] = 'card';
        $data['card_number'] = $this->getCard()->getNumber();
        $data['card_type'] = $this->getBrandCode($this->getCard()->getBrand());
        $data['card_expiry_date'] = sprintf('%02s', $this->getCard()->getExpiryMonth()) . '-' . $this->getCard()->getExpiryYear();
        $data['card_cvn'] = $this->getCard()->getCvv();
        $data['bill_to_forename'] = $this->getCard()->getBillingFirstName();
        $data['bill_to_surname'] = $this->getCard()->getBillingLastName();
        $data['bill_to_email'] = $this->getCard()->getEmail();
        $data['bill_to_phone'] = $this->getCard()->getBillingPhone();
        $data['bill_to_address_line1'] = $this->getCard()->getBillingAddress1();
        $data['bill_to_address_line2'] = $this->getCard()->getBillingAddress2();
        $data['bill_to_address_city'] = $this->getCard()->getCity();
        $data['bill_to_address_state'] = $this->getCard()->getState();
        $data['bill_to_address_country'] = $this->getCard()->getCountry();
        $data['bill_to_address_postal_code'] = $this->getCard()->getBillingPostcode();

        // fill signed_filed_names out with all fields that need signing
        $fields=array();
        foreach($data as $k=>$v){
            $fields[]=$k;
        }
        $data['signed_field_names'] = implode(',',$fields);

        // unsigned fields
        $data['signature'] = $this->sign($data);

        return $data;
    }

    public function sendData($data)
    {
        $httpResponse = $this->httpClient->post($this->getEndpoint(), null, $data)->send();

        return $this->response = new PurchaseResponse($this, $httpResponse);
    }
}
