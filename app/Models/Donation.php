<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Session;
use Omnipay\Common\GatewayFactory;

// use AuthorizeNet\Common\Type\Customer as AuthorizeNetCustomer;
// use AuthorizeNet\Common\Type\PaymentProfile as AuthorizeNetPaymentProfile;
// use AuthorizeNet\Common\Type\Address as AuthorizeNetAddress;
// use AuthorizeNet\Common\Type\Transaction as AuthorizeNetTransaction;
// use AuthorizeNet\Common\Type\LineItem as AuthorizeNetLineItem;
// use AuthorizeNet\Service\Cim\Request as AuthorizeNetCIM;
// use AuthorizeNet\Service\Aim\Request as AimRequest;


require_once(app_path().'/../vendor/authorizenet/authorizenet/lib/AuthorizeNetCIM.php');
require_once(app_path().'/../vendor/authorizenet/authorizenet/lib/AuthorizeNetARB.php');


class Donation extends Model
{

    use SoftDeletes;

    protected $dates = ['deleted_at'];
    
    
    /**
     * returns the designation whether it is an entity or an actual designation.
     *
     * @access public
     * @param mixed $type
     * @param mixed $designation_id
     * @return void
     */
    public function getDesignation($type, $designation_id)
    {
        
        $designation = ['code' => '', 'name' => '', 'type' => $type, 'id' => $designation_id, 'emailset_id' => '','program_name'=>''];

        if ($type == 1) {
            $donor = new Donor;
            $entity = $donor->getEntityName($designation_id);
            $program = Program::find($entity['program_id']);
            $emailset_id = '';
            $program_name = '';
            
            if (count($program)) {
                $emailset_id = $program->emailset_id;
                $program_name = $program->name;
            }
            $designation = ['name' => $entity['name'],'type'=>$type,'id'=>$designation_id, 'emailset_id' => $emailset_id,'program_name'=>$program_name];
        } else {
            $d = Designation::withTrashed()->find($designation_id);

            if (count($d)) {
                $designation = ['code' => $d->code, 'name' => $d->name,'type'=>$type,'id'=>$designation_id, 'emailset_id' => $d->emailset_id,'program_name'=>''];
            }
        }
        return $designation;
    }


    /**
     * convert the method id stored in the donation table in human readable format.
     *
     * @access public
     * @param mixed $method_id
     * @return void
     */
    public function getMethod($method_id)
    {

        $donor = new Donor;

        return $donor->getMethod($method_id) ;
    }
    

    /**
     * convert the method id stored in the donation table in human readable format.
     *
     * @access public
     * @param mixed $method_id
     * @return void
     */
    public function getMethods()
    {

        $donor = new Donor;

        return $donor->getMethods();
    }
    
    public function getFrequency($frequency_id)
    {
        
        $donor = new Donor;
        
        return $donor->getFrequency($frequency_id);
    }

    public function getFrequencyTotal($amount, $frequency)
    {
        
        $donor = new Donor;
        
        return $donor->getFrequencyTotal($amount, $frequency);
    }

    public function getFrequencies()
    {
        $donor = new Donor;
        return $donor->getFrequencies();
    }

    public function getDesignationFrequencies()
    {
        $donor = new Donor;
        return $donor->getDesignationFrequencies();
    }


    public function getDonationsTable($donations)
    {
        $out = [];
        foreach ($donations as $d) {
            $designation = $d->getDesignation($d->type, $d->designation);
                
            $type = '';
            if ($d->type == 1) {
                $type = 'Sponsorship';
            } else if ($d->type == 2) {
                $type = 'Donation';
            } else if ($d->type == 3) {
                $type = 'Recurring Donation';
            }
                
            $name = $designation['name'];
            $code = '';
            if (isset($designation['code'])) {
                $code = $designation['code'];
            }
                
            $method = $d->getMethod($d->method);
                
            $out[] = [
            'id' => $d->id,
            'date' => $d->created_at,
            'type' => $type,
            'code' => $code,
            'designation' => $name,
            'method' => $method,
            'result' => $d->result,
            'amount' => sprintf("%01.2f", $d->amount)
            ];
        }
            return $out;
    }

    public function getDonationsTableHTML($donations)
    {
        $d = new Donation;
        $out = $this->getDonationsTable($donations);
        $total = $donations->sum('amount');
        $processed="<table><thead><tr><th>Date</th><th>Gift Designation</th><th>Method</th><th>Amount</th></tr></thead><tbody>";
        if (!empty($out)) {
            foreach ($out as $o) {
                $processed.='<tr>'.
                '<td>'.$o['date']->toFormattedDateString().'</td>'.
                '<td>'.$o['designation'].'</td>'.
                '<td>'.$o['method'].'</td>'.
                '<td>'.$o['amount'].'</td>'.
                '</tr>';
            }
            $processed.='<tr><td></td><td>'.count($out).' Donations</td><td>Totaling:</td><td>'.$total.'</td></tr>';
            $processed.="</tbody></table>";

            return $processed;
        } else {
            return '';
        }
    }
    
    
    /**
     * retrieves the gateway settings.
     *
     * @access private
     * @return void
     */
    private function getGatewaySettings($client_id = null)
    {

        if ($client_id == null) {
            $client_id = Session::get('client_id');
        }


        $settings = Gateway::whereClientId($client_id)->orderBy('default', 'desc')->get();
        
        $return= [];

        $gateways = [];

        foreach ($settings as $setting) {
            $gateway = $setting->gateway;
            $gateway_settings = json_decode($setting->settings);
            
            if ($gateway=='Stripe') {
                $gateways[]= ['gateway' => $gateway, 'apiKey' => $gateway_settings->StripeApiKey];
            }

            if ($gateway=='AuthorizeNet') {
                $gateways[]= ['gateway' => $gateway,
                    'apiLoginId' => $gateway_settings->ApiLoginId,
                    'transactionKey'=>$gateway_settings->TransactionKey];
            }
        }

        return $gateways;
    }

    /**
     * retrieves the gateway.
     *
     * @access private
     * @return void
     */
    private function getGateway($client_id, $all = false)
    {

        $gate_factory= new GatewayFactory;
        $all_settings = $this->getGatewaySettings($client_id);
        $gateways=[];
        foreach ($all_settings as $settings) {
            if ($settings['gateway']=='Stripe') {
                $gateway = $gate_factory->create($settings['gateway']);
                $gateway->setApiKey($settings['apiKey']);
                $gateway->gateway_type='stripe';
                if ($all) {
                    $gateways['Stripe']=$gateway;
                } else {
                    return $gateway;
                }
            }

            if ($settings['gateway']=='AuthorizeNet') {
                //$gateway->setApiLoginId($settings['apiLoginId']);
                //$gateway->setTransactionKey($settings['transactionKey']);
                $gateway= new AuthorizeNetCIM($settings['apiLoginId'], $settings['transactionKey']);
                $gateway->setSandbox(false);
                $gateway->gateway_type='authorize';
                if ($all) {
                    $gateways['AuthorizeNet']=$gateway;
                } else {
                    return $gateway;
                }
            }
        }

        if ($all) {
            return $gateways;
        } else {
            return null;
        }
    }

    /**
     * retrieves the gateway.
     *
     * @access private
     * @return void
     */
    private function getARBGateway($client_id)
    {

        $gate_factory= new GatewayFactory;
        $all_settings = $this->getGatewaySettings($client_id);
        $gateways=[];
        foreach ($all_settings as $settings) {
            if ($settings['gateway']=='AuthorizeNet') {
                //$gateway->setApiLoginId($settings['apiLoginId']);
                //$gateway->setTransactionKey($settings['transactionKey']);
                $gateway= new AuthorizeNetARB($settings['apiLoginId'], $settings['transactionKey']);
                $gateway->setSandbox(false);
                $gateway->gateway_type='authorize';
                return $gateway;
            }
        }
            return null;
    }
    
    public function isDonorCardActive($donor_id, $client_id = null)
    {
        if ($client_id == null) {
            $client_id = Session::get('client_id');
        }

        $gateway_type=$this->checkUseCC($client_id);

        if ($gateway_type=='authorize') {
            $donor=Donor::find($donor_id);
            if (!empty($donor->authorize_profile)) {
                return true;
            } else {
                return false;
            }
        }
        if ($gateway_type=='stripe') {
            $donor=Donor::find($donor_id);
            if (!empty($donor->stripe_cust_id)) {
                return true;
            } else {
                return false;
            }
        }


        return false;
    }

    public function isAnyDonorCardActive($donor_id, $client_id = null)
    {
        if ($client_id == null) {
            $client_id = Session::get('client_id');
        }

        $gateway_type=$this->checkUseCC($client_id);

        if ($gateway_type=='authorize') {
            $donor=Donor::find($donor_id);
            if (!empty($donor->authorize_profile)) {
                return true;
            } else {
                if ($this->checkUseStripe($client_id)&&!empty($donor->stripe_cust_id)) {
                    return true;
                }

                return false;
            }
        }
        if ($gateway_type=='stripe') {
            $donor=Donor::find($donor_id);
            if (!empty($donor->stripe_cust_id)) {
                return true;
            } else {
                if ($this->checkUseAuthorize($client_id)&&!empty($donor->authorize_profile)) {
                        return true;
                }

                return false;
            }
        }

        
        return false;
    }

        
    /**
     * checks to see if client is using stripe
     * by checking to see if api key has been saved.
     *
     * @access public
     * @return void
     */
    public function checkUseStripe($client_id = null)
    {
        if ($client_id == null) {
            $client_id = Session::get('client_id');
        }

        $useStripe = false;
        $gateway = Gateway::whereClientId($client_id)->where('gateway', 'Stripe')->first();
        if (isset($gateway->settings)) {
            $settings = json_decode($gateway->settings, true);
            if (!empty($settings['StripeApiKey'])) {
                $useStripe = true;
            }
        }
        return $useStripe;
    }

    /**
     * checks to see if client is using stripe
     * by checking to see if api key has been saved.
     *
     * @access public
     * @return void
     */
    public function checkUseCC($client_id = null)
    {
        if ($client_id == null) {
            $client_id = Session::get('client_id');
        }

        $useCC = false;
        $gateway = Gateway::whereClientId($client_id)->where(function ($query) {
            $query->where('gateway', 'Stripe')->orWhere('gateway', 'AuthorizeNet');
        })->orderBy('default', 'desc')->first();
        
        if (isset($gateway->settings)) {
            $settings = json_decode($gateway->settings, true);
            if (isset($settings['ApiLoginId'])&&!empty($settings['ApiLoginId'])&&isset($settings['TransactionKey'])&&!empty($settings['TransactionKey'])) {
                $useCC= 'authorize';
            }
            if (isset($settings['StripeApiKey'])&&!empty($settings['StripeApiKey'])) {
                $useCC = 'stripe';
            }
        }

        return $useCC;
    }


    /**
     * checks to see if client is using Authorize.net
     * by checking to see if API Login ID has been saved.
     *
     * @access public
     * @return void
     */
    public function checkUseAuthorize($client_id = null)
    {
        if ($client_id == null) {
            $client_id = Session::get('client_id');
        }

        $useAuthorize = false;
        $gateway = Gateway::whereClientId($client_id)->where('gateway', 'AuthorizeNet')->first();
        if (isset($gateway->settings)) {
            $settings = json_decode($gateway->settings, true);
            if (!empty($settings['ApiLoginId'])&&!empty($settings['TransactionKey'])) {
                $useAuthorize = true;
            }
        }
        return $useAuthorize;
    }

    
    /**
     * Create customer (save the credit card).
     *
     * @access public
     * @param array $card
     * @param array $params
     * @return void
     */
    public function createCustomer(array $card, array $params, $client_id = null)
    {
        if ($client_id == null) {
            $client_id = Session::get('client_id');
        }

        $gateway=$this->getGateway($client_id);
        $donor = Donor::find($params['donor_id']);

        if (!count($donor)) {
            return false;
        }


        if ($gateway->gateway_type=='stripe') {
            //If there is address information for this Donor, get it!
            $address_info=$donor->getAddressInfo($donor->id, 'stripe');

            //Add the address info to card (if there is any)
            $params['card'] = array_merge($card, $address_info);

            $response= $gateway->createCard($params)->send();
            $response->success=$response->isSuccessful();
            if ($response->success) {
                $response->result=$response->getTransactionReference();
                $donor->stripe_cust_id = $response->getCardReference();
                $donor->authorize_profile = '';
                $donor->save();
            } else {
                $response->result = $response->getMessage();
            }
            return $response;
        }

        if ($gateway->gateway_type=='authorize') {
            //If there is address information for this Donor, get it!
            $address_info=$donor->getAddressInfo($donor->id, 'authorize');

            $params['card'] = $card;

            //Add Customer profile
            $customerProfile = new AuthorizeNetCustomer();
            $customerProfile->description = $card['firstName']." ".$card['lastName'];
            //$profile['merchant_customer_id']=
            $customerProfile->merchantCustomerId = time().rand(1, 100);
            ;
            $customerProfile->email = $donor->email;

            // Add payment profile.
            $paymentProfile = new AuthorizeNetPaymentProfile();
            $paymentProfile->customerType = "individual";
            $paymentProfile->payment->creditCard->cardNumber = $card['number'];
            $paymentProfile->payment->creditCard->expirationDate = $card['expiryYear'].'-'.$card['expiryMonth'];

            if (!empty($address_info)) {
                 $paymentProfile->billTo->firstName = $card['firstName'];
                 $paymentProfile->billTo->lastName = $card['lastName'];
                 
                if (isset($address_info['billingAddress1'])) {
                    $paymentProfile->billTo->address = $address_info['billingAddress1'];
                }
                if (isset($address_info['billingCity'])) {
                    $paymentProfile->billTo->city = $address_info['billingCity'];
                }
                if (isset($address_info['billingState'])) {
                    $paymentProfile->billTo->state = $address_info['billingState'];
                }
                if (isset($address_info['billingPostCode'])) {
                    $paymentProfile->billTo->zip = $address_info['billingPostCode'];
                }
            }

            $customerProfile->paymentProfiles[] = $paymentProfile;

            $response = $gateway->createCustomerProfile($customerProfile);

            $response->result=$response->xml->messages->message->code." : ". $response->xml->messages->message->text ;
            $response->success=$response->isOk();

            if ($response->success) {
                $profile['customer_id']=$response->getCustomerProfileId();

                $temp_profile=$response->getCustomerPaymentProfileIds();
                $profile['profile_id']=$temp_profile;
                $donor->authorize_profile = json_encode($profile);
                $donor->stripe_cust_id = '';
                $donor->save();
            }
            return $response;
        }

        // $response = $gateway->createCard($params)->send();
        // return $response;
        return false;
    }


    
    /**
     * create the charge to saved card by passing in the gateway customer id in params.
     *
     * @access public
     * @param array $params
     * @return void
     */
    public function createCharge(array $params, $client_id = null)
    {
        
        if ($client_id == null) {
            $client_id = Session::get('client_id');
        }
        
        
        $donor = Donor::find($params['donor_id']);


        if ($this->isDonorCardActive($params['donor_id'])) {
            $gateway= $this->getGateway($client_id);
        } else {
            $gateways=$this->getGateway($client_id, true);

            if (!empty($donor->stripe_cust_id)) {
                $gateway=$gateways['Stripe'];
            }
            if (!empty($donor->authorize_profile)) {
                $gateway=$gateways['AuthorizeNet'];
            }
        }

        

        if ($gateway->gateway_type=='stripe') {
            $params['cardReference'] = $donor->stripe_cust_id;
            $response = $gateway->purchase($params)->send();

            $response->success=$response->isSuccessful();
            $response->result=$response->getTransactionReference();

            return $response;
        }

        if ($gateway->gateway_type=='authorize') {
            $old_profile=json_decode($donor->authorize_profile);

            $transaction = new AuthorizeNetTransaction();
            $transaction->amount = $params['amount'];
            $transaction->customerProfileId = $old_profile->customer_id;
            $transaction->customerPaymentProfileId = $old_profile->profile_id;

            $lineItem              = new AuthorizeNetLineItem;
            $lineItem->itemId      = "1";
            $lineItem->name        = "Sponsorship";
            $lineItem->description = $params['description'];
            $lineItem->quantity    = "1";
            $lineItem->unitPrice   = $params['amount'];
            $lineItem->taxable     = "false";

            $transaction->lineItems[] = $lineItem;

            $response= $gateway->createCustomerProfileTransaction("AuthCapture", $transaction);

            $transactionResponse = $response->getTransactionResponse();

            if ($response->success=$response->isOk()) {
                $response->result=$transactionResponse->transaction_id;
            } else {
                $response->result= "Error Code : ".$response->xml->messages->message->code ." : " . $response->xml->messages->message->text;
            }
            
            return $response;
        }
    }

    // /**
    //  * create the charge to saved card by passing in the gateway customer id in params.
    //  *
    //  * @access public
    //  * @param array $params
    //  * @return void
    //  */
    // public function createGatewayAgnosticCharge(array $params, $client_id = null) {
        
    //  if ($client_id == null)
    // 		$client_id = Session::get('client_id');
        
    // 	$gateways=$this->getGateway($client_id,true);

    // 	if($this->isDonorCardActive($params['donor_id']))
    // 		$gateway= $gateways[0];
    // 	elseif(isset($gateways[1])&&!empty($gateways[1]))
    // 	{
    // 		$gateway=$gateways[1];
    // 	}


    // 	$donor = Donor::find($params['donor_id']);

    // 	if($gateway->gateway_type=='stripe')
    // 	{
    // 		$params['cardReference'] = $donor->stripe_cust_id;
    // 		$response = $gateway->purchase($params)->send();

    // 		$response->success=$response->isSuccessful();
    // 		$response->result=$response->getTransactionReference();

    // 		return $response;
    // 	}

    // 	if($gateway->gateway_type=='authorize')
    // 	{
    // 		$old_profile=json_decode($donor->authorize_profile);

    // 	    $transaction = new AuthorizeNetTransaction();
    // 	    $transaction->amount = $params['amount'];
    // 	    $transaction->customerProfileId = $old_profile->customer_id;
    // 	    $transaction->customerPaymentProfileId = $old_profile->profile_id;

    // 	    $response= $gateway->createCustomerProfileTransaction("AuthCapture", $transaction);
            
    // 	    $transactionResponse = $response->getTransactionResponse();

    // 	    $response->result= "Error Code : ".$response->xml->messages->message->code ." : " . $response->xml->messages->message->text;
    // 		$response->success=$response->isOk();

    // 		return $response;

    // 	}
        
    // }
    
    public function modifyARBCommitmentAmount($commitment, $new_amount)
    {


        $client_id= $commitment->client_id;

        $client = Client::find($client_id);
        if ($client->arb_enabled!='1') {
            return false;
        }

        $gateway = $this->getARBGateway($client_id);
        
        $subscription = new AuthorizeNet_Subscription;
        
        $subscription->amount = $new_amount;
        
        $response = $gateway->updateSubscription($commitment->arb_subscription_id, $subscription);

        if ($response->xml->messages->resultCode=='Ok') {
            return 'Ok';
        } else {
            return $response->response;
        }
    }
    
    /**
     * update saved credit card at gateway.
     *
     * @access public
     * @param array $card
     * @param array $params
     * @return void
     */
    public function updateCard(array $card, array $params, $client_id = null)
    {
        if ($client_id == null) {
            $client_id = Session::get('client_id');
        }
        

        $gateway=$this->getGateway($client_id);
        $donor = Donor::find($params['donor_id']);

        if ($gateway->gateway_type=='stripe') {
            //If there is address information for this Donor, get it!
            $address_info=$donor->getAddressInfo($donor->id, 'stripe');

            //Add the address info to card (if there is any)
            $params['card'] = array_merge($card, $address_info);

            $params['cardReference']=$donor->stripe_cust_id;
            $response= $gateway->updateCard($params)->send();
            $response->success=$response->isSuccessful();
            if ($response->success) {
                $response->result=$response->getTransactionReference();
                $donor->stripe_cust_id = $response->getCardReference();
                $donor->authorize_profile = '';
                $donor->save();
            }
            return $response;
        }
        
        if ($gateway->gateway_type=='authorize') {
            //If there is address information for this Donor, get it!
            $address_info=$donor->getAddressInfo($donor->id, 'authorize');

            $params['card'] = $card;

            $customerProfile = new AuthorizeNetCustomer();
            $customerProfile->description = $card['firstName']." ".$card['lastName'];
            $profile['customer_id']=time().rand(1, 100);
            $customerProfile->merchantCustomerId = $profile['customer_id'];
            $customerProfile->email = $donor->email;

            // Add payment profile.
            $paymentProfile = new AuthorizeNetPaymentProfile();
            $paymentProfile->customerType = "individual";
            $paymentProfile->payment->creditCard->cardNumber = $card['number'];
            $paymentProfile->payment->creditCard->expirationDate = $card['expiryYear'].'-'.$card['expiryMonth'];

            if (!empty($address_info)) {
                 $paymentProfile->billTo->firstName = $card['firstName'];
                 $paymentProfile->billTo->lastName = $card['lastName'];
                 
                if (isset($address_info['billingAddress1'])) {
                    $paymentProfile->billTo->address = $address_info['billingAddress1'];
                }
                if (isset($address_info['billingCity'])) {
                    $paymentProfile->billTo->city = $address_info['billingCity'];
                }
                if (isset($address_info['billingState'])) {
                    $paymentProfile->billTo->state = $address_info['billingState'];
                }
                if (isset($address_info['billingPostCode'])) {
                    $paymentProfile->billTo->zip = $address_info['billingPostCode'];
                }
            }

            $customerProfile->paymentProfiles[] = $paymentProfile;

            $response = $gateway->createCustomerProfile($customerProfile);

            $response->result=$response->xml->messages->message->code." : ". $response->xml->messages->message->text ;
            $response->success=$response->isOk();

            if ($response->success) {
                if (!empty($donor->authorize_profile)) { //If a previous entry exists, delete it from Authorize.net
                    $old_profile=json_decode($donor->authorize_profile);
                    $gateway->deleteCustomerPaymentProfile($old_profile->profile_id, $old_profile->customer_id);
                    $gateway->deleteCustomerProfile($old_profile->profile_id);
                }
                
                $profile['customer_id']=$response->getCustomerProfileId();
                $profile['profile_id']=$response->getCustomerPaymentProfileIds();
                $donor->authorize_profile = json_encode($profile);
                $donor->stripe_cust_id = '';
                $donor->save();
            }
            return $response;
        }
    }
    
    
       /**
         * Returns donor stripe_cust_id.
         *
         * @access public
         * @return Stripe ID or False
         */
    public function getDonorStripeId($donor_id)
    {
        $donor = Donor::find($donor_id);

        if (!empty($donor->stripe_cust_id)) {
            $stripe_id= $donor->stripe_cust_id;
        }
                
        if (isset($stripe_id)) {
                return ($stripe_id);
        } else {
            return (false);
        }
    }
    
    /**
     * delete the credit card saved at the gateway.
     *
     * @access public
     * @param array $params
     * @param mixed $settings_id
     * @return void
     */
    public function deleteCard(array $params, $client_id = null)
    {
        
        if ($client_id == null) {
            $client_id = Session::get('client_id');
        }
        
        $gateway=$this->getGateway($client_id);
        $donor = Donor::find($params['donor_id']);

        if ($gateway->gateway_type=='stripe') {
            $params['cardReference']=$donor->stripe_cust_id;
            $response= $gateway->deleteCard($params)->send();
            $response->success=$response->isSuccessful();
            $response->result=$response->getTransactionReference();
            
            $donor->stripe_cust_id = '';
            $donor->authorize_profile = '';
            $donor->save();
            
            return $response;
        }

        if ($gateway->gateway_type=='authorize') {
            if (!empty($donor->authorize_profile)) { //If a previous entry exists, delete it from Authorize.net
                $old_profile=json_decode($donor->authorize_profile);
                $gateway->deleteCustomerPaymentProfile($old_profile->customer_id, $old_profile->profile_id);
                $response = $gateway->deleteCustomerProfile($old_profile->profile_id);
            }
            $response->result='';
            $response->success=$response->isOk();
            
            $response->result='Deleted';
            $donor->stripe_cust_id = '';
            $donor->authorize_profile = '';
            $donor->save();
            
            return $response;
        }
    }


    public function getYears($donor)
    {

        $years = [];

        foreach (Donation::where('donor_id', $donor->id)->get() as $donation) {
            $years[$donation->id]=$donation->created_at->year;
        }

        return array_unique($years);
    }

    //This function returns a list of all years that
    public function getAllYears($hysform_id)
    {

        $years = [];

        $donors = Donor::where('hysform_id', $hysform_id)->lists('id');

        if (count($donors)<1) {
            return [];
        }

        foreach (Donation::whereIn('donor_id', $donors)->get()->groupBy(function ($date) {
            return Carbon::parse($date->created_at)->format('Y');
        }) as $y => $year) {
            $years[$y] =$y;
        }

        return $years;
    }
}
