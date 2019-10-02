<?php
// The line below prevents direct access to this file which may lead to a path disclosure vulnerability
if(!defined('CC_DS')) die('Access Denied');

//include currency handling
require 'modules/plugins/Paylike_Payments/currencies.php';

/* Capture block for authorized payments */
// orderid exists
if($record['cart_order_id']) {
  // when order status set to complete
  if(isset($_POST['order']['status'])&&$_POST['order']['status']=='3') {
    // get latest transaction status, authorized|captured
    $txns = $GLOBALS['db']->select('CubeCart_transactions', false, array('order_id'=>$record['cart_order_id'],'gateway'=>'Paylike_Payments'), array('time'=>'DESC'));
    if ($txns) {
      // if authorized, attempt to capture
      if($txns[0]['status']=='Authorized') {
        // we have txnid
        if($txns[0]['trans_id']) {
          // load module vars
          $modcfg = $GLOBALS['config']->get('Paylike_Payments');
          $modlang = $GLOBALS['language']->getStrings('paylike_text');
          
          // create a new transaction log
          $newlog = $txns[0];
          unset($newlog['id']);
          $newlog['notes'] = array();
          
          // set app key
          $appkey = $modcfg['livekey_app'];
          if($modcfg['mode']=='test') {
            $appkey = $modcfg['testkey_app'];
          }

          // init Paylike
          require_once(CC_ROOT_DIR.'/modules/plugins/Paylike_Payments/api/init.php');
          $paylike = new \Paylike\Paylike($appkey);
          $transactions = $paylike->transactions();
          
          try {
            $res = $transactions->capture(
              $txns[0]['trans_id'], 
              array(
                'amount'=>get_paylike_amount($txns[0]['amount'], $storeCurrency),
                'descriptor'=>substr(preg_replace("/[^\x20-\x7e]/", "", $GLOBALS['config']->get('config','store_name')),0,22)
              )
            );
          } catch (\Paylike\Exception\NotFound $e) {
            // The transaction was not found
            $newlog['notes'][] = $e->message;
          } catch (\Paylike\Exception\InvalidRequest $e) {
            // Bad (invalid) request - see $e->getJsonBody() for the error
            if(is_array($e->getJsonBody())) {
              foreach ($e->getJsonBody() as $line) {
                $newlog['notes'][] = $line['message'];
              }
            } else {
              $newlog['notes'][] = $e->getJsonBody();
            }
          } catch (\Paylike\Exception\Forbidden $e) {
            // You are correctly authenticated but do not have access.
            $newlog['notes'][] = $e->message;
          } catch (\Paylike\Exception\Unauthorized $e) {
            // You need to provide credentials (an app's API key)
            $newlog['notes'][] = $e->message;
          } catch (\Paylike\Exception\Conflict $e) {
            // Everything you submitted was fine at the time of validation, but something changed in the meantime and came into conflict with this (e.g. double-capture).
            $newlog['notes'][] = $e->message;
          } catch (\Paylike\Exception\ApiConnection $e) {
            // Network error on connecting via cURL
            $newlog['notes'][] = $e->message;
          } catch (\Paylike\Exception\ApiException $e) {
            // Unknown api error
            $newlog['notes'][] = $e->message;
          }
          
          if($res['successful']) {
            $newlog['notes'][] = $modlang['captured'];
            $GLOBALS['main']->successMessage($modlang['captured']);
            $newlog['status'] = 'Captured';
          }

          //save new log
          $order->logTransaction($newlog);
        }
      }
    }
  }
}



/* Void block */
// void request posted
if(isset($GLOBALS['_POST']['confirmplvoid'])&&$GLOBALS['_POST']['confirmplvoid']) {
  // get latest transaction status, authorized|captured
  $txns = $GLOBALS['db']->select('CubeCart_transactions', false, array('order_id'=>$record['cart_order_id'],'gateway'=>'Paylike_Payments'), array('time'=>'DESC'));
  if ($txns) {
    // attempt to refund only when = Authorized
    if($txns[0]['status']=='Authorized') {
      // load module vars
      $modcfg = $GLOBALS['config']->get('Paylike_Payments');
      $modlang = $GLOBALS['language']->getStrings('paylike_text');

      // create a new transaction log
      $newlog = $txns[0];
      unset($newlog['id']);
      $newlog['notes'] = array();

      // set app key
      $appkey = $modcfg['livekey_app'];
      if($modcfg['mode']=='test') {
        $appkey = $modcfg['testkey_app'];
      }

      // init Paylike
      require_once(CC_ROOT_DIR.'/modules/plugins/Paylike_Payments/api/init.php');
      $paylike = new \Paylike\Paylike($appkey);
      $transactions = $paylike->transactions();

      try {
        $void = $transactions->void(
          $txns[0]['trans_id'],
          array(
            'amount'=>get_paylike_amount($txns[0]['amount'], $storeCurrency)
          )
        );
      } catch (\Paylike\Exception\NotFound $e) {
        // The transaction was not found
        $newlog['notes'][] = $e->message;
      } catch (\Paylike\Exception\InvalidRequest $e) {
        // Bad (invalid) request - see $e->getJsonBody() for the error
        if(is_array($e->getJsonBody())) {
          foreach ($e->getJsonBody() as $line) {
            $newlog['notes'][] = $line['message'];
          }
        } else {
          $newlog['notes'][] = $e->getJsonBody();
        }
      } catch (\Paylike\Exception\Forbidden $e) {
        // You are correctly authenticated but do not have access.
        $newlog['notes'][] = $e->message;
      } catch (\Paylike\Exception\Unauthorized $e) {
        // You need to provide credentials (an app's API key)
        $newlog['notes'][] = $e->message;
      } catch (\Paylike\Exception\Conflict $e) {
        // Everything you submitted was fine at the time of validation, but something changed in the meantime and came into conflict with this (e.g. double-capture).
        $newlog['notes'][] = $e->message;
      } catch (\Paylike\Exception\ApiConnection $e) {
        // Network error on connecting via cURL
        $newlog['notes'][] = $e->message;
      } catch (\Paylike\Exception\ApiException $e) {
        // Unknown api error
        $newlog['notes'][] = $e->message;
      }

      if($void['successful']) {
        $newlog['notes'][] = $modlang['voided'];
        $GLOBALS['main']->successMessage($modlang['voided']);
        $newlog['status'] = 'Voided';
      }

      //save new log
      $order->logTransaction($newlog);
    }
  }
}



/* Refund block */
// refund request posted
if(isset($GLOBALS['_POST']['confirmplrefund'])&&$GLOBALS['_POST']['confirmplrefund']) {
  // get latest transaction status, authorized|captured
  $txns = $GLOBALS['db']->select('CubeCart_transactions', false, array('order_id'=>$record['cart_order_id'],'gateway'=>'Paylike_Payments'), array('time'=>'DESC'));
  if ($txns) {
    // attempt to refund only when = Captured
    if($txns[0]['status']=='Captured') {
      // load module vars
      $modcfg = $GLOBALS['config']->get('Paylike_Payments');
      $modlang = $GLOBALS['language']->getStrings('paylike_text');

      // create a new transaction log
      $newlog = $txns[0];
      unset($newlog['id']);
      $newlog['notes'] = array();

      // set app key
      $appkey = $modcfg['livekey_app'];
      if($modcfg['mode']=='test') {
        $appkey = $modcfg['testkey_app'];
      }

      // init Paylike
      require_once(CC_ROOT_DIR.'/modules/plugins/Paylike_Payments/api/init.php');
      $paylike = new \Paylike\Paylike($appkey);
      $transactions = $paylike->transactions();
      
      try {
        $rfd = $transactions->refund(
          $txns[0]['trans_id'], 
          array(
            'amount'=>get_paylike_amount($txns[0]['amount'], $storeCurrency)
          )
        );
      } catch (\Paylike\Exception\NotFound $e) {
        // The transaction was not found
        $newlog['notes'][] = $e->message;
      } catch (\Paylike\Exception\InvalidRequest $e) {
        // Bad (invalid) request - see $e->getJsonBody() for the error
        if(is_array($e->getJsonBody())) {
          foreach ($e->getJsonBody() as $line) {
            $newlog['notes'][] = $line['message'];
          }
        } else {
          $newlog['notes'][] = $e->getJsonBody();
        }
      } catch (\Paylike\Exception\Forbidden $e) {
        // You are correctly authenticated but do not have access.
        $newlog['notes'][] = $e->message;
      } catch (\Paylike\Exception\Unauthorized $e) {
        // You need to provide credentials (an app's API key)
        $newlog['notes'][] = $e->message;
      } catch (\Paylike\Exception\Conflict $e) {
        // Everything you submitted was fine at the time of validation, but something changed in the meantime and came into conflict with this (e.g. double-capture).
        $newlog['notes'][] = $e->message;
      } catch (\Paylike\Exception\ApiConnection $e) {
        // Network error on connecting via cURL
        $newlog['notes'][] = $e->message;
      } catch (\Paylike\Exception\ApiException $e) {
        // Unknown api error
        $newlog['notes'][] = $e->message;
      }

      if($rfd['successful']) {
        $newlog['notes'][] = $modlang['refunded'];
        $GLOBALS['main']->successMessage($modlang['refunded']);
        $newlog['status'] = 'Refunded';
      }

      //save new log
      $order->logTransaction($newlog);
    }
  }
}
