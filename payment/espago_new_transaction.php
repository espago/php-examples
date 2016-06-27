<html>
<body>
<?php
//Example script executing the query to /api/charges on Espago gateway
//Code is published "as is" without any warranty of any kind,
//express or implied, as to the operation of this script.
//You may (and you sholud) improve this code in a way
//that ensures the correctness and security of data on your website.
//Last change: 2016-04-27

//Initial parameters from Espago panel:

/** Loads the config file */
require( dirname( __FILE__ ) . '/espago-config.php' );

//error codes in XML, you can use EN, PL or FR language
//in most cases you will prefer mysql for storing error codes
//if you want change language use different file and object id (erren/errpl/errfr)
$xmlerrorcodes = 'erren.xml';

//Parameters of this transaction: AMOUNT and DESCRIPTION
// date_default_timezone_set('Europe/Warsaw'); //not neccesary - for testing description text
// $transaction_description = "Test transaction ".date("Y-m-d H:i:s"); //not neccesary - for testing description text
//$transaction_description = $_POST['transaction_description'];
//$transaction_amount = $_POST['transaction_amount'];
$transaction_description = "Test transaction";
$transaction_amount = "5.0";


//1. Preparing request to /api/charges using token - PREFERRED SOLUTION FOR SINGLE PAYMENTS
//You need to use token taken from the payment form.
//Remember: one token can be used one time. Http error 422 may mean you try to use token second time.
//$idToken = "cc_xxxxxxxxxxxx";
$idToken = $_POST['card_token']; //recieving token from form in index.html
//$postData = array('description' => "$transaction_description", 'amount' => "$transaction_amount", 'currency' => "$espago_currency", 'card' => $idToken); // for testing
$postData = array('description' => "$transaction_description", 'amount' => "$transaction_amount", 'currency' => "$espago_currency", 'card' => $idToken, 'email' => "$email_notification");
//if (!$idToken){echo "\nError, no token received.\n";}  //FOR DEBUG

//2. Preparing request to /api/charges using client profile - SOLUTION FOR RETURNED CLIENTS
//You need to use id_client from client created earlier.
//One client can be used multiple times. Http error 422 may mean there is no such client id.
//$idClient = "cli_FhTnCxFitgGcIc";
//$postData = array('description' => "$transaction_description", 'currency','amount' => "$transaction_amount", 'currency' => "$espago_currency", 'client' => "$idClient");

//3. Preparing request to /api/charges using full credit card information - FOR TEST ONLY (no need to create token or client)
//Http error 422 may mean wrong currency, wrong amount, wrong card descriptions.
//Http error 401 may mean wrong Application ID or API Password
//$postData = array('description' => "$transaction_description", 'amount' => "$transaction_amount", 'currency' => "$espago_currency", 'card' => array('verification_value' => 123, 'number' => '4242424242424242', 'year' => 2019, 'month' => 11, 'first_name' => "Luke", 'last_name' => "Skywalker"));



//Create charge request and get responsw
$curl=curl_init();
curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
curl_setopt($curl, CURLOPT_USERPWD, $espago_app_id.':'.$espago_password_api);
curl_setopt($curl, CURLOPT_POST, 1);
curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_URL,$espago_gateway.$espago_gateway_path);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
$charge = curl_exec($curl);
$result_decoded = json_decode($charge, true);

//$connection_info = curl_getinfo($curl);  //FOR DEBUG - usefull with connection error
//echo "Response code: " . $connection_info['http_code']; //FOR DEBUG

curl_close($curl);


/*
//Alternvative request method
//On some servers function file_get_contents can be not supported
$data = http_build_query($postData);
$context =
            array("http"=>
              array(
                "method" => "POST",
                "timeout" => 20,
                "header" =>
                  "Content-type: application/x-www-form-urlencoded\r\n".
                  "Content-Length: " . strlen($data) . "\r\n".
                  "Authorization: Basic ".base64_encode("$espago_app_id:$espago_password_api")."\r\n" .
                  "Accept: vnd.espago.v2+json\r\n",
                "content" => $data
              ),
              "ssl" => array(
                  "verify_peer" => false
                  //sholud be true!!
              )
            );

$context = stream_context_create($context);
$result =  file_get_contents("$espago_gateway/api/charges", false, $context);
$result_decoded = json_decode($result, true);
*/


$transaction_id = $result_decoded['transaction_id'];
$payment_id = $result_decoded['id'];
$description = $result_decoded['description'];
$amount = $result_decoded['amount'];
$currency =  $result_decoded['currency'];
$state = $result_decoded['state'];
$issuer_response_code = $result_decoded['issuer_response_code'];

$card_last4 = $result_decoded['card']['last4'];
$card_first_name = $result_decoded['card']['first_name'];
$card_last_name = $result_decoded['card']['last_name'];


//You can customize response
if ($state == 'executed'){
    $summary = "Transaction succesfull";
    echo "$summary";;
  }
elseif ($state == 'rejected'){
    $summary = "Transaction rejected";
    echo "$summary";;
  }
else{
    $summary = "Transaction failed because of a communication or other error.";
    echo "$summary";;
  }


///
//print "Raw response from server: \n $result <br><br>"; // for debug
print "\n\n<br><br>Information from Payment Gateway about transaction: \n <br>";
print "Amount: $amount $currency \n<br>";
print "State: $state \n<br>";
print "Payment ID: $payment_id \n<br>";
print "Description: $description \n<br>"; //not necessary

print "\n<br>Informations about used card: \n<br>";
print "Card last 4 digits: ".$card_last4."\n<br>";
print "Card owner: ".$card_first_name." ".$card_last_name."\n<br>";

// Informations about transaction - for DEBUGGING, generally client doesn't need it
//print "<br>Detailed informations:\n<br>";
//print "Transaction ID: $transaction_id \n<br>";
//print "Payment ID: $payment_id \n<br>";
//print "Issuer response code: $issuer_response_code \n<br><br>";



//getting error information from XML file
//IF XML file exists AND IF transaction is rejected
if (file_exists($xmlerrorcodes)) {
    if ($state == 'rejected') {
    $errorcodes = simplexml_load_file($xmlerrorcodes);
    $error_xml = $errorcodes->xpath("/errorcodes/erren[err_id=$issuer_response_code]");

    foreach($error_xml as $error_this) {
           $err_short = $error_this->err_short;
           $err_long = $error_this->err_long;
    }
    print "\n<br>Reject reason: \n<br>";
    print "$err_short \n<br>";
    print "$err_long \n<br>";
    }
}

    // YOU SHOULD STORE OR UPDATE INFORMATION ABOUT PAYMENT IN YOUR SYSTEM/DATABASE

    // Optionally and for DEBUG you can add e-mail notification;
    // it may be needed to additional server configuration to send e-mails
    // PREPARING MESSAGE
    $to      = $email_notification;
    $subject = 'New Payment attempt: ' . $card_first_name .' '.$card_last_name;
    $message  = "\nNew Payment attempt: $summary \n";
    $message .= "\nCard owner:  " . $card_first_name ." ". $card_last_name;
    $message .= "\nUsed token:  " . $idToken;
    $message .= "\nDescription:  ".$description;
    $message .= "\nAmount: " . $amount . " " .  $currency;
    $message .= "\nPayment ID: ".$payment_id;
    $message .= "\nTransaction ID: ".$transaction_id;
    $message .= "\nState: ".$state;
    $message .= "\nIssuer response code: ".$issuer_response_code;
    if (strpos($state,'rejected') !== false) {
    $message .= "\nReject reason: ".$err_short;
    $message .= "\nReject reason: ".$err_long;
    }
    $message .= "\n";
    //$message .= "\nRawdata: " . $rawdata;  // FOR DEBUG include full received data
    $headers = 'From: test-espago@example.com' . "\r\n" .
      'X-Mailer: PHP/' . phpversion();

   // Condition - if mail should be send only on successfull transaction
   //if ($state == 'executed') {
       mail($to, $subject, $message, $headers);
   //}

   // FOR DEBUG save data into local file
   file_put_contents("message.log", $message, FILE_APPEND);
   // echo file_put_contents("message.log",$message);


?>
</body>
</html>