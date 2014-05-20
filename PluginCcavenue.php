<?php
/*****************************************************************/
// function plugin_ccavenue_variables($params) - required function
/*****************************************************************/
require_once 'modules/admin/models/GatewayPlugin.php';

/**
* @package Plugins
*/
class PluginCcavenue extends GatewayPlugin
{
    function getVariables() {
        /* Specification
        itemkey     - used to identify variable in your other functions
        type          - text,textarea,yesno,password,hidden ( hiddens are not visable to the user )
        description - description of the variable, displayed in ClientExec
        value     - default value
        */

        $variables = array (
            /*T*/"Plugin Name"/*/T*/ => array (
                                "type"          =>"hidden",
                                "description"   =>/*T*/"How ClientExec sees this plugin (not to be confused with the Signup Name)"/*/T*/,
                                "value"         =>/*T*/"CCAvenue"/*/T*/
                                ),
            /*T*/"Merchant ID"/*/T*/ => array (
                                "type"          =>"text",
                                "description"   =>/*T*/"This is the identifier for your CCAvenue merchant Account."/*/T*/,
                                "value"         =>""
                                ),
            /*T*/"Access Code"/*/T*/ => array (
                                "type"          =>"text",
                                "description"   =>/*T*/"This is the access code for your application."/*/T*/,
                                "value"         =>""
                                ),
            /*T*/"Encryption Key"/*/T*/ => array (
                                "type"          =>"text",
                                "description"   =>/*T*/"The secret key used for encrypting each request originating from your application.<br>Ensure you are using the correct key while encrypting requests from different URLs registered with CCAvenue."/*/T*/,
                                "value"         =>""
                                ),
            /*T*/"Test Mode"/*/T*/ => array(
                                "type"          =>"yesno",
                                "description"   =>/*T*/"Select YES if you want to use Testing server, so no actual monetary transactions are made."/*/T*/,
                                "value"         =>"0"
                                ),
            /*T*/"Visa"/*/T*/ => array (
                                "type"          =>"yesno",
                                "description"   =>/*T*/"Select YES to allow Visa card acceptance with this plugin.  No will prevent this card type."/*/T*/,
                                "value"         =>"1"
                                ),
            /*T*/"MasterCard"/*/T*/ => array (
                                "type"          =>"yesno",
                                "description"   =>/*T*/"Select YES to allow MasterCard acceptance with this plugin. No will prevent this card type."/*/T*/,
                                "value"         =>"1"
                                ),
            /*T*/"AmericanExpress"/*/T*/ => array (
                                "type"          =>"yesno",
                                "description"   =>/*T*/"Select YES to allow American Express card acceptance with this plugin. No will prevent this card type."/*/T*/,
                                "value"         =>"0"
                                ),
            /*T*/"Discover"/*/T*/ => array (
                                "type"          =>"yesno",
                                "description"   =>/*T*/"Select YES to allow Discover card acceptance with this plugin. No will prevent this card type."/*/T*/,
                                "value"         =>"0"
                                ),
            /*T*/"Invoice After Signup"/*/T*/ => array (
                                "type"          =>"yesno",
                                "description"   =>/*T*/"Select YES if you want an invoice sent to the customer after signup is complete."/*/T*/,
                                "value"         =>"1"
                                ),
            /*T*/"Signup Name"/*/T*/ => array (
                                "type"          =>"text",
                                "description"   =>/*T*/"Select the name to display in the signup process for this payment type. Example: eCheck or Credit Card."/*/T*/,
                                "value"         =>"Credit Card"
                                ),
            /*T*/"Dummy Plugin"/*/T*/ => array (
                                "type"          =>"hidden",
                                "description"   =>/*T*/"1 = Only used to specify a billing type for a customer. 0 = full fledged plugin requiring complete functions"/*/T*/,
                                "value"         =>"0"
                                ),
            /*T*/"Accept CC Number"/*/T*/ => array (
                                "type"          =>"hidden",
                                "description"   =>/*T*/"Selecting YES allows the entering of CC numbers when using this plugin type. No will prevent entering of cc information"/*/T*/,
                                "value"         =>"0"
                                ),
            /*T*/"Auto Payment"/*/T*/ => array (
                                "type"          =>"hidden",
                                "description"   =>/*T*/"No description"/*/T*/,
                                "value"         =>"0"
                                ),
            /*T*/"30 Day Billing"/*/T*/ => array (
                                "type"          =>"hidden",
                                "description"   =>/*T*/"Select YES if you want ClientExec to treat monthly billing by 30 day intervals.  If you select NO then the same day will be used to determine intervals."/*/T*/,
                                "value"         =>"0"
                                ),
            /*T*/"Check CVV2"/*/T*/ => array (
                                "type"          =>"hidden",
                                "description"   =>/*T*/"Select YES if you want to accept CVV2 for this plugin."/*/T*/,
                                "value"         =>"0"
                                )
        );
        return $variables;
    }

    function credit($params)
    {}

    /*****************************************************************/
    // function plugin_ccavenue_singlepayment($params) - required function
    /*****************************************************************/
    function singlepayment($params)
    {
        //Function needs to build the url to the payment processor
        //Plugin variables can be accesses via $params["plugin_[pluginname]_[variable]"] (ex. $params["plugin_paypal_UserID"])

        include("plugins/gateways/ccavenue/Crypto.php");
        $access_code = $params["plugin_ccavenue_Access Code"];
        $WorkingKey = $params["plugin_ccavenue_Encryption Key"];

        //Need to check to see if user is coming from signup
        if ($params['isSignup']==1) {
            // Actually handle the signup URL setting
            if($this->settings->get('Signup Completion URL') != '') {
                $returnURL_Cancel = $this->settings->get('Signup Completion URL');
            }else{
                $returnURL_Cancel = $params["clientExecURL"]."/order.php?step=3";
            }
        }else {
            $returnURL_Cancel = $params["invoiceviewURLCancel"];
        }

        $merchant_data = //Required Parameters: Merchant must send the following parameters to the CCAvenue PG for processing an order.
                         'merchant_id='.urlencode($params["plugin_ccavenue_Merchant ID"])
                        .'&order_id='.urlencode($params['invoiceNumber']."_".time()) // OrderID should be unique for ccavenue gateway. So we need to append timestamp with the InvoiceID like invoiceid_timestamp
                        .'&currency='.urlencode($params["currencytype"])
                        .'&amount='.urlencode(sprintf("%01.2f", round($params["invoiceTotal"], 2)))
                        .'&redirect_url='.urlencode($params['clientExecURL']."/plugins/gateways/ccavenue/callback.php")
                        .'&cancel_url='.urlencode($returnURL_Cancel)
                        .'&language='.urlencode('EN')

                        //Billing Information: Merchant can send any of the following parameters in addition to the required parameters.
                        .'&billing_name='.urlencode($params["userFirstName"]." ".$params["userLastName"])
                        .'&billing_address='.urlencode($params["userAddress"])
                        .'&billing_city='.urlencode($params["userCity"])
                        .'&billing_state='.urlencode($params["userState"])
                        .'&billing_zip='.urlencode($params["userZipcode"])
                        .'&billing_country='.urlencode($params["userCountry"])
                        .'&billing_tel='.urlencode($params["userPhone"])
                        .'&billing_email='.urlencode($params["userEmail"])

                        //Shipping Information: Merchant can send any of the following parameters in addition to the required parameters.
                        .'&delivery_name='.urlencode($params["userFirstName"]." ".$params["userLastName"])
                        .'&delivery_address='.urlencode($params["userAddress"])
                        .'&delivery_city='.urlencode($params["userCity"])
                        .'&delivery_state='.urlencode($params["userState"])
                        .'&delivery_zip='.urlencode($params["userZipcode"])
                        .'&delivery_country='.urlencode($params["userCountry"])
                        .'&delivery_tel='.urlencode($params["userPhone"])

                        .'&merchant_param1='.urlencode($params['isSignup'])
                        .'&merchant_param2='.urlencode("Invoice #".$params['invoiceNumber'])
                        //.'&merchant_param3='.urlencode($merchant_param3)
                        //.'&merchant_param4='.urlencode($merchant_param4)
                        //.'&merchant_param5='.urlencode($merchant_param5)

                        //.'&promo_code='.urlencode($promo_code)

                        //.'&customer_identifier='.urlencode($customer_identifier)
                        ;

        $encrypted_data = encrypt($merchant_data, $WorkingKey); // Method for encrypting the data.

        if ( $params['plugin_ccavenue_Test Mode'] == '1' ) {
            $ccavenuePost = 'test';
        }else{
            $ccavenuePost = 'secure';
        }

        $strRet = "<html>\n";
        $strRet .= "<head></head>\n";
        $strRet .= "<body>\n";
        $strRet .= "<form name=ccavenue method=\"post\" action=\"https://".$ccavenuePost.".ccavenue.com/transaction/transaction.do?command=initiateTransaction\">";
        //$strRet .= "<input type=hidden name=command value=initiateTransaction>";
        $strRet .= "<input type=hidden name=encRequest value=".$encrypted_data.">";
        $strRet .= "<input type=hidden name=access_code value=".$access_code.">";
        $strRet .= "<script language=\"JavaScript\">\n";
        $strRet .= "document.forms['ccavenue'].submit();\n";
        $strRet .= "</script>\n";
        $strRet .= "</form>\n";
        $strRet .= "</body></html>";

        echo $strRet;
        exit;
    }
}
?>
