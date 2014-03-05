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
                                "description"   =>/*T*/"ID used to identify you to CCAvenue."/*/T*/,
                                "value"         =>""
                                ),
            /*T*/"Working Key"/*/T*/ => array (
                                "type"          =>"text",
                                "description"   =>/*T*/"32 bit alphanumber CCAvenue key.<br>Note: This key is available at 'Generate Working Key' of the 'Settings & Options' section."/*/T*/,
                                "value"         =>""
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

        include("plugins/gateways/ccavenue/libfuncs.php");

        $Merchant_Id = $params["plugin_ccavenue_Merchant ID"];//This id(also User Id)  available at "Generate Working Key" of "Settings & Options"
        $Amount = sprintf("%01.2f", round($params["invoiceTotal"], 2));//your script should substitute the amount in the quotes provided here
        $Order_Id = $params['invoiceNumber'];//your script should substitute the order description in the quotes provided here

        /**
         * OrderID should be unique for ccavenue gateway. So we need to append timestamp with the InvoiceID like invoiceid_timestamp
         */

	$Order_Id = $Order_Id."_".time();
        $Redirect_Url = $params['clientExecURL']."/plugins/gateways/ccavenue/callback.php";//your redirect URL where your customer will be redirected after authorisation from CCAvenue
        $WorkingKey = $params["plugin_ccavenue_Working Key"];//put in the 32 bit alphanumeric key in the quotes provided here.Please note that get this key ,login to your CCAvenue merchant account and visit the "Generate Working Key" section at the "Settings & Options" page.
        $Checksum = getCheckSum($Merchant_Id,$Amount,$Order_Id ,$Redirect_Url,$WorkingKey);

        $strRet = "<html>\n";
        $strRet .= "<head></head>\n";
        $strRet .= "<body>\n";
        $strRet .= "<form name=ccavenue method=\"post\" action=\"https://www.ccavenue.com/shopzone/cc_details.jsp\">";
        $strRet .= "<input type=hidden name=Merchant_Id value=\"$Merchant_Id\">";
        $strRet .= "<input type=hidden name=Amount value=\"$Amount\">";
        $strRet .= "<input type=hidden name=Order_Id value=\"$Order_Id\">";
        $strRet .= "<input type=hidden name=Redirect_Url value=\"$Redirect_Url\">";
        $strRet .= "<input type=hidden name=Checksum value=\"$Checksum\">";

        $strRet .= "<input type=\"hidden\" name=\"billing_cust_name\" value=\"".$params["userFirstName"]." ".$params["userLastName"]."\">";
        $strRet .= "<input type=\"hidden\" name=\"billing_cust_address\" value=\"".$params["userAddress"]."\">";
        $strRet .= "<input type=\"hidden\" name=\"billing_cust_country\" value=\"".$params["userCountry"]."\">";
        $strRet .= "<input type=\"hidden\" name=\"billing_cust_tel\" value=\"".$params["userPhone"]."\">";
        $strRet .= "<input type=\"hidden\" name=\"billing_cust_email\" value=\"".$params["userEmail"]."\">";
        $strRet .= "<input type=\"hidden\" name=\"delivery_cust_name\" value=\"".$params["userFirstName"]." ".$params["userLastName"]."\">";
        $strRet .= "<input type=\"hidden\" name=\"delivery_cust_address\" value=\"".$params["userAddress"]."\">";
        $strRet .= "<input type=\"hidden\" name=\"delivery_cust_tel\" value=\"".$params["userPhone"]."\">";
        $strRet .= "<input type=\"hidden\" name=\"delivery_cust_notes\" value=\"Invoice #".$Order_Id."\">";
        $strRet .= "<input type=\"hidden\" name=\"Merchant_Param\" value=\"".$params['isSignup']."\">";

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
