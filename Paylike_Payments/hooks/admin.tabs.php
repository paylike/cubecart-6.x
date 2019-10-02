<?php
// The line below prevents direct access to this file which may lead to a path disclosure vulnerability
if(!defined('CC_DS')) die('Access Denied');



$g = isset($GLOBALS['_GET']['_g'])?$GLOBALS['_GET']['_g']:'';
$action = isset($GLOBALS['_GET']['action'])?$GLOBALS['_GET']['action']:'';
$orderid = isset($GLOBALS['_GET']['order_id'])?$GLOBALS['_GET']['order_id']:'';

// on order edit page
if($g=='orders'&&$action=='edit'&&$orderid) {
  // paid with Paylike
  $selsummary = $GLOBALS['db']->select('CubeCart_order_summary','gateway',array('cart_order_id'=>$orderid));
  if($selsummary[0]['gateway']=='Paylike Payments'||$selsummary[0]['gateway']=='Paylike_Payments') {
    // display only if = Captured
    $txns = $GLOBALS['db']->select('CubeCart_transactions', false, array('order_id'=>$orderid,'gateway'=>'Paylike_Payments'), array('time'=>'DESC'));
    if ($txns) {

      // init module lang
      $modlang = $GLOBALS['language']->getStrings('paylike_text');


      // display void tab only when = Authorized
      if($txns[0]['status']=='Authorized') {
        $voidtab = array(
          'name' => $modlang['void'],
          'target' => '#plvoid',
          'url' => '',
          'accesskey' => '',
          'notify' => 0,
          'a_target' => '_self',
          'tab_id' => 'tab_plvoid'
        );
        $tabs[]=$voidtab;
      }

      // display refund tab only when = Captured
      if($txns[0]['status']=='Captured') {
        $refundtab = array(
          'name' => $modlang['refund'],
          'target' => '#plrefund',
          'url' => '',
          'accesskey' => '',
          'notify' => 0,
          'a_target' => '_self',
          'tab_id' => 'tab_plrefund'
        );
        $tabs[]=$refundtab;
      }



    }
  }
}

// clear cache when Paylike settings are saved
if($g=='plugins') {
  if(isset($_GET['module'])) {
     if ($_GET['module']=='Paylike_Payments') {
       if(isset($_POST['module']['status'])) {
         $GLOBALS['cache']->clear();
       }
     }
  }
}
