<?php include "db_conn_open.php" ?>
<?php include "includes.php" ?>
<?php include "inc/incemail.php" ?>
<?php include "inc/languagefile.php" ?>
<?php include "inc/incfunctions.php" ?>
<?php
// read post from PayPal system and add 'cmd'
$req = 'cmd=_notify-validate';
foreach ($_POST as $key => $value) {
  $value = urlencode(stripslashes($value));
  $req .= "&$key=$value";
}
// post back to PayPal system to validate
$header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
$header .= 'Content-Length: ' . strlen($req) . "\r\n\r\n";
$fp = fsockopen ('www.paypal.com', 80, $errno, $errstr, 30);
// assign posted variables to local variables
$Receiver_email = @$_POST['receiver_email'];
$Item_number = @$_POST['item_number'];
$Invoice = @$_POST['invoice'];
$Payment_status = @$_POST['payment_status'];
$Payment_gross = @$_POST['payment_gross'];
$Txn_id = @$_POST['txn_id'];
$Payer_email = @$_POST['payer_email'];
$ordID = trim(@$_POST['custom']);
// Check notification validation
if (!$fp){
	echo "$errstr ($errno)"; // HTTP error handling
}else{
	fputs ($fp, $header . $req);
	while (!feof($fp)) {
		$res = fgets ($fp, 1024);
		if(strcmp ($res, "VERIFIED") == 0 && ($ordID != "")){
			// check the payment_status is Completed
			// check that txn_id has not been previously processed
			// check that receiver_email is an email address in your PayPal account process payment
			$alreadygotadmin = getadminsettings();
			if($Payment_status=="Completed"){
				do_stock_management($ordID);
				mysql_query("UPDATE cart SET cartCompleted=1 WHERE cartOrderID='" . mysql_escape_string($ordID) . "'") or print(mysql_error());
				mysql_query("UPDATE orders SET ordStatus=3,ordAuthNumber='" . $Txn_id . "' WHERE ordID='" . mysql_escape_string($ordID) . "'") or print(mysql_error());
				do_order_success($ordID,$emailAddr,$sendEmail,FALSE,TRUE,TRUE,TRUE);
			}
		}elseif(strcmp ($res, "INVALID") == 0){
			; // log for manual investigation
		}else{
			if(@$debugmode==TRUE) print $res; // error
		}
	}
	fclose ($fp);
}
?>