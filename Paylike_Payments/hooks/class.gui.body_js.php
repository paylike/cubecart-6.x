<?php
// The line below prevents direct access to this file which may lead to a path disclosure vulnerability
if(!defined('CC_DS')) die('Access Denied');

if(isset($_GET['_a'])&&in_array($_GET['_a'],array('checkout','basket','confirm','gateway'))) {
  $pluginjs = 'modules/plugins/Paylike_Payments/skin/scripts/paylike_checkout.js';
  $body_js[] = $pluginjs;
}
