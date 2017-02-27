<?php
//This code is copyright (c) Internet Business Solutions SL, all rights reserved.
//The contents of this file are protect under law as the intellectual property of Internet
//Business Solutions SL. Any use, reproduction, disclosure or copying of any kind 
//without the express and written permission of Internet Business Solutions SL is forbidden.
//Author: Vince Reid, vince@virtualred.net
$lisuccess=0;
if(@$dateadjust=="") $dateadjust=0;
if(@$dateformatstr == "") $dateformatstr = "m/d/Y";
$admindatestr="Y-m-d";
if(@$admindateformat=="") $admindateformat=0;
if($admindateformat==1)
	$admindatestr="m/d/Y";
elseif($admindateformat==2)
	$admindatestr="d/m/Y";
if(@$storesessionvalue=="") $storesessionvalue="virtualstore".time();
if(@$_SESSION["loggedon"] != $storesessionvalue && trim(@$_COOKIE["WRITECKL"])!=""){
	$sSQL="SELECT adminID FROM admin WHERE adminUser='" . mysql_escape_string(unstripslashes(trim(@$_COOKIE["WRITECKL"]))) . "' AND adminPassword='" . mysql_escape_string(unstripslashes(trim(@$_COOKIE["WRITECKP"]))) . "' AND adminID=1";
	$result = mysql_query($sSQL) or print(mysql_error());
	if(mysql_num_rows($result)>0)
		@$_SESSION["loggedon"] = $storesessionvalue;
	else
		$lisuccess=2;
	mysql_free_result($result);
}
if($_SESSION["loggedon"] != $storesessionvalue && $lisuccess!=2) exit;
if(@$htmlemails==TRUE) $emlNl = "<br />"; else $emlNl="\n";
function release_stock($smOrdId){
	global $stockManage;
	if($stockManage != 0){
		$sSQL="SELECT cartID,cartProdID,cartQuantity,pSell FROM cart INNER JOIN products ON cart.cartProdID=products.pID WHERE cartCompleted=1 AND cartOrderID=" . $smOrdId;
		$result = mysql_query($sSQL) or print(mysql_error());
		while($rs = mysql_fetch_array($result)){
			if((($rs["pSell"] & 2) == 2)){
				$sSQL = "SELECT coOptID FROM cartoptions INNER JOIN (options INNER JOIN optiongroup ON options.optGroup=optiongroup.optGrpID) ON cartoptions.coOptID=options.optID WHERE (optType=2 OR optType=-2) AND coCartID=" . $rs["cartID"];
				$result2 = mysql_query($sSQL) or print(mysql_error());
				while($rs2 = mysql_fetch_array($result2)){
					$sSQL = "UPDATE options SET optStock=optStock+" . $rs["cartQuantity"] . " WHERE optID=" . $rs2["coOptID"];
					mysql_query($sSQL) or print(mysql_error());
				}
				mysql_free_result($result2);
			}else{
				$sSQL = "UPDATE products SET pInStock=pInStock+" . $rs["cartQuantity"] . " WHERE pID='" . $rs["cartProdID"] . "'";
				mysql_query($sSQL) or print(mysql_error());
			}
		}
		mysql_free_result($result);
	}
}
if($lisuccess==2){
?>
	  <table border="0" cellspacing="0" cellpadding="0" width="100%" bgcolor="" align="center">
        <tr>
          <td width="100%">
            <table width="100%" border="0" cellspacing="0" cellpadding="2" bgcolor="">
			  <tr> 
                <td width="100%" colspan="4" align="center"><p>&nbsp;</p><p>&nbsp;</p>
				  <p><strong><?php print $yyOpFai?></strong></p><p>&nbsp;</p>
				  <p><?php print $yyCorCoo?> <?php print $yyCorLI?> <a href="login.php"><?php print $yyClkHer?></a>.</p>
				</td>
			  </tr>
			</table>
		  </td>
		</tr>
	  </table>
<?php
}else{
$success=true;
$alreadygotadmin = getadminsettings();
if(@$_POST["updatestatus"]=="1"){
	mysql_query("UPDATE orders SET ordStatusInfo='" . mysql_escape_string(unstripslashes(trim(@$_POST["ordStatusInfo"]))) . "' WHERE ordID=" . @$_POST["orderid"]) or print(mysql_error());
}elseif(@$_GET["id"] != ""){
	if(@$_POST["delccdets"] != ""){
		mysql_query("UPDATE orders SET ordCNum='' WHERE ordID=" . @$_GET["id"]);
	}
	$sSQL = "SELECT ordID,ordName,ordAddress,ordCity,ordState,ordZip,ordCountry,ordEmail,ordPhone,ordShipName,ordShipAddress,ordShipCity,ordShipState,ordShipZip,ordShipCountry,ordPayProvider,ordAuthNumber,ordTotal,ordDate,ordStateTax,ordCountryTax,ordHSTTax,ordShipping,ordShipType,ordIP,ordAffiliate,ordDiscount,ordHandling,ordDiscountText,ordComLoc,ordExtra1,ordExtra2,ordAddInfo,ordCNum,ordStatusInfo FROM orders LEFT JOIN payprovider ON payprovider.payProvID=orders.ordPayProvider WHERE ordID=" . $_GET["id"];
	$result = mysql_query($sSQL) or print(mysql_error());
	$alldata = mysql_fetch_array($result);
	$alldata["ordDate"] = strtotime($alldata["ordDate"]);
	// if(@$dateadjust != "") $alldata["ordDate"] += $dateadjust*60*60;
	mysql_free_result($result);

	$sSQL = "SELECT cartProdId,cartProdName,cartProdPrice,cartQuantity,cartID FROM cart WHERE cartOrderID=" . $_GET["id"];
	$allorders = mysql_query($sSQL) or print(mysql_error());
}else{
	// Delete old uncompleted orders.
	if($delccafter != 0){
		$sSQL = "UPDATE orders SET ordCNum='' WHERE ordDate<'" . date("Y-m-d H:i:s", time()-($delccafter*60*60*24)) . "'";
		mysql_query($sSQL) or print(mysql_error());
	}
	if($delAfter != 0){
		$sSQL = "SELECT cartOrderID,cartID FROM cart WHERE cartCompleted=0 AND cartDateAdded<'" . date("Y-m-d H:i:s", time()-($delAfter*60*60*24)) . "'";
		$result = mysql_query($sSQL) or print(mysql_error());
		if(mysql_num_rows($result)>0){
			$delStr="";
			$delOptions="";
			$addcomma = "";
			while($rs = mysql_fetch_assoc($result)){
				$delStr .= $addcomma . $rs["cartOrderID"];
				$delOptions .= $addcomma . $rs["cartID"];
				$addcomma = ",";
			}
			mysql_query("DELETE FROM orders WHERE ordID IN (" . $delStr . ")") or print(mysql_error());
			mysql_query("DELETE FROM cartoptions WHERE coCartID IN (" . $delOptions . ")") or print(mysql_error());
			mysql_query("DELETE FROM cart WHERE cartID IN (" . $delOptions . ")") or print(mysql_error());
		}
		mysql_free_result($result);
	}else{
		$sSQL = "SELECT cartOrderID,cartID FROM cart WHERE cartCompleted=0 AND cartOrderID=0 AND cartDateAdded<'" . date("Y-m-d H:i:s", time()-(3*60*60*24)) . "'";
		$result = mysql_query($sSQL) or print(mysql_error());
		if(mysql_num_rows($result)>0){
			$delStr="";
			$delOptions="";
			$addcomma = "";
			while($rs = mysql_fetch_assoc($result)){
				$delStr .= $addcomma . $rs["cartOrderID"];
				$delOptions .= $addcomma . $rs["cartID"];
				$addcomma = ",";
			}
			mysql_query("DELETE FROM cartoptions WHERE coCartID IN (" . $delOptions . ")") or print(mysql_error());
			mysql_query("DELETE FROM cart WHERE cartID IN (" . $delOptions . ")") or print(mysql_error());
		}
		mysql_free_result($result);
	}
	$numstatus=0;
	$sSQL = "SELECT statID,statPrivate FROM orderstatus WHERE statPrivate<>'' ORDER BY statID";
	$result = mysql_query($sSQL) or print(mysql_error());
	while($rs = mysql_fetch_assoc($result)){
		$allstatus[$numstatus++]=$rs;
	}
	mysql_free_result($result);
}
if(@$_POST["updatestatus"]=="1"){
?>
<script language="javascript" type="text/javascript">
<!--
setTimeout("history.go(-2);",1100);
// -->
</script>
	  <table border="0" cellspacing="0" cellpadding="0" width="100%" bgcolor="" align="center">
        <tr>
          <td width="100%">
            <table width="100%" border="0" cellspacing="0" cellpadding="2" bgcolor="">
			  <tr> 
                <td width="100%" colspan="4" align="center"><br /><strong><?php print $yyUpdSuc?></strong><br /><br /><?php print $yyNowFrd?><br /><br />
                        <?php print $yyNoAuto?> <a href="javascript:history.go(-2)"><strong><?php print $yyClkHer?></strong></a>.<br /><br />
						<img src="../images/clearpixel.gif" width="300" height="3" alt="" /></td>
			  </tr>
			</table>
		  </td>
		</tr>
	  </table>
<?php
}elseif(@$_GET["id"] != ""){
?>
<script language="javascript" type="text/javascript">
<!--
function openemailpopup(id) {
  popupWin = window.open('popupemail.php?'+id,'emailpopup','menubar=no, scrollbars=no, width=300, height=250, directories=no,location=no,resizable=yes,status=no,toolbar=no')
}
//-->
</script>
	  <table border="0" cellspacing="0" cellpadding="0" width="100%" bgcolor="" align="center">
        <tr>
          <td width="100%">
            <table width="100%" border="0" cellspacing="0" cellpadding="2" bgcolor="">
<?php		if($isprinter && @$invoiceheader != ""){ ?>
			  <tr> 
                <td width="100%" colspan="4"><?php print $invoiceheader?></td>
			  </tr>
<?php		} ?>
			  <tr> 
                <td width="100%" colspan="4" align="center"><strong><?php print $xxOrdNum . " " . $alldata["ordID"] . "<br /><br />" . date($dateformatstr, $alldata["ordDate"]) . " " . date("H:i", $alldata["ordDate"])?></strong></td>
			  </tr>
<?php		if($isprinter && @$invoiceaddress != ""){ ?>
			  <tr> 
                <td width="100%" colspan="4"><?php print $invoiceaddress?></td>
			  </tr>
<?php		} ?>
<?php		if(trim(@$extraorderfield1)!=""){ ?>
			<tr>
			  <td width="20%" align="right"><strong><?php print $extraorderfield1 ?>:</strong></td>
			  <td align="left" colspan="3"><?php print $alldata["ordExtra1"]?></td>
			</tr>
<?php		} ?>
			<tr>
			  <td width="20%" align="right"><strong><?php print $xxName?>:</strong></td>
			  <td width="30%" align="left"><?php print $alldata["ordName"]?></td>
			  <td width="20%" align="right"><?php if(! $isprinter && $alldata["ordAuthNumber"] != "") print '<input type="button" value="Resend" onclick="javascript:openemailpopup(\'id=' . $alldata["ordID"] . '\')" />' ?>
			  <strong><?php print $xxEmail?>:</strong></td>
			  <td width="30%" align="left"><?php if(! $isprinter) print '<a href="mailto:' . $alldata["ordEmail"] . '">' . $alldata["ordEmail"] . '</a>'; else print $alldata["ordEmail"];?></td>
			</tr>
			<tr>
			  <td align="right"><strong><?php print $xxAddress?>:</strong></td>
			  <td align="left"><?php print $alldata["ordAddress"]?></td>
			  <td align="right"><strong><?php print $xxCity?>:</strong></td>
			  <td align="left"><?php print $alldata["ordCity"]?></td>
			</tr>
			<tr>
			  <td align="right"><strong><?php print $xxAllSta?>:</strong></td>
			  <td align="left"><?php print $alldata["ordState"]?></td>
			  <td align="right"><strong><?php print $xxCountry?>:</strong></td>
			  <td align="left"><?php print $alldata["ordCountry"]?></td>
			</tr>
			<tr>
			  <td align="right"><strong><?php print $xxZip?>:</strong></td>
			  <td align="left"><?php print $alldata["ordZip"]?></td>
			  <td align="right"><strong><?php print $xxPhone?>:</strong></td>
			  <td align="left"><?php print $alldata["ordPhone"]?></td>
			</tr>
<?php if(trim(@$extraorderfield2)!=""){ ?>
			<tr>
			  <td align="right"><strong><?php print @$extraorderfield2 ?>:</strong></td>
			  <td align="left" colspan="3"><?php print $alldata["ordExtra2"]?></td>
			</tr>
<?php } ?>
<?php if(! $isprinter){ ?>
			<tr>
			  <td align="right"><strong>IP Address:</strong></td>
			  <td align="left"><?php print $alldata["ordIP"]?></td>
			  <td align="right"><strong>Affiliate:</strong></td>
			  <td align="left"><?php print $alldata["ordAffiliate"]?></td>
			</tr>
<?php }
	  if(trim($alldata["ordDiscountText"])!=""){ ?>
			<tr>
			  <td align="right" valign="top"><strong><?php print $xxAppDs?>:</strong></td>
			  <td align="left" colspan="3"><?php print $alldata["ordDiscountText"]?></td>
			</tr>
<?php }
	  if($alldata["ordShipAddress"] != "" || $alldata["ordShipCity"] != "" || $alldata["ordShipState"] != ""){ ?>
			<tr>
			  <td width="100%" align="center" colspan="4"><strong><?php print $xxShpDet?>.</strong></td>
			</tr>
			<tr>
			  <td align="right"><strong><?php print $xxName?>:</strong></td>
			  <td align="left" colspan="3"><?php print $alldata["ordShipName"]?></td>
			</tr>
			<tr>
			  <td align="right"><strong><?php print $xxAddress?>:</strong></td>
			  <td align="left"><?php print $alldata["ordShipAddress"]?></td>
			  <td align="right"><strong><?php print $xxCity?>:</strong></td>
			  <td align="left"><?php print $alldata["ordShipCity"]?></td>
			</tr>
			<tr>
			  <td align="right"><strong><?php print $xxAllSta?>:</strong></td>
			  <td align="left"><?php print $alldata["ordShipState"]?></td>
			  <td align="right"><strong><?php print $xxCountry?>:</strong></td>
			  <td align="left"><?php print $alldata["ordShipCountry"]?></td>
			</tr>
			<tr>
			  <td align="right"><strong><?php print $xxZip?>:</strong></td>
			  <td align="left" colspan="3"><?php print $alldata["ordShipZip"]?></td>
			</tr>
<?php }
	  if($alldata["ordShipType"] != "" || $alldata["ordComLoc"]>0){ ?>
			<tr>
			  <td align="right"><strong><?php print $xxShpMet?>:</strong></td>
			  <td align="left"><?php print $alldata["ordShipType"];
									 if(($alldata["ordComLoc"]&2)==2) print $xxWtIns?></td>
			  <td align="right"><strong><?php print $xxCLoc?>:</strong></td>
			  <td align="left"><?php if(($alldata["ordComLoc"]&1)==1) print "Yes"; else print "No"?></td>
			</tr>
<?php }
	  $ordAddInfo = Trim($alldata["ordAddInfo"]);
      if($ordAddInfo != ""){ ?>
			<tr>
			  <td align="right" valign="top"><strong><?php print $xxAddInf?>:</strong></td>
			  <td align="left" colspan="3"><?php print str_replace(array("\r\n","\n"),array("<br />","<br />"),$ordAddInfo)?></td>
			</tr>
<?php }
if(! $isprinter){
?>
		  <form method="post" action="adminorders.php">
		  <input type="hidden" name="updatestatus" value="1" />
		  <input type="hidden" name="orderid" value="<?php print @$_GET["id"]?>" />
			<tr>
			  <td align="right" valign="top"><strong><?php print $yyStaInf?>:</strong></td>
			  <td align="left" colspan="3"><textarea name="ordStatusInfo" cols="50" rows="4" wrap=virtual><?php print $alldata["ordStatusInfo"]?></textarea> <input type="submit" value="<?php print $yyUpdate?>" /></td>
			</tr>
<?php	if(($alldata["ordPayProvider"]==3 || $alldata["ordPayProvider"]==13) && $alldata["ordAuthNumber"] != ""){ ?>
			<tr>
			  <td width="50%" align="center" colspan="4">
				<input type="button" value="Capture Funds" onclick="javascript:openemailpopup('oid=<?php print $alldata["ordID"]?>')" />
			  </td>
			</tr>
<?php	} ?>
		  </form>
<?php
	if((int)$alldata["ordPayProvider"]==10){ ?>
			<tr>
			  <td width="50%" align="center" colspan="4"><hr width="50%">
			  </td>
			</tr>
<?php	if(@$_SERVER["HTTPS"] != "on" && (@$_SERVER["SERVER_PORT"] != "443") && @$nochecksslserver != TRUE){ ?>
			<tr>
			  <td width="50%" align="right" colspan="4"><strong><font color="#FF0000">You do not appear to be viewing this page on a secure (https) connection. Credit card information cannot be shown.</strong></td>
			</tr>
<?php	}else{
			$ordCNum = $alldata["ordCNum"];
			if($ordCNum != ""){
				$cnumarr = "";
				$encryptmethod = strtolower(@$encryptmethod);
				if($encryptmethod=="none"){
					$cnumarr = explode("&",$ordCNum);
				}elseif($encryptmethod=="mcrypt"){
					if(@$mcryptalg == "") $mcryptalg = MCRYPT_BLOWFISH;
					$td = mcrypt_module_open($mcryptalg, '', 'cbc', '');
					$thekey = @$ccencryptkey;
					$thekey = substr($thekey, 0, mcrypt_enc_get_key_size($td));
					$cnumarr = explode(" ", $ordCNum);
					$iv = @$cnumarr[0];
					$iv = @pack("H" . strlen($iv), $iv);
					$ordCNum = @pack("H" . strlen(@$cnumarr[1]), @$cnumarr[1]);
					mcrypt_generic_init($td, $thekey, $iv);
					$cnumarr = explode("&", mdecrypt_generic($td, $ordCNum));
					mcrypt_generic_deinit($td);
					mcrypt_module_close($td);
				}else{
					print '<tr><td colspan="4">WARNING: $encryptmethod is not set. Please see http://www.ecommercetemplates.com/phphelp/ecommplus/parameters.asp#encryption</td></tr>';
				}
			} ?>
			<tr>
			  <td width="50%" align="right" colspan="2"><strong><?php print $xxCCName?>:</strong></td>
			  <td width="50%" align="left" colspan="2"><?php
			if(@$encryptmethod!=""){
					if(is_array(@$cnumarr)) print URLDecode(@$cnumarr[4]);
			} ?></td>
			</tr>
			<tr>
			  <td width="50%" align="right" colspan="2"><strong><?php print $yyCarNum?>:</strong></td>
			  <td width="50%" align="left" colspan="2"><?php
			if($ordCNum != ""){
				if(is_array($cnumarr)) print $cnumarr[0];
			}else{
				print "(no data)";
			} ?></td>
			</tr>
			<tr>
			  <td width="50%" align="right" colspan="2"><strong><?php print $yyExpDat?>:</strong></td>
			  <td width="50%" align="left" colspan="2"><?php
			if(@$encryptmethod!=""){
					if(is_array(@$cnumarr)) print @$cnumarr[1];
			} ?></td>
			</tr>
			<tr>
			  <td width="50%" align="right" colspan="2"><strong>CVV Code:</strong></td>
			  <td width="50%" align="left" colspan="2"><?php
			if(@$encryptmethod!=""){
					if(is_array(@$cnumarr)) print @$cnumarr[2];
			} ?></td>
			</tr>
			<tr>
			  <td width="50%" align="right" colspan="2"><strong>Issue Number:</strong></td>
			  <td width="50%" align="left" colspan="2"><?php
			if(@$encryptmethod!=""){
					if(is_array(@$cnumarr)) print @$cnumarr[3];
			} ?></td>
			</tr>
<?php		if($ordCNum != ""){ ?>
		  <form method=POST action="adminorders.php?id=<?php print $_GET["id"]?>">
			<input type="hidden" name="delccdets" value="<?php print $_GET["id"]?>" />
			<tr>
			  <td width="100%" align="center" colspan="4"><input type=submit value="<?php print $yyDelCC?>" /></td>
			</tr>
		  </form>
<?php		}
		}
	}
} // isprinter ?>
			<tr>
			  <td width="100%" align="center" colspan="4">&nbsp;<br /></td>
			</tr>
<?php
	if(mysql_num_rows($allorders)>0){
?>
		  </table>
		  <table width="100%" border="1" cellspacing="0" cellpadding="4" bordercolor="#E7EAEF" bgcolor="">
			<tr>
			  <td><strong><?php print $xxPrId?></strong></td>
			  <td><strong><?php print $xxPrNm?></strong></td>
			  <td><strong><?php print $xxPrOpts?></strong></td>
			  <td><strong><?php print $xxQuant?></strong></td>
			  <td><strong><?php print $xxPrice?></strong></td>
			</tr>
<?php
		while($rsOrders = mysql_fetch_assoc($allorders)){
?>
			<tr>
			  <td valign="top"><strong><?php print $rsOrders["cartProdId"]?></strong></td>
			  <td valign="top"><?php print $rsOrders["cartProdName"]?></td>
			  <td valign="top"><?php
			$sSQL = "SELECT coOptGroup,coCartOption,coPriceDiff FROM cartoptions WHERE coCartID=" . $rsOrders["cartID"] . " ORDER BY coID";
			$result = mysql_query($sSQL) or print(mysql_error());
			$gotone=false;
			while($rs = mysql_fetch_array($result)){
				$gotone=true;
				print '<strong>' . $rs["coOptGroup"] . ':</strong> ' . $rs["coCartOption"] . '<br />';
				$rsOrders["cartProdPrice"] += $rs["coPriceDiff"];
			}
			mysql_free_result($result);
			if(! $gotone) print ' - ';
?></td>
			  <td valign="top"><?php print $rsOrders["cartQuantity"]?></td>
			  <td valign="top"><?php print FormatEuroCurrency($rsOrders["cartProdPrice"]*$rsOrders["cartQuantity"])?></td>
			</tr>
<?php	}
	}
?>
			<tr>
			  <td align="right" colspan="4"><strong><?php print $xxOrdTot?>:</strong></td>
			  <td align="left"><?php print FormatEuroCurrency($alldata["ordTotal"])?></td>
			</tr>
<?php if((double)$alldata["ordShipping"]!=0.0){ ?>
			<tr>
			  <td align="right" colspan="4"><strong><?php print $xxShippg?>:</strong></td>
			  <td align="left"><?php print FormatEuroCurrency($alldata["ordShipping"])?></td>
			</tr>
<?php }
	  if((double)$alldata["ordHandling"]!=0.0){ ?>
			<tr>
			  <td align="right" colspan="4"><strong><?php print $xxHndlg?>:</strong></td>
			  <td align="left"><?php print FormatEuroCurrency($alldata["ordHandling"])?></td>
			</tr>
<?php }
	  if((double)$alldata["ordDiscount"]!=0.0){ ?>
			<tr>
			  <td align="right" colspan="4"><strong><?php print $xxDscnts?>:</strong></td>
			  <td align="left"><font color="#FF0000"><?php print FormatEuroCurrency($alldata["ordDiscount"])?></font></td>
			</tr>
<?php }
	  if((double)$alldata["ordStateTax"]!=0.0){ ?>
			<tr>
			  <td align="right" colspan="4"><strong><?php print $xxStaTax?>:</strong></td>
			  <td align="left"><?php print FormatEuroCurrency($alldata["ordStateTax"])?></td>
			</tr>
<?php }
	  if((double)$alldata["ordCountryTax"]!=0.0){ ?>
			<tr>
			  <td align="right" colspan="4"><strong><?php print $xxCntTax?>:</strong></td>
			  <td align="left"><?php print FormatEuroCurrency($alldata["ordCountryTax"])?></td>
			</tr>
<?php }
	  if((double)$alldata["ordHSTTax"]!=0.0){ ?>
			<tr>
			  <td align="right" colspan="4"><strong><?php print $xxHST?>:</strong></td>
			  <td align="left"><?php print FormatEuroCurrency($alldata["ordHSTTax"])?></td>
			</tr>
<?php } ?>
			<tr>
			  <td align="right" colspan="4"><strong><?php print $xxGndTot?>:</strong></td>
			  <td align="left"><?php print FormatEuroCurrency(($alldata["ordTotal"]+$alldata["ordStateTax"]+$alldata["ordCountryTax"]+$alldata["ordHSTTax"]+$alldata["ordShipping"]+$alldata["ordHandling"])-$alldata["ordDiscount"])?></td>
			</tr>
			</table>
		  </td>
		</tr>
<?php	if($isprinter && @$invoicefooter != ""){ ?>
		<tr> 
          <td width="100%"><?php print $invoicefooter?></td>
		</tr>
<?php	} ?>
	  </table>
<?php
}else{
	$sSQL = "SELECT ordID FROM orders WHERE ordStatus=1";
	if(@$_POST["act"] != "purge") $sSQL .= " AND ordStatusDate<'" . date("Y-m-d H:i:s", time()-(3*60*60*24)) . "'";
	$result = mysql_query($sSQL) or print(mysql_error());
	while($rs = mysql_fetch_assoc($result)){
		$theid = $rs["ordID"];
		$delOptions = "";
		$addcomma = "";
		$result2 = mysql_query("SELECT cartID FROM cart WHERE cartOrderID=" . $theid) or print(mysql_error());
		while($rs2 = mysql_fetch_assoc($result2)){
			$delOptions .= $addcomma . $rs2["cartID"];
			$addcomma = ",";
		}
		if($delOptions != ""){
			$sSQL = "DELETE FROM cartoptions WHERE coCartID IN (" . $delOptions . ")";
			mysql_query($sSQL) or print(mysql_error());
		}
		mysql_query("DELETE FROM cart WHERE cartOrderID=" . $theid) or print(mysql_error());
		mysql_query("DELETE FROM orders WHERE ordID=" . $theid) or print(mysql_error());
	}
	if(@$_POST["act"]=="authorize"){
		do_stock_management(trim($_POST["id"]));
		if(trim($_POST["authcode"]) != "")
			$sSQL = "UPDATE orders set ordAuthNumber='" . mysql_escape_string(trim($_POST["authcode"])) . "',ordStatus=3 WHERE ordID=" . $_POST["id"];
		else
			$sSQL = "UPDATE orders set ordAuthNumber='" . mysql_escape_string($yyManAut) . "',ordStatus=3 WHERE ordID=" . $_POST["id"];
		mysql_query($sSQL) or print(mysql_error());
		mysql_query("UPDATE cart SET cartCompleted=1 WHERE cartOrderID=" . $_POST["id"]) or print(mysql_error());
	}elseif(@$_POST["act"]=="status"){
		$maxitems=(int)($_POST["maxitems"]);
		for($index=0; $index < $maxitems; $index++){
			$iordid = trim($_POST["ordid" . $index]);
			$ordstatus = trim($_POST["ordstatus" . $index]);
			$ordauthno = "";
			$oldordstatus=999;
			$result = mysql_query("SELECT ordStatus,ordAuthNumber,ordEmail,ordDate,".getlangid("statPublic",64).",ordStatusInfo,ordName FROM orders INNER JOIN orderstatus ON orders.ordStatus=orderstatus.statID WHERE ordID=" . $iordid) or print(mysql_error());
			if($rs = mysql_fetch_assoc($result)){
				$oldordstatus=$rs["ordStatus"];
				$ordauthno=$rs["ordAuthNumber"];
				$ordemail=$rs["ordEmail"];
				$orddate=strtotime($rs["ordDate"]);
				$oldstattext=$rs[getlangid("statPublic",64)];
				$ordstatinfo=$rs["ordStatusInfo"];
				$ordername=$rs["ordName"];
			}
			if(! ($oldordstatus==999) && ($oldordstatus < 3 && $ordstatus >=3)){
				// This is to force stock management
				mysql_query("UPDATE cart SET cartCompleted=0 WHERE cartOrderID=" . $iordid) or print(mysql_error());
				do_stock_management($iordid);
				mysql_query("UPDATE cart SET cartCompleted=1 WHERE cartOrderID=" . $iordid) or print(mysql_error());
				if($ordauthno=="") mysql_query("UPDATE orders SET ordAuthNumber='". mysql_escape_string($yyManAut) . "' WHERE ordID=" . $iordid) or print(mysql_error());
			}
			if(! ($oldordstatus==999) && ($oldordstatus >=3 && $ordstatus < 3)) release_stock($iordid);
			if($iordid != "" && $ordstatus != ""){
				if($oldordstatus != (int)$ordstatus && @$_POST["emailstat"]=="1"){
					$result = mysql_query("SELECT ".getlangid("statPublic",64)." FROM orderstatus WHERE statID=" . $ordstatus);
					if($rs = mysql_fetch_assoc($result))
						$newstattext = $rs[getlangid("statPublic",64)];
					$emailsubject = "Order status updated";
					if(@$orderstatussubject != "") $emailsubject=$orderstatussubject;
					$ose = $orderstatusemail;
					$ose = str_replace("%orderid%", $iordid, $ose);
					$ose = str_replace("%orderdate%", date($dateformatstr, $orddate) . " " . date("H:i", $orddate), $ose);
					$ose = str_replace("%oldstatus%", $oldstattext, $ose);
					$ose = str_replace("%newstatus%", $newstattext, $ose);
					$thetime = time() + ($dateadjust*60*60);
					$ose = str_replace("%date%", date($dateformatstr, $thetime) . " " . date("H:i", $thetime), $ose);
					$ose = str_replace("%statusinfo%", $ordstatinfo, $ose);
					$ose = str_replace("%ordername%", $ordername, $ose);
					$ose = str_replace("%nl%", $emlNl, $ose);
					$headers = "MIME-Version: 1.0\n";
					$headers .= "From: " . $emailAddr . " <" . $emailAddr . ">\n";
					if(@$htmlemails==TRUE)
						$headers .= "Content-type: text/html; charset=".$emailencoding."\n";
					else
						$headers .= "Content-type: text/plain; charset=".$emailencoding."\n";
					mail($ordemail, $emailsubject, $ose, $headers);
				}
				if($oldordstatus != (int)$ordstatus) mysql_query("UPDATE orders SET ordStatus=" . $ordstatus . ",ordStatusDate='" . date("Y-m-d H:i:s", time() + ($dateadjust*60*60)) . "' WHERE ordID=" . $iordid) or print(mysql_error());
			}
		}
	}
	if(@$_POST["sd"] != "")
		$sd = @$_POST["sd"];
	elseif(@$_GET["sd"] != "")
		$sd = @$_GET["sd"];
	else
		$sd = date($admindatestr, time() + ($dateadjust*60*60));
	if(@$_POST["ed"] != "")
		$ed = @$_POST["ed"];
	elseif(@$_GET["ed"] != "")
		$ed = @$_GET["ed"];
	else
		$ed = date($admindatestr, time() + ($dateadjust*60*60));
	$sd = parsedate($sd);
	$ed = parsedate($ed);
	if($sd > $ed) $ed = $sd;
	$fromdate = trim(@$_POST["fromdate"]);
	$todate = trim(@$_POST["todate"]);
	$ordid = trim(str_replace('"',"",str_replace("'","",@$_POST["ordid"])));
	$origsearchtext = trim(unstripslashes(@$_POST["searchtext"]));
	$searchtext = trim(mysql_escape_string(unstripslashes(@$_POST["searchtext"])));
	$ordstatus = "";
	if(@$_POST["powersearch"]=="1"){
		$sSQL = "SELECT ordID,ordName,payProvName,ordAuthNumber,ordDate,ordStatus,ordTotal-ordDiscount AS ordTot FROM orders INNER JOIN payprovider ON payprovider.payProvID=orders.ordPayProvider WHERE ordStatus>=0 ";
		$addcomma = "";
		if(is_array(@$_POST["ordstatus"])){
			foreach($_POST["ordstatus"] as $objValue){
				if(is_array($objValue))$objValue=$objValue[0];
				$ordstatus .= $addcomma . $objValue;
				$addcomma = ",";
			}
		}else
			$ordstatus = trim((string)@$_POST["ordstatus"]);
		if($ordid != ""){
			if(is_numeric($ordid)){
				$sSQL .= " AND ordID=" . $ordid;
			}else{
				$success=FALSE;
				$errmsg="The order id you specified seems to be invalid - " . $ordid;
				$sSQL .= " AND ordID=0";
			}
		}else{
			if($fromdate != ""){
				if(is_numeric($fromdate))
					$thefromdate = time()-($fromdate*60*60*24);
				else
					$thefromdate = parsedate($fromdate);
				if($todate=="")
					$thetodate = $thefromdate;
				elseif(is_numeric($todate))
					$thetodate = time()-($todate*60*60*24);
				else
					$thetodate = parsedate($todate);
				if($thefromdate > $thetodate){
					$tmpdate = $thetodate;
					$thetodate = $thefromdate;
					$thefromdate = $tmpdate;
				}
				$sd = $thefromdate;
				$ed = $thetodate;
				$sSQL .= " AND ordDate BETWEEN '" . date("Y-m-d", $sd) . "' AND '" . date("Y-m-d", $ed) . " 23:59:59'";
			}
			if($ordstatus != "" && strpos($ordstatus,"9999")===FALSE) $sSQL .= " AND ordStatus IN (" . $ordstatus . ")";
			if($searchtext != "") $sSQL .= " AND (ordAuthNumber LIKE '%" . $searchtext . "%' OR ordName LIKE '%" . $searchtext . "%' OR ordEmail LIKE '%" . $searchtext . "%' OR ordAddress LIKE '%" . $searchtext . "%' OR ordCity LIKE '%" . $searchtext . "%' OR ordState LIKE '%" . $searchtext . "%' OR ordZip LIKE '%" . $searchtext . "%' OR ordPhone LIKE '%" . $searchtext . "%')";
		}
		$sSQL .= " ORDER BY ordID";
	}else{
		$sSQL = "SELECT ordID,ordName,payProvName,ordAuthNumber,ordDate,ordStatus,ordTotal-ordDiscount AS ordTot FROM orders LEFT JOIN payprovider ON payprovider.payProvID=orders.ordPayProvider WHERE ordStatus<>1 AND ordDate BETWEEN '" . date("Y-m-d", $sd) . "' AND '" . date("Y-m-d", $ed) . " 23:59:59' ORDER BY ordID";
	}
	$alldata = mysql_query($sSQL) or print(mysql_error());
	$hasdeleted=false;
	$sSQL = "SELECT COUNT(*) AS NumDeleted FROM orders WHERE ordStatus=1";
	$result = mysql_query($sSQL) or print(mysql_error());
	$rs = mysql_fetch_assoc($result);
	if($rs["NumDeleted"] > 0) $hasdeleted=true;
	mysql_free_result($result);
?>
<script language="javascript" type="text/javascript" src="popcalendar.js">
</script>
<script language="javascript" type="text/javascript">
<!--
function delrec(id) {
cmsg = "<?php print $yyConDel?>\n"
if (confirm(cmsg)) {
	document.mainform.id.value = id;
	document.mainform.act.value = "delete";
	document.mainform.sd.value="<?php print date($admindatestr, $sd)?>";
	document.mainform.ed.value="<?php print date($admindatestr, $ed)?>";
	document.mainform.submit();
}
}
function authrec(id) {
var aucode;
cmsg = "<?php print $yyEntAuth?>"
if ((aucode=prompt(cmsg,'<?php print $yyManAut?>'))!=null) {
	document.mainform.id.value = id;
	document.mainform.act.value = "authorize";
	document.mainform.authcode.value = aucode;
	document.mainform.sd.value="<?php print date($admindatestr, $sd)?>";
	document.mainform.ed.value="<?php print date($admindatestr, $ed)?>";
	document.mainform.submit();
}
}
function checkcontrol(tt,evt){
<?php if(strstr(@$HTTP_SERVER_VARS["HTTP_USER_AGENT"], "Gecko")){ ?>
theevnt = evt;
return;
<?php }else{ ?>
theevnt=window.event;
<?php } ?>
if(theevnt.ctrlKey){
	maxitems=document.mainform.maxitems.value;
	for(index=0;index<maxitems;index++){
		if(eval('document.mainform.ordstatus'+index+'.length') > tt.selectedIndex){
			eval('document.mainform.ordstatus'+index+'.selectedIndex='+tt.selectedIndex);
			eval('document.mainform.ordstatus'+index+'.options['+tt.selectedIndex+'].selected=true');
		}
	}
}
}
function displaysearch(){
thestyle = document.getElementById('searchspan').style;
if(thestyle.display=='none')
	thestyle.display = 'block';
else
	thestyle.display = 'none';
}
function checkprinter(tt,evt){
<?php if(strstr(@$HTTP_SERVER_VARS["HTTP_USER_AGENT"], "Gecko")){ ?>
if(evt.ctrlKey || evt.altKey || document.mainform.ctrlmod.checked){
	tt.href += "&printer=true";
	window.location.href = tt.href;
}
<?php }else{ ?>
theevnt=window.event;
if(theevnt.ctrlKey || document.mainform.ctrlmod.checked)tt.href += "&printer=true";
<?php } ?>
return(true);
}
// -->
</script>
      <table border="0" cellspacing="0" cellpadding="0" width="100%" bgcolor="">
        <tr>
          <td width="100%" align="center">
<?php	$themask = 'yyyy-mm-dd';
		if($admindateformat==1)
			$themask='mm/dd/yyyy';
		elseif($admindateformat==2)
			$themask='dd/mm/yyyy';
		if(! $success) print "<p><font color='#FF0000'>" . $errmsg . "</font></p>"; ?>
			<span name="searchspan" id="searchspan" <?php if($usepowersearch) print 'style="display:block"'; else print 'style="display:none"'?>>
            <table width="100%" border="0" cellspacing="1" cellpadding="1" bgcolor="">
			  <form method="post" action="adminorders.php" name="psearchform">
			  <input type="hidden" name="powersearch" value="1" />
			  <tr bgcolor="#030133"><td colspan="4"><strong><font color="#E7EAEF">&nbsp;<?php print $yyPowSea?></font></strong></td></tr>
			  <tr bgcolor="#E7EAEF"> 
                <td align="right" width="25%"><strong><?php print $yyOrdFro?>:</strong></td>
				<td align="left" width="25%">&nbsp;<input type="text" size="14" name="fromdate" value="<?php print $fromdate?>" /> <input type=button onclick="popUpCalendar(this, document.forms.psearchform.fromdate, '<?php print $themask?>', 0)" value='DP' /></td>
				<td align="right" width="25%"><strong><?php print $yyOrdTil?>:</strong></td>
				<td align="left" width="25%">&nbsp;<input type="text" size="14" name="todate" value="<?php print $todate?>" /> <input type=button onclick="popUpCalendar(this, document.forms.psearchform.todate, '<?php print $themask?>', -205)" value='DP' /></td>
			  </tr>
			  <tr bgcolor="#EAECEB">
				<td align="right"><strong><?php print $yyOrdId?>:</strong></td>
				<td align="left">&nbsp;<input type="text" size="14" name="ordid" value="<?php print $ordid?>" /></td>
				<td align="right"><strong><?php print $yySeaTxt?>:</strong></td>
				<td align="left">&nbsp;<input type="text" size="24" name="searchtext" value="<?php print $origsearchtext?>" /></td>
			  </tr>
			  <tr bgcolor="#E7EAEF">
				<td align="right"><strong><?php print $yyOrdSta?>:</strong></td>
				<td align="left">&nbsp;<select name="ordstatus[]" size="5" multiple><option value="9999" <?php if(strpos($ordstatus,"9999") !== FALSE) print "selected"?>><?php print $yyAllSta?></option><?php
						$ordstatus="";
						$addcomma = "";
						if(is_array(@$_POST["ordstatus"])){
							foreach($_POST["ordstatus"] as $objValue){
								if(is_array($objValue))$objValue=$objValue[0];
								$ordstatus .= $addcomma . $objValue;
								$addcomma = ",";
							}
						}else
							$ordstatus = trim(@$_POST["ordstatus"]);
						$ordstatusarr = explode(",", $ordstatus);
						for($index=0; $index < $numstatus; $index++){
							print '<option value="' . $allstatus[$index]["statID"] . '"';
							if(is_array($ordstatusarr)){
								foreach($ordstatusarr as $objValue)
									if($objValue==$allstatus[$index]["statID"]) print " selected";
							}
							print ">" . $allstatus[$index]["statPrivate"] . "</option>";
						} ?></select></td>
				<td colspan="2" align="center"><input type="checkbox" name="startwith" value="1" <?php if($usepowersearch) print "checked"?> /> <strong><?php print $yyStaPow?></strong><br /><br />
				  <input type="submit" value="<?php print $yySearch?>" /></td>
			  </tr>
			  <tr><td colspan="4">&nbsp;</td></tr>
			  </form>
			</table>
			</span>
            <table width="100%" border="0" cellspacing="0" cellpadding="2" bgcolor="">
			  <form method="post" action="adminorders.php">
			  <tr>
			    <td align="center"><input type="button" value="<?php print $yyPowSea?>" onclick="displaysearch()" /></td>
                <td align="center"><p><strong><?php print $yyShoFrm?>:</strong> <select name="sd" size="1"><?php
					$gotmatch=FALSE;
					$thetime = time() + ($dateadjust*60*60);
					$dayToday = date("d",$thetime);
					$monthToday = date("m",$thetime);
					$yearToday = date("Y",$thetime);
					for($index=$dayToday; $index > 0; $index--){
						$thedate = mktime(0, 0, 0, $monthToday, $index, $yearToday);
						$thedatestr = date($admindatestr, $thedate);
						print "<option value='" . $thedatestr . "'";
						if($thedate==$sd){
							print " selected";
							$gotmatch=TRUE;
						}
						print ">" . $thedatestr . "</option>\n";
					}
					for($index=1; $index<=12; $index++){
						$thedatestr = date($admindatestr, $thedate = mktime(0,0,0,date("m",$thetime)-$index,1,date("Y",$thetime)));
						if(! $gotmatch && $thedate < $sd){
							print "<option value='" . date($admindatestr, $sd) . "' selected>" . date($admindatestr, $sd) . "</option>";
							$gotmatch=TRUE;
						}
						print "<option value='" . $thedatestr . "'";
						if($thedate==$sd){
							print " selected";
							$gotmatch=TRUE;
						}
						print ">" . $thedatestr . "</option>\n";
					}
					if(!$gotmatch) print "<option value='" . date($admindatestr, $sd) . "' selected>" . date($admindatestr, $sd) . "</option>";
				?></select> <strong><?php print $yyTo?>:</strong> <select name="ed" size="1"><?php
					$gotmatch=FALSE;
					$dayToday = date("d",$thetime);
					$monthToday = date("m",$thetime);
					$yearToday = date("Y",$thetime);
					for($index=$dayToday; $index > 0; $index--){
						$thedate = mktime(0, 0, 0, $monthToday, $index, $yearToday);
						$thedatestr = date($admindatestr, $thedate);
						print "<option value='" . $thedatestr . "'";
						if($thedate==$ed){
							print " selected";
							$gotmatch=TRUE;
						}
						print ">" . $thedatestr . "</option>\n";
					}
					for($index=1; $index<=12; $index++){
						if(! $gotmatch && $thedate < $ed){
							print "<option value='" . date($admindatestr, $ed) . "' selected>" . date($admindatestr, $ed) . "</option>";
							$gotmatch=TRUE;
						}
						$thedatestr = date($admindatestr, $thedate = mktime(0,0,0,date("m",$thetime)-$index,1,date("Y",$thetime)));
						print "<option value='" . $thedatestr . "'";
						if($thedate==$ed){
							print " selected";
							$gotmatch=TRUE;
						}
						print ">" . $thedatestr . "</option>\n";
					}
					if(!$gotmatch) print "<option value='" . date($admindatestr, $sd) . "' selected>" . date($admindatestr, $sd) . "</option>";
				?></select> <input type="submit" value="Go" /></td>
			  </tr>
			  <tr><td colspan="2">&nbsp;</td></tr>
			  </form>
			</table>
			<table width="100%" border="0" cellspacing="1" cellpadding="2" bgcolor="">
			  <tr bgcolor="#030133"> 
                <td align="center"><strong><font color="#E7EAEF"><?php print $yyOrdId?></font></strong></td>
				<td align="center"><strong><font color="#E7EAEF"><?php print $yyName?></font></strong></td>
				<td align="center"><strong><font color="#E7EAEF"><?php print $yyMethod?></font></strong></td>
				<td align="center"><strong><font color="#E7EAEF"><?php print $yyAutCod?></font></strong></td>
				<td align="center"><strong><font color="#E7EAEF"><?php print $yyDate?></font></strong></td>
				<td align="center"><strong><font color="#E7EAEF"><?php print $yyStatus?></font></strong></td>
			  </tr>
			  <form method="post" name="mainform" action="adminorders.php">
			  <?php if(@$_POST["powersearch"]=="1"){ ?>
			  <input type="hidden" name="powersearch" value="1" />
			  <input type="hidden" name="fromdate" value="<?php print trim(@$_POST["fromdate"])?>" />
			  <input type="hidden" name="todate" value="<?php print trim(@$_POST["todate"])?>" />
			  <input type="hidden" name="ordid" value="<?php print trim(str_replace('"','',str_replace("'",'',@$_POST["ordid"])))?>" />
			  <input type="hidden" name="origsearchtext" value="<?php print trim(str_replace('"','&quot;',@$_POST["searchtext"]))?>" />
			  <input type="hidden" name="searchtext" value="<?php print trim(str_replace('"',"&quot;",@$_POST["searchtext"]))?>" />
			  <input type="hidden" name="ordstatus[]" value="<?php print $ordstatus?>" />
			  <input type="hidden" name="startwith" value="<?php if($usepowersearch) print "1"?>" />
			  <?php } ?>
			  <input type="hidden" name="act" value="xxx" />
			  <input type="hidden" name="id" value="xxx" />
			  <input type="hidden" name="authcode" value="xxx" />
			  <input type="hidden" name="ed" value="<?php print date($admindatestr, $ed)?>" />
			  <input type="hidden" name="sd" value="<?php print date($admindatestr, $sd)?>" />
<?php
	if(mysql_num_rows($alldata) > 0){
		$rowcounter=0;
		$ordTot=0;
		while($rs = mysql_fetch_assoc($alldata)){
			if($rs["ordStatus"]>=3) $ordTot += $rs["ordTot"];
			if($rs["ordAuthNumber"]=="" || is_null($rs["ordAuthNumber"])){
				$startfont="<font color='#FF0000'>";
				$endfont="</font>";
			}else{
				$startfont="";
				$endfont="";
			}
			if(@$bgcolor=="#E7EAEF") $bgcolor="#EAECEB"; else $bgcolor="#E7EAEF";
?>
			  <tr bgcolor="<?php print $bgcolor?>"> 
                <td align="center"><a onclick="return(checkprinter(this,event));" href="adminorders.php?id=<?php print $rs["ordID"]?>"><?php print "<strong>" . $startfont . $rs["ordID"] . $endfont . "</strong>"?></a></td>
				<td align="center"><a onclick="return(checkprinter(this,event));" href="adminorders.php?id=<?php print $rs["ordID"]?>"><?php print $startfont . $rs["ordName"] . $endfont?></a></td>
				<td align="center"><?php print $startfont . $rs["payProvName"] . $endfont?></td>
				<td align="center"><?php
					if($rs["ordAuthNumber"]=="" || is_null($rs["ordAuthNumber"])){
						$isauthorized=FALSE;
						print '<input type="button" name="auth" value="' . $yyAuthor . '" onclick="authrec(\'' . $rs["ordID"] . '\')" />';
					}else{
						print '<a href="#" onclick="authrec(\'' . $rs["ordID"] . '\');return(false);">' . $startfont . $rs["ordAuthNumber"] . $endfont . '</a>';
						$isauthorized=TRUE;
					}
				?></td>
				<td align="center"><font size="1"><?php print $startfont . date($admindatestr . "\<\\b\\r\>H:i:s", strtotime($rs["ordDate"])) . $endfont?></font></td>
				<td align="center"><input type="hidden" name="ordid<?php print $rowcounter?>" value="<?php print $rs["ordID"]?>" /><select name="ordstatus<?php print $rowcounter?>" size="1" onChange="checkcontrol(this,event)"><?php
						$gotitem=FALSE;
						for($index=0; $index<$numstatus; $index++){
							if(! $isauthorized && $allstatus[$index]["statID"]>2) break;
							if(! ($rs["ordStatus"] != 2 && $allstatus[$index]["statID"]==2)){
								print '<option value="' . $allstatus[$index]["statID"] . '"';
								if($rs["ordStatus"]==$allstatus[$index]["statID"]){
									print " selected";
									$gotitem=TRUE;
								}
								print ">" . $allstatus[$index]["statPrivate"] . "</option>";
							}
						}
						if(! $gotitem) print '<option value="" selected>' . $yyUndef . '</option>' ?></select></td>
			  </tr>
<?php		$rowcounter++;
			if($rowcounter>=250){
				print "<tr><td colspan='6' align='center'><strong>Limit of " . $rowcounter . " orders reached. Please refine your search.</strong></td></tr>";
				break;
			}
		}
?>
			  <tr>
				<td align="center"><?php print FormatEuroCurrency($ordTot)?></td>
				<td align="center"><?php if($hasdeleted){ ?><input type="submit" value="<?php print $yyPurDel?>" onclick="document.mainform.act.value='purge';" /><?php } ?></td><td colspan="3"><input type="checkbox" name="ctrlmod" value="1" /> <?php print $yyPPSlip?>&nbsp;&nbsp;&nbsp;<?php if(@$orderstatusemail != ""){ ?><input type="checkbox" name="emailstat" value="1" <?php if(@$_POST["emailstat"]=="1" || @$alwaysemailstatus==true) print "checked"?> /> <?php print $yyEStat?><?php } ?></td>
				<td align="center"><input type="hidden" name="maxitems" value="<?php print $rowcounter?>" /><input type="submit" value="<?php print $yyUpdate?>" onclick="document.mainform.act.value='status';" /> <input type="reset" value="<?php print $yyReset?>" /></td>
			  </tr>
			  </form>
			  <form method="post" action="dumporders.php" name="dumpform">
			  <?php if(@$_POST["powersearch"]=="1"){ ?>
			  <input type="hidden" name="powersearch" value="1" />
			  <input type="hidden" name="fromdate" value="<?php print trim(@$_POST["fromdate"])?>" />
			  <input type="hidden" name="todate" value="<?php print trim(@$_POST["todate"])?>" />
			  <input type="hidden" name="ordid" value="<?php print trim(str_replace('"','',str_replace("'",'',@$_POST["ordid"])))?>" />
			  <input type="hidden" name="origsearchtext" value="<?php print trim(str_replace('"','&quot;',@$_POST["searchtext"]))?>" />
			  <input type="hidden" name="searchtext" value="<?php print trim(str_replace('"',"&quot;",@$_POST["searchtext"]))?>" />
			  <input type="hidden" name="ordstatus[]" value="<?php print $ordstatus?>" />
			  <input type="hidden" name="startwith" value="<?php if($usepowersearch) print "1"?>" />
			  <?php } ?>
			  <input type="hidden" name="sd" value="<?php print date($admindatestr, $sd)?>" />
			  <input type="hidden" name="ed" value="<?php print date($admindatestr, $ed)?>" />
			  <input type="hidden" name="details" value="false" />
			  <tr> 
                <td colspan="3" align="center"><input type="submit" value="<?php print $yyDmpOrd?>" onclick="document.dumpform.details.value='false';" /></td>
				<td colspan="3" align="center"><input type="submit" value="<?php print $yyDmpDet?>" onclick="document.dumpform.details.value='true';" /></td>
			  </tr>
			  </form>
<?php
	}else{
?>
			  <tr> 
                <td width="100%" colspan="6" align="center">
					<p><?php
					if(@$_POST["powersearch"]=="1")
						print $yyNoMat1;
					elseif($sd==$ed)
						print $yyNoMat2 . " " . date($admindatestr, $sd) . ".";
					else
						print $yyNoMat3 . " " . date($admindatestr, $sd) . " and " . date($admindatestr, $ed) . ".";
					?></p>
				</td>
			  </tr>
			  <?php if($hasdeleted){ ?>
			  <tr> 
				<td colspan="6"><input type="submit" value="<?php print $yyPurDel?>" onclick="document.mainform.act.value='purge';" /></td>
			  </tr>
			  <?php } ?>
			  </form>
<?php
	} ?>
			  <tr> 
                <td width="100%" colspan="6" align="center">
				  <p><br />
					<a href="adminorders.php?sd=<?php print date($admindatestr,mktime(0,0,0,date("m",$sd)-1,date("d",$sd),date("Y",$sd)))?>&ed=<?php print date($admindatestr,mktime(0,0,0,date("m",$ed)-1,date("d",$ed),date("Y",$ed)))?>"><strong>- <?php print $yyMonth?></strong></a> | 
					<a href="adminorders.php?sd=<?php print date($admindatestr,mktime(0,0,0,date("m",$sd),date("d",$sd)-7,date("Y",$sd)))?>&ed=<?php print date($admindatestr,mktime(0,0,0,date("m",$ed),date("d",$ed)-7,date("Y",$ed)))?>"><strong>- <?php print $yyWeek?></strong></a> | 
					<a href="adminorders.php?sd=<?php print date($admindatestr,mktime(0,0,0,date("m",$sd),date("d",$sd)-1,date("Y",$sd)))?>&ed=<?php print date($admindatestr,mktime(0,0,0,date("m",$ed),date("d",$ed)-1,date("Y",$ed)))?>"><strong>- <?php print $yyDay?></strong></a> | 
					<a href="adminorders.php?sd=<?php print date($admindatestr,time())?>&ed=<?php print date($admindatestr,time())?>"><strong><?php print $yyToday?></strong></a> | 
					<a href="adminorders.php?sd=<?php print date($admindatestr,mktime(0,0,0,date("m",$sd),date("d",$sd)+1,date("Y",$sd)))?>&ed=<?php print date($admindatestr,mktime(0,0,0,date("m",$ed),date("d",$ed)+1,date("Y",$ed)))?>"><strong><?php print $yyDay?> +</strong></a> | 
					<a href="adminorders.php?sd=<?php print date($admindatestr,mktime(0,0,0,date("m",$sd),date("d",$sd)+7,date("Y",$sd)))?>&ed=<?php print date($admindatestr,mktime(0,0,0,date("m",$ed),date("d",$ed)+7,date("Y",$ed)))?>"><strong><?php print $yyWeek?> +</strong></a> | 
					<a href="adminorders.php?sd=<?php print date($admindatestr,mktime(0,0,0,date("m",$sd)+1,date("d",$sd),date("Y",$sd)))?>&ed=<?php print date($admindatestr,mktime(0,0,0,date("m",$ed),date("d",$ed)+1,date("Y",$ed)))?>"><strong><?php print $yyMonth?> +</strong></a>
				  </p>
				</td>
			  </tr>
			</table>
		  </td>
		</tr>
      </table>
<?php
}
}
?>