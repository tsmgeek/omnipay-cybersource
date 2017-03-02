<?php

namespace Omnipay\Cybersource\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Common\Message\RequestInterface;

/**
 * Cybersource Purchase Response
 */
class PurchaseResponse extends AbstractResponse implements RedirectResponseInterface
{

    private $tags=array();
    
    /* @var \Guzzle\Http\Message\Response */
    private $response;

    private $decisionCodeMap = array(
        'ACCEPT' => array(100, 110),
        'REVIEW' => array(200, 201, 230, 520),
        'DECLINE' => array(102, 200, 202, 203, 204, 205, 207, 208, 210, 211, 221, 222, 230, 231, 232, 233, 234, 236, 240, 475, 476),
        'ERROR' => array(102, 104),
        'CANCEL' => array()
    );

    private $reasonCodes = array(
        100 => "Successful transaction.",
        102 => "One or more fields in the request contains invalid data.\nPossible action: See the reply fields invalid_fields for which fields are invalid. Resend the request with the correct information.",
        104 => "The access_key and transaction_uuid for this authorization request matches the access_key and transaction_uuid of another authorization request that you sent within the past 15 minutes.\nPossible action: Resend the request with a unique access_key and transaction_uuid.",
        110 => "Only a partial amount was approved.",
        200 => "The authorization request was approved by the issuing bank but declined by CyberSource because it did not pass the Address Verification System (AVS) check.\nPossible action: You can capture the authorization, but consider reviewing the order for the possibility of fraud.",
        201 => "The issuing bank has questions about the request. You do not receive an authorization code programmatically, but you might receive one verbally by calling the processor.\nPossible action: Call your processor to possibly receive a verbal authorization. For contact phone numbers, refer to your merchant bank information.",
        202 => "Expired card. You might also receive this value if the expiration date you provided does not match the date the issuing bank has on file.\nPossible action: Request a different card or other form of payment.",
        203 => "General decline of the card. No other information was provided by the issuing bank.\n,Possible action: Request a different card or other form of payment.",
        204 => "Insufficient funds in the account.\nPossible action: Request a different card or other form of payment.",
        205 => "Stolen or lost card.\nPossible action: Review this transaction manually to ensure that you submitted the correct information.",
        207 => "Issuing bank unavailable.\nPossible action: Wait a few minutes and resend the request.",
        208 => "Inactive card or card not authorized for card-not-present transactions.\nPossible action: Request a different card or other form of payment.",
        210 => "The card has reached the credit limit. Possible action: Request a different card or other form of payment.",
        211 => "Invalid CVN.\nPossible action: Request a different card or other form of payment.",
        221 => "The customer matched an entry on the processor’s negative file.\nPossible action: Review the order and contact the payment processor.",
        222 => "Account frozen.",
        230 => "The authorization request was approved by the issuing bank but declined by CyberSource because it did not pass the CVN check.\n
Possible action: You can capture the authorization, but consider reviewing the order for the possibility of fraud.",
        231 => "Invalid account number.\nPossible action: Request a different card or other form of payment.",
        232 => "The card type is not accepted by the payment processor.\nPossible action: Contact your merchant bank to confirm that your account is set up to receive the card in question.",
        233 => "General decline by the processor.\nPossible action: Request a different card or other form of payment.",
        234 => "There is a problem with the information in your CyberSource account.\nPossible action: Do not resend the request. Contact CyberSource Customer Support to correct the information in your account.",
        236 => "Processor failure.\nPossible action: Wait a few minutes and resend the request.",
        240 => "The card type sent is invalid or does not correlate with the credit card number.\nPossible action: Confirm that the card type correlates with the credit card number specified in the request, then resend the request.",
        475 => "The cardholder is enrolled for payer authentication.\nPossible action: Authenticate cardholder before proceeding.",
        476 => "Payer authentication could not be authenticated.",
        520 => "The authorization request was approved by the issuing bank but declined by CyberSource based on your legacy Smart Authorization settings.\nPossible action: Review the authorization request."
    );

    public function __construct(RequestInterface $request, \Guzzle\Http\Message\Response $response)
    {
        parent::__construct($request,$response->getBody());
        $this->response=$response;
        $this->tags=$this->processData($this->data);
    }

    public function isSuccessful()
    {
        if($this->tags['decision']['value'] == 'ACCEPT')
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function getData()
    {
        return $this->tags;
    }

    public function getRawData()
    {
        return $this->data;
    }
    
    public function getResponse(){
        return $this->response;
    }

    private function processData()
    {
        $output = preg_match_all('/<input id="(?P<id>.*)" name="(?P<name>.*)" type="(?<type>.*)" value="(?<value>.*)" \/>/',(string) $this->data,$matches);
        if(!$output) $output = preg_match_all('/<input type="(?<type>.*)" name="(?P<name>.*)" id="(?P<id>.*)" value="(?<value>.*)" \/>/',(string) $this->data,$matches);
        if(!$output){
            $output = preg_match('/<title>(?<title>.*)<\/title>/',(string) $this->data,$matches);
            if($output){
                throw new \Exception('Response error - '.$matches['title']);
            }
            throw new \Exception('Response error');
        }


        $tags = array();
        foreach($matches[0] as $k=>$i){
          $tags[$matches['id'][$k]]=array(
            'id'=>$matches['id'][$k],
            'name'=>$matches['name'][$k],
            'type'=>$matches['type'][$k],
            'value'=>$matches['value'][$k]
          );
        }

        return $tags;
    }

    public function getDecision()
    {
        return $this->tags['decision']['value'];
    }

    public function getReasonCode()
    {
        return (isset($this->tags['reason_code']['value'])) ? $this->tags['reason_code']['value'] : '';
    }

    public function getReasonText()
    {
        return (isset($this->reasonCodes[$this->getReasonCode()]))? $this->reasonCodes[$this->getReasonCode()] : $this->tags['decision']['value'];
    }
    
    /**
     * Get the error message from the response.
     *
     * Returns null if the request was successful.
     *
     * @return string|null
     */
    public function getCode()
    {
        if (!$this->isSuccessful()) {
            return $this->getReasonCode();
        }
        return null;
    }

    /**
     * Get the error message from the response.
     *
     * Returns null if the request was successful.
     *
     * @return string|null
     */
    public function getMessage()
    {
        if (!$this->isSuccessful()) {
            return $this->getReasonText();
        }
        return null;
    }
    
    public function getInvalidFields()
    {
        return explode(',',$this->tags['invalid_fields']);
    }

    public function getTransactionReference()
    {
        return (isset($this->tags['transaction_id']['value'])) ? $this->tags['transaction_id']['value'] : '';
    }

    public function isRedirect()
    {
        return false;
    }

    public function getRedirectUrl()
    {
        return null;
    }

    public function getRedirectMethod()
    {
        return null;
    }

    public function getRedirectData()
    {
        return null;
    }
}
