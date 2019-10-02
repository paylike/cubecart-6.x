<?php
// The line below prevents direct access to this file which may lead to a path disclosure vulnerability
if(!defined('CC_DS')) die('Access Denied');

# Save latest transaction ID
if(isset($_POST['paylike_token'])) {
   $_SESSION['paylike_token'] = $_POST['paylike_token'];
}

#Display Paylike Option on Checkout Page
if(isset($_GET['_a'])&&in_array($_GET['_a'],array('checkout','basket','confirm'))) {
  $settings	= $GLOBALS['config']->get('Paylike_Payments');
  
  // Paylike enabled
  if ($settings['status']) {
    $selmod = $GLOBALS['db']->select('CubeCart_modules','*',array('folder'=>'Paylike_Payments'));
    $paylike = $selmod[0];
    $paylike['plugin'] = true;
    $paylike['base_folder'] = 'Paylike_Payments';
    $paylike['desc'] = $settings['desc'];

    //If Paylike is default, reset other gateways default to 0
    $newgws = $gateways;
    if($settings['default']) {
      foreach ($newgws as $gwk=>$gw) {
        $newgws[$gwk]['default'] = 0;
      }
    }

    $newgws[] = $paylike;
    usort($newgws, function($a, $b) {
      if(!isset($a['position'])) { $a['position']=100; } // prevent PHP notice errors
      if(!isset($b['position'])) { $b['position']=100; }
      if ($a['position'] == $b['position']) {
        return 0;
      }
      return ($a['position'] < $b['position']) ? -1 : 1;
    });
    $gateways=$newgws;
  }
}
  
#Proceed to Gateway Page
if(isset($_GET['_a'])&&$_GET['_a']=='gateway') {
  if($gateways[0]['folder']=='Paylike_Payments') {
    $gateways[0]['plugin'] = true;
    $gateways[0]['base_folder'] = 'Paylike_Payments';
  }
}
