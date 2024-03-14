<?php

require "../../../init.php";
$whmcs->load_function("gateway");
$whmcs->load_function("invoice");

$gatewayParams = getGatewayVariables('semestapay');

if (empty($_REQUEST['transactionCode']) || empty($_REQUEST['invoiceCode']) || empty($_REQUEST['merchantCode'])) {
	error_log('wrong query string please contact admin.');
	echo 'wrong query string please contact admin.';
	exit;
}

$transactionCode = stripslashes($_REQUEST['transactionCode']);
$invoiceCode = stripslashes($_REQUEST['invoiceCode']);
$merchantCode = stripslashes($_REQUEST['merchantCode']);

$target = $gatewayParams['endpoint']. '/api/v1/payment/confirm-payment/'. '?code=' . $transactionCode;
$merchant = $gatewayParams['code'];
$apikey = $gatewayParams['apikey'];
$timestamp = round(microtime(true) * 1000);

$signature = Semestapay_Helper::generate_signature($merchant, $apikey, $timestamp);
$headers = array(
	'Content-Type: application/json',
	'signature: ' . $signature,
	'merchantcode: ' . $merchant,
	'timestamp: ' . $timestamp
);

if (extension_loaded('curl')) {
	try {
	  $respone = Semestapay_ApiRequestor::get($target, $headers);
	  if ($respone->status == 1 || $respone->amount > 0) {
		addInvoicePayment(
		  $respone->invoiceCode,
		  $respone->transactionCode,
		  $respone->amount,
		  0,
		  "semestapay"
		);
		logActivity('semestapay notification accepted: Payment success order ' . $respone->merchantOrderId . '.', 0);
		echo "Payment success notification accepted";
		$url = $CONFIG['SystemURL'] . "/viewinvoice.php?id=" . $respone->invoiceCode . "&paymentsuccess=true";
	  } else if ($respone->status == 0) {
		$url = $CONFIG['SystemURL'] . "/viewinvoice.php?id=" . $respone->invoiceCode;
	  } else {
		$url = $CONFIG['SystemURL'] . "/viewinvoice.php?id=" . $respone->invoiceCode . "&paymentfailed=true";
	  }
	  header('Location: ' . $url);
	  exit;
	} catch (Exception $e) {
	  echo $e->getMessage();
	}
} else {
	throw new Exception("Semestapay payment need curl extension, please enable curl extension in your web server");
}
			