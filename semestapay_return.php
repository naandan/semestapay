<?php

require "../../../init.php";
$whmcs->load_function("gateway");
$whmcs->load_function("invoice");

$gatewayParams = getGatewayVariables('semestapay');

if (empty($_REQUEST['reference']) || empty($_REQUEST['paymentId']) || empty($_REQUEST['merchantCode'])) {
	error_log('wrong query string please contact admin.');
	echo 'wrong query string please contact admin.';
	exit;
}

$reference = stripslashes($_REQUEST['reference']);
$paymentId = stripslashes($_REQUEST['paymentId']);
$merchantCode = stripslashes($_REQUEST['merchantCode']);

$target = $gatewayParams['endpoint']. '/api/v1/payment/confirm/?reference=' . $reference;
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
		$url = $CONFIG['SystemURL'] . "/clientarea.php";
	  } else if ($respone->status == 0) {
		$url = $CONFIG['SystemURL'] . "/viewinvoice.php?id=" . $respone->paymentId;
	  } else {
		$url = $CONFIG['SystemURL'] . "/viewinvoice.php?id=" . $respone->paymentId . "&paymentfailed=true";
	  }
	  header('Location: ' . $url);
	  exit;
	} catch (Exception $e) {
	  echo $e->getMessage();
	}
} else {
	throw new Exception("Semestapay payment need curl extension, please enable curl extension in your web server");
}
			