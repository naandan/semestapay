<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once(dirname(__FILE__) . '/semestapay-lib/Semestapay.php');

/*
 * Semestapay
 * https://sti-group.co.id
 */

 /*
 * Meta Data
 * @return array
 */
function semestapay_MetaData()
{
    return array(
        'DisplayName' => 'Semestapay Payment Gateway Module',
        'APIVersion' => '1.0', // Use API Version 1.1
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => true,
    );
}

/*
 * Configuration Page
 * @return array
 */
function semestapay_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Semestapay',
        ),
        'apikey' => array(
            'FriendlyName' => 'API Key',
            'Type' => 'password',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Masukkan API Key.',
        ),
        'endpoint' => array(
            'FriendlyName' => 'Endpoint',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Masukkan Endpoint.',
        ),
        'recheck_time' => array(
            'FriendlyName' => 'Recheck Time',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Masukkan Recheck Time.',
        ),
        'code' => array(
            'FriendlyName' => 'Code',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Masukkan Code.',
        ),
    );
}

/*
 * Payment Link
 * @param array $params
 * @return string
 */

function semestapay_link($params)
{	
    $target = $params['endpoint']. '/api/v1/payment/create-transaction/';
	$merchant = $params['code'];
    $merchantKey = $params['apikey'];
    $timestamp = round(microtime(true) * 1000);
    $signature = Semestapay_Helper::generate_signature($merchant, $merchantKey, $timestamp);
    
    $invoiceCode = $params['invoiceid'];
    $paymentAmount = $params['amount'];

    // Items Details
    $items = $params["cart"]->items->toArray();
    $itemsDetails = [];
    foreach ($items as $item) {
        $name = $item->name;
        $qty = $item->qty;
        $price = intval(preg_replace("/[^0-9]/", "", $item->amount))/100;

        $itemsDetails[] = array(
            'name' => $name,
            'quantity' => $qty,
            'price' => $price
        ); 
    }

    // Customer Details
    $customerDetails = array(
        'firstname' => $params['clientdetails']['firstname'],
        'lastname' => $params['clientdetails']['lastname'],
        'email' => $params['clientdetails']['email'],
        'phone' => $params['clientdetails']['phonenumber'],
        'address' => $params['clientdetails']['address1'],
        'city' => $params['clientdetails']['city'],
        'province' => $params['clientdetails']['state'],
        'postal_code' => $params['clientdetails']['postcode'],
        'country' => $params['clientdetails']['country']
    );

    $body = array(
        'invoiceCode' => $invoiceCode,
        'paymentAmount' => $paymentAmount,
        'customerDetails' => $customerDetails,
        'itemsDetails' => $itemsDetails,
        'callback_url' => $params['systemurl'] . '/modules/gateways/callback/semestapay_callback.php',
        'return_url' => $params['systemurl'] . '/modules/gateways/callback/semestapay_return.php',
    );

    $headers = array(
        'Content-Type: application/json',
        'signature: ' . $signature,
        'merchantcode: ' . $merchant,
        'timestamp: ' . $timestamp
    );

    if (extension_loaded('curl')) {
        try {
            $respone = Semestapay_ApiRequestor::post($target, $headers, $body);
            $payment_url = $respone->urlPayment;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    } else {
        throw new Exception("Semestapay payment need curl extension, please enable curl extension in your web server");
    }

    return "<a href='" . $payment_url . "' class='btn btn-primary'>Bayar Sekarang</a>";
}