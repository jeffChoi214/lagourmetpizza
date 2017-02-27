<html>
<head>
<title>Thanks for shopping with us</title>
<?php include "db_conn_open.php" ?>
<?php include "includes.php" ?>
<?php include "inc/incemail.php" ?>
<?php include "inc/languagefile.php" ?>
<?php include "inc/incfunctions.php" ?>
<meta http-equiv="Content-Type" content="text/html; charset=<?php print $adminencoding ?>">
<style type="text/css">
<!--
A:link {
	COLOR: #FFFFFF; TEXT-DECORATION: none
}
A:visited {
	COLOR: #FFFFFF; TEXT-DECORATION: none
}
A:active {
	COLOR: #FFFFFF; TEXT-DECORATION: none
}
A:hover {
	COLOR: #f39000; TEXT-DECORATION: underline
}
TD {
	FONT-FAMILY: Verdana; FONT-SIZE: 13px
}
P {
	FONT-FAMILY: Verdana; FONT-SIZE: 13px
}
-->
</style>
</head>
<?php
//This code is copyright (c) Internet Business Solutions SL, all rights reserved.
//The contents of this file are protect under law as the intellectual property of Internet
//Business Solutions SL. Any use, reproduction, disclosure or copying of any kind 
//without the express and written permission of Internet Business Solutions SL is forbidden.
//Author: Vince Reid, vince@virtualred.net
$success=FALSE;
$errtext="";
$errormsg="";
$thereference="";
$orderText="";
$ordGrandTotal = 0;
$_SESSION["couponapply"]="";
$alreadygotadmin = getadminsettings();
$success = FALSE;
$isworldpay = FALSE;
$isauthnet = FALSE;
$isnetbanx = FALSE;
$issecpay = FALSE;
if(trim(@$_POST["transStatus"]) != ""){ // WorldPay
	$isworldpay = TRUE;
	if(trim(@$_POST["transStatus"])=="Y"){
		$ordID = trim(@$_POST["cartId"]);
		$avscode = trim(@$_POST["AVS"]);
		if($avscode != "") $avscode .= "-";
		if(trim(@$_POST["wafMerchMessage"]) != "") $avscode = trim(@$_POST["wafMerchMessage"]) . " " . $avscode;
		do_stock_management($ordID);
		$sSQL="UPDATE cart SET cartCompleted=1 WHERE cartOrderID='" . mysql_escape_string($ordID) . "'";
		mysql_query($sSQL) or print(mysql_error());
		$sSQL="UPDATE orders SET ordStatus=3,ordAuthNumber='" . mysql_escape_string($avscode . trim(@$_POST["transId"])) . "' WHERE ordPayProvider=5 AND ordID='" . mysql_escape_string($ordID) . "'";
		mysql_query($sSQL) or print(mysql_error());
		do_order_success($ordID,$emailAddr,$sendEmail,FALSE,TRUE,TRUE,TRUE);
		$success = TRUE;
	}
}elseif(trim(@$_POST["x_response_code"]) != ""){ // Authorize.net
	$isauthnet = TRUE;
	$ordID = trim(@$_POST["x_ect_ordid"]);
	if(trim(@$_POST["x_response_code"])=="1" && $ordID != ""){
		do_stock_management($ordID);
		$sSQL="UPDATE cart SET cartCompleted=1 WHERE cartOrderID='" . mysql_escape_string($ordID) . "'";
		mysql_query($sSQL) or print(mysql_error());
		$sSQL="UPDATE orders SET ordStatus=3,ordAuthNumber='" . mysql_escape_string(trim(@$_POST["x_avs_code"]) . "-" . trim(@$_POST["x_auth_code"])) . "' WHERE ordPayProvider=3 AND ordID='" . mysql_escape_string($ordID) . "'";
		mysql_query($sSQL) or print(mysql_error());
		do_order_success($ordID,$emailAddr,$sendEmail,FALSE,TRUE,TRUE,TRUE);
		$success = TRUE;
	}else
		$errormsg = trim(@$_POST["x_response_reason_text"]);
}elseif(trim(@$_POST["trans_id"]) != ""){ // Secpay
	$issecpay = TRUE;
	if(trim(@$_POST["valid"])=="true" && trim(@$_POST["auth_code"])!=""){
		$ordID = trim(@$_POST["trans_id"]);
		do_stock_management($ordID);
		$sSQL="UPDATE cart SET cartCompleted=1 WHERE cartOrderID='" . mysql_escape_string($ordID) . "'";
		mysql_query($sSQL) or print(mysql_error());
		$sSQL="UPDATE orders SET ordStatus=3,ordAuthNumber='";
		if(trim(@$_POST["cv2avs"]) != "") $sSQL .= mysql_escape_string(trim(@$_POST["cv2avs"])) . "-";
		$sSQL .= mysql_escape_string(trim(@$_POST["auth_code"])) . "' WHERE ordPayProvider=9 AND ordID='" . mysql_escape_string($ordID) . "'";
		mysql_query($sSQL) or print(mysql_error());
		do_order_success($ordID,$emailAddr,$sendEmail,FALSE,TRUE,TRUE,TRUE);
		$success = TRUE;
	}else
		$errormsg = trim(@$_POST["message"]);
}elseif(trim(@$_POST["netbanx_reference"]) != ""){ // Netbanx
	$isnetbanx = TRUE;
	$thereference = trim(@$_POST["netbanx_reference"]);
	if(trim(@$_SERVER["REMOTE_ADDR"]) != "195.224.77.2")
		$errormsg = "Error: This transaction does not appear to have been initiated by Netbanx";
	elseif($thereference!="0" && trim(@$_POST["order_id"])!=""){
		$ordID = trim(@$_POST["order_id"]);
		do_stock_management($ordID);
		$sSQL="UPDATE cart SET cartCompleted=1 WHERE cartOrderID='" . mysql_escape_string($ordID) . "'";
		mysql_query($sSQL) or print(mysql_error());
		if(trim(@$_POST["houseno_auth"])=="Matched")
			$allchecks = "Y";
		elseif(trim(@$_POST["houseno_auth"])=="Not matched")
			$allchecks = "N";
		else
			$allchecks = "X";
		if(trim(@$_POST["postcode_auth"])=="Matched")
			$allchecks .= "Y";
		elseif(trim(@$_POST["postcode_auth"])=="Not matched")
			$allchecks .= "N";
		else
			$allchecks .= "X";
		if(trim(@$_POST["CV2_auth"])=="Matched")
			$allchecks .= "Y";
		elseif(trim(@$_POST["CV2_auth"])=="Not matched")
			$allchecks .= "N";
		else
			$allchecks .= "X";
		$sSQL="UPDATE orders SET ordStatus=3,ordAuthNumber='" . $allchecks . "-" . $thereference . "' WHERE ordPayProvider=15 AND ordID='" . mysql_escape_string($ordID) . "'";
		mysql_query($sSQL) or print(mysql_error());
		do_order_success($ordID,$emailAddr,$sendEmail,FALSE,TRUE,TRUE,TRUE);
		$success = TRUE;
	}else
		$errormsg = "Transaction Declined";
}
?>
<body bgcolor="#FFFFFF" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F39900">
  <tr>
    <td>
      <table width="100%" border="1" cellspacing="1" cellpadding="3">
        <tr> 
          <td rowspan="4" bgcolor="#333333">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
          <td width="100%" bgcolor="#333333" align="center"><font color="#FFFFFF" face="Arial, Helvetica, sans-serif"><strong><?php print $xxInAssc . "&nbsp;";
		if($isworldpay)
			print "WorldPay";
		elseif($isauthnet)
			print "Authorize.Net";
		elseif($isnetbanx)
			print "Netbanx";
		elseif($issecpay)
			print "SECPay";
		else
			print '<a href="http://www.ecommercetemplates.com">EcommerceTemplates.com</a>' ?></strong></font></td>
          <td rowspan="4" bgcolor="#333333">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        </tr>
        <tr> 
          <td width="100%" bgcolor="#637BAD" align="center"><font color="#FFFFFF"><strong><font face="Verdana, Arial, Helvetica, sans-serif" size="3"><?php print $xxTnkStr?></font></strong></font></td>
        </tr>
        <tr> 
          <td width="100%" align="center" bgcolor="#F5F5F5"> 
<?php if($isworldpay){ ?>
			<p>&nbsp;</p>
			<p align="center"><font face="Verdana, Arial, Helvetica, sans-serif" size="2"><strong><?php print $xxTnkWit?> <WPDISPLAY ITEM=compName></strong></font></p>
            <p><wpdisplay item="banner"></p>
            <p>&nbsp;</p>
			<p><font face="Verdana, Arial, Helvetica, sans-serif" size="2"><strong><?php print $xxPlsNt1 . " " . $xxMerRef . " " . $xxPlsNt2?></strong></font></p>
			<p>&nbsp;</p>
<?php }elseif($success){ ?>
		  <table border="0" cellspacing="<?php print $maintablespacing?>" cellpadding="<?php print $maintablepadding?>" width="<?php print $maintablewidth?>" bgcolor="<?php print $maintablebg?>" align="center">
			<tr>
			  <td width="100%" align="center">
				<table width="80%" border="0" cellspacing="<?php print $innertablespacing?>" cellpadding="<?php print $innertablepadding?>" bgcolor="<?php print $innertablebg?>">
				  <tr> 
					<td width="100%" align="center"><?php print $xxThkYou?>
					</td>
				  </tr>
<?php	if(@$digidownloads==TRUE){
			print '</table>';
			$noshowdigiordertext = TRUE;
			include "inc/digidownload.php";
			print '<table width="80%" border="0" cellspacing="' . $innertablespacing . '" cellpadding="' . $innertablepadding . '" bgcolor="' . $innertablebg . '">';
		}
?>
				  <tr> 
					<td width="100%"><?php print str_replace(array("\r\n","\n"),array("<br />","<br />"),$orderText)?>
					</td>
				  </tr>
				  <tr> 
					<td width="100%" align="center"><br /><br />
					<?php print $xxRecEml?><br /><br />
					<a href="<?php print $storeurl?>"><font color="#637BAD"><strong><?php print $xxCntShp?></strong></font></a><br />
					<p>&nbsp;</p>
					</td>
				  </tr>
				</table>
			  </td>
			</tr>
		  </table>
<?php }else{ ?>
		  <p>&nbsp;</p>
		  <table border="0" cellspacing="<?php print $maintablespacing?>" cellpadding="<?php print $maintablepadding?>" width="<?php print $maintablewidth?>" bgcolor="<?php print $maintablebg?>" align="center">
			<tr>
			  <td width="100%">
				<table width="<?php print $innertablewidth?>" border="0" cellspacing="<?php print $innertablespacing?>" cellpadding="<?php print $innertablepadding?>" bgcolor="<?php print $innertablebg?>">
				  <tr> 
					<td width="100%" colspan="2" align="center"><?php print $xxThkErr?>
					<p>The error report returned by the server was:<br /><strong><?php print $errormsg?></strong></p>
					<a href="<?php print $storeurl?>"><font color="#637BAD"><strong><?php print $xxCntShp?></strong></font></a><br />
					<p>&nbsp;</p>
					</td>
				  </tr>
				</table>
			  </td>
			</tr>
		  </table>
<?php } ?>
          </td>
        </tr>
        <tr> 
          <td width="100%" bgcolor="#333333" align="center"><font color="#FFFFFF"><strong><font face="Verdana, Arial, Helvetica, sans-serif" size="2"><a href="<?php print $storeurl?>"><?php print $xxClkBck?></a></font></strong></font></td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
