jQuery(function ($) {
    /**
     * Object to handle Paylike payment forms.
     */
    var cc_paylike_form = {

            /**
             * Initialize e handlers and UI state.
             */
            init: function (form) {
                this.form = form;
                this.paylike_submit = false;
                $(this.form).on('click', '#checkout_proceed', this.onSubmit);
            },
            isPaylikeChosen: function () {
                return $('input#Paylike_Payments').is(':checked');
            },
            isPaylikeModalNeeded: function () {
                var token = cc_paylike_form.form.find('input.paylike_token').length,
                    card = cc_paylike_form.form.find('input.paylike_card_id').length,
                    $required_inputs;

                // If this is a paylike submission (after modal) and token exists, allow submit.
                if (cc_paylike_form.paylike_submit && token) {
                  if (cc_paylike_form.form.find('input.paylike_token').val() !== '')
                    return false;
                }

                // If this is a paylike submission (after modal) and card exists, allow submit.
                if (cc_paylike_form.paylike_submit && card) {
                  if (cc_paylike_form.form.find('input.paylike_card_id').val() !== '')
                    return false;
                }

                // Don't affect submission if modal is not needed.
                if (!cc_paylike_form.isPaylikeChosen()) {
                    return false;
                }

                // if address isn't defined yet
                if(!cc_paylike_params.address_defined) {
                    return false;
                }

                // Don't open modal if required fields are not complete
                if ($('input#reg_terms').length === 1 && $('input#reg_terms:checked').length === 0) {
                    return false;
                }
                var $account_password = $('#reg_password');
                if ($('#show-reg').is(':checked') && $account_password.length && $account_password.val() === '') {
                    return false;
                }

                // check required inputs
                $required_inputs = $('input[required]');
                if ($required_inputs.length) {
                    var required_error = false;

                    $required_inputs.each(function () {
                        if ($(this).find('input, select').val() === '') {
                            required_error = true;
                        }
                    });

                    if (required_error) {
                        return false;
                    }
                }
                return true;
            },
            getName: function () {
                var $name = $("[name='user[first_name]']");
                var name = '';
                if ($name.length > 0) {
                    name = $name.val() + ' ' + $("[name='user[last_name]']").val();
                } else {
                    name = cc_paylike_params.name;
                }
                return cc_paylike_form.escapeQoutes(name);
            },
            getAddress: function () {
                var $address = $("[name='billing[line1]']");
                var address = '';
                if ($address.length > 0) {
                    address = $address.val() + ' ' + $("[name='billing[line2]']").val();
                } else {
                    address = cc_paylike_params.address;
                }
                return cc_paylike_form.escapeQoutes(address);
            },
            getPhoneNo: function () {
                var $phone = $("[name='user[phone]']");
                var phone = '';
                if ($phone.length > 0) {
                    phone = $phone.val()
                } else {
                    phone = cc_paylike_params.phone;
                }
                return cc_paylike_form.escapeQoutes(phone);
            },
            getEmail: function () {
                var $phone = $("[name='user[email]']");
                var phone = '';
                if ($phone.length > 0) {
                    phone = $phone.val()
                } else {
                    phone = cc_paylike_params.email;
                }
                return cc_paylike_form.escapeQoutes(phone);
            },
            onSubmit: function (e) {
                if (cc_paylike_form.isPaylikeModalNeeded()) {
                    e.preventDefault();

                    // Capture submit and open paylike modal
                    var $form = cc_paylike_form.form,
                        token = $form.find('input.paylike_token');

                    console.log($form);

                    token.val('');

                    var name = cc_paylike_form.getName();
                    var phoneNo = cc_paylike_form.getPhoneNo();
                    var address = cc_paylike_form.getAddress();
                    var eMail = cc_paylike_form.getEmail();
                    var paylike = Paylike(cc_paylike_params.key);
                    var args = {
                        title: cc_paylike_params.title,
                        currency: cc_paylike_params.currency,
                        amount: cc_paylike_params.amount,
                        locale: cc_paylike_params.locale,
                        custom: {
                            email: eMail,
                            // orderId: cc_paylike_params.order_id, //omit orderId
                            products: [cc_paylike_params.products],
                            customer: {
                                name: name,
                                email: eMail,
                                phoneNo: phoneNo,
                                address: address,
                                IP: cc_paylike_params.customer_IP
                            },
                            platform: {
                                name: 'Cubecart',
                                version: cc_paylike_params.platform_version
                            },
                            paylikePluginVersion: cc_paylike_params.version
                        }
                    };


                    // used for cases like trial,
                    // change payment method
                    // see @https://github.com/paylike/sdk#popup-to-save-tokenize-a-card-for-later-use
                    if (args.amount === 0) {
                        delete args.amount;
                        delete args.currency;
                    }

                    paylike.popup(args,
                        function (err, res) {
                          if(err=='closed') { return false; }
                          if (res.transaction) {
                              var trxid = res.transaction.id;
                              $form.find('input.paylike_token').remove();
                              $form.append('<input type="hidden" class="paylike_token" name="paylike_token" value="' + trxid + '"/>');
                          } else {
                              var cardid = res.card.id;
                              $form.find('input.paylike_card_id').remove();
                              $form.append('<input type="hidden" class="paylike_card_id" name="paylike_card_id" value="' + cardid + '"/>');
                          }
                          cc_paylike_form.paylike_submit = true;
                          $form.submit();
                        }
                    );

                    return false;
                }
                return true;
            },
            escapeQoutes:function(str) {
                return str.toString().replace(/"/g, '\\"');
            }
        }
    ;

    cc_paylike_form.init($("form#checkout_form"));
});
