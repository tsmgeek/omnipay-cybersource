<?php

namespace Omnipay\Cybersource\Message;

/**
 * Cybersource Purchase Request
 */
class PurchaseRequest extends AbstractRequest
{
    public function getEndpoint()
    {
        return parent::getEndpoint()."/pay";
    }

    public function getReference()
    {
        $ref = $this->getParameter('reference');
        return (!is_null($ref)?$ref:'');
    }

    public function setReference($value)
    {
        return $this->setParameter('reference', $value);
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
        $data['signed_field_names'] = implode(',', array_keys($data));

        // unsigned fields
        $data['signature'] = $this->sign($data);

        return $data;
    }

    public function sendData($data)
    {
        $request = $this->httpClient->post($this->getEndpoint(), array(), $data);
        $httpResponse = $request->send();
        return $this->response = new PurchaseResponse($this, $httpResponse);
    }
}
