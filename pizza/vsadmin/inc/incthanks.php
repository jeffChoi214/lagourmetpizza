<?php
//This code is copyright (c) Internet Business Solutions SL, all rights reserved.
//The contents of this file are protect under law as the intellectual property of Internet
//Business Solutions SL. Any use, reproduction, disclosure or copying of any kind 
//without the express and written permission of Internet Business Solutions SL is forbidden.
//Author: Vince Reid, vince@virtualred.net
include "./vsadmin/inc/incemail.php";
$success=FALSE;
$errtext="";
$errormsg="";
$thereference="";
$orderText="";
$ordGrandTotal = 0;
$_SESSION["couponapply"]="";
function order_failed(){
	global $maintablebg,$innertablebg,$maintablewidth,$innertablewidth,$maintablespacing,$innertablespacing,$maintablepadding,$innertablepadding;
	global $xxThkErr,$storeurl,$xxCntShp,$errtext;
?>
      <table border="0" cellspacing="<?php print $maintablespacing?>" cellpadding="<?php print $maintablepadding?>" width="<?php print $maintablewidth?>" bgcolor="<?php print $maintablebg?>" align="center">
        <tr>
          <td width="100%">
            <table width="<?php print $innertablewidth?>" border="0" cellspacing="<?php print $innertablespacing?>" cellpadding="<?php print $innertablepadding?>" bgcolor="<?php print $innertablebg?>">
			  <tr> 
                <td width="100%" colspan="2" align="center"><?php print $xxThkErr?>
				<?php if($errtext != "") print "<p><strong>" . $errtext . "</strong></p>" ?>
				<a href="<?php print $storeurl?>"><strong><?php print $xxCntShp?></strong></a><br />
				<img src="images/clearpixel.gif" width="300" height="3" alt="" />
                </td>
			  </tr>
			</table>
		  </td>
        </tr>
      </table>
<?php
}
$alreadygotadmin = getadminsettings();
if(@$_POST["custom"] != ""){
	$ordID = trim(@$_POST["custom"]);
	$txn_id = trim(@$_POST["txn_id"]);
	$sSQL = "SELECT ordAuthNumber FROM orders WHERE ordPayProvider=1 AND ordStatus>=3 AND ordAuthNumber='" . mysql_escape_string($txn_id) . "' AND ordID='" . mysql_escape_string($ordID) . "'";
	$result = mysql_query($sSQL) or print(mysql_error());
	$success = FALSE;
	if($rs = mysql_fetch_assoc($result))
		$success = (trim($rs["ordAuthNumber"])!="");
	mysql_free_result($result);
	if($success)
		do_order_success($ordID,$emailAddr,FALSE,TRUE,FALSE,FALSE,FALSE);
	else{
		mysql_query("UPDATE cart SET cartCompleted=2 WHERE cartCompleted=0 AND cartOrderID='" . mysql_escape_string($ordID) . "'") or print(mysql_error());
		mysql_query("UPDATE orders SET ordAuthNumber='no ipn' WHERE ordAuthNumber='' AND ordPayProvider=1 AND ordID='" . mysql_escape_string($ordID) . "'") or print(mysql_error());
		$errtext = $xxNoCnf;
		order_failed();
	}
}elseif(@$_GET["ncretval"] != "" && @$_GET["ncsessid"] != ""){ // NOCHEX
	$ordID = trim(@$_GET["ncretval"]);
	$ncsessid = trim(@$_GET["ncsessid"]);
	$sSQL = "SELECT ordAuthNumber FROM orders WHERE ordPayProvider=6 AND ordStatus>=3 AND ordSessionID='" . mysql_escape_string($ncsessid) . "' AND ordID='" . mysql_escape_string($ordID) . "'";
	$result = mysql_query($sSQL) or print(mysql_error());
	$success = FALSE;
	if($rs = mysql_fetch_assoc($result))
		$success = (trim($rs["ordAuthNumber"])!="");
	mysql_free_result($result);
	if($success)
		do_order_success($ordID,$emailAddr,FALSE,TRUE,FALSE,FALSE,FALSE);
	else{
		mysql_query("UPDATE cart SET cartCompleted=2 WHERE cartCompleted=0 AND cartOrderID='" . mysql_escape_string($ordID) . "'") or print(mysql_error());
		mysql_query("UPDATE orders SET ordAuthNumber='no apc' WHERE ordAuthNumber='' AND ordPayProvider=6 AND ordID='" . mysql_escape_string($ordID) . "'") or print(mysql_error());
		$errtext = $xxNoCnf;
		order_failed();
	}
}elseif(@$_POST["xxpreauth"] != ""){
	$ordID = trim(@$_POST["xxpreauth"]);
	$sSQL = "SELECT ordAuthNumber FROM orders WHERE ordID='" . mysql_escape_string($ordID) . "'";
	$result = mysql_query($sSQL) or print(mysql_error());
	$success = FALSE;
	if($rs = mysql_fetch_assoc($result))
		$success = (trim($rs["ordAuthNumber"])!="");
	mysql_free_result($result);
	if($success)
		order_success($ordID,$emailAddr,$sendEmail);
	else
		order_failed();
}elseif(@$_POST["cart_order_id"] != "" && @$_POST["order_number"] != ""){ // 2Checkout Transaction
	if(trim(@$_POST["credit_card_processed"])=="Y"){
		$ordID = trim(@$_POST["cart_order_id"]);
		$sSQL = "SELECT payProvData1,payProvData2 FROM payprovider WHERE payProvID=2";
		$result = mysql_query($sSQL) or print(mysql_error());
		$rs = mysql_fetch_assoc($result);
		$acctno = trim($rs["payProvData1"]);
		$md5key = trim($rs["payProvData2"]);
		mysql_free_result($result);
		$keysmatch=TRUE;
		if($md5key != ""){
			$theirkey = trim(@$_POST["key"]);
			$ourkey = trim(strtoupper(md5($md5key . $acctno . @$_POST["order_number"] . @$_POST["total"])));
			if($ourkey==$theirkey) $keysmatch=TRUE; else $keysmatch=FALSE;
		}
		if($keysmatch){
			do_stock_management($ordID);
			$sSQL="UPDATE cart SET cartCompleted=1 WHERE cartOrderID='" . mysql_escape_string($ordID) . "'";
			mysql_query($sSQL) or print(mysql_error());
			$sSQL="UPDATE orders SET ordStatus=3,ordAuthNumber='" . mysql_escape_string(trim(@$_POST["order_number"])) . "' WHERE ordPayProvider=2 AND ordID='" . mysql_escape_string($ordID) . "'";
			mysql_query($sSQL) or print(mysql_error());
			order_success($ordID,$emailAddr,$sendEmail);
		}else{
			order_failed();
		}
	}else{
		order_failed();
	}
}elseif(@$_POST["CUSTID"] != "" && @$_POST["AUTHCODE"] != ""){ // PayFlow Link
	if(trim(@$_POST["RESULT"])=="0"){
		$ordID = trim(@$_POST["CUSTID"]);
		do_stock_management($ordID);
		$sSQL="UPDATE cart SET cartCompleted=1 WHERE cartOrderID='" . mysql_escape_string($ordID) . "'";
		mysql_query($sSQL) or print(mysql_error());
		$sSQL="UPDATE orders SET ordStatus=3,ordAuthNumber='" . mysql_escape_string(trim(@$_POST["CSCMATCH"])) . mysql_escape_string(unstripslashes(trim(@$_POST["AVSDATA"]))) . "-" . mysql_escape_string(unstripslashes(trim(@$_POST["AUTHCODE"]))) . "' WHERE ordPayProvider=8 AND ordID='" . mysql_escape_string($ordID) . "'";
		mysql_query($sSQL) or print(mysql_error());
		order_success($ordID,$emailAddr,$sendEmail);
	}else{
		order_failed();
	}
}elseif(@$_POST["emailorder"] != "" || @$_POST["secondemailorder"] != ""){
	if(@$emailorderstatus != "") $ordStatus=$emailorderstatus; else $ordStatus=3;
	if(@$_POST["emailorder"] != ""){
		$ordID = trim(@$_POST["emailorder"]);
		$ppid = 4;
	}else{
		$ordID = trim(@$_POST["secondemailorder"]);
		$ppid = 17;
	}
	$thesessionid = trim(str_replace("'","",@$_POST["thesessionid"]));
	$sSQL = "SELECT ordAuthNumber FROM orders WHERE ordSessionID='" . mysql_escape_string($thesessionid) . "' AND ordID='" . mysql_escape_string($ordID) . "'";
	$result = mysql_query($sSQL) or print(mysql_error());
	$success = FALSE;
	if(mysql_num_rows($result) > 0)
		$success = TRUE;
	mysql_free_result($result);
	$sSQL = "SELECT payProvShow FROM payprovider WHERE payProvID=" . $ppid;
	$result = mysql_query($sSQL) or print(mysql_error());
	$rs = mysql_fetch_assoc($result);
	$authnumber = $rs["payProvShow"];
	mysql_free_result($result);
	if($success){
		if($ordStatus >= 3) do_stock_management($ordID);
		$sSQL="UPDATE cart SET cartCompleted=1 WHERE cartOrderID='" . mysql_escape_string($ordID) . "'";
		mysql_query($sSQL) or print(mysql_error());
		$sSQL="UPDATE orders SET ordStatus=" . $ordStatus . ",ordAuthNumber='" . mysql_escape_string($authnumber) . "' WHERE ordPayProvider=" . $ppid . " AND ordID='" . mysql_escape_string($ordID) . "'";
		mysql_query($sSQL) or print(mysql_error());
		order_success($ordID,$emailAddr,$sendEmail);
	}else{
		order_failed();
	}
}elseif(@$_GET["OrdNo"] != "" && @$_GET["RefNo"] != ""){ // PSiGate
	$ordID = trim(@$_GET["OrdNo"]);
	do_stock_management($ordID);
	$sSQL="UPDATE cart SET cartCompleted=1 WHERE cartOrderID='" . mysql_escape_string($ordID) . "'";
	mysql_query($sSQL) or print(mysql_error());
	$sSQL="UPDATE orders SET ordStatus=3,ordAuthNumber='" . mysql_escape_string(trim(@$_GET["RefNo"])) . "' WHERE (ordPayProvider=11 OR ordPayProvider=12) AND ordID='" . mysql_escape_string($ordID) . "'";
	mysql_query($sSQL) or print(mysql_error());
	order_success($ordID,$emailAddr,$sendEmail);
}elseif(@$_POST["oid"] != "" && @$_POST["approval_code"] != ""){ // Linkpoint
	$ordID=mysql_escape_string(trim(@$_POST["oid"]));
	$ordIDa=split(",", $ordID);
	$ordID=$ordIDa[0];
	$theauthcode=mysql_escape_string(trim(@$_POST["approval_code"]));
	$thesuccess=strtolower(trim(@$_POST["status"]));
	if($thesuccess=="approved" || $thesuccess=="submitted"){
		do_stock_management($ordID);
		$sSQL="UPDATE cart SET cartCompleted=1 WHERE cartOrderID='$ordID'";
		mysql_query($sSQL) or print(mysql_error());
		$sSQL="UPDATE orders SET ordStatus=3,ordAuthNumber='" . $theauthcode . "' WHERE ordPayProvider=16 AND ordID='" . $ordID . "'";
		mysql_query($sSQL) or print(mysql_error());
		order_success($ordID,$emailAddr,$sendEmail);
	}
}elseif(@$_POST["docapture"] == "vsprods"){
	$ordID=trim(@$_POST["ordernumber"]);
	$success = TRUE;
	if(@$capturecardorderstatus != "") $ordStatus=$capturecardorderstatus; else $ordStatus=3;
	$encryptmethod = strtolower(@$encryptmethod);
	if($encryptmethod=="none"){
		$enctext = trim(str_replace("'","",@$_POST["ACCT"])) . "&" . trim(str_replace("'","",@$_POST["EXMON"])) . "/" . trim(str_replace("'","",@$_POST["EXYEAR"])) . "&" . trim(str_replace("'","",@$_POST["CVV2"])) . "&" . trim(str_replace("'","",@$_POST["IssNum"]) . "&" . trim(URLEncode(@$_POST["cardname"])));
	}elseif($encryptmethod=="mcrypt"){
		$thekey = @$ccencryptkey;
		if(@$mcryptalg == "") $mcryptalg = MCRYPT_BLOWFISH;
		$td = mcrypt_module_open($mcryptalg, '', 'cbc', '');
		$thekey = substr($thekey, 0, mcrypt_enc_get_key_size($td));
		if(strlen($thekey)<10){
			print "<strong>Warning ! CC Encryption key is too short.</strong>";
			$enctext = "";
		}else{
			$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
			mcrypt_generic_init($td, $thekey, $iv);
			$enctext = bin2hex($iv) . " " . bin2hex(mcrypt_generic($td, trim(str_replace("'","",@$_POST["ACCT"])) . "&" . trim(str_replace("'","",@$_POST["EXMON"])) . "/" . trim(str_replace("'","",@$_POST["EXYEAR"])) . "&" . trim(str_replace("'","",@$_POST["CVV2"])) . "&" . trim(str_replace("'","",@$_POST["IssNum"])) . "&" . trim(URLEncode(@$_POST["cardname"]))));
			mcrypt_generic_deinit($td);
			mcrypt_module_close($td);
		}
	}else{
		print "WARNING: \$encryptmethod is not set. Please see http://www.ecommercetemplates.com/phphelp/ecommplus/parameters.asp#encryption<br />";
	}
	do_stock_management($ordID);
	$sSQL="UPDATE cart SET cartCompleted=1 WHERE cartOrderID='" . mysql_escape_string($ordID) . "'";
	mysql_query($sSQL) or print(mysql_error());
	$sSQL="UPDATE orders SET ordStatus=".$ordStatus.",ordAuthNumber='Card Capture',ordCNum='" . @$enctext . "' WHERE ordPayProvider=10 AND ordID='" . mysql_escape_string($ordID) . "'";
	mysql_query($sSQL) or print(mysql_error());
	order_success($ordID,$emailAddr,$sendEmail);
}elseif(@$_GET["OrdNo"] != "" && @$_GET["ErrMsg"] != ""){ // PSiGate Error Reporting
	$errtext = @$_GET["ErrMsg"];
	order_failed();
}else{
	include "./vsadmin/inc/customppreturn.php";
}
?>