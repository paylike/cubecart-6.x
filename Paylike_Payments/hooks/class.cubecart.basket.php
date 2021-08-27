<?php
if(!defined('CC_INI_SET')) die('Access Denied');


//include currency handling
require 'modules/plugins/Paylike_Payments/currencies.php';

// Build Paylike JS Params (cc_paylike_params)
$selmod = $GLOBALS['db']->select('CubeCart_modules','*',array('folder'=>'Paylike_Payments'));
$paylike = $selmod[0];
$modcfg = $GLOBALS['config']->get('Paylike_Payments');
$paylikejs['customer_IP'] = get_ip_address();
$paylikejs['key'] = $modcfg['livekey_public'];
if($modcfg['mode']=='test') { $paylikejs['key'] = $modcfg['testkey_public']; }
$paylikejs['order_id'] = isset($GLOBALS['cart']->basket['cart_order_id'])?$GLOBALS['cart']->basket['cart_order_id']:'';
$paylikejs['products'] = array();
$cart_contents = $GLOBALS['cart']->basket['contents'];
if(is_array($cart_contents)) {
  foreach ($cart_contents as $cp) {
    $paylikejs['products'][] = array('ID'=>$cp['id'],'name'=>$cp['name'],'quantity'=>$cp['quantity']);
  }
}
$paylikejs['platform_version'] = CC_VERSION;
$paylikejs['version'] = 'Unknown';

// load plugin config xml
try {
    $xml   = new SimpleXMLElement(file_get_contents('modules/plugins/Paylike_Payments/config.xml'));
} catch (Exception $e) {
    trigger_error($e, E_USER_WARNING);
}

// get version info from xml
if (is_object($xml)) {
  if(isset($xml->info->version)) {
    $paylikejs['version'] = (string)$xml->info->version;
  }
}

if ($GLOBALS['session']->has('currency', 'client')) {
    $clientCurrency = $GLOBALS['session']->get('currency', 'client');
} else {
    $clientCurrency = $storeCurrency;
}

$paylikejs['test_mode'] = $modcfg['mode'];
$paylikejs['title'] = $GLOBALS['config']->get('config','store_name');
$paylikejs['currency'] = $clientCurrency;
$paylikejs['amount'] = (int)(get_paylike_amount($GLOBALS['cart']->getTotal(), $clientCurrency));
$paylikejs['exponent'] = get_paylike_currency($clientCurrency)['exponent'];
$paylikejs['locale'] = $GLOBALS['config']->get('config','default_language');

$paylikejs['address_defined'] = false;
$user = $GLOBALS['user']->get();
if($user) {
  $paylikejs['name'] = $user['first_name']." ".$user['last_name'];
  $paylikejs['email'] = $user['email'];
  $paylikejs['phone'] = $user['phone'];
  $uad = $GLOBALS['user']->getAddresses(false); // default billing address, true for all addresses
  $paylikejs['address'] = implode(' ', array($uad[0]['line1'], $uad[0]['line2'], $uad[0]['town'], $uad[0]['state'], $uad[0]['postcode'], $uad[0]['country']));
  $paylikejs['address_defined'] = true;
}

if(isset($GLOBALS['cart']->basket['billing_address']['user_defined'])) {
  if($GLOBALS['cart']->basket['billing_address']['user_defined']) {
    $paylikejs['address_defined'] = true;
  }
}

$content .= '<script type="text/javascript">var cc_paylike_params = '.json_encode($paylikejs).';</script>
<script src="modules/plugins/Paylike_Payments/skin/scripts/paylike_checkout.js"></script>
<script src="https://sdk.paylike.io/10.js"></script>';
