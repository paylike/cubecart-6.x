<?php
class Gateway {
	private $_config;
	private $_module;
	private $_basket;
    private $_lang;
	private $_result_message;
  
    public function __construct($module = false, $basket = false) {
		$this->_config	= $GLOBALS['config']->get('config');
		$this->_module	= $module;
		$this->_basket	= $GLOBALS['cart']->basket;
        $GLOBALS['language']->loadDefinitions('paylike_text', CC_ROOT_DIR.'/modules/plugins/Paylike_Payments/language', 'module.definitions.xml');
		$this->_lang = $GLOBALS['language']->getStrings('paylike_text');
	}
  
    public function transfer() {
		$transfer	= array(
			'action'	=> 'index.php?_g=rm&type=plugins&cmd=call&module=Paylike_Payments&cart_order_id='.$this->_basket['cart_order_id'],
			'method'	=> 'post',
			'target'	=> '_self',
			'submit'	=> 'auto',
		);
		return $transfer;
	}
  
    public function call() {
      $GLOBALS['gui']->changeTemplateDir(dirname(__FILE__).'/'.'skin/');

      $GLOBALS['smarty']->assign('MODULE', $this->_module);

      $order				= Order::getInstance();
      $cart_order_id		= sanitizeVar($_GET['cart_order_id']);
      $order_summary		= $order->getSummary($cart_order_id);
      $GLOBALS['smarty']->assign('ORDERID', $cart_order_id);

      // If txn id, redirect to process
      if(isset($_SESSION['paylike_token'])) {
        $GLOBALS['smarty']->assign('TXNID', $_SESSION['paylike_token']);
        $GLOBALS['smarty']->display('redirect.tpl');
      }
    }
  
    public function process() {
      if((isset($_GET['orderid'])&&$_GET['orderid'])&&(isset($_GET['transactionid'])&&$_GET['transactionid'])) { 
        $orderid = sanitizeVar($_GET['orderid']);
        $transactionid = sanitizeVar($_GET['transactionid']);
        
        $order				= Order::getInstance();
        $order_summary		= $order->getSummary($orderid);
        
        // txn log
        $transData = array();
        $transData['status'] = 'Pending';
		$transData['trans_id'] = $transactionid;
		$transData['order_id'] = $orderid;
		$transData['amount'] = sprintf("%.2f",$order_summary["total"]);
		$transData['customer_id'] = $order_summary["customer_id"];
		//$transData['gateway'] = $this->_module['name'];
        $transData['gateway'] = "Paylike_Payments";
        $transData['notes'] = array();
        
        $confirmed = false;
        if($order_summary['status']=='1') {

          // set app key
          $appkey = $this->_module['livekey_app'];
          if($this->_module['mode']=='test') {
            $appkey = $this->_module['testkey_app'];
          }

          if($appkey) {
            require_once(__DIR__.'/api/init.php');
            $paylike = new \Paylike\Paylike($appkey);
            $transactions = $paylike->transactions();
            
            // fetch transaction
            $res = false;
            try {
                $res = $transactions->fetch($transactionid);
            } catch (\Paylike\Exception\NotFound $e) {
              // The transaction was not found
              $transData['notes'][] = $e->message;
            } catch (\Paylike\Exception\InvalidRequest $e) {
              // Bad (invalid) request - see $e->getJsonBody() for the error
              if(is_array($e->getJsonBody())) {
                foreach ($e->getJsonBody() as $line) {
                  $transData['notes'][] = $line['message'];
                }
              } else {
                $transData['notes'][] = $e->getJsonBody();
              }
            } catch (\Paylike\Exception\Forbidden $e) {
              // You are correctly authenticated but do not have access.
              $transData['notes'][] = $e->message;
            } catch (\Paylike\Exception\Unauthorized $e) {
              // You need to provide credentials (an app's API key)
              $transData['notes'][] = $e->message;
            } catch (\Paylike\Exception\Conflict $e) {
              // Everything you submitted was fine at the time of validation, but something changed in the meantime and came into conflict with this (e.g. double-capture).
              $transData['notes'][] = $e->message;
            } catch (\Paylike\Exception\ApiConnection $e) {
              // Network error on connecting via cURL
              $transData['notes'][] = $e->message;
            } catch (\Paylike\Exception\ApiException $e) {
              // Unknown api error
              $transData['notes'][] = $e->message;
            }
            


            if($res) {
              if(!$res['error']) {
                if($res['successful']) {
                  if(($res['amount']==$res['pendingAmount'])||($res['amount']==$res['capturedAmount'])) {
                    if($res['test']) {
                      $transData['notes'][] = $this->_lang['uselive'];
                    }
                    
                    // payment successful, set order&payment status
                    $order->paymentStatus(Order::PAYMENT_SUCCESS, $orderid);
                    $order->orderStatus(Order::ORDER_PROCESS, $orderid);
                    $transData['notes'][] = $this->_lang['paysuccess'];
                    if($res['pendingAmount']) { $transData['status'] = 'Authorized'; }
                    
                    // instant capture
                    if($this->_module['capturemode']=='instant') {
                      // include currency handling
                      include 'currencies.php';

                      $cap = array('successful'=>false,'capturedAmount'=>0);
                      try {
                        $cap = $transactions->capture($transactionid,array(
                          'amount' => get_paylike_amount($order_summary["total"], $storeCurrency),
                          'descriptor' => substr(preg_replace("/[^\x20-\x7e]/", "", $GLOBALS['config']->get('config','store_name')),0,22)
                        ));
                      } catch (\Paylike\Exception\NotFound $e) {
                        // The transaction was not found
                        $transData['notes'][] = $e->message;
                      } catch (\Paylike\Exception\InvalidRequest $e) {
                        // Bad (invalid) request - see $e->getJsonBody() for the error
                        if(is_array($e->getJsonBody())) {
                          foreach ($e->getJsonBody() as $line) {
                            $transData['notes'][] = $line['message'];
                          }
                        } else {
                          $transData['notes'][] = $e->getJsonBody();
                        }
                      } catch (\Paylike\Exception\Forbidden $e) {
                        // You are correctly authenticated but do not have access.
                        $transData['notes'][] = $e->message;
                      } catch (\Paylike\Exception\Unauthorized $e) {
                        // You need to provide credentials (an app's API key)
                        $transData['notes'][] = $e->message;
                      } catch (\Paylike\Exception\Conflict $e) {
                        // Everything you submitted was fine at the time of validation, but something changed in the meantime and came into conflict with this (e.g. double-capture).
                        $transData['notes'][] = $e->message;
                      } catch (\Paylike\Exception\ApiConnection $e) {
                        // Network error on connecting via cURL
                        $transData['notes'][] = $e->message;
                      } catch (\Paylike\Exception\ApiException $e) {
                        // Unknown api error
                        $transData['notes'][] = $e->message;
                      }
                      
                      if($cap['successful']&&$cap['capturedAmount']) {
                        $transData['status'] = 'Captured';
                        $transData['notes'][] = $this->_lang['captured'];
                      }
                    }
                    
                    
                    // unset txnid for popup payments
                    if(isset($_SESSION['paylike_token'])) { unset($_SESSION['paylike_token']); }
                    
                    $confirmed = true;
                    
                  } else {
                    $transData['notes'][] = $this->_lang['amountmismatch'];
                  }
                } else {
                  $transData['notes'][] = $this->_lang['notsuccessful'];
                }
              } else {
                $transData['notes'][] = $this->_lang['confirmerror'];
                // @todo : need example of error object
                //$transData['notes'][] = $res['error']->code.' : '.$res['error']->message;
              }
              // api response can be added to transaction log for more info
              //$transData['notes'][] = json_encode($res);
            } else {
              $transData['notes'][] = $this->_lang['invalidtxn'];
            }

            // Let user know, if errors on payment
            if(!$confirmed) {
              $GLOBALS['main']->errorMessage($this->_lang['user_payment_error']);
            }
          }
        }
        
        // transactionid already exists?
        $trans_id	= $GLOBALS['db']->select('CubeCart_transactions', array('id'), array('trans_id' => $transactionid));
        if ($trans_id) {
            $transData['notes'][]	= $this->_lang['txn_exists'];
        }
        
        // Log transaction
        $order->logTransaction($transData);
        
        // Everythings good, continue to page 'complete'
        if($confirmed) {
          httpredir(currentPage(array('_g', 'type', 'cmd', 'module', 'transactionid', 'orderid'), array('_a' => 'complete')));
          return true;
        } 
        
        // Unknown errors
        $GLOBALS['main']->errorMessage($GLOBALS['language']->paylike_text['error_unknown']);
        httpredir(currentPage(array('_g', 'type', 'cmd', 'module', 'transactionid', 'orderid'), array('_a' => 'checkout')));
        return false;
      }
      
      // OrderID/TxnID missing
      $GLOBALS['main']->errorMessage($GLOBALS['language']->paylike_text['idsmissing']);
      httpredir(currentPage(array('_g', 'type', 'cmd', 'module', 'transactionid', 'orderid'), array('_a' => 'checkout')));
      return false;
	}

	public function form() {
      return false;
	}

}
