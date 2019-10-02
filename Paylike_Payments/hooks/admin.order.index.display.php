<?php
// The line below prevents direct access to this file which may lead to a path disclosure vulnerability
if(!defined('CC_DS')) die('Access Denied');


$g = isset($GLOBALS['_GET']['_g'])?$GLOBALS['_GET']['_g']:'';
$action = isset($GLOBALS['_GET']['action'])?$GLOBALS['_GET']['action']:'';
$orderid = isset($GLOBALS['_GET']['order_id'])?$GLOBALS['_GET']['order_id']:'';

// order edit page
if($g=='orders'&&$action=='edit'&&$orderid) {
  // paid with paylike
  if($summary[0]['gateway']=='Paylike Payments'||$summary[0]['gateway']=='Paylike_Payments') {
    // display only if = Captured
    $txns = $GLOBALS['db']->select('CubeCart_transactions', false, array('order_id'=>$orderid,'gateway'=>'Paylike_Payments'), array('time'=>'DESC'));
    if ($txns) {

      // init module lang
      $modlang = $GLOBALS['language']->getStrings('paylike_text');



      // Void when only Authorized
      if($txns[0]['status']=='Authorized') {
        $tabcontent = <<<EOT
<div id="plvoid" class="tab_content">
   <h3>${modlang['void_title']}</h3>
   <table>
    <tbody>
      <tr>
        <td>
           <span>
            <input type="hidden" name="confirmplvoid" id="confirmplvoid" class="toggle" value="0" original="0">
           </span>
        </td>
        <td>
          <label for="confirmplvoid" style="color:red;">${modlang['void_confirm']}</label>
        </td>
      </tr>
    </tbody>
   </table>
</div>
EOT;
        $smarty_data['plugin_tabs'][] = $tabcontent;
      }




      // Refund when only Captured
      if($txns[0]['status']=='Captured') {
$tabcontent = <<<EOT
<div id="plrefund" class="tab_content">
   <h3>${modlang['refund_title']}</h3>
   <table>
    <tbody>
      <tr>
        <td>
           <span>
            <input type="hidden" name="confirmplrefund" id="confirmplrefund" class="toggle" value="0" original="0">
           </span>
        </td>
        <td>
          <label for="confirmplrefund" style="color:red;">${modlang['refund_confirm']}</label>
        </td>
      </tr>
    </tbody>
   </table>
</div>
EOT;
        $smarty_data['plugin_tabs'][] = $tabcontent;  
      }





    }

  }
}

