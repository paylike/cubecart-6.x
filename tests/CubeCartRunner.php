<?php


namespace CubeCart;

use Facebook\WebDriver\Exception\UnrecognizedExceptionException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\Exception\UnexpectedTagNameException;
use Facebook\WebDriver\WebDriverDimension;

class CubeCartRunner extends CubeCartTestHelper
{

    /**
     * @param $args
     *
     * @throws NoSuchElementException
     * @throws UnexpectedTagNameException
     */
    public function ready($args)
    {
        $this->set($args);
        $this->go();
    }

    /**
     * @throws NoSuchElementException
     * @throws TimeOutException
     */
    public function loginAdmin()
    {
        $this->goToPage('', '#username', true);

        while (!$this->hasValue('#username', $this->user)) {
            $this->typeLogin();
        }
        $this->click('#login');
        $this->waitForElement('.dashboard_content');
    }

    /**
     *  Insert user and password on the login screen
     */
    private function typeLogin()
    {
        $this->type('#username', $this->user);
        $this->type('#password', $this->pass);
    }

    /**
     * @param $args
     */
    private function set($args)
    {
        foreach ($args as $key => $val) {
            $name = $key;
            if (isset($this->{$name})) {
                $this->{$name} = $val;
            }
        }
    }

    /**
     * @throws NoSuchElementException
     * @throws TimeOutException
     */
    public function changeCurrency()
    {
        $this->goToPage("", "#box-currency", false);
        $currentcurrencyRaw = $this->getText(".columns #box-currency .button");
        $currentcurrency = preg_replace("/[^A-Za-z0-9\-]/", "", $currentcurrencyRaw);
        if ($currentcurrency != $this->currency) {
            $this->goToPage("/index.php?set_currency=$this->currency", "#box-currency", false);
        }

    }

    /**
     * @throws NoSuchElementException
     * @throws TimeOutException
     */
    public function changeMode()
    {
        $this->goToPage('?_g=plugins&type=plugins&module=Paylike_Payments', '#Paylike_Form', true);
        $this->click("//select[@name = 'module[capturemode]']");
        $this->click("//option[contains(text(), '" . $this->capture_mode . "')]");
        $this->captureMode();
    }


    /**
     * @throws NoSuchElementException
     * @throws TimeOutException
     */

    private function logVersionsRemotly()
    {
        $versions = $this->getVersions();
        $this->wd->get(getenv('REMOTE_LOG_URL') . '&key=' . $this->get_slug($versions['ecommerce']) . '&tag=cubecart&view=html&' . http_build_query($versions));
        $this->waitForElement('#message');
        $message = $this->getText('#message');
        $this->main_test->assertEquals('Success!', $message, "Remote log failed");
    }

    /**
     * @throws NoSuchElementException
     * @throws TimeOutException
     */
    private function getVersions()
    {
        $this->goToPage("?_g=plugins", "#plugins", "true");
       $paylike = $this->wd->executeScript("
            var paylikeLabel = document.querySelectorAll('input[name=\"status[Paylike_Payments]\"]');
            return paylikeLabel[0].parentNode.nextSibling.nextSibling.innerText;
     ");
        $this->goToPage("?#advanced", "#advanced", "true");
        $cubecart = $this->getText('.tab_content#advanced fieldset:nth-child(2) dl dd:nth-child(2)');
        return ['ecommerce' => $cubecart, 'plugin' => $paylike];
    }

    /**
     * @throws NoSuchElementException
     * @throws TimeOutException
     */
    private function outputVersions()
    {
        $this->goToPage('/index.php?controller=AdminDashboard', null, true);
        $this->main_test->log('ThirtyBees Version:', $this->getText('#shop_version'));
        $this->goToPage("/index.php?controller=AdminModules", null, true);
        $this->waitForElement("#filter_payments_gateways");
        $this->click("#filter_payments_gateways");
        $this->waitForElement("#anchorPaylikepayment");
        $this->main_test->log('Paylike Version:', $this->getText('.table #anchorPaylikepayment .module_name'));

    }

    public function submitAdmin()
    {
        $this->click('#module_form_submit_btn');
    }

    /**
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @throws UnexpectedTagNameException
     */
    private function directPayment()
    {

        $this->changeCurrency();

        $this->addToCart();
        $this->proceedToCheckout();
        $this->amountVerification();
        $this->finalPaylike();
        $this->selectOrder();
        if ($this->capture_mode == 'Delayed') {
            $this->capture();
        } else {
            $this->refund();
        }

    }


    /**
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @throws UnexpectedTagNameException
     */
    public function capture()
    {
        $this->selectValue(".tab_content #o_status", "3");
        $this->click("//input[@value = 'Save']");
        $this->waitForElement("#gui_message");
        $messages = $this->getText('.success:nth-child(2)');
        $this->main_test->assertEquals('Successfully captured authorized amount on Paylike.', $messages, "Completed");
    }

    /**
     *
     */

    /**
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @throws UnrecognizedExceptionException
     */
    public function captureMode()
    {
        $this->click(".form_control input");
        $this->waitforElement(".success");
    }


    /**
     *
     */
    public function addToCart()
    {
        $this->click(".add_to_basket .columns button");
        $this->waitforElementToBeClickeble(".basket-detail-container .success");

    }

    /**
     *
     */
    public function proceedToCheckout()
    {
        $this->click(".basket-detail-container .success");
        $this->waitForElement(".checkout_wrapper #checkout_login");
        $this->click(".checkout_wrapper #checkout_login");
        $this->waitforElementToBeClickeble(".checkout_wrapper  #login-username");
        $this->type("#login-username", $this->client_user);
        $this->type("#login-password", $this->client_pass);
        $this->click("#checkout_login_btn");
        $this->waitForElement("#checkout_form");
        $this->click("#checkout_proceed");

    }

    /**
     *
     */
    public function amountVerification()
    {

        $amount = $this->getText('.checkout_wrapper tfoot tr:last-child .text-right');
        $amount = preg_replace("/[^0-9.]/", "", $amount);
        $expectedAmount = $this->getText('.paylike .action .amount');
        $expectedAmount = preg_replace("/[^0-9.]/", "", $expectedAmount);
        $this->main_test->assertEquals($expectedAmount, $amount, "Checking minor amount for " . $this->currency);

    }

    /**
     * @throws NoSuchElementException
     * @throws TimeOutException
     */
    public function choosePaylike()
    {
        $this->waitForElement('#paylike-btn');
        $this->click('#paylike-btn');
    }

    /**
     * @throws NoSuchElementException
     * @throws TimeOutException
     */
    public function finalPaylike()
    {
        $this->popupPaylike();
        $this->waitForElement(".alert-box.success");
        $completedValueRaw = $this->getText(".alert-box.success");
        $completedValue = str_replace("\n", "", $completedValueRaw);
        $completedValue = str_replace("×", "", $completedValue);
        // because the title of the page matches the checkout title, we need to use the order received class on body
        if ($this->capture_mode == "Instant") {
            $this->main_test->assertEquals('Many thanks for your order! Payment has been received and your order is now complete.', $completedValue);
        } else {
            $this->main_test->assertEquals('Many thanks for your order! Payment has been received and your order is now being processed.', $completedValue);
        }
    }


    /**
     * @throws NoSuchElementException
     * @throws TimeOutException
     */
    public function popupPaylike()
    {
        try {
            $this->type('.paylike.overlay .payment form #card-number', 41000000000000);
            $this->type('.paylike.overlay .payment form #card-expiry', '11/22');
            $this->type('.paylike.overlay .payment form #card-code', '122');
            $this->click('.paylike.overlay .payment form button');
        } catch (NoSuchElementException $exception) {
            $this->confirmOrder();
            $this->popupPaylike();
        }

    }

    /**
     * @throws NoSuchElementException
     * @throws TimeOutException
     */
    public function selectOrder()
    {
        $this->goToPage("?_g=orders", "#orders", true);
        $this->click(".fa-pencil-square-o");
    }

    /**
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @throws UnexpectedTagNameException
     */
    public function refund()
    {
        $this->click("#tab_plrefund");
        $this->waitForElement("#plrefund");
        $this->click("//img[@rel = '#confirmplrefund']");
        $this->click("//input[@value = 'Save']");
        $this->waitForElement("#gui_message");
        $messages = $this->getText('.success:nth-child(2)');
        $this->main_test->assertEquals('Payment is refunded back to customer credit card.', $messages, "Completed");
    }

    /**
     * @throws NoSuchElementException
     * @throws TimeOutException
     */
    public function confirmOrder()
    {
        $this->waitForElement('#paylike-payment-button');
        $this->click('#paylike-payment-button');
    }

    /**
     * @throws NoSuchElementException
     * @throws TimeOutException
     */
    private function settings()
    {
        $this->changeMode();
    }

    /**
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @throws UnexpectedTagNameException
     */
    private function go()
    {
        $this->changeWindow();
        $this->loginAdmin();
        if ($this->log_version) {
            $this->logVersionsRemotly();

            return $this;
        }

        $this->settings();
        $this->directPayment();

    }

    /**
     *
     */
    private function changeWindow()
    {
        $this->wd->manage()->window()->setSize(new WebDriverDimension(1600, 1024));
    }


}

