<?php
//This code is copyright (c) Internet Business Solutions SL, all rights reserved.
//The contents of this file are protect under law as the intellectual property of Internet
//Business Solutions SL. Any use, reproduction, disclosure or copying of any kind 
//without the express and written permission of Internet Business Solutions SL is forbidden.
//Author: Vince Reid, vince@virtualred.net
$cartEmpty=FALSE;
$isInStock=TRUE;
$tempOutOfStock=FALSE;
if(@$dateadjust=="") $dateadjust=0;
$errormsg = "";
$demomode = FALSE;
$WSP = "";
$OWSP = "";
$nodiscounts=FALSE;
if(trim(@$_POST["sessionid"]) != "") $thesessionid=trim($_POST["sessionid"]); else $thesessionid=session_id();
if(@$_SESSION["clientUser"] != ""){
	if(($_SESSION["clientActions"] & 8) == 8){
		$WSP = "pWholesalePrice AS ";
		if(@$wholesaleoptionpricediff==TRUE) $OWSP = 'optWholesalePriceDiff AS ';
		if(@$nowholesalediscounts==TRUE) $nodiscounts=TRUE;
	}
	if(($_SESSION["clientActions"] & 16) == 16){
		$WSP = $_SESSION["clientPercentDiscount"] . "*pPrice AS ";
		if(@$wholesaleoptionpricediff==TRUE) $OWSP = $_SESSION["clientPercentDiscount"] . '*optPriceDiff AS ';
		if(@$nowholesalediscounts==TRUE) $nodiscounts=TRUE;
	}
}
$theid = mysql_escape_string(trim(@$_POST["id"]));
$alreadygotadmin = getadminsettings();
if(trim(@$_POST["payprovider"]) != "") eval('$handling += @$handlingcharge' . trim($_POST["payprovider"]) . ';');
if(@$_SESSION["couponapply"] != ""){
	mysql_query("UPDATE coupons SET cpnNumAvail=cpnNumAvail+1 WHERE cpnID IN (0" . $_SESSION["couponapply"] . ")") or print(mysql_error());
	$_SESSION["couponapply"]="";
}
function checkuserblock($thepayprov){
	global $blockmultipurchase;
	$multipurchaseblocked=FALSE;
	if($thepayprov != "7" && $thepayprov != "13"){
		$theip = @$_SERVER["REMOTE_ADDR"];
		if($theip == "") $theip = "none";
		if(@$blockmultipurchase != ""){
			mysql_query("DELETE FROM multibuyblock WHERE lastaccess<'" . date("Y-m-d H:i:s", time()-(60*60*24)) . "'") or print(mysql_error());
			$sSQL = "SELECT ssdenyid,sstimesaccess FROM multibuyblock WHERE ssdenyip = '" . trim(mysql_escape_string($theip)) . "'";
			$result = mysql_query($sSQL) or print(mysql_error());
			if($rs = mysql_fetch_array($result)){
				mysql_query("UPDATE multibuyblock SET sstimesaccess=sstimesaccess+1,lastaccess='" . date("Y-m-d H:i:s", time()) . "' WHERE ssdenyid=" . $rs["ssdenyid"]) or print(mysql_error());
				if($rs["sstimesaccess"] >= $blockmultipurchase) $multipurchaseblocked=TRUE;
			}else{
				mysql_query("INSERT INTO multibuyblock (ssdenyip,lastaccess) VALUES ('" . trim(mysql_escape_string($theip)) . "','" . date("Y-m-d H:i:s", time()) . "')") or print(mysql_error());
			}
			mysql_free_result($result);
		}
		if($theip == "none")
			$sSQL = "SELECT TOP 1 dcid FROM ipblocking";
		else
			$sSQL = "SELECT dcid FROM ipblocking WHERE (dcip1=" . ip2long($theip) . " AND dcip2=0) OR (dcip1 <= " . ip2long($theip) . " AND " . ip2long($theip) . " <= dcip2 AND dcip2 <> 0)";
		$result = mysql_query($sSQL) or print(mysql_error());
		if(mysql_num_rows($result) > 0)
			$multipurchaseblocked = TRUE;
	}
	return($multipurchaseblocked);
}
function checkpricebreaks($cpbpid,$origprice){
	global $WSP;
	$newprice="";
	$sSQL = "SELECT SUM(cartQuantity) AS totquant FROM cart WHERE cartCompleted=0 AND cartSessionID='" . session_id() . "' AND cartProdID='".mysql_escape_string($cpbpid)."'";
	$result = mysql_query($sSQL) or print(mysql_error());
	$rs=mysql_fetch_assoc($result);
	if(is_null($rs["totquant"])) $thetotquant=0; else $thetotquant = $rs["totquant"];
	$sSQL="SELECT ".$WSP."pPrice FROM pricebreaks WHERE ".$thetotquant.">=pbQuantity AND pbProdID='".mysql_escape_string($cpbpid)."' ORDER BY " . ($WSP==""?"pPrice":str_replace(' AS ','',$WSP));
	$result = mysql_query($sSQL) or print(mysql_error());
	if($rs=mysql_fetch_assoc($result))
		$thepricebreak = $rs["pPrice"];
	else
		$thepricebreak = $origprice;
	$sSQL = "UPDATE cart SET cartProdPrice=".$thepricebreak." WHERE cartCompleted=0 AND cartSessionID='" . session_id() . "' AND cartProdID='".mysql_escape_string($cpbpid)."'";
	mysql_query($sSQL) or print(mysql_error());
}
function multShipWeight($theweight, $themul){
	return(($theweight*$themul)/100.0);
}
function subtaxesfordiscounts($theExemptions, $discAmount){
	if(($theExemptions & 1)==1) $statetaxfree -= $discAmount;
	if(($theExemptions & 2)==2) $countrytaxfree -= $discAmount;
	if(($theExemptions & 4)==4) $shipfreegoods -= $discAmount;
}
function addadiscount($resset, $groupdiscount, $dscamount, $subcpns, $cdcpncode, $statetaxhandback, $countrytaxhandback, $theexemptions, $thetax){
	global $totaldiscounts, $cpnmessage, $statetaxfree, $countrytaxfree, $gotcpncode, $perproducttaxrate, $countryTax;
	$totaldiscounts += $dscamount;
	if($groupdiscount){
		$statetaxfree -= ($dscamount * $statetaxhandback);
		$countrytaxfree -= ($dscamount * $countrytaxhandback);
	}else{
		subtaxesfordiscounts($theexemptions, $dscamount);
		if(@$perproducttaxrate) $countryTax -= (($dscamount * $thetax) / 100.0);
	}
	if(stristr($cpnmessage,"<br />" . $resset[getlangid("cpnName",1024)] . "<br />") == FALSE) $cpnmessage .= $resset[getlangid("cpnName",1024)] . "<br />";
	if($subcpns){
		$theres = mysql_query("SELECT cpnID FROM coupons WHERE cpnNumAvail>0 AND cpnNumAvail<30000000 AND cpnID=" . $resset["cpnID"]) or print(mysql_error());
		if($theresset = mysql_fetch_assoc($theres)) @$_SESSION["couponapply"] .= "," . $resset["cpnID"];
		mysql_query("UPDATE coupons SET cpnNumAvail=cpnNumAvail-1 WHERE cpnNumAvail>0 AND cpnNumAvail<30000000 AND cpnID=" . $resset["cpnID"]) or print(mysql_error());
	}
	if($cdcpncode!="" && strtolower(trim($resset["cpnNumber"]))==strtolower($cdcpncode)) $gotcpncode=TRUE;
}
function timesapply($taquant,$tathresh,$tamaxquant,$tamaxthresh,$taquantrepeat,$tathreshrepeat){
	if($taquantrepeat==0 && $tathreshrepeat==0)
		$tatimesapply = 1.0;
	elseif($tamaxquant==0)
		$tatimesapply = (int)(($tathresh - $tamaxthresh) / $tathreshrepeat)+1;
	elseif($tamaxthresh==0)
		$tatimesapply = (int)(($taquant - $tamaxquant) / $taquantrepeat)+1;
	else{
		$ta1 = (int)(($taquant - $tamaxquant) / $taquantrepeat)+1;
		$ta2 = (int)(($tathresh - $tamaxthresh) / $tathreshrepeat)+1;
		if($ta2 < $ta1) $tatimesapply = $ta2; else $tatimesapply = $ta1;
	}
	return($tatimesapply);
}
function calculatediscounts($cdgndtot, $subcpns, $cdcpncode){
	global $totaldiscounts, $cpnmessage, $statetaxfree, $countrytaxfree, $nodiscounts, $WSP, $cpncode, $gotcpncode, $thesessionid, $countryTaxRate, $countryTax;
	$totaldiscounts = 0;
	$cpnmessage = "<br />";
	$cdtotquant=0;
	if($cdgndtot==0){
		$statetaxhandback = 0.0;
		$countrytaxhandback = 0.0;
	}else{
		$statetaxhandback = 1.0 - (($cdgndtot - $statetaxfree) / $cdgndtot);
		$countrytaxhandback = 1.0 - (($cdgndtot - $countrytaxfree) / $cdgndtot);
	}
	if(! $nodiscounts){
		$sSQL = "SELECT cartProdID,SUM(cartProdPrice*cartQuantity) AS thePrice,SUM(cartQuantity) AS sumQuant,pSection,COUNT(cartProdID),pExemptions,pTax FROM products INNER JOIN cart ON cart.cartProdID=products.pID WHERE cartCompleted=0 AND cartSessionID='" . $thesessionid . "' GROUP BY cartProdID,pSection,pExemptions,pTax";
		$cdresult = mysql_query($sSQL) or print(mysql_error());
		$cdadindex=0;
		while($cdrs = mysql_fetch_assoc($cdresult)){
			$cdalldata[$cdadindex++]=$cdrs;
		}
		for($index=0; $index<$cdadindex; $index++){
			$cdrs = $cdalldata[$index];
			$sSQL = "SELECT SUM(coPriceDiff*cartQuantity) AS totOpts FROM cart LEFT OUTER JOIN cartoptions ON cart.cartID=cartoptions.coCartID WHERE cartCompleted=0 AND cartSessionID='" . $thesessionid . "' AND cartProdID='" . $cdrs["cartProdID"] . "'";
			$cdresult2 = mysql_query($sSQL) or print(mysql_error());
			$cdrs2 = mysql_fetch_assoc($cdresult2);
			if(! is_null($cdrs2["totOpts"])) $cdrs["thePrice"] += $cdrs2["totOpts"];
			$cdtotquant += $cdrs["sumQuant"];
			$topcpnids = $cdrs["pSection"];
			$thetopts = $cdrs["pSection"];
			if(is_null($cdrs["pTax"])) $cdrs["pTax"] = $countryTaxRate;
			for($cpnindex=0; $cpnindex<= 10; $cpnindex++){
				if($thetopts==0)
					break;
				else{
					$sSQL = "SELECT topSection FROM sections WHERE sectionID=" . $thetopts;
					$result2 = mysql_query($sSQL) or print(mysql_error());
					if($rs2 = mysql_fetch_assoc($result2)){
						$thetopts = $rs2["topSection"];
						$topcpnids .= "," . $thetopts;
					}else
						break;
				}
			}
			$sSQL = "SELECT cpnID,cpnDiscount,cpnType,cpnNumber,".getlangid("cpnName",1024).",cpnThreshold,cpnQuantity,cpnSitewide,cpnThresholdRepeat,cpnQuantityRepeat FROM coupons LEFT OUTER JOIN cpnassign ON coupons.cpnID=cpnassign.cpaCpnID WHERE cpnNumAvail>0 AND cpnEndDate>='" . date("Y-m-d",time()) ."' AND (cpnIsCoupon=0";
			if($cdcpncode != "") $sSQL .= " OR (cpnIsCoupon=1 AND cpnNumber='" . $cdcpncode . "')";
			$sSQL .= ") AND cpnThreshold<=" . $cdrs["thePrice"] . " AND (cpnThresholdMax>" . $cdrs["thePrice"] . " OR cpnThresholdMax=0) AND cpnQuantity<=" . $cdrs["sumQuant"] . " AND (cpnQuantityMax>" . $cdrs["sumQuant"] . " OR cpnQuantityMax=0) AND (cpnSitewide=0 OR cpnSitewide=2) AND ";
			$sSQL .= "(cpnSitewide=2 OR (cpaType=2 AND cpaAssignment='" . $cdrs["cartProdID"] . "') ";
			$sSQL .= "OR (cpaType=1 AND cpaAssignment IN ('" . str_replace(",","','",$topcpnids) . "')))";
			$result2 = mysql_query($sSQL) or print(mysql_error());
			while($rs2 = mysql_fetch_assoc($result2)){
				if($rs2["cpnType"]==1){ // Flat Rate Discount
					$thedisc = (double)$rs2["cpnDiscount"] * timesapply($cdrs["sumQuant"], $cdrs["thePrice"], $rs2["cpnQuantity"], $rs2["cpnThreshold"], $rs2["cpnQuantityRepeat"], $rs2["cpnThresholdRepeat"]);
					if($cdrs["thePrice"] < $thedisc) $thedisc = $cdrs["thePrice"];
					addadiscount($rs2, FALSE, $thedisc, $subcpns, $cdcpncode, $statetaxhandback, $countrytaxhandback, $cdrs["pExemptions"], $cdrs["pTax"]);
				}elseif($rs2["cpnType"]==2){ // Percentage Discount
					addadiscount($rs2, FALSE, (((double)$rs2["cpnDiscount"] * (double)$cdrs["thePrice"]) / 100.0), $subcpns, $cdcpncode, $statetaxhandback, $countrytaxhandback, $cdrs["pExemptions"], $cdrs["pTax"]);
				}
			}
		}
		$sSQL = "SELECT cpnID,cpnDiscount,cpnType,cpnNumber,".getlangid("cpnName",1024).",cpnSitewide,cpnThreshold,cpnThresholdMax,cpnQuantity,cpnQuantityMax,cpnThresholdRepeat,cpnQuantityRepeat FROM coupons WHERE cpnNumAvail>0 AND cpnEndDate>='" . date("Y-m-d",time()) ."' AND (cpnIsCoupon=0";
		if($cdcpncode != "") $sSQL .= " OR (cpnIsCoupon=1 AND cpnNumber='" . $cdcpncode . "')";
		$sSQL .= ") AND cpnThreshold<=" . $cdgndtot . " AND cpnQuantity<=" . $cdtotquant . " AND (cpnSitewide=1 OR cpnSitewide=3) AND (cpnType=1 OR cpnType=2)";
		$result2 = mysql_query($sSQL) or print(mysql_error());
		while($rs2 = mysql_fetch_assoc($result2)){
			$totquant = 0;
			$totprice = 0;
			if($rs2["cpnSitewide"]==3){
				$sSQL = "SELECT cpaAssignment FROM cpnassign WHERE cpaType=1 AND cpacpnID=" . $rs2["cpnID"];
				$result3 = mysql_query($sSQL) or print(mysql_error());
				$secids = "";
				$addcomma = "";
				while($rs3 = mysql_fetch_assoc($result3)){
					$secids .= $addcomma . $rs3["cpaAssignment"];
					$addcomma = ",";
				}
				if($secids != ""){
					$secids = getsectionids($secids, FALSE);
					$sSQL = "SELECT SUM(cartProdPrice*cartQuantity) AS totPrice,SUM(cartQuantity) AS totQuant FROM products INNER JOIN cart ON cart.cartProdID=products.pID WHERE cartCompleted=0 AND cartSessionID='" . $thesessionid . "' AND products.pSection IN (" . $secids . ")";
					$result3 = mysql_query($sSQL) or print(mysql_error());
					$rs3 = mysql_fetch_assoc($result3);
					if(is_null($rs3["totPrice"])) $totprice = 0; else $totprice = $rs3["totPrice"];
					if(is_null($rs3["totQuant"])) $totquant=0; else $totquant = $rs3["totQuant"];
					$sSQL = "SELECT SUM(coPriceDiff*cartQuantity) AS optPrDiff FROM products INNER JOIN cart ON cart.cartProdID=products.pID LEFT OUTER JOIN cartoptions ON cart.cartID=cartoptions.coCartID WHERE cartCompleted=0 AND cartSessionID='" . $thesessionid . "' AND products.pSection IN (" . $secids . ")";
					$result3 = mysql_query($sSQL) or print(mysql_error());
					$rs3 = mysql_fetch_assoc($result3);
					if(! is_null($rs3["optPrDiff"])) $totprice = $totprice+$rs3["optPrDiff"];
				}
			}else{ // cpnSitewide==1
				$totquant = $cdtotquant;
				$totprice = $cdgndtot;
			}
			if($totquant > 0 && $rs2["cpnThreshold"] <= $totprice && ($rs2["cpnThresholdMax"] > $totprice || $rs2["cpnThresholdMax"]==0) && $rs2["cpnQuantity"] <= $totquant && ($rs2["cpnQuantityMax"] > $totquant || $rs2["cpnQuantityMax"]==0)){
				if($rs2["cpnType"]==1){ // Flat Rate Discount
					$thedisc = (double)$rs2["cpnDiscount"] * timesapply($totquant, $totprice, $rs2["cpnQuantity"], $rs2["cpnThreshold"], $rs2["cpnQuantityRepeat"], $rs2["cpnThresholdRepeat"]);
					if($totprice < $thedisc) $thedisc = $totprice;
				}elseif($rs2["cpnType"]==2){ // Percentage Discount
					$thedisc = ((double)$rs2["cpnDiscount"] * (double)$totprice) / 100.0;
				}
				addadiscount($rs2, TRUE, $thedisc, $subcpns, $cdcpncode, $statetaxhandback, $countrytaxhandback, 3, 0);
				if(@$perproducttaxrate && $cdgndtot > 0){
					for($index=0; $index<$cdadindex; $index++){
						$cdrs = $cdalldata[$index];
						if($rs2["cpnType"]==1) // Flat Rate Discount
							$applicdisc = $thedisc / ($cdtotquant / $cdrs["sumQuant"]);
						elseif($rs2["cpnType"]==2) // Percentage Discount
							$applicdisc = $thedisc / ($cdgndtot / $cdrs["thePrice"]);
						if(($cdrs["pExemptions"] & 2) != 2) $countryTax -= (($applicdisc * $cdrs["pTax"]) / 100.0);
					}
				}
			}
		}
	}
	if($statetaxfree < 0) $statetaxfree = 0;
	if($countrytaxfree < 0) $countrytaxfree = 0;
	$totaldiscounts = round($totaldiscounts, 2);
}
function calculateshippingdiscounts($subcpns){
	global $freeshippingapplied, $nodiscounts, $totalgoods, $runTotQuant, $cpncode, $freeshipapplies, $isstandardship, $cpnmessage, $shipping, $freeshipamnt, $gotcpncode;
	if(! $nodiscounts){
		$sSQL = "SELECT cpnID,".getlangid("cpnName",1024).",cpnNumber,cpnDiscount,cpnThreshold,cpnCntry FROM coupons WHERE cpnType=0 AND cpnSitewide=1 AND cpnNumAvail>0 AND cpnThreshold<=".$totalgoods." AND (cpnThresholdMax>".$totalgoods." OR cpnThresholdMax=0) AND cpnQuantity<=".$runTotQuant." AND (cpnQuantityMax>".$runTotQuant." OR cpnQuantityMax=0) AND cpnEndDate>='" . date("Y-m-d",time()) ."' AND (cpnIsCoupon=0 OR (cpnIsCoupon=1 AND cpnNumber='".$cpncode."'))";
		$result = mysql_query($sSQL) or print(mysql_error());
		while($rs=mysql_fetch_assoc($result)){
			if($freeshipapplies || (int)$rs["cpnCntry"]==0){
				if($cpncode!="" && strtolower(trim($rs["cpnNumber"]))==strtolower($cpncode)) $gotcpncode=TRUE;
				if($isstandardship){
					if(stristr($cpnmessage,"<br />" . $rs[getlangid("cpnName",1024)] . "<br />") == FALSE) $cpnmessage .= $rs[getlangid("cpnName",1024)] . "<br />";
					$freeshipamnt = $shipping;
					if($subcpns){
						$theres = mysql_query("SELECT cpnID FROM coupons WHERE cpnNumAvail>0 AND cpnNumAvail<30000000 AND cpnID=" . $rs["cpnID"]) or print(mysql_error());
						if($theresset = mysql_fetch_assoc($theres)) @$_SESSION["couponapply"] .= "," . $rs["cpnID"];
						mysql_query("UPDATE coupons SET cpnNumAvail=cpnNumAvail-1 WHERE cpnNumAvail>0 AND cpnNumAvail<30000000 AND cpnID=" . $rs["cpnID"]) or print(mysql_error());
					}
				}
				$freeshippingapplied = true;
			}
		}
		mysql_free_result($result);
	}
}
if($stockManage != 0){
	$sSQL = "SELECT cartOrderID,cartID FROM cart WHERE (cartCompleted=0 AND cartOrderID=0 AND cartDateAdded<'" . date("Y-m-d H:i:s", time()+(($dateadjust-$stockManage)*60*60)) . "')";
	if($delAfter != 0)
		$sSQL .= " OR (cartCompleted=0 AND cartDateAdded<'" . date("Y-m-d H:i:s", time()-($delAfter*60*60*24)) . "')";
	$result = mysql_query($sSQL) or print(mysql_error());
	if(mysql_num_rows($result)>0){
		$addcomma = "";
		$delstr="";
		$delcart="";
		while($rs = mysql_fetch_assoc($result)){
			$delcart .= $addcomma . $rs["cartOrderID"];
			$delstr .= $addcomma . $rs["cartID"];
			$addcomma = ",";
		}
		if($delAfter != 0) mysql_query("DELETE FROM orders WHERE ordID IN (" . $delcart . ")") or print(mysql_error());
		mysql_query("DELETE FROM cart WHERE cartID IN (" . $delstr . ")") or print(mysql_error());
		mysql_query("DELETE FROM cartoptions WHERE coCartID IN (" . $delstr . ")") or print(mysql_error());
	}
	mysql_free_result($result);
}
if(@$_POST["mode"]=="update"){
	foreach(@$_POST as $objItem => $objValue){
		if(substr($objItem,0,5)=="quant"){
			if((int)$objValue==0){
				$sSQL="DELETE FROM cartoptions WHERE coCartID=" . substr($objItem, 5);
				mysql_query($sSQL) or print(mysql_error());
				$sSQL="DELETE FROM cart WHERE cartID=" . substr($objItem, 5);
				mysql_query($sSQL) or print(mysql_error());
			}else{
				$totQuant = 0;
				$pPrice = 0;
				$sSQL="SELECT cartQuantity,pInStock,pID,pSell,".$WSP."pPrice FROM cart LEFT JOIN products ON cart.cartProdId=products.pID WHERE cartID=" . substr($objItem, 5);
				$result = mysql_query($sSQL) or print(mysql_error());
				if($rs = mysql_fetch_array($result)){
					$pID = trim($rs["pID"]);
					$pInStock = (int)$rs["pInStock"];
					$pSell = (int)$rs["pSell"];
					$pPrice = $rs["pPrice"];
					$cartQuantity = (int)$rs["cartQuantity"];
					mysql_free_result($result);
					$sSQL = "SELECT SUM(cartQuantity) AS cartQuant FROM cart WHERE cartCompleted=0 AND cartProdID='" . $pID . "'";
					$result = mysql_query($sSQL) or print(mysql_error());
					if($rs = mysql_fetch_array($result))
						$totQuant = (int)$rs["cartQuant"];
				}
				mysql_free_result($result);
				if($stockManage != 0){
					$quantavailable = abs((int)$objValue);
					if(($pSell & 2) == 2){
						$hasalloptions=true;
						$sSQL = "SELECT coID,optStock,cartQuantity,coOptID FROM cart INNER JOIN cartoptions ON cart.cartID=cartoptions.coCartID INNER JOIN options ON cartoptions.coOptID=options.optID INNER JOIN optiongroup ON options.optGroup=optiongroup.optGrpID WHERE (optType=2 OR optType=-2) AND cartID=" . substr($objItem, 5);
						$result = mysql_query($sSQL) or print(mysql_error());
						if(mysql_num_rows($result)>0){
							while($rs = mysql_fetch_assoc($result)){
								$pInStock = (int)$rs["optStock"];
								$totQuant = 0;
								$cartQuantity = (int)$rs["cartQuantity"];
								$sSQL = "SELECT SUM(cartQuantity) AS cartQuant FROM cart INNER JOIN cartoptions ON cart.cartID=cartoptions.coCartID WHERE cartCompleted=0 AND coOptID=" . $rs["coOptID"];
								$result2 = mysql_query($sSQL) or print(mysql_error());
								if($rs2 = mysql_fetch_assoc($result2))
									if(! is_null($rs2["cartQuant"])) $totQuant = (int)$rs2["cartQuant"];
								mysql_free_result($result2);
								if((int)($pInStock - $totQuant + $cartQuantity) < $quantavailable) $quantavailable = ($pInStock - $totQuant + $cartQuantity);
								if(($pInStock - $totQuant + $cartQuantity - abs((int)$objValue)) < 0) $hasalloptions=false;
							}
							$sSQL="UPDATE cart SET cartQuantity=" . $quantavailable . " WHERE cartID=" . substr($objItem, 5);
							mysql_query($sSQL) or print(mysql_error());
							if(! $hasalloptions) $isInStock = false;
						}
						mysql_free_result($result);
					}else{
						if(($pInStock - $totQuant + $cartQuantity - $quantavailable) < 0){
							$quantavailable = ($pInStock - $totQuant + $cartQuantity);
							if($quantavailable < 0) $quantavailable=0;
							$isInStock = FALSE;
						}
						$sSQL="UPDATE cart SET cartQuantity=" . $quantavailable . " WHERE cartID=" . substr($objItem, 5);
						mysql_query($sSQL) or print(mysql_error());
					}
				}else{
					$sSQL="UPDATE cart SET cartQuantity=" . abs((int)$objValue) . " WHERE cartID=" . substr($objItem, 5);
					mysql_query($sSQL) or print(mysql_error());
				}
				checkpricebreaks($pID,$pPrice);
			}
		}elseif(substr($objItem,0,5)=="delet"){
			$sSQL="DELETE FROM cart WHERE cartID=" . substr($objItem, 5);
			mysql_query($sSQL) or print(mysql_error());
			$sSQL="DELETE FROM cartoptions WHERE coCartID=" . substr($objItem, 5);
			mysql_query($sSQL) or print(mysql_error());
		}
	}
}
if(@$_POST["mode"]=="add"){
	$bExists = FALSE;
	if(trim(@$_POST["frompage"])!="") $_SESSION["frompage"]=$_POST["frompage"]; else $_SESSION["frompage"]="";
	if(@$_POST["quant"]=="" || ! is_numeric(@$_POST["quant"]))
		$quantity=1;
	else
		$quantity=abs((int)@$_POST["quant"]);
	$sSQL = "SELECT cartID FROM cart WHERE cartCompleted=0 AND cartSessionID='" . session_id() . "' AND cartProdID='" . $theid . "'";
	$result = mysql_query($sSQL) or print(mysql_error());
	while($rs = mysql_fetch_assoc($result)){
		$bExists = TRUE;
		$cartID = $rs["cartID"];
		foreach(@$_POST as $objItem => $objValue){ // We have the product. Check we have all the same options
			if(substr($objItem,0,4)=="optn"){
				if(@$_POST["v" . $objItem] != ""){
					$sSQL="SELECT coID FROM cartoptions WHERE coCartID=" . $cartID . " AND coOptID='" . mysql_escape_string($objValue) . "' AND coCartOption='" . mysql_escape_string(unstripslashes(trim(@$_POST["v" . $objItem]))) . "'";
					$result2 = mysql_query($sSQL) or print(mysql_error());
					if(mysql_num_rows($result2)==0) $bExists=FALSE;
					mysql_free_result($result2);
				}else{
					$sSQL="SELECT coID FROM cartoptions WHERE coCartID=" . $cartID . " AND coOptID='" . mysql_escape_string($objValue) . "'";
					$result2 = mysql_query($sSQL) or print(mysql_error());
					if(mysql_num_rows($result2)==0) $bExists=FALSE;
					mysql_free_result($result2);
				}
			}
			if(! $bExists) break;
		}
		if($bExists) break;
	}
	mysql_free_result($result);
	$sSQL = "SELECT ".getlangid("pName",1).",".$WSP."pPrice,pInStock,pWeight,pSell FROM products WHERE pID='" . $theid . "'";
	$result = mysql_query($sSQL) or print(mysql_error());
	$rsStock = mysql_fetch_array($result);
	mysql_free_result($result);
	if($stockManage != 0){
		if(($rsStock["pSell"] & 2)==2){
			$isInStock = true;
			foreach(@$_POST as $objItem => $objValue){
				if(substr($objItem,0,4)=="optn"){
					$sSQL="SELECT optStock FROM options INNER JOIN optiongroup ON options.optGroup=optiongroup.optGrpID WHERE (optType=2 OR optType=-2) AND optID='" . mysql_escape_string($objValue) . "'";
					$result = mysql_query($sSQL) or print(mysql_error());
					if($rs = mysql_fetch_array($result))
						$isInStock = ($isInStock && ($rs["optStock"] >= $quantity));
					mysql_free_result($result);
				}
			}
			if($isInStock){ // Check cart
				$bestDate = time()+(60*60*24*62);
				foreach(@$_POST as $objItem => $objValue){
					$totQuant = 0;
					$stockQuant = 0;
					if(substr($objItem,0,4)=="optn"){
						$sSQL = "SELECT cartQuantity,cartDateAdded,cartOrderID,optStock FROM cart INNER JOIN cartoptions ON cart.cartID=cartoptions.coCartID INNER JOIN options ON cartoptions.coOptID=options.optID INNER JOIN optiongroup ON options.optGroup=optiongroup.optGrpID WHERE (optType=2 OR optType=-2) AND cartCompleted=0 AND coOptID='" . mysql_escape_string($objValue) . "'";
						$result = mysql_query($sSQL) or print(mysql_error());
						if(mysql_num_rows($result)>0){
							$rs = mysql_fetch_array($result);
							$stockQuant = $rs["optStock"];
							do{
								$totQuant += $rs["cartQuantity"];
								if((int)$rs["cartOrderID"]==0 && strtotime($rs["cartDateAdded"]) < $bestDate) $bestDate = strtotime($rs["cartDateAdded"]);
							}while($rs = mysql_fetch_array($result));
							if(($totQuant+$quantity) > $stockQuant){
								$isInStock=false;
								$tempOutOfStock=true;
							}
						}
						mysql_free_result($result);
					}
				}
			}
		}else{
			if($isInStock = (($rsStock["pInStock"]-$quantity) >= 0)){ // Check cart
				$totQuant = 0;
				$bestDate = time()+(60*60*24*62);
				$sSQL = "SELECT cartQuantity,cartDateAdded,cartOrderID FROM cart WHERE cartCompleted=0 AND cartProdID='" . $theid . "'";
				$result = mysql_query($sSQL) or print(mysql_error());
				while($rs = mysql_fetch_array($result)){
					$totQuant += $rs["cartQuantity"];
					if((int)$rs["cartOrderID"]==0 && strtotime($rs["cartDateAdded"]) < $bestDate) $bestDate = strtotime($rs["cartDateAdded"]);
				}
				mysql_free_result($result);
				if(($rsStock["pInStock"]-($totQuant+$quantity)) < 0){
					$isInStock = FALSE;
					$tempOutOfStock = TRUE;
				}
			}
		}
	}
	if($isInStock){
		if($bExists){
			$sSQL = "UPDATE cart SET cartQuantity=cartQuantity+" . $quantity . " WHERE cartID=" . $cartID;
			mysql_query($sSQL) or print(mysql_error());
		}else{
			$sSQL = "INSERT INTO cart (cartSessionID,cartProdID,cartQuantity,cartCompleted,cartProdName,cartProdPrice,cartOrderID,cartDateAdded) VALUES (";
			$sSQL .= "'" . session_id() . "',";
			$sSQL .= "'" . $theid . "',";
			$sSQL .= $quantity . ",";
			$sSQL .= "0,";
			$sSQL .= "'" . mysql_escape_string($rsStock[getlangid("pName",1)]) . "',";
			$sSQL .= "'" . $rsStock["pPrice"] . "',";
			$sSQL .= "0,";
			$sSQL .= "'" . date("Y-m-d H:i:s", time() + ($dateadjust*60*60)) . "')";
			mysql_query($sSQL) or print(mysql_error());
			$cartID = mysql_insert_id();
			foreach(@$_POST as $objItem => $objValue){
				if(substr($objItem,0,4)=="optn"){
					if(trim(@$_POST["v" . $objItem])==""){
						$sSQL="SELECT optID,".getlangid("optGrpName",16).",".getlangid("optName",32)."," . $OWSP . "optPriceDiff,optWeightDiff,optType,optFlags FROM options LEFT JOIN optiongroup ON options.optGroup=optiongroup.optGrpID WHERE optID='" . mysql_escape_string($objValue) . "'";
						$result = mysql_query($sSQL) or print(mysql_error());
						if($rs = mysql_fetch_array($result)){
							if(abs($rs["optType"]) != 3){
								$sSQL = "INSERT INTO cartoptions (coCartID,coOptID,coOptGroup,coCartOption,coPriceDiff,coWeightDiff) VALUES (" . $cartID . "," . $rs["optID"] . ",'" . mysql_escape_string($rs[getlangid("optGrpName",16)]) . "','" . mysql_escape_string($rs[getlangid("optName",32)]) . "',";
								if(($rs["optFlags"]&1)==0) $sSQL .= $rs["optPriceDiff"] . ","; else $sSQL .= round(($rs["optPriceDiff"] * $rsStock["pPrice"])/100.0, 2) . ",";
								if(($rs["optFlags"]&2)==0) $sSQL .= $rs["optWeightDiff"] . ")"; else $sSQL .= multShipWeight($rsStock["pWeight"],$rs["optWeightDiff"]) . ")";
							}else
								$sSQL = "INSERT INTO cartoptions (coCartID,coOptID,coOptGroup,coCartOption,coPriceDiff,coWeightDiff) VALUES (" . $cartID . "," . $rs["optID"] . ",'" . mysql_escape_string($rs[getlangid("optGrpName",16)]) . "','',0,0)";
							mysql_query($sSQL) or print(mysql_error());
						}
						mysql_free_result($result);
					}else{
						$sSQL="SELECT optID,".getlangid("optGrpName",16).",".getlangid("optName",32)." FROM options LEFT JOIN optiongroup ON options.optGroup=optiongroup.optGrpID WHERE optID='" . mysql_escape_string($objValue) . "'";
						$result = mysql_query($sSQL) or print(mysql_error());
						$rs = mysql_fetch_array($result);
						$sSQL = "INSERT INTO cartoptions (coCartID,coOptID,coOptGroup,coCartOption,coPriceDiff,coWeightDiff) VALUES (" . $cartID . "," . $rs["optID"] . ",'" . mysql_escape_string($rs[getlangid("optGrpName",16)]) . "','" . mysql_escape_string(unstripslashes(trim(@$_POST["v" . $objItem]))) . "',0,0)";
						mysql_query($sSQL) or print(mysql_error());
						mysql_free_result($result);
					}
				}
			}
		}
		checkpricebreaks($theid,$rsStock["pPrice"]);
		if(trim(@$_POST["frompage"])!="" && @$actionaftercart==3)
			print '<meta http-equiv="Refresh" content="3; URL=' . trim(@$_POST["frompage"]) . '">';
		else
			print '<meta http-equiv="Refresh" content="3; URL=cart.php">';
?>
      <table border="0" cellspacing="<?php print $maintablespacing?>" cellpadding="<?php print $maintablepadding?>" width="<?php print $maintablewidth?>" bgcolor="<?php print $maintablebg?>" align="center">
        <tr> 
          <td width="100%" align="center">
            <table width="<?php print $innertablewidth?>" border="0" cellspacing="<?php print $innertablespacing?>" cellpadding="<?php print $innertablepadding?>" bgcolor="<?php print $innertablebg?>">
			  <tr>
			    <td align="center"><p>&nbsp;</p>
<?php		print '<p>' . $quantity . ' <strong>' . $rsStock[getlangid("pName",1)] . '</strong> ' . $xxAddOrd . '</p>';
			print '<p>' . $xxPlsWait . ' <a href="';
			if(trim(@$_POST["frompage"])!="" && @$actionaftercart==3) print trim(@$_POST["frompage"]); else print 'cart.php';
			print '"><strong>' . $xxClkHere . '</strong></a>.</p>'; ?>
				<p>&nbsp;</p>
				<p>&nbsp;</p>
				</td>
			  </tr>
			</table>
		  </td>
        </tr>
      </table>
<?php
	}else{
?>
      <table border="0" cellspacing="<?php print $maintablespacing?>" cellpadding="<?php print $maintablepadding?>" width="<?php print $maintablewidth?>" bgcolor="<?php print $maintablebg?>" align="center">
        <tr> 
          <td width="100%" align="center">
            <table width="<?php print $innertablewidth?>" border="0" cellspacing="<?php print $innertablespacing?>" cellpadding="<?php print $innertablepadding?>" bgcolor="<?php print $innertablebg?>">
			  <tr>
			    <td align="center"><p>&nbsp;</p>
				<?php print "<p>" . $xxSrryItm . " <strong>" . $rsStock[getlangid("pName",1)] . "</strong> " . $xxIsCntly;
				if($tempOutOfStock) print " " . $xxTemprly;
				print " " . $xxOutStck . "</p>";
				if($tempOutOfStock){
					print "<p>" . $xxNotChOu . " ";
					$bestDate += $stockManage*(60*60);
					$totMins = (int)($bestDate - (time()+($dateadjust*60*60)));
					$totMins = (int)($totMins / 60)+1;
					if($totMins > 300)
						print $xxShrtWhl;
					else{
						if($totMins >= 60) print (int)($totMins / 60) . " hour";
						if($totMins >= 120) print "s";
						$totMins -= ((int)($totMins / 60) * 60);
						if($totMins > 0) print " " . $totMins . " minute";
						if($totMins > 1) print "s";
					}
					print $xxChkBack . "</p>";
				} ?>
				<p><?php print $xxPlease?> <a href="javascript:history.go(-1)"><strong><?php print $xxClkHere?></strong></a> <?php print $xxToRetrn?></p>
				<p>&nbsp;</p>
				<p>&nbsp;</p>
				</td>
			  </tr>
			</table>
		  </td>
        </tr>
      </table>
<?php
	}
}elseif(@$_POST["mode"]=="checkout"){
	$remember=FALSE;
	if(@$_POST["checktmplogin"]=="1"){
		$sSQL = "SELECT tmploginname FROM tmplogin WHERE tmploginid='" . trim(@$_POST["sessionid"]) . "'";
		$result = mysql_query($sSQL) or print(mysql_error());
		if($rs = mysql_fetch_array($result)){
			$_SESSION["clientUser"]=$rs["tmploginname"];
			mysql_free_result($result);
			mysql_query("DELETE FROM tmplogin WHERE tmploginid='" . trim(@$_POST["sessionid"]) . "'") or print(mysql_error());
			$sSQL = "SELECT clientActions,clientLoginLevel FROM clientlogin WHERE clientUser='" . $_SESSION["clientUser"] . "'";
			$result = mysql_query($sSQL) or print(mysql_error());
			if($rs = mysql_fetch_array($result)){
				$_SESSION["clientActions"]=$rs["clientActions"];
				$_SESSION["clientLoginLevel"]=$rs["clientLoginLevel"];
			}
		}
		mysql_free_result($result);
	}
	if(@$_COOKIE["id1"] != "" && @$_COOKIE["id2"] != ""){
		$sSQL = "SELECT ordName,ordAddress,ordCity,ordState,ordZip,ordCountry,ordEmail,ordPhone,ordShipName,ordShipAddress,ordShipCity,ordShipState,ordShipZip,ordShipCountry,ordPayProvider,ordComLoc,ordExtra1,ordExtra2,ordAddInfo FROM orders WHERE ordID='" . mysql_escape_string(unstripslashes($_COOKIE["id1"])) . "' AND ordSessionID='" . mysql_escape_string(unstripslashes($_COOKIE["id2"])) . "'";
		$result = mysql_query($sSQL) or print(mysql_error());
		if($rs = mysql_fetch_array($result)){
			$ordName = $rs["ordName"];
			$ordAddress = $rs["ordAddress"];
			$ordCity = $rs["ordCity"];
			$ordState = $rs["ordState"];
			$ordZip = $rs["ordZip"];
			$ordCountry = $rs["ordCountry"];
			$ordEmail = $rs["ordEmail"];
			$ordPhone = $rs["ordPhone"];
			$ordShipName = $rs["ordShipName"];
			$ordShipAddress = $rs["ordShipAddress"];
			$ordShipCity = $rs["ordShipCity"];
			$ordShipState = $rs["ordShipState"];
			$ordShipZip = $rs["ordShipZip"];
			$ordShipCountry = $rs["ordShipCountry"];
			$ordPayProvider = $rs["ordPayProvider"];
			$ordComLoc = $rs["ordComLoc"];
			$ordExtra1 = $rs["ordExtra1"];
			$ordExtra2 = $rs["ordExtra2"];
			$ordAddInfo = $rs["ordAddInfo"];
			$remember=TRUE;
		}
		mysql_free_result($result);
	}
?>
<script Language="JavaScript" type="text/javascript">
<!--
var checkedfullname=false;
var numhomecountries=0;
function checkform(frm)
{
<?php if(trim(@$extraorderfield1)!="" && @$extraorderfield1required==TRUE){ ?>
if(frm.ordextra1.value==""){
	alert("<?php print $xxPlsEntr?> \"<?php print $extraorderfield1?>\".");
	frm.ordextra1.focus();
	return (false);
}
<?php } ?>
if(frm.name.value==""){
	alert("<?php print $xxPlsEntr?> \"<?php print $xxName?>\".");
	frm.name.focus();
	return (false);
}
gotspace=false;
var checkStr = frm.name.value;
for (i = 0; i < checkStr.length; i++){
	if(checkStr.charAt(i)==" ")
		gotspace=true;
}
if(!checkedfullname && !gotspace){
	alert("<?php print $xxFulNam?> \"<?php print $xxName?>\".");
	frm.name.focus();
	checkedfullname=true;
	return (false);
}
if(frm.email.value==""){
	alert("<?php print $xxPlsEntr?> \"<?php print $xxEmail?>\".");
	frm.email.focus();
	return (false);
}
validemail=0;
var checkStr = frm.email.value;
for (i = 0; i < checkStr.length; i++){
	if(checkStr.charAt(i)=="@")
		validemail |= 1;
	if(checkStr.charAt(i)==".")
		validemail |= 2;
}
if(validemail != 3){
	alert("<?php print $xxValEm?>");
	frm.email.focus();
	return (false);
}
if(frm.address.value==""){
	alert("<?php print $xxPlsEntr?> \"<?php print $xxAddress?>\".");
	frm.address.focus();
	return (false);
}
if(frm.city.value==""){
	alert("<?php print $xxPlsEntr?> \"<?php print $xxCity?>\".");
	frm.city.focus();
	return (false);
}
if(frm.country.selectedIndex < numhomecountries){
	if(frm.state.selectedIndex==0){
		alert("<?php print $xxPlsSlct . " " . $xxState?>");
		frm.state.focus();
		return (false);
	}
}
else{
	if(frm.state2.value==""){
		alert("<?php print $xxPlsEntr?> \"<?php print str_replace("<br />"," ",$xxNonState)?>\".");
		frm.state2.focus();
		return (false);
	}
}
if(frm.zip.value==""){
	alert("<?php print $xxPlsEntr?> \"<?php print $xxZip?>\".");
	frm.zip.focus();
	return (false);
}
if(frm.phone.value==""){
	alert("<?php print $xxPlsEntr?> \"<?php print $xxPhone?>\".");
	frm.phone.focus();
	return (false);
}
<?php if(trim(@$extraorderfield2)!="" && @$extraorderfield2required==TRUE){ ?>
if(frm.ordextra2.value==""){
	alert("<?php print $xxPlsEntr?> \"<?php print $extraorderfield2?>\".");
	frm.ordextra2.focus();
	return (false);
}
<?php } ?>
<?php if(@$noshipaddress!=TRUE){ ?>
if(frm.saddress.value!=""){
	if(frm.scity.value==""){
		alert("<?php print $xxShpDtls?>\n\n<?php print $xxPlsEntr?> \"<?php print $xxCity?>\".");
		frm.scity.focus();
		return (false);
	}
	if(frm.scountry.selectedIndex < numhomecountries){
		if(frm.sstate.selectedIndex==0){
			alert("<?php print $xxShpDtls?>\n\n<?php print $xxPlsSlct . " " . $xxState?>.");
			frm.sstate.focus();
			return (false);
		}
	}
	else{
		if(frm.sstate2.value==""){
			alert("<?php print $xxShpDtls?>\n\n<?php print $xxPlsEntr?> \"<?php print str_replace("<br />"," ",$xxNonState)?>\".");
			frm.sstate2.focus();
			return (false);
		}
	}
	if(frm.szip.value==""){
		alert("<?php print $xxShpDtls?>\n\n<?php print $xxPlsEntr?> \"<?php print $xxZip?>\".");
		frm.szip.focus();
		return (false);
	}
}
<?php } ?>
if(frm.remember.checked==false){
	if(confirm("<?php print $xxWntRem?>")){
		frm.remember.checked=true
	}
}
<?php if(@$termsandconditions==TRUE){ ?>
if(frm.license.checked==false){
	alert("<?php print $xxPlsProc?>");
	frm.license.focus();
	return (false);
}
<?php } ?>
return (true);
}
<?php if(@$termsandconditions==TRUE){ ?>
function showtermsandconds(){
newwin=window.open("termsandconditions.php","Terms","menubar=no, scrollbars=yes, width=420, height=380, directories=no,location=no,resizable=yes,status=no,toolbar=no");
}
<?php } ?>
var savestate=0;
var ssavestate=0;
function checkoutspan(shp){
thestyle = document.getElementById(shp+'outspan').style;
thecntry = eval('document.forms.mainform.'+shp+'country');
thestate = eval('document.forms.mainform.'+shp+'state');
if(thecntry.selectedIndex < numhomecountries){
thestyle.visibility="hidden";
thestate.disabled=false;
eval('thestate.selectedIndex='+shp+'savestate');
}else{
thestyle.visibility="visible";
if(thestate.disabled==false){
thestate.disabled=true;
eval(shp+'savestate = thestate.selectedIndex');
thestate.selectedIndex=0;
}
}
}
//-->
</script>
	  <table border="0" cellspacing="<?php print $maintablespacing?>" cellpadding="<?php print $maintablepadding?>" width="<?php print $maintablewidth?>" bgcolor="<?php print $maintablebg?>" align="center">
        <tr> 
          <td width="100%">
		    <form method="post" name="mainform" action="cart.php" onsubmit="return checkform(this)">
			  <input type="hidden" name="mode" value="go" />
			  <input type="hidden" name="sessionid" value="<?php print @$_POST["sessionid"]?>" />
			  <input type="hidden" name="PARTNER" value="<?php print @$_POST["PARTNER"]?>" />
			  <table width="<?php print $innertablewidth?>" border="0" cellspacing="<?php print $innertablespacing?>" cellpadding="<?php print $innertablepadding?>" bgcolor="<?php print $innertablebg?>">
				<tr>
				  <td align="center" colspan="4"><strong><?php print $xxCstDtl?></strong></td>
				</tr>
			<?php if(trim(@$extraorderfield1)!=""){ ?>
				<tr>
				  <td align="right"><strong><?php if(@$extraorderfield1required==TRUE) print "<font color='#FF0000'>*</font>";
									print $extraorderfield1 ?>:</strong></td>
				  <td colspan="3"><?php if(@$extraorderfield1html != "")print $extraorderfield1html; else print '<input type="text" name="ordextra1" size="' . atb(20) . '" value="' . @$ordExtra1 . '" />'?></td>
				</tr>
			<?php } ?>
				<tr>
				  <td align="right"><strong><font color='#FF0000'>*</font><?php print $xxName?>:</strong></td>
				  <td align="left"><input type="text" name="name" size="<?php print atb(20)?>" value="<?php print @$ordName?>" /></td>
				  <td align="right"><strong><font color='#FF0000'>*</font><?php print $xxEmail?>:</strong></td>
				  <td align="left"><input type="text" name="email" size="<?php print atb(20)?>" value="<?php print @$ordEmail?>" /></td>
				</tr>
				<tr>
				  <td align="right"><strong><font color='#FF0000'>*</font><?php print $xxAddress?>:</strong></td>
				  <td align="left"><input type="text" name="address" size="<?php print atb(20)?>" value="<?php print @$ordAddress?>" /></td>
				  <td align="right"><strong><font color='#FF0000'>*</font><?php print $xxCity?>:</strong></td>
				  <td align="left"><input type="text" name="city" size="<?php print atb(20)?>" value="<?php print @$ordCity?>" /></td>
				</tr>
				<tr>
				  <td align="right"><strong><?php print $xxState?>:</strong></td>
				  <td align="left"><select name="state" size="1">
<?php
function show_states($tstate){
	global $xxOutState;
	$foundmatch=FALSE;
	$sSQL = "SELECT stateName FROM states WHERE stateEnabled=1 ORDER BY stateName";
	$result = mysql_query($sSQL) or print(mysql_error());
	print "<option value=''>" . $xxOutState . "</option>";
	while($rs = mysql_fetch_array($result)){
		print '<option value="' . str_replace('"','&quot;',$rs["stateName"]) . '"';
		if($tstate==$rs["stateName"]){
			print ' selected';
			$foundmatch=TRUE;
		}
		print '>' . $rs["stateName"] . "</option>\n";
	}
	mysql_free_result($result);
	return $foundmatch;
}
$havestate = show_states(@$ordState);
?>
					</select>
				  </td>
				  <td align="right"><strong><font color='#FF0000'><span name="outspan" id="outspan" style="visibility:hidden">*</span></font><?php print $xxNonState?>:</strong></td>
				  <td align="left"><input type="text" name="state2" size="<?php print atb(20)?>" value="<?php if(! $havestate) print @$ordState?>" /></td>
				</tr>
				<tr>
				  <td align="right"><strong><font color='#FF0000'>*</font><?php print $xxCountry?>:</strong></td>
				  <td align="left"><select name="country" size="1" onChange="checkoutspan('')">
<?php
function show_countries($tcountry){
	global $numhomecountries;
	$numhomecountries = 0;
	$sSQL = "SELECT countryName,countryOrder FROM countries WHERE countryEnabled=1 ORDER BY countryOrder DESC, countryName";
	$result = mysql_query($sSQL) or print(mysql_error());
	while($rs = mysql_fetch_array($result)){
		print '<option value="' . str_replace('"','&quot;',$rs["countryName"]) . '"';
		if($rs["countryOrder"]==2) $numhomecountries++;
		if($tcountry==$rs["countryName"])
			print " selected";
		print '>' . $rs["countryName"] . "</option>\n";
	}
}
show_countries(@$ordCountry)
?>
					</select>
				  </td>
				  <td align="right"><strong><font color='#FF0000'>*</font><?php print $xxZip?>:</strong></td>
				  <td align="left"><input type="text" name="zip" size="<?php print atb(10)?>" value="<?php print @$ordZip?>" /></td>
				</tr>
				<tr>
				  <td align="right"><strong><font color='#FF0000'>*</font><?php print $xxPhone?>:</strong></td>
				  <td align="left"<?php if(trim(@$extraorderfield2)=="") print ' colspan="3"'; ?>><input type="text" name="phone" size="<?php print atb(20)?>" value="<?php print @$ordPhone?>" /></td>
			<?php	if(trim(@$extraorderfield2)!=""){ ?>
				  <td align="right"><strong><?php if(@$extraorderfield2required==TRUE) print '<font color="#FF0000">*</font>';
									print $extraorderfield2 ?>:</strong></td>
				  <td align="left"><?php if(@$extraorderfield2html != "")print $extraorderfield2html; else print '<input type="text" name="ordextra2" size="' . atb(20) . '" value="' . @$ordExtra2 . '" />'?></td>
			<?php	} ?>
				</tr>
			<?php	if(@$commercialloc==TRUE || $shipType==4){ ?>
				<tr>
				  <td align="right"><input type="checkbox" name="commercialloc" value="Y" <?php if((@$ordComLoc&1)==1) print "checked"?> /></td>
				  <td align="left" colspan="3"><font size="1"><?php print $xxComLoc ?></font></td>
				</tr>
			<?php	}
					if(abs(@$addshippinginsurance)==2){ ?>
				<tr>
				  <td align="right"><input type="checkbox" name="wantinsurance" value="Y" <?php if((@$ordComLoc&2)==2) print "checked"?> /></td>
				  <td align="left" colspan="3"><font size="1"><?php print $xxWantIns ?></font></td>
				</tr>
			<?php	}
					if(@$noshipaddress!=TRUE){ ?>
				<tr>
				  <td width="100%" align="center" colspan="4"><strong><?php print $xxShpDiff?></strong></td>
				</tr>
				<tr>
				  <td align="right"><strong><?php print $xxName?>:</strong></td>
				  <td align="left" colspan="3"><input type="text" name="sname" size="<?php print atb(20)?>" value="<?php print @$ordShipName?>" /></td>
				</tr>
				<tr>
				  <td align="right"><strong><?php print $xxAddress?>:</strong></td>
				  <td align="left"><input type="text" name="saddress" size="<?php print atb(20)?>" value="<?php print trim(@$ordShipAddress)?>" /></td>
				  <td align="right"><strong><?php print $xxCity?>:</strong></td>
				  <td align="left"><input type="text" name="scity" size="<?php print atb(20)?>" value="<?php print @$ordShipCity?>" /></td>
				</tr>
				<tr>
				  <td align="right"><strong><?php print $xxState?>:</strong></td>
				  <td align="left"><select name="sstate" size="1">
<?php
$havestate = show_states(@$ordShipState);
?>
					</select>
				  </td>
				  <td align="right"><strong><font color='#FF0000'><span name="outspan" id="soutspan" style="visibility:hidden">*</span></font><?php print $xxNonState?>:</strong></td>
				  <td align="left"><input type="text" name="sstate2" size="<?php print atb(20)?>" value="<?php if(! $havestate) print @$ordShipState?>" /></td>
				</tr>
				<tr>
				  <td align="right"><strong><?php print $xxCountry?>:</strong></td>
				  <td align="left"><select name="scountry" size="1" onChange="checkoutspan('s')">
<?php
show_countries(@$ordShipCountry);
?>
					</select>
				  </td>
				  <td align="right"><strong><?php print $xxZip?>:</strong></td>
				  <td align="left"><input type="text" name="szip" size="<?php print atb(10)?>" value="<?php print @$ordShipZip?>" /></td>
				</tr>
			<?php	} // $noshipaddress ?>
				<tr>
				  <td align="center" colspan="4">
					<strong><?php print $xxAddInf?>.</strong><br />
					<textarea name="ordAddInfo" rows="3" wrap=virtual cols="<?php print atb(44)?>"><?php print @$ordAddInfo?></textarea> 
				  </td>
				</tr>
<?php if(@$termsandconditions==TRUE){ ?>
				<tr>
				  <td align="center" colspan="4"><input type="checkbox" name="license" value="1" />
					<?php print $xxTermsCo?>
				  </td>
				</tr>
<?php } ?>
				<tr>
				  <td align="center" colspan="4"><input type="checkbox" name="remember" value="1" <?php if($remember) print "checked"?> />
					<strong><?php print $xxRemMe?></strong><br />
					<font size="1"><?php print $xxOpCook?></font>
				  </td>
				</tr>
<?php				if(!@$nogiftcertificate){ ?>
				<tr>
				  <td align="right" colspan="2"><strong><?php print $xxGifNum?>:</strong></td><td colspan="2"><input type="text" name="cpncode" size="<?php print atb(20)?>" /></td>
				</tr>
				<tr>
				  <td align="center" colspan="4"><font size="1"><?php print $xxGifEnt?></font></td>
				</tr>
<?php
					}
					$sSQL = "SELECT payProvID,".getlangid("payProvShow",128)." FROM payprovider WHERE payProvEnabled=1 ORDER BY payProvOrder";
					$result = mysql_query($sSQL) or print(mysql_error());
					if(mysql_num_rows($result)==0){
?>
				<tr>
				  <td colspan="4" align="center"><strong><?php print $xxNoPay?></strong></td>
				</tr>
<?php
					}elseif(mysql_num_rows($result)==1){
						$rs = mysql_fetch_array($result);
?>
				<tr>
				  <td colspan="4" align="center"><input type="hidden" name="payprovider" value="<?php print $rs["payProvID"]?>" /><strong><?php print $xxClkCmp?></strong></td>
				</tr>
<?php
					}else{
?>			    <tr>
				  <td colspan="4" align="center"><p><strong><?php print $xxPlsChz?></strong></p>
				    <p><select name="payprovider" size="1">
<?php
						while($rs = mysql_fetch_array($result)){
							print "<option value='" . $rs["payProvID"] . "'";
							if(@$ordPayProvider==$rs["payProvID"]) print " selected";
							print ">" . $rs[getlangid("payProvShow",128)] . "</option>\n";
						}
?>
				    </select></p>
				  </td>
			    </tr>
<?php
					}
?>
				<tr>
				  <td width="50%" align="center" colspan="4"><input type="image" src="images/checkout.gif" border="0" /></td>
				</tr>
			  </table>
			</form>
		  </td>
        </tr>
      </table>
<script Language="JavaScript" type="text/javascript">
savestate = document.forms.mainform.state.selectedIndex;
numhomecountries=<?php print $numhomecountries?>;
checkoutspan('');
<?php if(@$noshipaddress!=TRUE) print "ssavestate = document.forms.mainform.sstate.selectedIndex;\r\ncheckoutspan('s')\r\n" ?>
</script>
<?php
}elseif(@$_POST["mode"]=="go"){
?>
<?php include "./vsadmin/inc/uspsshipping.php" ?>
<?php
	$success=TRUE;
	$checkIntOptions=FALSE;
	$alldata = "";
	$shipMethod = "";
	$shipping = 0;
	$iTotItems = 0;
	$iWeight = 0;
	$countryTaxRate=0;
	$stateTaxRate=0;
	$countryTax=0;
	$stateTax=0;
	$stateAbbrev="";
	$international = "";
	$thePQuantity = 0;
	$thePWeight = 0;
	$runTotQuant = 0;
	$statetaxfree = 0;
	$countrytaxfree = 0;
	$shipfreegoods = 0;
	$totalgoods = 0;
	$somethingToShip = FALSE;
	$freeshippingapplied = FALSE;
	$cpncode = trim(str_replace("'","",@$_POST["cpncode"]));
	$gotcpncode=FALSE;
	$isstandardship = FALSE;
	$numshipoptions=0;
	$maxshipoptions=20;
	$homecountry = FALSE;
	for($i=0; $i < $maxshipoptions; $i++){
		$intShipping[$i][0]="";
		$intShipping[$i][1]="";
		$intShipping[$i][2]=0;
		$intShipping[$i][3]=0;
	}
	if(trim(@$_POST["saddress"])<>""){
		$shipcountry = trim(unstripslashes(@$_POST["scountry"]));
		$shipstate = trim(unstripslashes(@$_POST["sstate"]));
		$destZip = trim(unstripslashes(@$_POST["szip"]));
	}else{
		$shipcountry = trim(unstripslashes(@$_POST["country"]));
		$shipstate = trim(unstripslashes(@$_POST["state"]));
		$destZip = trim(unstripslashes(@$_POST["zip"]));
	}
	$sSQL = "SELECT countryID,countryCode,countryOrder FROM countries WHERE countryName='" . mysql_escape_string(unstripslashes(trim(@$_POST["country"]))) . "'";
	$result = mysql_query($sSQL) or print(mysql_error());
	if($rs = mysql_fetch_array($result)){
		$countryID = $rs["countryID"];
		$countryCode = $rs["countryCode"];
		$homecountry = ($rs["countryOrder"]==2);
	}
	mysql_free_result($result);
	if(! $homecountry) $perproducttaxrate=FALSE;
	$sSQL = "SELECT countryID,countryTax,countryCode,countryFreeShip,countryOrder FROM countries WHERE countryName='" . mysql_escape_string($shipcountry) . "'";
	$result = mysql_query($sSQL) or print(mysql_error());
	if($rs = mysql_fetch_array($result)){
		$countryTaxRate = $rs["countryTax"];
		$shipCountryID = $rs["countryID"];
		$shipCountryCode = $rs["countryCode"];
		$freeshipapplies = ($rs["countryFreeShip"]==1);
		$shiphomecountry = ($rs["countryOrder"]==2);
	}
	mysql_free_result($result);
	if($homecountry){
		$sSQL = "SELECT stateTax,stateAbbrev FROM states WHERE stateName='" . mysql_escape_string(unstripslashes(trim(@$_POST["state"]))) . "'";
		$result = mysql_query($sSQL) or print(mysql_error());
		if($rs = mysql_fetch_array($result))
			$stateAbbrev=$rs["stateAbbrev"];
		mysql_free_result($result);
	}
	if($shiphomecountry){
		$sSQL = "SELECT stateTax,stateAbbrev,stateFreeShip FROM states WHERE stateName='" . mysql_escape_string($shipstate) . "'";
		$result = mysql_query($sSQL) or print(mysql_error());
		if($rs = mysql_fetch_array($result)){
			$stateTaxRate=$rs["stateTax"];
			$shipStateAbbrev=$rs["stateAbbrev"];
			$freeshipapplies = ($freeshipapplies && ($rs["stateFreeShip"]==1));
		}
		mysql_free_result($result);
	}
	if($shipcountry != $origCountry){
		$international = "Intl";
		$willpickuptext = "";
	}
	if($shipType==2 || $shipType==5){ // Weight / Price based shipping
		$allzones="";
		$index=0;
		$numzones=0;
		$useUSState=FALSE;
		$zoneid=0;
		if($splitUSZones)
			if($shiphomecountry) $useUSState=TRUE;
		if($useUSState)
			$sSQL = "SELECT pzID,pzMultiShipping,pzFSA,pzMethodName1,pzMethodName2,pzMethodName3,pzMethodName4,pzMethodName5 FROM states INNER JOIN postalzones ON postalzones.pzID=states.stateZone WHERE stateName='" . mysql_escape_string($shipstate) . "'";
		else
			$sSQL = "SELECT pzID,pzMultiShipping,pzFSA,pzMethodName1,pzMethodName2,pzMethodName3,pzMethodName4,pzMethodName5 FROM countries INNER JOIN postalzones ON postalzones.pzID=countries.countryZone WHERE countryName='" . mysql_escape_string($shipcountry) . "'";
		$result = mysql_query($sSQL) or print(mysql_error());
		if($rs = mysql_fetch_array($result)){
			$zoneid=$rs["pzID"];
			$numshipoptions=$rs["pzMultiShipping"]+1;
			$pzFSA = $rs["pzFSA"];
			for($index3=0; $index3 < $numshipoptions; $index3++){
				$intShipping[$index3][0]=$rs["pzMethodName" . ($index3+1)];
				$intShipping[$index3][2]=0;
			}
		}else{
			$success=FALSE;
			$errormsg = "Country / state shipping zone is unassigned.";
		}
		mysql_free_result($result);
		$sSQL = "SELECT zcWeight,zcRate,zcRate2,zcRate3,zcRate4,zcRate5 FROM zonecharges WHERE zcZone=" . $zoneid . " ORDER BY zcWeight";
		$result = mysql_query($sSQL) or print(mysql_error());
		while($rs = mysql_fetch_row($result))
			$allzones[$index++] = $rs;
		mysql_free_result($result);
		$numzones=$index;
	}elseif($shipType==3){ // USPS
		$uspsmethods="";
		$numuspsmeths=0;
		$sSQL = "SELECT uspsMethod,uspsFSA,uspsShowAs FROM uspsmethods WHERE uspsID<100 AND uspsUseMethod=1 AND uspsLocal=";
		if($international=="") $sSQL .= "1"; else $sSQL .= "0";
		$result = mysql_query($sSQL) or print(mysql_error());
		if(mysql_num_rows($result) > 0){
			while($rs = mysql_fetch_row($result))
				$uspsmethods[$numuspsmeths++] = $rs;
		}else{
			$success=FALSE;
			$errormsg = "USPS Admin Error: " . $xxNoMeth;
		}
		mysql_free_result($result);
	}elseif($shipType==4 || $shipType==6){ // UPS / Canada Post
		$uspsmethods="";
		$numuspsmeths=0;
		if($shipType==4)
			$sSQL = "SELECT uspsMethod,uspsFSA,uspsShowAs FROM uspsmethods WHERE uspsID>100 AND uspsID<200 AND uspsUseMethod=1";
		else
			$sSQL = "SELECT uspsMethod,uspsFSA,uspsShowAs FROM uspsmethods WHERE uspsID>200 AND uspsID<300 AND uspsUseMethod=1";
		$result = mysql_query($sSQL) or print(mysql_error());
		if(mysql_num_rows($result) > 0){
			while($rs = mysql_fetch_row($result))
				$uspsmethods[$numuspsmeths++] = $rs;
		}else{
			$success=FALSE;
			$errormsg = "UPS / Canada Post Admin Error: " . $xxNoMeth;
		}
		mysql_free_result($result);
	}
	$sSQL = "SELECT cartID,cartProdID,cartProdPrice,cartQuantity,pWeight,pShipping,pShipping2,pExemptions,pSection,topSection,pDims,pTax FROM cart LEFT JOIN products ON cart.cartProdID=products.pId LEFT OUTER JOIN sections ON products.pSection=sections.sectionID WHERE cartCompleted=0 AND cartSessionID='" . @$_POST["sessionid"] . "'";
	$allcart = mysql_query($sSQL) or print(mysql_error());
	if(($itemsincart=mysql_num_rows($allcart))==0) $allcart = "";
	if($success && $allcart<>""){
		if($shipType==3)
			$sXML = "<" . $international . "RateRequest USERID=\"" . $uspsUser . "\" PASSWORD=\"" . $uspsPw . "\">";
		elseif($shipType==4){
			$sXML = "<?xml version=\"1.0\"?><AccessRequest xml:lang=\"en-US\"><AccessLicenseNumber>" . $upsAccess . "</AccessLicenseNumber><UserId>" . $upsUser . "</UserId><Password>" . $upsPw . "</Password></AccessRequest><?xml version=\"1.0\"?>";
			$sXML .= "<RatingServiceSelectionRequest xml:lang=\"en-US\"><Request><TransactionReference><CustomerContext>Rating and Service</CustomerContext><XpciVersion>1.0001</XpciVersion></TransactionReference>";
			$sXML .= "<RequestAction>Rate</RequestAction><RequestOption>shop</RequestOption></Request>";
			if(@$upspickuptype!="") $sXML .= "<PickupType><Code>" . @$upspickuptype . "</Code></PickupType>";
			$sXML .= "<Shipment><Shipper><Address>";
			$sXML .= "<PostalCode>" . $origZip . "</PostalCode>";
			$sXML .= "<CountryCode>" . $origCountryCode . "</CountryCode>";
			$sXML .= "</Address></Shipper><ShipTo><Address>";
			$sXML .= "<PostalCode>" . $destZip . "</PostalCode>";
			$sXML .= "<CountryCode>" . $shipCountryCode . "</CountryCode>";
			if(@$_POST["commercialloc"]!="Y") $sXML .= "<ResidentialAddress/>";
			$sXML .= "</Address></ShipTo>";
			//sXML = "<Service><Code>11</Code></Service>";
		}elseif($shipType==6){
			$sXML = '<?xml version="1.0" ?> ' .
					"<eparcel>" .
					"<language> en </language>" .
					"<ratesAndServicesRequest>" .
					"<merchantCPCID> " . $adminCanPostUser . " </merchantCPCID>" .
					"<fromPostalCode> " . $origZip . " </fromPostalCode>" .
					"<lineItems>";
		}
		$rowcounter = 0;
		$index=0;
		while($rsCart=mysql_fetch_array($allcart)){
			$index++;
			$sSQL = "SELECT SUM(coPriceDiff) AS coPrDff FROM cartoptions WHERE coCartID=". $rsCart["cartID"];
			$result = mysql_query($sSQL) or print(mysql_error());
			if($rs = mysql_fetch_array($result)){
				$rsCart["cartProdPrice"] += (double)$rs["coPrDff"];
			}
			mysql_free_result($result);
			$sSQL = "SELECT SUM(coWeightDiff) AS coWghtDff FROM cartoptions WHERE coCartID=". $rsCart["cartID"];
			$result = mysql_query($sSQL) or print(mysql_error());
			if($rs = mysql_fetch_array($result)){
				$rsCart["pWeight"] += (double)$rs["coWghtDff"];
			}
			mysql_free_result($result);
			$runTot=$rsCart["cartProdPrice"] * (int)($rsCart["cartQuantity"]);
			$runTotQuant += (int)($rsCart["cartQuantity"]);
			$totalgoods += $runTot;
			$thistopcat=0;
			if(trim(@$_SESSION["clientUser"]) != "") $rsCart["pExemptions"] = ((int)$rsCart["pExemptions"] | (int)$_SESSION["clientActions"]);
			if(($shipType==2 || $shipType==3 || $shipType==4 || $shipType==6) && (double)$rsCart["pWeight"]<=0.0)
				$rsCart["pExemptions"] = ($rsCart["pExemptions"] | 4);
			if(($rsCart["pExemptions"] & 1)==1) $statetaxfree += $runTot;
			if(@$perproducttaxrate==TRUE){
				if(is_null($rsCart["pTax"])) $rsCart["pTax"] = $countryTaxRate;
				if(($rsCart["pExemptions"] & 2) != 2) $countryTax += (($rsCart["pTax"] * $runTot) / 100.0);
			}else{
				if(($rsCart["pExemptions"] & 2)==2) $countrytaxfree += $runTot;
			}
			if(($rsCart["pExemptions"] & 4)==4) $shipfreegoods += $runTot;
			if($packtogether)
				$iTotItems=1;
			else
				$iTotItems += 1;
			$shipThisProd=TRUE;
			if(($rsCart["pExemptions"] & 4)==4){ // No Shipping on this product
				if(! $packtogether) $iTotItems -= (int)$rsCart["cartQuantity"];
				$shipThisProd=FALSE;
			}
			if($shipType==1){ // Flat rate shipping
				if($shipThisProd) $shipping += $rsCart["pShipping"] + $rsCart["pShipping2"] * ($rsCart["cartQuantity"]-1);
			}elseif(($shipType==2 || $shipType==5) && @$_POST["shipping"]==""){ // Weight / Price based shipping
				$havematch=FALSE;
				for($index3=0; $index3 < $numshipoptions; $index3++)
					$dHighest[$index3]=0;
				if(is_array($allzones)){
					if($shipThisProd){
						$somethingToShip=TRUE;
						if($packtogether){
							if($shipType==2)
								$thePWeight += ((double)($rsCart["cartQuantity"])*(double)($rsCart["pWeight"]));
							else
								$thePWeight += ((double)($rsCart["cartQuantity"])*(double)($rsCart["cartProdPrice"]));
							$thePQuantity = 1;
						}else{
							if($shipType==2)
								$thePWeight = (double)$rsCart["pWeight"];
							else
								$thePWeight = (double)$rsCart["cartProdPrice"];
							$thePQuantity = (double)$rsCart["cartQuantity"];
						}
					}
					if(((!$packtogether && $shipThisProd) || ($packtogether && ($index == $itemsincart))) && $somethingToShip){ // Only calculate pack together when we have the total
						for($index2=0; $index2 < $numzones; $index2++){
							if($allzones[$index2][0] >= $thePWeight){
								$havematch=TRUE;
								for($index3=0; $index3 < $numshipoptions; $index3++)
									$intShipping[$index3][2] += ((double)$allzones[$index2][1+$index3]*$thePQuantity);
								break;
							}
							$dHighWeight = $allzones[$index2][0];
							for($index3=0; $index3 < $numshipoptions; $index3++)
								$dHighest[$index3]=$allzones[$index2][1+$index3];
						}
						if(! $havematch){
							for($index3=0; $index3 < $numshipoptions; $index3++)
								$intShipping[$index3][2] += $dHighest[$index3];
							if($allzones[0][0] < 0){
								$dHighWeight = $thePWeight - $dHighWeight;
								while($dHighWeight > 0){
									for($index3=0; $index3 < $numshipoptions; $index3++)
										$intShipping[$index3][2] += ((double)($allzones[0][1+$index3])*$thePQuantity);
									$dHighWeight += $allzones[0][0];
								}
							}
						}
					}
				}
			}elseif($shipType==3 && @$_POST["shipping"]==""){ // USPS Shipping
				if($packtogether){
					if($shipThisProd){
						$somethingToShip=TRUE;
						$iWeight += ((double)$rsCart["pWeight"] * (int)$rsCart["cartQuantity"]);
					}
					if(($index == $itemsincart) && $somethingToShip){
						$numpacks=1;
						if(@$splitpackat != "")
							if($iWeight > $splitpackat) $numpacks=ceil($iWeight/$splitpackat);
						if($numpacks > 1){
							if($international != "")
								$sXML .= addInternational($rowcounter,$splitpackat,$numpacks-1,"Package",$shipcountry);
							else
								$sXML .= addDomestic($rowcounter,"Parcel",$origZip,$destZip,$splitpackat,$numpacks-1,"None","REGULAR","True");
							$iTotItems++;
							$iWeight -= ($splitpackat*($numpacks-1));
							$rowcounter++;
						}
						if($international != "")
							$sXML .= addInternational($rowcounter,$iWeight,1,"Package",$shipcountry);
						else
							$sXML .= addDomestic($rowcounter,"Parcel",$origZip,$destZip,$iWeight,1,"None","REGULAR","True");
						$rowcounter++;
					}
				}else{
					if($shipThisProd){
						$somethingToShip=TRUE;
						$iWeight=$rsCart["pWeight"];
						$numpacks=1;
						if(@$splitpackat != "")
							if($iWeight > $splitpackat) $numpacks=ceil($iWeight/$splitpackat);
						if($numpacks > 1){
							if($international != "")
								$sXML .= addInternational($rowcounter,$splitpackat,$rsCart["cartQuantity"]*($numpacks-1),"Package",$shipcountry);
							else
								$sXML .= addDomestic($rowcounter,"Parcel",$origZip,$destZip,$splitpackat,$rsCart["cartQuantity"]*($numpacks-1),"None","REGULAR","True");
							$iTotItems++;
							$iWeight -= ($splitpackat*($numpacks-1));
							$rowcounter++;
						}
						if($international != "")
							$sXML .= addInternational($rowcounter,$iWeight,$rsCart["cartQuantity"],"Package",$shipcountry);
						else
							$sXML .= addDomestic($rowcounter,"Parcel",$origZip,$destZip,$iWeight,$rsCart["cartQuantity"],"None","REGULAR","True");
						$rowcounter++;
					}
				}
			}elseif(($shipType==4 || $shipType==6) && @$_POST["shipping"]==""){ // UPS Shipping
				if(@$upspacktype=="") $upspacktype="02";
				if($packtogether){
					if($shipThisProd){
						$somethingToShip=TRUE;
						$iWeight += ((double)$rsCart["pWeight"] * (int)$rsCart["cartQuantity"]);
					}
					if(($index == $itemsincart) && $somethingToShip){
						$numpacks=1;
						if(@$splitpackat != "")
							if($iWeight > $splitpackat)
								$numpacks=ceil($iWeight/$splitpackat);
						for($index3=0;$index3 < $numpacks; $index3++)
							if($shipType==4)
								$sXML .= addUPSInternational($iWeight / $numpacks,$adminUnits,$upspacktype,$shipCountryCode,$totalgoods-$shipfreegoods);
							else
								$sXML .= addCanadaPostPackage($iWeight / $numpacks,$adminUnits,$upspacktype,$shipCountryCode,$totalgoods-$shipfreegoods, "");
					}
				}else{
					if($shipThisProd){
						$somethingToShip=TRUE;
						$iWeight=$rsCart["pWeight"];
						$numpacks=1;
						if(@$splitpackat != "")
							if($iWeight > $splitpackat)
								$numpacks=ceil($iWeight/$splitpackat);
						for($index2=0;$index2 < (int)$rsCart["cartQuantity"]; $index2++)
							for($index3=0;$index3 < $numpacks; $index3++)
								if($shipType==4)
									$sXML .= addUPSInternational($iWeight / $numpacks,$adminUnits,$upspacktype,$shipCountryCode,$rsCart["cartProdPrice"]);
								else
									$sXML .= addCanadaPostPackage($iWeight / $numpacks,$adminUnits,$upspacktype,$shipCountryCode,$rsCart["cartProdPrice"],$rsCart["pDims"]);
					}
				}
			}
		}
		calculatediscounts(round($totalgoods,2), true, $cpncode);
		if(@$_POST["shipping"] != ""){
			$shipArr = split('\|',$_POST["shipping"],3);
			$shipping = (double)$shipArr[0];
			$isstandardship = ((int)$shipArr[1]==1);
			$shipMethod = $shipArr[2];
		}elseif($shipType==1){
			$isstandardship = TRUE;
		}elseif(($shipType==2 || $shipType==5) && ($somethingToShip || @$willpickuptext != "")){
			$checkIntOptions = (@$_POST["shipping"]=="");
			if(is_array($allzones)){
				$shipping = $intShipping[0][2];
				$shipMethod = $intShipping[0][0];
				$isstandardship = (($pzFSA & 1) == 1);
				if($numshipoptions == 1 && @$willpickuptext=="")
					$checkIntOptions = FALSE;
			}else{
				if(@$willpickuptext != ""){
					if(@$willpickupcost != "") $shipping = $willpickupcost;
					$shipMethod = $willpickuptext;
				}else
					$checkIntOptions = FALSE;
			}
		}elseif($shipType==3 && $somethingToShip){
			$checkIntOptions = (@$_POST["shipping"]=="");
			if(@$_POST["shipping"]==""){
				$sXML .= "</" . $international . "RateRequest>";
				$success = USPSCalculate($sXML,$international,$shipping, $errormsg, $intShipping);
				if(substr($errormsg, 0, 30)=="Warning - Bound Printed Matter") $success=true;
				if($success && $checkIntOptions){ // Look for a single valid shipping option
					$totShipOptions = 0;
					foreach($intShipping as $shipRow){
						if($iTotItems==$shipRow[3]){
							for($index2=0;$index2<$numuspsmeths;$index2++){
								if(trim($shipRow[0]) == trim($uspsmethods[$index2][0])){
									$totShipOptions++;
									$shipping = $shipRow[2];
									$shipMethod = trim($uspsmethods[$index2][2]);
									$isstandardship = (int)$uspsmethods[$index2][1];
								}
							}
						}
					}
					if($totShipOptions==1)
						$checkIntOptions=FALSE;
					elseif($totShipOptions==0 && @$willpickuptext==""){
						$checkIntOptions=FALSE;
						$success=FALSE;
						$errormsg=$xxNoMeth;
					}
					if(@$willpickuptext != "") $checkIntOptions = TRUE;
				}
				elseif(! $success)
					$errormsg = "USPS error: " . $errormsg;
			}
		}elseif($shipType==4 && $somethingToShip){
			$checkIntOptions = (@$_POST["shipping"]=="");
			if(@$_POST["shipping"]==""){
				$sXML .= "<ShipmentServiceOptions/></Shipment></RatingServiceSelectionRequest>";
				if(trim($upsUser) != "" && trim($upsPw) != "")
					$success = UPSCalculate($sXML,$international,$shipping, $errormsg, $intShipping);
				else{
					$success = FALSE;
					$errormsg = "You must register with UPS by logging on to your online admin section and clicking the &quot;Register with UPS&quot; link before you can use the UPS OnLine&reg; Shipping Rates and Services Selection";
				}
				if($success){
					$totShipOptions = 0;
					foreach($intShipping as $shipRow){
						if($shipRow[3]==TRUE){
							$totShipOptions++;
							$shipping = $shipRow[2];
							$shipMethod = $shipRow[1];
							$isstandardship = $shipRow[4];
						}
					}
					if($totShipOptions==1)
						$checkIntOptions=FALSE;
					elseif($totShipOptions == 0 && @$willpickuptext==""){
						$checkIntOptions = FALSE;
						$success=FALSE;
						$errormsg=$xxNoMeth;
					}
					if(@$willpickuptext != "") $checkIntOptions = TRUE;
				}
			}
		}elseif($shipType==6 && $somethingToShip){
			$checkIntOptions = (@$_POST["shipping"]=="");
			if(@$_POST["shipping"]==""){
				$sXML .= " </lineItems><city> </city> ";
				if($shipstate!="")
					$sXML .= "<provOrState> " . $shipstate . " </provOrState>";
				else{
					if($shipCountryCode=="US" || $shipCountryCode=="CA"){
						if(trim(@$_POST["sname"]) != "" || trim(@$_POST["saddress"]) != "")
							$sXML .= "<provOrState> " . @$_POST["sstate2"] . " </provOrState>";
						else
							$sXML .= "<provOrState> " . @$_POST["state2"] . " </provOrState>";
					}else
						$sXML .= "<provOrState> </provOrState>";
				}
				$sXML .= "<country>" . $shipCountryCode . "</country><postalCode>" . $destZip . "</postalCode></ratesAndServicesRequest></eparcel>";
				$success = CanadaPostCalculate($sXML,$international,$shipping, $errormsg, $intShipping);
				if($success){
					$totShipOptions = 0;
					foreach($intShipping as $shipRow){
						if($shipRow[3]==TRUE){
							$totShipOptions++;
							$shipping = $shipRow[2];
							$shipMethod = $shipRow[1];
							$isstandardship = $shipRow[4];
						}
					}
					if($totShipOptions==1)
						$checkIntOptions=FALSE;
					elseif($totShipOptions == 0 && @$willpickuptext==""){
						$checkIntOptions = FALSE;
						$success=FALSE;
						$errormsg=$xxNoMeth;
					}
					if(@$willpickuptext != "") $checkIntOptions = TRUE;
				}
			}
		}
		if(is_numeric(@$shipinsuranceamt) && trim(@$_POST["shipping"])=="" && $somethingToShip){
			if((trim(@$_POST["wantinsurance"])=="Y" && @$addshippinginsurance==2) || @$addshippinginsurance==1){
				for($index3=0; $index3 < $maxshipoptions; $index3++)
					$intShipping[$index3][2] += (((double)$totalgoods*(double)$shipinsuranceamt)/100.0);
				$shipping += (((double)$totalgoods*(double)$shipinsuranceamt)/100.0);
			}elseif((trim(@$_POST["wantinsurance"])=="Y" && @$addshippinginsurance==-2) || @$addshippinginsurance==-1){
				for($index3=0; $index3 < $maxshipoptions; $index3++)
					$intShipping[$index3][2] += $shipinsuranceamt;
				$shipping += $shipinsuranceamt;
			}
		}
		if(@$taxShipping==1 && trim(@$_POST["shipping"])==""){
			for($index3=0; $index3 < $maxshipoptions; $index3++)
				$intShipping[$index3][2] += ((double)$intShipping[$index3][2]*((double)$stateTaxRate+(double)$countryTaxRate))/100.0;
			$shipping += ((double)$shipping*((double)$stateTaxRate+(double)$countryTaxRate))/100.0;
		}
		if(@$taxHandling==1){
			$handling += ((double)$handling*((double)$stateTaxRate+(double)$countryTaxRate))/100.0;
		}
		if(! $checkIntOptions){
			$freeshipamnt = 0;
			calculateshippingdiscounts(true);
			if(@$_SESSION["clientUser"] != "" && @$_SESSION["clientActions"] != 0) $cpnmessage .= $xxLIDis . $_SESSION["clientUser"] . "<br />";
			$cpnmessage = substr($cpnmessage,6);
			if($totaldiscounts > $totalgoods) $totaldiscounts = $totalgoods;
			if($freeshipamnt > $shipping) $freeshipamnt = $shipping;
			$usehst=false;
			if(@$canadataxsystem==true && $shipCountryID==2 && ($shipStateAbbrev=="NB" || $shipStateAbbrev=="NF" || $shipStateAbbrev=="NS"))
				$usehst=true;
			if(@$canadataxsystem==true && $shipCountryID==2 && ($shipStateAbbrev=="PE" || $shipStateAbbrev=="QC")){
				$statetaxable = 0;
				$countrytaxable = 0;
				if(@$taxShipping==2 && ($shipping - $freeshipamnt > 0)){
					$statetaxable += ((double)$shipping-(double)$freeshipamnt);
					$countrytaxable += ((double)$shipping-(double)$freeshipamnt);
				}
				if(@$taxHandling==2){
					$statetaxable += (double)$handling;
					$countrytaxable += (double)$handling;
				}
				if($totalgoods>0){
					$statetaxable += ((double)$totalgoods-((double)$totaldiscounts+(double)$statetaxfree));
					$countrytaxable += ((double)$totalgoods-((double)$totaldiscounts+(double)$countrytaxfree));
				}
				$countryTax = $countrytaxable*(double)$countryTaxRate/100.0;
				$stateTax = ($statetaxable+(double)$countryTax)*(double)$stateTaxRate/100.0;
			}else{
				if($totalgoods>0){
					$stateTax = ((double)$totalgoods-((double)$totaldiscounts+(double)$statetaxfree))*(double)$stateTaxRate/100.0;
					if(@$perproducttaxrate != TRUE) $countryTax = ((double)$totalgoods-((double)$totaldiscounts+(double)$countrytaxfree))*(double)$countryTaxRate/100.0;
				}
				if(@$taxShipping==2 && ($shipping - $freeshipamnt > 0)){
					$stateTax += (((double)$shipping-(double)$freeshipamnt)*(double)$stateTaxRate/100.0);
					$countryTax += (((double)$shipping-(double)$freeshipamnt)*(double)$countryTaxRate/100.0);
				}
				if(@$taxHandling==2){
					$stateTax += ((double)$handling*(double)$stateTaxRate/100.0);
					$countryTax += ((double)$handling*(double)$countryTaxRate/100.0);
				}
			}
			$totalgoods = round($totalgoods,2);
			$shipping = round($shipping,2);
			$stateTax = round($stateTax,2);
			$countryTax = round($countryTax,2);
			$handling = round($handling,2);
			if($stateTax < 0) $stateTax = 0;
			if($countryTax < 0) $countryTax = 0;
			$totaldiscounts += $freeshipamnt;
			$totaldiscounts = round($totaldiscounts, 2);
			$grandtotal = round(($totalgoods + $shipping + $stateTax + $countryTax + $handling) - $totaldiscounts, 2);
			if($grandtotal < 0) $grandtotal = 0;
			$sSQL = "SELECT ordID FROM orders WHERE ordSessionID='" . trim(@$_POST["sessionid"]) . "' AND ordAuthNumber=''";
			$result = mysql_query($sSQL) or print(mysql_error());
			if($rs = mysql_fetch_array($result))
				$orderid=$rs["ordID"];
			else
				$orderid="";
			mysql_free_result($result);
			$ordComLoc = 0;
			if($orderid==""){
				$sSQL = "INSERT INTO orders (ordSessionID,ordName,ordAddress,ordCity,ordState,ordZip,ordCountry,ordEmail,ordPhone,ordShipName,ordShipAddress,ordShipCity,ordShipState,ordShipZip,ordShipCountry,ordPayProvider,ordAuthNumber,ordShipping,ordStateTax,ordCountryTax,ordHSTTax,ordHandling,ordShipType,ordTotal,ordDate,ordStatus,ordStatusDate,ordComLoc,ordIP,ordAffiliate,ordExtra1,ordExtra2,ordDiscount,ordDiscountText,ordAddInfo) VALUES (";
				$sSQL .= "'" . mysql_escape_string(trim(@$_POST["sessionid"])) . "',";
				$sSQL .= "'" . mysql_escape_string(trim(unstripslashes(@$_POST["name"]))) . "',";
				$sSQL .= "'" . mysql_escape_string(trim(unstripslashes(@$_POST["address"]))) . "',";
				$sSQL .= "'" . mysql_escape_string(trim(unstripslashes(@$_POST["city"]))) . "',";
				if(trim(@$_POST["state"]) != "")
					$sSQL .= "'" . mysql_escape_string(trim(@$_POST["state"])) . "',";
				else
					$sSQL .= "'" . mysql_escape_string(trim(@$_POST["state2"])) . "',";
				$sSQL .= "'" . mysql_escape_string(trim(@$_POST["zip"])) . "',";
				$sSQL .= "'" . mysql_escape_string(trim(@$_POST["country"])) . "',";
				$sSQL .= "'" . mysql_escape_string(trim(@$_POST["email"])) . "',";
				$sSQL .= "'" . mysql_escape_string(trim(@$_POST["phone"])) . "',";
				$sSQL .= "'" . mysql_escape_string(trim(unstripslashes(@$_POST["sname"]))) . "',";
				$sSQL .= "'" . mysql_escape_string(trim(unstripslashes(@$_POST["saddress"]))) . "',";
				$sSQL .= "'" . mysql_escape_string(trim(unstripslashes(@$_POST["scity"]))) . "',";
				if(trim(@$_POST["sstate"]) != "")
					$sSQL .= "'" . mysql_escape_string(trim(unstripslashes(@$_POST["sstate"]))) . "',";
				else
					$sSQL .= "'" . mysql_escape_string(trim(unstripslashes(@$_POST["sstate2"]))) . "',";
				$sSQL .= "'" . mysql_escape_string(trim(@$_POST["szip"])) . "',";
				$sSQL .= "'" . mysql_escape_string(trim(unstripslashes(@$_POST["scountry"]))) . "',";
				$sSQL .= "'" . mysql_escape_string(trim(@$_POST["payprovider"])) . "',";
				$sSQL .= "'',";
				$sSQL .= "'" . mysql_escape_string($shipping) . "',";
				if($usehst){
					$sSQL .= "0,";
					$sSQL .= "0,";
					$sSQL .= ($stateTax + $countryTax) . ",";
				}else{
					$sSQL .= "'" . mysql_escape_string($stateTax) . "',";
					$sSQL .= "'" . mysql_escape_string($countryTax) . "',";
					$sSQL .= "0,";
				}
				$sSQL .= "'" . mysql_escape_string($handling) . "',";
				$sSQL .= "'" . mysql_escape_string($shipMethod) . "',";
				$sSQL .= "'" . mysql_escape_string($totalgoods) . "',";
				$sSQL .= "'" . date("Y-m-d H:i:s", time() + ($dateadjust*60*60)) . "',";
				$sSQL .= "2,"; // Status
				$sSQL .= "'" . date("Y-m-d H:i:s", time() + ($dateadjust*60*60)) . "',";
				if(trim(@$_POST["commercialloc"])=="Y") $ordComLoc = 1;
				if(trim(@$_POST["wantinsurance"])=="Y" || abs(@$addshippinginsurance)==1) $ordComLoc += 2;
				$sSQL .= "'" . $ordComLoc . "',";
				$sSQL .= "'" . @$_SERVER["REMOTE_ADDR"] . "',";
				$sSQL .= "'" . mysql_escape_string(trim(@$_POST["PARTNER"])) . "',";
				$sSQL .= "'" . mysql_escape_string(trim(unstripslashes(@$_POST["ordextra1"]))) . "',";
				$sSQL .= "'" . mysql_escape_string(trim(unstripslashes(@$_POST["ordextra2"]))) . "',";
				$sSQL .= "'" . mysql_escape_string($totaldiscounts) . "',";
				$sSQL .= "'" . mysql_escape_string(trim(substr(unstripslashes($cpnmessage),0,255))) . "',";
				$sSQL .= "'" . mysql_escape_string(trim(unstripslashes(@$_POST["ordAddInfo"]))) . "')";
				mysql_query($sSQL) or print(mysql_error());
				$orderid = mysql_insert_id();
			}else{
				$sSQL = "UPDATE orders SET ";
				$sSQL .= "ordSessionID='" . mysql_escape_string(trim(@$_POST["sessionid"])) . "',";
				$sSQL .= "ordName='" . mysql_escape_string(trim(unstripslashes(@$_POST["name"]))) . "',";
				$sSQL .= "ordAddress='" . mysql_escape_string(trim(unstripslashes(@$_POST["address"]))) . "',";
				$sSQL .= "ordCity='" . mysql_escape_string(trim(unstripslashes(@$_POST["city"]))) . "',";
				if(trim(@$_POST["state"]) != "")
					$sSQL .= "ordState='" . mysql_escape_string(unstripslashes(trim(@$_POST["state"]))) . "',";
				else
					$sSQL .= "ordState='" . mysql_escape_string(unstripslashes(trim(@$_POST["state2"]))) . "',";
				$sSQL .= "ordZip='" . mysql_escape_string(unstripslashes(trim(@$_POST["zip"]))) . "',";
				$sSQL .= "ordCountry='" . mysql_escape_string(unstripslashes(trim(@$_POST["country"]))) . "',";
				$sSQL .= "ordEmail='" . mysql_escape_string(unstripslashes(trim(@$_POST["email"]))) . "',";
				$sSQL .= "ordPhone='" . mysql_escape_string(unstripslashes(trim(@$_POST["phone"]))) . "',";
				$sSQL .= "ordShipName='" . mysql_escape_string(trim(unstripslashes(@$_POST["sname"]))) . "',";
				$sSQL .= "ordShipAddress='" . mysql_escape_string(trim(unstripslashes(@$_POST["saddress"]))) . "',";
				$sSQL .= "ordShipCity='" . mysql_escape_string(trim(unstripslashes(@$_POST["scity"]))) . "',";
				if(trim(@$_POST["sstate"]) != "")
					$sSQL .= "ordShipState='" . mysql_escape_string(unstripslashes(trim(@$_POST["sstate"]))) . "',";
				else
					$sSQL .= "ordShipState='" . mysql_escape_string(unstripslashes(trim(@$_POST["sstate2"]))) . "',";
				$sSQL .= "ordShipZip='" . mysql_escape_string(unstripslashes(trim(@$_POST["szip"]))) . "',";
				$sSQL .= "ordShipCountry='" . mysql_escape_string(unstripslashes(trim(@$_POST["scountry"]))) . "',";
				$sSQL .= "ordPayProvider='" . mysql_escape_string(unstripslashes(trim(@$_POST["payprovider"]))) . "',";
				$sSQL .= "ordAuthNumber='',"; // Not yet authorized
				$sSQL .= "ordShipping='" . $shipping . "',";
				if($usehst){
					$sSQL .= "ordStateTax=0,";
					$sSQL .= "ordCountryTax=0,";
					$sSQL .= "ordHSTTax=" . ($stateTax + $countryTax) . ",";
				}else{
					$sSQL .= "ordStateTax='" . $stateTax . "',";
					$sSQL .= "ordCountryTax='" . $countryTax . "',";
					$sSQL .= "ordHSTTax=0,";
				}
				$sSQL .= "ordHandling='" . $handling . "',";
				$sSQL .= "ordShipType='" . $shipMethod . "',";
				$sSQL .= "ordTotal='" . $totalgoods . "',";
				$sSQL .= "ordDate='" . date("Y-m-d H:i:s", time() + ($dateadjust*60*60)) . "',";
				if(trim(@$_POST["commercialloc"])=="Y") $ordComLoc = 1;
				if(trim(@$_POST["wantinsurance"])=="Y" || abs(@$addshippinginsurance)==1) $ordComLoc += 2;
				$sSQL .= "ordComLoc=" . $ordComLoc . ",";
				$sSQL .= "ordIP='" . @$_SERVER["REMOTE_ADDR"] . "',";
				$sSQL .= "ordAffiliate='" . trim(@$_POST["PARTNER"]) . "',";
				$sSQL .= "ordExtra1='" . mysql_escape_string(unstripslashes(trim(@$_POST["ordextra1"]))) . "',";
				$sSQL .= "ordExtra2='" . mysql_escape_string(unstripslashes(trim(@$_POST["ordextra2"]))) . "',";
				$sSQL .= "ordDiscount='" . $totaldiscounts . "',";
				$sSQL .= "ordDiscountText='" . mysql_escape_string(trim(substr(unstripslashes($cpnmessage),0,255))) . "',";
				$sSQL .= "ordAddInfo='" . mysql_escape_string(trim(unstripslashes(@$_POST["ordAddInfo"]))) . "'";
				$sSQL .= " WHERE ordID='" . $orderid . "'";
				mysql_query($sSQL) or print(mysql_error());
			}
			$sSQL="UPDATE cart SET cartOrderID=". $orderid . " WHERE cartCompleted=0 AND cartSessionID='" . mysql_escape_string(trim(@$_POST["sessionid"])) . "'";
			mysql_query($sSQL) or print(mysql_error());
			$descstr="";
			$addcomma = "";
			$sSQL="SELECT cartQuantity,cartProdName FROM cart WHERE cartOrderID=" . $orderid . " AND cartCompleted=0";
			$result = mysql_query($sSQL) or print(mysql_error());
			while($rs=mysql_fetch_assoc($result)){
				$descstr .= $addcomma . $rs["cartQuantity"] . " " . $rs["cartProdName"];
				$addcomma = ", ";
			}
			mysql_free_result($result);
			$descstr = str_replace('"','',$descstr);
			if(@$_POST["remember"]=="1")
				print "<script src='vsadmin/savecookie.php?id1=" . $orderid . "&id2=" . trim(@$_POST["sessionid"]) . "'></script>";
		}
	}else{
		$success=FALSE;
	}
	if($checkIntOptions && $success){
		$success = FALSE; // So not to print the order totals.
?>
	<br />
	<form method="post" action="cart.php">
	  <?php
		foreach(@$_POST as $objItem => $objValue)
			print "<input type='hidden' name='" . $objItem . "' value=\"" . str_replace('"','&quot;',unstripslashes($objValue)) . "\" />\n";
	  ?>
            <table class="cobtbl" width="<?php print $maintablewidth?>" border="0" bordercolor="#B1B1B1" cellspacing="1" cellpadding="3" bgcolor="#B1B1B1">
			  <tr>
			    <td height="34" align="center" class="cobhl" bgcolor="#EBEBEB"><strong><?php print $xxShpOpt?></strong></td>
			  </tr>
			  <tr>
				<td height="34" align="center" class="cobll" bgcolor="#FFFFFF">
				  <table width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#FFFFFF">
					<tr>
					  <td height="34" align="right" width="50%" class="cobll" bgcolor="#FFFFFF"><?php if($shipType==4) print '<img src="images/LOGO_S.gif" alt="UPS" />&nbsp;&nbsp;'; else print "&nbsp;"; ?></td>
					  <td height="34" align="center" class="cobll" bgcolor="#FFFFFF"><?php
						calculateshippingdiscounts(false);
						print "<select name='shipping' size='1'>";
						if($shipType==2 || $shipType==5){
							if(is_array($allzones)){
								for($index3=0; $index3 < $numshipoptions; $index3++){
									print "<option value='" . $intShipping[$index3][2] . "|" . (($pzFSA & pow(2, $index3))!=0?"1":"0") . "|" . $intShipping[$index3][0] . "'>";
									print ($freeshippingapplied && ($pzFSA & pow(2, $index3))!=0 ? $xxFree . " " . $intShipping[$index3][0] : $intShipping[$index3][0] . " " . FormatEuroCurrency($intShipping[$index3][2])) . '</option>';
								}
							}
						}else{
							for($index2=0; $index2 < 20; $index2++){
								$intShipping[$index2][2] = (double)$intShipping[$index2][2];
								for($index=1; $index < 20; $index++){
									if((double)$intShipping[$index][2] < (double)$intShipping[$index-1][2]){
										$tt = $intShipping[$index];
										$intShipping[$index] = $intShipping[$index-1];
										$intShipping[$index-1] = $tt;
									}
								}
							}
							foreach($intShipping as $shipRow){
								if($shipType==3){
									if($iTotItems==$shipRow[3]){
										for($index2=0;$index2<$numuspsmeths;$index2++){
											if(trim($shipRow[0]) == trim($uspsmethods[$index2][0])){
												print "<option value='" . $shipRow[2] . "|". trim($uspsmethods[$index2][1]) ."|" . trim($uspsmethods[$index2][2]) . "'" . (freeshippingapplied && $uspsmethods[$index2][1]==1 ? " selected>" : ">");
												print trim($uspsmethods[$index2][2]) . " (" . $shipRow[1] . ") " . ($freeshippingapplied && $uspsmethods[$index2][1]==1 ? $xxFree : FormatEuroCurrency($shipRow[2]));
												print "</option>";
											}
										}
									}
								}elseif($shipType==4 || $shipType==6){
									if($shipRow[3]){
										print "<option value='" . $shipRow[2] . "|". $shipRow[4] ."|" . $shipRow[0] . "'" . ($freeshippingapplied && $shipRow[4]==1 ? " selected>" : ">") . $shipRow[0] . " ";
										if(trim($shipRow[1]) != "") print "(" . $xxGuar . " " . $shipRow[1] . ") ";
										print ($freeshippingapplied && $shipRow[4]==1 ? $xxFree : FormatEuroCurrency($shipRow[2]));
										print "</option>";
									}
								}
							}
						}
						if(@$willpickuptext != ""){
							if(@$willpickupcost=="") $willpickupcost=0;
							print '<option value="' . $willpickupcost . "|1|" . str_replace('"','&quot;',$willpickuptext) . '">';
							print $willpickuptext . " " . FormatEuroCurrency($willpickupcost) . "</option>";
						}
						print "</select>";
					?></td>
					  <td height="34" align="left" width="50%" class="cobll" bgcolor="#FFFFFF">&nbsp;</td>
					</tr>
				  </table>
				</td>
			  </tr>
			  <tr>
			    <td height="34" align="center" class="cobll" bgcolor="#FFFFFF"><table width="100%" cellspacing="0" cellpadding="0" border="0">
				    <tr>
					  <td class="cobll" bgcolor="#FFFFFF" width="16" height="26" align="right" valign="bottom">&nbsp;</td>
					  <td class="cobll" bgcolor="#FFFFFF" width="100%" align="center"><input type="image" value="Checkout" border="0" src="images/checkout.gif" /></td>
					  <td class="cobll" bgcolor="#FFFFFF" width="16" height="26" align="right" valign="bottom"><img src="images/tablebr.gif" alt="" /></td>
					</tr>
				  </table>
				</td>
			  </tr>
			</table>
		<?php if($shipType==4){ ?>
			<p align="center">&nbsp;<br /><font size="1">UPS&reg;, UPS & Shield Design&reg; and UNITED PARCEL SERVICE&reg; 
			  are<br />registered trademarks of United Parcel Service of America, Inc.</font></p>
		<?php } ?>
	</form>
<?php
	}elseif(! $success){
?>
      <table border="0" cellspacing="<?php print $maintablespacing?>" cellpadding="<?php print $maintablepadding?>" width="<?php print $maintablewidth?>" bgcolor="<?php print $maintablebg?>" align="center">
        <tr> 
          <td width="100%">
            <table width="<?php print $innertablewidth?>" border="0" cellspacing="<?php print $innertablespacing?>" cellpadding="<?php print $innertablepadding?>" bgcolor="<?php print $innertablebg?>">
			  <tr>
			    <td align="center"><p>&nbsp;</p><p><strong><?php print $xxSryErr?></strong></p><p><strong><?php print "<br />" . $errormsg ?></strong></p><p>&nbsp;</p></td>
			  </tr>
			</table>
		  </td>
        </tr>
      </table>
<?php
	}elseif(trim(@$_POST["payprovider"]) != ""){
		$blockuser=checkuserblock(@$_POST["payprovider"]);
		if($blockuser){
			$orderid = 0;
			$thesessionid = "";
		}else{
			$sSQL = "SELECT payProvDemo,payProvData1,payProvData2,payProvMethod FROM payprovider WHERE payProvID=" . @$_POST["payprovider"];
			$result = mysql_query($sSQL) or print(mysql_error());
			$rs = mysql_fetch_array($result);
			$demomode = ((int)$rs["payProvDemo"]==1);
			$data1 = $rs["payProvData1"];
			$data2 = $rs["payProvData2"];
			$ppmethod = (int)$rs["payProvMethod"];
			mysql_free_result($result);
		}
		if($grandtotal > 0 && @$_POST["payprovider"]=="1"){ // PayPal
?>
	<form method="post" action="https://www.paypal.com/cgi-bin/webscr">
	<input type="hidden" name="cmd" value="_ext-enter" />
	<input type="hidden" name="redirect_cmd" value="_xclick" />
	<input type="hidden" name="business" value="<?php print $data1?>" />
	<input type="hidden" name="return" value="<?php print $storeurl?>thanks.php" />
	<input type="hidden" name="notify_url" value="<?php print $storeurl?>vsadmin/ppconfirm.php" />
	<input type="hidden" name="item_name" value="<?php print substr($descstr,0,127)?>" />
	<input type="hidden" name="custom" value="<?php print $orderid?>" />
	<input type="hidden" name="amount" value="<?php print number_format($grandtotal,2,'.','')?>" />
	<input type="hidden" name="currency_code" value="<?php print $countryCurrency?>" />
	<input type="hidden" name="bn" value="ecommercetemplates.php.ecommplus" />
<?php		$thename = trim(@$_POST["name"]);
			if($thename != ""){
				if(strstr($thename," ")){
					$namearr = split(" ",$thename,2);
					print '<input type="hidden" name="first_name" value="' . $namearr[0] . "\" />\n";
					print '<input type="hidden" name="last_name" value="' . $namearr[1] . "\" />\n";
				}else
					print '<input type="hidden" name="last_name" value="' . $thename . "\" />\n";
			}
?>
	<input type="hidden" name="address1" value="<?php print @$_POST["address"]?>" />
	<input type="hidden" name="city" value="<?php print @$_POST["city"]?>" />
<?php		if($countryID==1 && $stateAbbrev != ""){ ?>
	<input type="hidden" name="state" value="<?php print $stateAbbrev?>" />
<?php		}else{ ?>
	<input type="hidden" name="state" value="<?php if(trim(@$_POST["state"]) != "") print unstripslashes(@$_POST["state"]); else print unstripslashes(@$_POST["state2"]);?>" />
<?php		} ?>
	<input type="hidden" name="country" value="<?php print $countryCode?>" />
	<input type="hidden" name="email" value="<?php print @$_POST["email"]?>" />
	<input type="hidden" name="zip" value="<?php print @$_POST["zip"]?>" />
	<input type="hidden" name="cancel_return" value="<?php print $storeurl?>sorry.php" />
<?php	}elseif($grandtotal > 0 && @$_POST["payprovider"]=="2"){ // 2Checkout
			$courl='https://www.2checkout.com/cgi-bin/sbuyers/cartpurchase.2c';
			if(is_numeric($data1))
				if($data1>200000 || @$use2checkoutv2==TRUE) $courl='https://www2.2checkout.com/2co/buyer/purchase';
?>
	<form method="post" action="<?php print $courl?>">
	<input type="hidden" name="cart_order_id" value="<?php print $orderid?>" />
	<input type="hidden" name="sid" value="<?php print $data1?>" />
	<input type="hidden" name="total" value="<?php print $grandtotal?>" />
	<input type="hidden" name="card_holder_name" value="<?php print @$_POST["name"]?>" />
	<input type="hidden" name="street_address" value="<?php print @$_POST["address"]?>" />
	<input type="hidden" name="city" value="<?php print @$_POST["city"]?>" />
	<input type="hidden" name="state" value="<?php if(trim(@$_POST["state"]) != "") print unstripslashes(@$_POST["state"]); else print unstripslashes(@$_POST["state2"]);?>" />
	<input type="hidden" name="zip" value="<?php print @$_POST["zip"]?>" />
	<input type="hidden" name="country" value="<?php print @$_POST["country"]?>" />
	<input type="hidden" name="email" value="<?php print @$_POST["email"]?>" />
	<input type="hidden" name="phone" value="<?php print @$_POST["phone"]?>" />
	<input type="hidden" name="id_type" value="1" />
<?php		$sSQL = "SELECT cartID,cartProdID,pName,pPrice,cartQuantity," . (@$digidownloads==TRUE ? "pDownload," : "") . "pDescription FROM cart INNER JOIN products on cart.cartProdID=products.pID WHERE cartCompleted=0 AND cartSessionID='" .  $thesessionid . "'";
			$result = mysql_query($sSQL) or print(mysql_error());
			$index=1;
			while($rs=mysql_fetch_assoc($result)){
				$thedesc = substr(trim(preg_replace("(\r\n|\n|\r)",'\\n',strip_tags($rs["pDescription"]))),0,254);
				if($thedesc=="") $thedesc = substr(trim(preg_replace("(\r\n|\n|\r)",'\\n',strip_tags($rs["pName"]))),0,254);
				print '<input type="hidden" name="c_prod_' . $index . '" value="' . str_replace(',','&#44;',str_replace('"','&quot;',$rs["cartProdID"])) . "," . $rs["cartQuantity"] . "\" />\r\n";
				print '<input type="hidden" name="c_name_' . $index . '" value="' . str_replace('"','&quot;',$rs["pName"]) . "\" />\r\n";
				print '<input type="hidden" name="c_description_' . $index . '" value="' . str_replace('"','&quot;',$thedesc) . "\" />\r\n";
				print '<input type="hidden" name="c_price_' . $index . '" value="' . number_format($rs["pPrice"],2,'.','') . "\" />\r\n";
				if(@$digidownloads==TRUE)
					if(trim($rs["pDownload"]) != "") print '<input type="hidden" name="c_tangible_' . $index . '" value="N" />' . "\r\n";
				$index++;
			}
			if(trim(@$_POST["sname"]) != "" || trim(@$_POST["saddress"]) != ""){ ?>
	  <input type="hidden" name="ship_name" value="<?php print @$_POST["sname"]?>" />
	  <input type="hidden" name="ship_street_address" value="<?php print @$_POST["saddress"]?>" />
	  <input type="hidden" name="ship_city" value="<?php print @$_POST["scity"]?>" />
	  <input type="hidden" name="ship_state" value="<?php print @$_POST["sstate"]?>" />
	  <input type="hidden" name="ship_zip" value="<?php print @$_POST["szip"]?>" />
	  <input type="hidden" name="ship_country" value="<?php print @$_POST["scountry"]?>" />
<?php		}
			if($demomode)
				print "<input type=\"hidden\" name=\"demo\" value=\"Y\" />";
		}elseif($grandtotal > 0 && @$_POST["payprovider"]=="3"){ // Authorize.net SIM
			if(@$secretword != ""){
				$data1 = upsdecode($data1, $secretword);
				$data2 = upsdecode($data2, $secretword);
			} ?>
	<FORM METHOD=POST ACTION="https://secure.authorize.net/gateway/transact.dll">
	<input type="hidden" name="x_Version" value="3.0" />
	<input type="hidden" name="x_Login" value="<?php print $data1?>" />
	<input type="hidden" name="x_Show_Form" value="PAYMENT_FORM" />
<?php
	  if($ppmethod==1) print '<input type="hidden" name="x_type" value="AUTH_ONLY" />';
		function vrhmac($key, $text){
			$idatastr = "                                                                ";
			$odatastr = "                                                                ";
			$hkey = (string)$key;
			$idatastr .= $text;
			for($i=0; $i<64; $i++){
				$idata[$i] = $ipad[$i] = 0x36;
				$odata[$i] = $opad[$i] = 0x5C;
			}
			for($i=0; $i< strlen($hkey); $i++){
				$ipad[$i] ^= ord($hkey{$i});
				$opad[$i] ^= ord($hkey{$i});
				$idata[$i] = ($ipad[$i] & 0xFF);
				$odata[$i] = ($opad[$i] & 0xFF);
			}
			for($i=0; $i< strlen($text); $i++){
				$idata[64+$i] = ord($text{$i}) & 0xFF;
			}
			for($i=0; $i< strlen($idatastr); $i++){
				$idatastr{$i} = chr($idata[$i] & 0xFF);
			}
			for($i=0; $i< strlen($odatastr); $i++){
				$odatastr{$i} = chr($odata[$i] & 0xFF);
			}
			$innerhashout = md5($idatastr);
			for($i=0; $i<16; $i++)
				$odatastr .= chr(hexdec(substr($innerhashout,$i*2,2)));
			return md5($odatastr);
		}
		$thename = unstripslashes(trim(@$_POST["name"]));
		if($thename != ""){
			if(strstr($thename," ")){
				$namearr = split(" ",$thename,2);
				print '<input type="hidden" name="x_First_Name" value="' . str_replace('"','&quot;',$namearr[0]) . "\" />\n";
				print '<input type="hidden" name="x_Last_Name" value="' . str_replace('"','&quot;',$namearr[1]) . "\" />\n";
			}else
				print '<input type="hidden" name="x_Last_Name" value="' . str_replace('"','&quot;',$thename) . "\" />\n";
		}
		$sequence = $orderid;
		if(@$authnetadjust != "")
			$tstamp = time() + $authnetadjust;
		else
			$tstamp = time();
		$fingerprint = vrhmac($data2, $data1 . "^" . $sequence . "^" . $tstamp . "^" . number_format($grandtotal,2,'.','') . "^");
?>
	<input type="hidden" name="x_fp_sequence" value="<?php print $sequence?>" />
	<input type="hidden" name="x_fp_timestamp" value="<?php print $tstamp?>" />
	<input type="hidden" name="x_fp_hash" value="<?php print $fingerprint?>" />
	<input type="hidden" name="x_address" value="<?php print unstripslashes(@$_POST["address"])?>" />
	<input type="hidden" name="x_city" value="<?php print unstripslashes(@$_POST["city"])?>" />
	<input type="hidden" name="x_country" value="<?php print unstripslashes(@$_POST["country"])?>" />
	<input type="hidden" name="x_phone" value="<?php print unstripslashes(@$_POST["phone"])?>" />
	<input type="hidden" name="x_state" value="<?php if(trim(@$_POST["state"]) != "") print unstripslashes(@$_POST["state"]); else print unstripslashes(@$_POST["state2"]);?>" />
	<input type="hidden" name="x_zip" value="<?php print unstripslashes(@$_POST["zip"])?>" />
	<input type="hidden" name="x_cust_id" value="<?php print $orderid?>" />
	<input type="hidden" name="x_Invoice_Num" value="<?php print $orderid?>" />
	<input type="hidden" name="x_ect_ordid" value="<?php print $orderid?>" />
	<input type="hidden" name="x_Description" value="<?php print substr($descstr,0,255)?>" />
	<input type="hidden" name="x_email" value="<?php print unstripslashes(@$_POST["email"])?>" />
<?php		if(trim(@$_POST["sname"]) != "" || trim(@$_POST["saddress"]) != ""){
				$thename = trim(@$_POST["sname"]);
				if($thename != ""){
					if(strstr($thename," ")){
						$namearr = split(" ",$thename,2);
						print '<input type="hidden" name="x_Ship_To_First_Name" value="' . $namearr[0] . "\" />\n";
						print '<input type="hidden" name="x_Ship_To_Last_Name" value="' . $namearr[1] . "\" />\n";
					}else
						print '<input type="hidden" name="x_Ship_To_Last_Name" value="' . $thename . "\" />\n";
				} ?>
	<input type="hidden" name="x_ship_to_address" value="<?php print unstripslashes(@$_POST["saddress"])?>" />
	<input type="hidden" name="x_ship_to_city" value="<?php print unstripslashes(@$_POST["scity"])?>" />
	<input type="hidden" name="x_ship_to_country" value="<?php print unstripslashes(@$_POST["scountry"])?>" />
	<input type="hidden" name="x_ship_to_state" value="<?php if(trim(@$_POST["state"]) != "") print unstripslashes(@$_POST["sstate"]); else print unstripslashes(@$_POST["sstate2"]);?>" />
	<input type="hidden" name="x_ship_to_zip" value="<?php print unstripslashes(@$_POST["szip"])?>" />
<?php		} ?>
	<input type="hidden" name="x_Amount" value="<?php print number_format($grandtotal,2,'.','')?>" />
	<input type="hidden" name="x_Relay_Response" value="True" />
	<input type="hidden" name="x_Relay_URL" value="<?php print $storeurl?>vsadmin/wpconfirm.php" />
<?php		if($demomode){ ?>
	<input type="hidden" name="x_Test_Request" value="TRUE" />
<?php		}
		}elseif($grandtotal == 0 || @$_POST["payprovider"]=="4"){ // Email ?>
	<form method="post" action="thanks.php">
	<input type="hidden" name="emailorder" value="<?php print $orderid?>" />
	<input type="hidden" name="thesessionid" value="<?php print $thesessionid?>" />
<?php	}elseif($grandtotal > 0 && @$_POST["payprovider"]=="17"){ // Email 2 ?>
	<form method="post" action="thanks.php">
	<input type="hidden" name="secondemailorder" value="<?php print $orderid?>" />
	<input type="hidden" name="thesessionid" value="<?php print $thesessionid?>" />
<?php	}elseif($grandtotal > 0 && @$_POST["payprovider"]=="5"){ // WorldPay ?>
	<form method="post" action="https://select.worldpay.com/wcc/purchase">
	<input type="hidden" name="instId" value="<?php print $data1?>" />
	<input type="hidden" name="cartId" value="<?php print $orderid?>" />
	<input type="hidden" name="amount" value="<?php print number_format($grandtotal,2,'.','')?>" />
	<input type="hidden" name="currency" value="<?php print $countryCurrency?>" />
	<input type="hidden" name="desc" value="<?php print substr($descstr,0,255)?>" />
	<input type="hidden" name="name" value="<?php print @$_POST["name"]?>" />
	<input type="hidden" name="address" value="<?php print @$_POST["address"]?>&#10;<?php print @$_POST["city"]?>&#10;<?php
			if(trim(@$_POST["state"]) != "")
				print @$_POST["state"];
			else
				print @$_POST["state2"]; ?>" />
	<input type="hidden" name="postcode" value="<?php print @$_POST["zip"]?>" />
	<input type="hidden" name="country" value="<?php print $countryCode?>" />
	<input type="hidden" name="tel" value="<?php print @$_POST["phone"]?>" />
	<input type="hidden" name="email" value="<?php print @$_POST["email"]?>" />
	<input type="hidden" name="authMode" value="<?php if($ppmethod==1) print 'E'; else print 'A'; ?>" />
<?php		if($demomode){ ?>
	<input type="hidden" name="testMode" value="100" />
<?php		}
		}elseif($grandtotal > 0 && @$_POST["payprovider"]=="6"){ // NOCHEX ?>
	<form method="post" action="https://www.nochex.com/nochex.dll/checkout">
	<input type="hidden" name="email" value="<?php print $data1?>" />
	<input type="hidden" name="returnurl" value="<?php print $storeurl . (TRUE ? 'thanks.php?ncretval=' . $orderid . '&ncsessid=' . $thesessionid : '')?>" />
	<input type="hidden" name="responderurl" value="<?php print $storeurl?>vsadmin/ncconfirm.php" />
	<input type="hidden" name="description" value="<?php print substr($descstr,0,255)?>" />
	<input type="hidden" name="ordernumber" value="<?php print $orderid?>" />
	<input type="hidden" name="amount" value="<?php print number_format($grandtotal,2,'.','')?>" />
<?php	}elseif($grandtotal > 0 && @$_POST["payprovider"]=="7"){ // VeriSign Payflow Pro ?>
	<form method="post" action="cart.php" onsubmit="return isvalidcard(this)">
	<input type="hidden" name="mode" value="authorize" />
	<input type="hidden" name="method" value="payflowpro" />
	<input type="hidden" name="ordernumber" value="<?php print $orderid?>" />
<?php	}elseif($grandtotal > 0 && @$_POST["payprovider"]=="8"){ // VeriSign Payflow Link
			$paymentlink = 'https://payments.verisign.com/payflowlink';
			if($data2=="VSA") $paymentlink='https://payments.verisign.com.au/payflowlink'; ?>
	<form method="post" action="<?php print $paymentlink?>">
	<input type="hidden" name="LOGIN" value="<?php print $data1?>" />
	<input type="hidden" name="PARTNER" value="<?php print $data2?>" />
	<input type="hidden" name="CUSTID" value="<?php print $orderid?>" />
	<input type="hidden" name="AMOUNT" value="<?php print number_format($grandtotal,2,'.','')?>" />
	<input type="hidden" name="TYPE" value="S" />
	<input type="hidden" name="DESCRIPTION" value="<?php print substr($descstr,0,255)?>" />
	<input type="hidden" name="NAME" value="<?php print unstripslashes(@$_POST["name"])?>" />
	<input type="hidden" name="ADDRESS" value="<?php print unstripslashes(@$_POST["address"])?>" />
	<input type="hidden" name="CITY" value="<?php print unstripslashes(@$_POST["city"])?>" />
	<input type="hidden" name="STATE" value="<?php if(trim(@$_POST["state"]) != "") print unstripslashes(@$_POST["state"]); else print unstripslashes(@$_POST["state2"]);?>" />
	<input type="hidden" name="ZIP" value="<?php print unstripslashes(@$_POST["zip"])?>" />
	<input type="hidden" name="COUNTRY" value="<?php print unstripslashes(@$_POST["country"])?>" />
	<input type="hidden" name="EMAIL" value="<?php print unstripslashes(@$_POST["email"])?>" />
	<input type="hidden" name="PHONE" value="<?php print unstripslashes(@$_POST["phone"])?>" />
	<input type="hidden" name="METHOD" value="CC" />
	<input type="hidden" name="ORDERFORM" value="TRUE" />
	<input type="hidden" name="SHOWCONFIRM" value="FALSE" />
<?php		if(trim(@$_POST["sname"]) != "" || trim(@$_POST["saddress"]) != ""){ ?>
	<input type="hidden" name="NAMETOSHIP" value="<?php print unstripslashes(@$_POST["sname"])?>" />
	<input type="hidden" name="ADDRESSTOSHIP" value="<?php print unstripslashes(@$_POST["saddress"])?>" />
	<input type="hidden" name="CITYTOSHIP" value="<?php print unstripslashes(@$_POST["scity"])?>" />
	<input type="hidden" name="STATETOSHIP" value="<?php if(trim(@$_POST["state"]) != "") print unstripslashes(@$_POST["sstate"]); else print unstripslashes(@$_POST["sstate2"]);?>" />
	<input type="hidden" name="ZIPTOSHIP" value="<?php print unstripslashes(@$_POST["szip"])?>" />
	<input type="hidden" name="COUNTRYTOSHIP" value="<?php print unstripslashes(@$_POST["scountry"])?>" />
<?php		} ?>
<?php	}elseif($grandtotal > 0 && @$_POST["payprovider"]=="9"){ // SECPay ?>
	<form method="post" action="https://www.secpay.com/java-bin/ValCard">
	<input type="hidden" name="merchant" value="<?php print $data1?>" />
	<input type="hidden" name="trans_id" value="<?php print $orderid?>" />
	<input type="hidden" name="amount" value="<?php print number_format($grandtotal,2,'.','')?>" />
	<input type="hidden" name="callback" value="<?php print $storeurl?>vsadmin/wpconfirm.php" />
	<input type="hidden" name="currency" value="<?php print $countryCurrency?>" />
	<input type="hidden" name="cb_post" value="true" />
	<input type="hidden" name="bill_name" value="<?php print unstripslashes(@$_POST["name"])?>" />
	<input type="hidden" name="bill_addr_1" value="<?php print unstripslashes(@$_POST["address"])?>" />
	<input type="hidden" name="bill_city" value="<?php print unstripslashes(@$_POST["city"])?>" />
	<input type="hidden" name="bill_state" value="<?php if(trim(@$_POST["state"]) != "") print unstripslashes(@$_POST["state"]); else print unstripslashes(@$_POST["state2"]);?>" />
	<input type="hidden" name="bill_post_code" value="<?php print unstripslashes(@$_POST["zip"])?>" />
	<input type="hidden" name="bill_country" value="<?php print unstripslashes(@$_POST["country"])?>" />
	<input type="hidden" name="bill_email" value="<?php print unstripslashes(@$_POST["email"])?>" />
	<input type="hidden" name="bill_tel" value="<?php print unstripslashes(@$_POST["phone"])?>" />
<?php		if(trim(@$_POST["sname"]) != "" || trim(@$_POST["saddress"]) != ""){ ?>
	<input type="hidden" name="ship_name" value="<?php print unstripslashes(@$_POST["sname"])?>" />
	<input type="hidden" name="ship_addr_1" value="<?php print unstripslashes(@$_POST["saddress"])?>" />
	<input type="hidden" name="ship_city" value="<?php print unstripslashes(@$_POST["scity"])?>" />
	<input type="hidden" name="ship_state" value="<?php if(trim(@$_POST["state"]) != "") print unstripslashes(@$_POST["sstate"]); else print unstripslashes(@$_POST["sstate2"]);?>" />
	<input type="hidden" name="ship_post_code" value="<?php print unstripslashes(@$_POST["szip"])?>" />
	<input type="hidden" name="ship_country" value="<?php print unstripslashes(@$_POST["scountry"])?>" />
<?php		} ?>
<?php	}elseif($grandtotal > 0 && @$_POST["payprovider"]=="10"){ // Capture Card ?>
	<form method="post" action="thanks.php" onsubmit="return isvalidcard(this)">
	<input type="hidden" name="docapture" value="vsprods" />
	<input type="hidden" name="ordernumber" value="<?php print $orderid?>" />
<?php	}elseif($grandtotal > 0 && (@$_POST["payprovider"]=="11" || @$_POST["payprovider"]=="12")){ // PSiGate ?>
	<form method="post" action="https://order.psigate.com/psigate.asp" <?php if(@$_POST["payprovider"]=="12") print 'onsubmit="return isvalidcard(this)"' ?>>
	<input type="hidden" name="MerchantID" value="<?php print $data1?>" />
	<input type="hidden" name="Oid" value="<?php print $orderid?>" />
	<input type="hidden" name="FullTotal" value="<?php print number_format($grandtotal,2,'.','')?>" />
	<input type="hidden" name="ThanksURL" value="<?php print $storeurl?>thanks.php" />
	<input type="hidden" name="NoThanksURL" value="<?php print $storeurl?>thanks.php" />
	<input type="hidden" name="Chargetype" value="<?php if($ppmethod=="1") print "1"; else print "0"; ?>" />
	<?php if(@$_POST["payprovider"]=="11"){ ?><input type="hidden" name="Bname" value="<?php print unstripslashes(@$_POST["name"])?>" /><?php } ?>
	<input type="hidden" name="Baddr1" value="<?php print unstripslashes(@$_POST["address"])?>" />
	<input type="hidden" name="Bcity" value="<?php print unstripslashes(@$_POST["city"])?>" />
	<input type="hidden" name="IP" value="<?php print @$_SERVER["REMOTE_ADDR"]?>" />
<?php			if($countryID==1 && $stateAbbrev != ""){ ?>
	<input type="hidden" name="Bstate" value="<?php print $stateAbbrev?>" />
<?php			}else{ ?>
	<input type="hidden" name="Bstate" value="<?php if(trim(@$_POST["state"]) != "") print unstripslashes(@$_POST["state"]); else print unstripslashes(@$_POST["state2"]);?>" />
<?php			} ?>
	<input type="hidden" name="Bzip" value="<?php print unstripslashes(@$_POST["zip"])?>" />
	<input type="hidden" name="Bcountry" value="<?php print $countryCode?>" />
	<input type="hidden" name="Email" value="<?php print unstripslashes(@$_POST["email"])?>" />
	<input type="hidden" name="Phone" value="<?php print unstripslashes(@$_POST["phone"])?>" />
<?php			if(trim(@$_POST["sname"]) != "" || trim(@$_POST["saddress"]) != ""){ ?>
	<input type="hidden" name="Sname" value="<?php print unstripslashes(@$_POST["sname"])?>" />
	<input type="hidden" name="Saddr1" value="<?php print unstripslashes(@$_POST["saddress"])?>" />
	<input type="hidden" name="Scity" value="<?php print unstripslashes(@$_POST["scity"])?>" />
	<input type="hidden" name="Sstate" value="<?php if(trim(@$_POST["state"]) != "") print unstripslashes(@$_POST["sstate"]); else print unstripslashes(@$_POST["sstate2"]);?>" />
	<input type="hidden" name="Szip" value="<?php print unstripslashes(@$_POST["szip"])?>" />
	<input type="hidden" name="Scountry" value="<?php print unstripslashes(@$_POST["scountry"])?>" />
<?php			}
				if($demomode){ ?>
		<input type="hidden" name="Result" value="1" />
<?php			}
		}elseif($grandtotal > 0 && @$_POST["payprovider"]=="13"){ // Authorize.net AIM ?>
	<form method="post" action="cart.php" onsubmit="return isvalidcard(this)">
	<input type="hidden" name="mode" value="authorize" />
	<input type="hidden" name="method" value="authnetaim" />
	<input type="hidden" name="ordernumber" value="<?php print $orderid?>" />
	<input type="hidden" name="description" value="<?php print substr($descstr,0,254)?>" />
<?php	}elseif($grandtotal > 0 && @$_POST["payprovider"]=="14"){ // Custom Pay Provider
			include "./vsadmin/inc/customppsend.php";
		}elseif($grandtotal > 0 && @$_POST["payprovider"]=="15"){ // Netbanx ?>
	<form method="post" action="https://www.netbanx.com/cgi-bin/payment/<?php print $data1;?>">
	<input type="hidden" name="order_id" value="<?php print $orderid?>" />
	<input type="hidden" name="payment_amount" value="<?php print number_format($grandtotal,2,'.','')?>" />
	<input type="hidden" name="currency_code" value="<?php print $countryCurrency?>" />
	<input type="hidden" name="cardholder_name" value="<?php print unstripslashes(@$_POST["name"])?>" />
	<input type="hidden" name="email" value="<?php print unstripslashes(@$_POST["email"])?>" />
	<input type="hidden" name="postcode" value="<?php print unstripslashes(@$_POST["zip"])?>" />
<?php	}elseif($grandtotal > 0 && @$_POST["payprovider"]=="16"){ // Linkpoint ?>
	<form action="https://www.linkpointcentral.com/lpc/servlet/lppay" method="post"<?php if($data2=="1") print ' onsubmit="return isvalidcard(this)"' ?>>
	<input type="hidden" name="storename" value="<?php print $data1?>" />
	<input type="hidden" name="mode" value="payonly" />
	<input type="hidden" name="oid" value="<?php print $orderid?>" />
	<input type="hidden" name="responseURL" value="<?php print $storeurl?>thanks.php" />
	<input type="hidden" name="chargetotal" value="<?php print $grandtotal?>" />
	<?php if($data2!="1"){ ?><input type="hidden" name="bname" value="<?php print unstripslashes(@$_POST["name"])?>" /><?php } ?>
	<input type="hidden" name="baddr1" value="<?php print unstripslashes(@$_POST["address"])?>" />
	<input type="hidden" name="bcity" value="<?php print unstripslashes(@$_POST["city"])?>" />
<?php		if($countryID==1 && $stateAbbrev != ""){ ?>
		<input type="hidden" name="bstate" value="<?php print $stateAbbrev?>" />
<?php		}else{ ?>
		<input type="hidden" name="bstate" value="<?php if(trim(@$_POST["state"]) != "") print unstripslashes(@$_POST["state"]); else print unstripslashes(@$_POST["state2"]);?>" />
<?php		} ?>
	<input type="hidden" name="bzip" value="<?php print unstripslashes(@$_POST["zip"])?>" />	
	<input type="hidden" name="bcountry" value="<?php print $countryCode?>" />
	<input type="hidden" name="email" value="<?php print unstripslashes(@$_POST["email"])?>" />
	<input type="hidden" name="phone" value="<?php print unstripslashes(@$_POST["phone"])?>" />
	<input type="hidden" name="txntype" value="<?php if($ppmethod==1) print "preauth"; else print "sale" ?>" />
<?php		if(trim(@$_POST["sname"]) != "" || trim(@$_POST["saddress"]) != ""){ ?>
	<input type="hidden" name="sname" value="<?php print unstripslashes(@$_POST["sname"])?>" />
	<input type="hidden" name="saddr1" value="<?php print unstripslashes(@$_POST["saddress"])?>" />
	<input type="hidden" name="scity" value="<?php print unstripslashes(@$_POST["scity"])?>" />
	<input type="hidden" name="sstate" value="<?php if(trim(@$_POST["state"]) != "") print unstripslashes(@$_POST["sstate"]); else print unstripslashes(@$_POST["sstate2"]);?>" />
	<input type="hidden" name="szip" value="<?php print unstripslashes(@$_POST["szip"])?>" />
	<input type="hidden" name="scountry" value="<?php print $shipCountryCode?>" />
<?php		}
			if($demomode){ ?>
	<input type="hidden" name="txnmode" value="test" />
<?php		}
		}
	}
	if($success){
?>
	  <br />
            <table class="cobtbl" width="<?php print $maintablewidth?>" border="0" bordercolor="#B1B1B1" cellspacing="1" cellpadding="3" bgcolor="#B1B1B1">
			  <tr>
			    <td class="cobhl" bgcolor="#EBEBEB" height="30" colspan="2" align="center"><strong><?php print $xxChkCmp?></strong></td>
			  </tr>
<?php if($cpncode!="" && ! $gotcpncode){ ?>
			  <tr>
			    <td class="cobhl" bgcolor="#EBEBEB" height="30" align="right" width="50%"><strong><?php print $xxGifCer?>:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" height="30" align="left" width="50%"><font size="1"><?php
							if(@$_POST["shipping"]=="") $jumpback=1; else $jumpback=2;
								printf($xxNoGfCr,$cpncode,$jumpback);?></font></td>
			  </tr>
<?php }
	  if($cpnmessage!=""){ ?>
			  <tr>
			    <td class="cobhl" bgcolor="#EBEBEB" height="30" align="right" width="50%"><strong><?php print $xxAppDs?>:</strong></strong></td>
				<td class="cobll" bgcolor="#FFFFFF" height="30" align="left" width="50%"><?php print $cpnmessage?></td>
			  </tr>
<?php } ?>
			  <tr>
			    <td class="cobhl" bgcolor="#EBEBEB" height="30" align="right" width="50%"><strong><?php print $xxTotGds?>:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" height="30" align="left" width="50%"><?php print FormatEuroCurrency($totalgoods)?></td>
			  </tr>
<?php if($shipType != 0){ ?>
			  <tr>
			    <td class="cobhl" bgcolor="#EBEBEB" height="30" align="right" width="50%"><strong><?php print $xxShippg?>:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" height="30" align="left" width="50%"><?php print FormatEuroCurrency($shipping)?></td>
			  </tr>
<?php }
	  if($handling != 0){ ?>
			  <tr>
			    <td class="cobhl" bgcolor="#EBEBEB" height="30" align="right" width="50%"><strong><?php print $xxHndlg?>:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" height="30" align="left" width="50%"><?php print FormatEuroCurrency($handling)?></td>
			  </tr>
<?php }
	  if($totaldiscounts!=0){ ?>
			  <tr>
			    <td class="cobhl" bgcolor="#EBEBEB" height="30" align="right" width="50%"><strong><?php print $xxTotDs?>:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" height="30" align="left" width="50%"><font color="#FF0000"><?php print FormatEuroCurrency($totaldiscounts)?></font></td>
			  </tr>
			  <tr>
			    <td class="cobhl" bgcolor="#EBEBEB" height="30" align="right" width="50%"><strong><?php print $xxSubTot?>:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" height="30" align="left" width="50%"><?php print FormatEuroCurrency(($totalgoods+$shipping+$handling)-$totaldiscounts)?></td>
			  </tr>
<?php }
	  if($usehst){ ?>
			  <tr>
			    <td class="cobhl" bgcolor="#EBEBEB" height="30" align="right" width="50%"><strong><?php print $xxHST?>:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" height="30" align="left" width="50%"><?php print FormatEuroCurrency($stateTax+$countryTax)?></td>
			  </tr>
<?php }else{
		if($stateTax != 0.0){ ?>
			  <tr>
			    <td class="cobhl" bgcolor="#EBEBEB" height="30" align="right" width="50%"><strong><?php print $xxStaTax?>:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" height="30" align="left" width="50%"><?php print FormatEuroCurrency($stateTax)?></td>
			  </tr>
<?php	}
		if($countryTax != 0.0){ ?>
			  <tr>
			    <td class="cobhl" bgcolor="#EBEBEB" height="30" align="right" width="50%"><strong><?php print $xxCntTax?>:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" height="30" align="left" width="50%"><?php print FormatEuroCurrency($countryTax)?></td>
			  </tr>
<?php	}
	  }?>
			  <tr>
			    <td class="cobhl" bgcolor="#EBEBEB" height="30" align="right" width="50%"><strong><?php print $xxGndTot?>:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" height="30" align="left" width="50%"><?php print FormatEuroCurrency($grandtotal)?></td>
			  </tr>
<?php if($grandtotal > 0 && (@$_POST["payprovider"]=="7" || @$_POST["payprovider"]=="10" || @$_POST["payprovider"]=="12" || @$_POST["payprovider"]=="13" || (@$_POST["payprovider"]=="16" && $data2=="1"))){ // VeriSign Payflow Pro || Capture Card || PSiGate || Auth.NET AIM
			if(@$_POST["payprovider"]=="7" || @$_POST["payprovider"]=="12" || @$_POST["payprovider"]=="13" || @$_POST["payprovider"]=="16") $data1 = "XXXXXXX0XXXXXXXXXXXXXXXXX";
			$isPSiGate = (@$_POST["payprovider"]=="12");
			$isLinkpoint = (@$_POST["payprovider"]=="16");
			if($isPSiGate){
				$sscardname="bname";
				$sscardnum = "CardNumber";
				$ssexmon = "ExpMonth";
				$ssexyear = "ExpYear";
			}elseif($isLinkpoint){
				$sscardname="bname";
				$sscardnum = "cardnumber";
				$ssexmon = "expmonth";
				$ssexyear = "expyear";
				$sscvv2 = "cvm";
			}else{
				$sscardname="cardname";
				$sscardnum = "ACCT";
				$ssexmon = "EXMON";
				$ssexyear = "EXYEAR";
				$sscvv2 = "CVV2";
			}
			$acceptecheck = ((@$acceptecheck==true) && (@$_POST["payprovider"]=="13"));
?>
<script Language="JavaScript" type="text/javascript">
<!--
var isswitchcard=false;
function isCreditCard(st){
  // Encoding only works on cards with less than 19 digits
  if (st.length > 19)
    return (false);

  sum = 0; mul = 1; l = st.length;
  for (i = 0; i < l; i++) {
    digit = st.substring(l-i-1,l-i);
    tproduct = parseInt(digit ,10)*mul;
    if (tproduct >= 10)
      sum += (tproduct % 10) + 1;
    else
      sum += tproduct;
    if (mul == 1)
      mul++;
    else
      mul = mul - 1;
  }
  if ((sum % 10) == 0)
    return (true);
  else
    return (false);

}
function isVisa(cc){ // 4111 1111 1111 1111
  if (((cc.length == 16) || (cc.length == 13)) && (cc.substring(0,1) == 4))
    return isCreditCard(cc);
  return false;
}
function isMasterCard(cc){ // 5500 0000 0000 0004
  firstdig = cc.substring(0,1);
  seconddig = cc.substring(1,2);
  if ((cc.length == 16) && (firstdig == 5) && ((seconddig >= 1) && (seconddig <= 5)))
    return isCreditCard(cc);
  return false;
}
function isAmericanExpress(cc){ // 340000000000009
  firstdig = cc.substring(0,1);
  seconddig = cc.substring(1,2);
  if ((cc.length == 15) && (firstdig == 3) && ((seconddig == 4) || (seconddig == 7)))
    return isCreditCard(cc);
  return false;
}
function isDinersClub(cc){ // 30000000000004
  firstdig = cc.substring(0,1);
  seconddig = cc.substring(1,2);
  if ((cc.length == 14) && (firstdig == 3) &&
      ((seconddig == 0) || (seconddig == 6) || (seconddig == 8)))
    return isCreditCard(cc);
  return false;
}
function isDiscover(cc){ // 6011000000000004
  first4digs = cc.substring(0,4);
  if ((cc.length == 16) && (first4digs == "6011"))
    return isCreditCard(cc);
  return false;
}
function isAusBankcard(cc){ // 5610591000000009
  first4digs = cc.substring(0,4);
  if ((cc.length == 16) && (first4digs == "5610"))
    return isCreditCard(cc);
  return false;
}
function isEnRoute(cc){ // 201400000000009
  first4digs = cc.substring(0,4);
  if ((cc.length == 15) && ((first4digs == "2014") || (first4digs == "2149")))
    return isCreditCard(cc);
  return false;
}
function isJCB(cc){
  first4digs = cc.substring(0,4);
  if ((cc.length == 16) && ((first4digs == "3088") || (first4digs == "3096") || (first4digs == "3112") || (first4digs == "3158") || (first4digs == "3337") || (first4digs == "3528")))
    return isCreditCard(cc);
  return false;
}
function isSwitch(cc){ // 675911111111111128
  first4digs = cc.substring(0,4);
  if ((cc.length == 16 || cc.length == 17 || cc.length == 18 || cc.length == 19) && ((first4digs == "4903") || (first4digs == "4911") || (first4digs == "4936") || (first4digs == "5641") || (first4digs == "6333") || (first4digs == "6759") || (first4digs == "6334") || (first4digs == "6767"))){
    isswitchcard=isCreditCard(cc);
    return(isswitchcard);
  }
  return false;
}
function isvalidcard(theForm){
  cc = theForm.<?php print $sscardnum?>.value;
  newcode = "";
  l = cc.length;
  for(i=0;i<l;i++){
	digit = cc.substring(i,i+1);
	digit = parseInt(digit ,10);
	if(!isNaN(digit)) newcode += digit;
  }
  cc=newcode;
  if (theForm.<?php print $sscardname?>.value==""){
	alert("<?php print $xxPlsEntr . ' \"' . $xxCCName . '\"' ?>");
	theForm.<?php print $sscardname?>.focus();
    return false;
  }
<?php if($acceptecheck==true){ ?>
if(cc!="" && theForm.accountnum.value!=""){
alert("Please enter either Credit Card OR ECheck details");
return(false);
}else if(theForm.accountnum.value!=""){
  if(theForm.accountname.value==""){
    alert("Please enter a value in the field \"Account Name\".");
	theForm.accountname.focus();
    return false;
  }
  if(theForm.bankname.value==""){
    alert("Please enter a value in the field \"Bank Name\".");
	theForm.bankname.focus();
    return false;
  }
  if(theForm.routenumber.value==""){
    alert("Please enter a value in the field \"Routing Number\".");
	theForm.routenumber.focus();
    return false;
  }
  if(theForm.accounttype.selectedIndex==0){
    alert("Please select your account type: (Checking / Savings).");
	theForm.accounttype.focus();
    return false;
  }
<?php	if(@$wellsfargo==true){ ?>
  if(theForm.orgtype.selectedIndex==0){
    alert("Please select your account type: (Personal / Business).");
	theForm.orgtype.focus();
    return false;
  }
  if(theForm.taxid.value=="" && theForm.licensenumber.value==""){
    alert("Please enter either a Tax ID number or Drivers License Details.");
	theForm.taxid.focus();
    return false;
  }
  if(theForm.taxid.value==""){
	  if(theForm.licensestate.selectedIndex==0){
		alert("Please select your Drivers License State.");
		theForm.licensestate.focus();
		return false;
	  }
	  if(theForm.dldobmon.selectedIndex==0){
		alert("Please select your Drivers License D.O.B. Month.");
		theForm.dldobmon.focus();
		return false;
	  }
	  if(theForm.dldobday.selectedIndex==0){
		alert("Please select your Drivers License D.O.B. Day.");
		theForm.dldobday.focus();
		return false;
	  }
	  if(theForm.dldobyear.selectedIndex==0){
		alert("Please select your Drivers License D.O.B. year.");
		theForm.dldobyear.focus();
		return false;
	  }
  }
<?php	} ?>
}else{
<?php } ?>
  if (true <?php 
		if(substr($data1,0,1)=="X") print "&& !isVisa(cc) ";
		if(substr($data1,1,1)=="X") print "&& !isMasterCard(cc) ";
		if(substr($data1,2,1)=="X") print "&& !isAmericanExpress(cc) ";
		if(substr($data1,3,1)=="X") print "&& !isDinersClub(cc) ";
		if(substr($data1,4,1)=="X") print "&& !isDiscover(cc) ";
		if(substr($data1,5,1)=="X") print "&& !isEnRoute(cc) ";
		if(substr($data1,6,1)=="X") print "&& !isJCB(cc) ";
		if(substr($data1,7,1)=="X") print "&& !isSwitch(cc) ";
		if(substr($data1,8,1)=="X") print "&& !isAusBankcard(cc) "; ?>){
	<?php if($acceptecheck==true) $xxValCC="Please enter a valid credit card number or bank account details if paying by ECheck."; ?>
	alert("<?php print $xxValCC?>");
	theForm.<?php print $sscardnum?>.focus();
    return false;
  }
  if(theForm.<?php print $ssexmon?>.selectedIndex==0){
    alert("<?php print $xxCCMon?>");
	theForm.<?php print $ssexmon?>.focus();
    return false;
  }
  if(theForm.<?php print $ssexyear?>.selectedIndex==0){
    alert("<?php print $xxCCYear?>");
	theForm.<?php print $ssexyear?>.focus();
    return false;
  }
<?php if(substr($data1,7,1)=="X"){ ?>
  if(theForm.IssNum.value=="" && isswitchcard){
    alert("Please enter an issue number / start date for Switch/Solo cards.");
	theForm.IssNum.focus();
    return false;
  }
<?php }
	  if(@$requirecvv==TRUE){ ?>
  if(theForm.<?php print $sscvv2?>.value==""){
    alert("<?php print $xxPlsEntr . ' \"' . str_replace('"','\"',$xx34code) . '\"'?>");
	theForm.<?php print $sscvv2?>.focus();
    return false;
  }
<?php }
	  if(@$acceptecheck==true) print '}'; ?>
  return true;
}
//-->
</script>
<?php if(@$_SERVER["HTTPS"] != "on" && (@$_SERVER["SERVER_PORT"] != "443") && @$nochecksslserver != TRUE){ ?>
			  <tr height="30">
			    <td class="cobhl" bgcolor="#EBEBEB" align="center" colspan="2"><strong><font color="#FF0000">This site may not be secure. Do not enter real Credit Card numbers.</font></strong></td>
			  </tr>
<?php } ?>
			  <tr>
			    <td class="cobhl" bgcolor="#EBEBEB" height="30" colspan="2" align="center"><strong><?php print $xxCCDets ?></strong></td>
			  </tr>
			  <tr height="30">
			    <td class="cobhl" bgcolor="#EBEBEB" align="right" width="50%"><strong><?php print $xxCCName?>:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" align="left" width="50%"><input type="text" name="<?php print $sscardname?>" size="<?php print atb(21)?>" value="<?php print @$_POST["name"]?>" AUTOCOMPLETE="off" /></td>
			  </tr>
			  <tr height="30">
			    <td class="cobhl" bgcolor="#EBEBEB" align="right" width="50%"><strong><?php print $xxCrdNum?>:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" align="left" width="50%"><input type="text" name="<?php print $sscardnum?>" size="<?php print atb(21)?>" AUTOCOMPLETE="off" /></td>
			  </tr>
			  <tr height="30">
			    <td class="cobhl" bgcolor="#EBEBEB" align="right" width="50%"><strong><?php print $xxExpEnd?>:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" align="left" width="50%">
				  <select name="<?php print $ssexmon?>" size="1">
					<option value=""><?php print $xxMonth?></option>
					<?php	for($index=1; $index<=12; $index++){
								if($index < 10) $themonth = "0" . $index; else $themonth = $index;
								print "<option value='" . $themonth . "'>" . $themonth . "</option>\n";
							} ?>
				  </select> / <select name="<?php print $ssexyear?>" size="1">
					<option value=""><?php print $xxYear?></option>
					<?php	$thisyear=date("Y", time());
							for($index=$thisyear; $index <= $thisyear+10; $index++){
								if($isPSiGate)
									print "<option value='" . substr($index,-2) . "'>" . $index . "</option>\n";
								else
									print "<option value='" . $index . "'>" . $index . "</option>\n";
							} ?>
				  </select>
				</td>
			  </tr>
<?php			if(! $isPSiGate){ ?>
			  <tr height="30">
			    <td class="cobhl" bgcolor="#EBEBEB" align="right" width="50%"><strong><?php print $xx34code?>:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" align="left" width="50%"><input type="text" name="<?php print $sscvv2?>" size="<?php print atb(4)?>" AUTOCOMPLETE="off" /> <strong><?php if(@$requirecvv!=TRUE)print $xxIfPres?></strong></td>
			  </tr>
<?php			}
				if(substr($data1,7,1)=="X"){ ?>
			  <tr height="30">
			    <td class="cobhl" bgcolor="#EBEBEB" align="right" width="50%"><strong>Issue Number / Start Date:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" align="left" width="50%"><input type="text" name="IssNum" size="<?php print atb(4)?>" AUTOCOMPLETE="off" /> <strong>(Switch/Solo Only)</strong></td>
			  </tr>
<?php			}
				if($acceptecheck==true){ // Auth.net ?>
			  <tr>
			    <td class="cobhl" bgcolor="#EBEBEB" height="30" colspan="2" align="center"><strong>ECheck Details</strong><br /><font size="1">Please enter either Credit Card OR ECheck details</font></td>
			  </tr>
			  <tr height="30">
			    <td class="cobhl" bgcolor="#EBEBEB" align="right" width="50%"><strong>Account Name:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" align="left" width="50%"><input type="text" name="accountname" size="<?php print atb(21)?>" AUTOCOMPLETE="off" value="<?php print @$_POST["name"]?>" /></td>
			  </tr>
			  <tr height="30">
			    <td class="cobhl" bgcolor="#EBEBEB" align="right" width="50%"><strong>Account Number:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" align="left" width="50%"><input type="text" name="accountnum" size="<?php print atb(21)?>" AUTOCOMPLETE="off" /></td>
			  </tr>
			  <tr height="30">
			    <td class="cobhl" bgcolor="#EBEBEB" align="right" width="50%"><strong>Bank Name:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" align="left" width="50%"><input type="text" name="bankname" size="<?php print atb(21)?>" AUTOCOMPLETE="off" /></td>
			  </tr>
			  <tr height="30">
			    <td class="cobhl" bgcolor="#EBEBEB" align="right" width="50%"><strong>Routing Number:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" align="left" width="50%"><input type="text" name="routenumber" size="<?php print atb(10)?>" AUTOCOMPLETE="off" /></td>
			  </tr>
			  <tr height="30">
			    <td class="cobhl" bgcolor="#EBEBEB" align="right" width="50%"><strong>Account Type:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" align="left" width="50%"><select name="accounttype" size="1"><option value=""><?php print $xxPlsSel?></option><option value="CHECKING">Checking</option><option value="SAVINGS">Savings</option></select></td>
			  </tr>
<?php				if(@$wellsfargo==true){ ?>
			  <tr height="30">
			    <td class="cobhl" bgcolor="#EBEBEB" align="right" width="50%"><strong>Personal or Business Acct.:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" align="left" width="50%"><select name="orgtype" size="1"><option value=""><?php print $xxPlsSel?></option><option value="I">Personal</option><option value="B">Business</option></select></td>
			  </tr>
			  <tr height="30">
			    <td class="cobhl" bgcolor="#EBEBEB" align="right" width="50%"><strong>Tax ID:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" align="left" width="50%"><input type="text" name="taxid" size="<?php print atb(21)?>" AUTOCOMPLETE="off" /></td>
			  </tr>
			  <tr>
			    <td class="cobhl" bgcolor="#EBEBEB" height="30" colspan="2" align="center"><font size="1">If you have provided a Tax ID then the following information is not necessary</font></td>
			  </tr>
			  <tr height="30">
			    <td class="cobhl" bgcolor="#EBEBEB" align="right" width="50%"><strong>Drivers License Number:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" align="left" width="50%"><input type="text" name="licensenumber" size="<?php print atb(21)?>" AUTOCOMPLETE="off" /></td>
			  </tr>
			  <tr height="30">
			    <td class="cobhl" bgcolor="#EBEBEB" align="right" width="50%"><strong>Drivers License State:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" align="left" width="50%"><select size="1" name="licensestate"><option value=""><?php print $xxPlsSel?></option><?php
				$sSQL = "SELECT stateName,stateAbbrev FROM states WHERE stateEnabled=1 ORDER BY stateName";
				$result = mysql_query($sSQL) or print(mysql_error());
				while($rs = mysql_fetch_array($result)){
					print '<option value="' . str_replace('"','&quot;',$rs["stateAbbrev"]) . '"';
					print '>' . $rs["stateName"] . "</option>\n";
				}
				mysql_free_result($result); ?></select></td>
			  </tr>
			  <tr height="30">
			    <td class="cobhl" bgcolor="#EBEBEB" align="right" width="50%"><strong>Date Of Birth On License:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" align="left" width="50%">
				  <select name="dldobmon" size="1">
					<option value=""><?php print $xxMonth?></option>
					<?php for($index=1; $index <= 12; $index++){ ?>
					<option value="<?php print $index?>"><?php print date("M", mktime(1,0,0,$index,1,1990))?></option>
					<?php } ?>
				  </select>
				  <select name="dldobday" size="1">
					<option value="">Day</option>
					<?php for($index=1; $index <= 31; $index++){ ?>
					<option value="<?php print $index?>"><?php print $index?></option>
					<?php } ?>
				  </select>
				  <select name="dldobyear" size="1">
					<option value=""><?php print $xxYear?></option>
					<?php $thisyear = date("Y");
						  for($index=$thisyear-100; $index <= $thisyear; $index++){ ?>
					<option value="<?php print $index?>"><?php print $index?></option>
					<?php } ?>
				  </select>
				</td>
			  </tr>
<?php				}
				}
	} ?>
			  <tr>
			    <td class="cobhl" bgcolor="#EBEBEB" height="30" colspan="2" align="center"><strong><?php print $xxMstClk?></strong></td>
			  </tr>
			  <tr>
				<td class="cobll" bgcolor="#FFFFFF" colspan="2" align="center"><table width="100%" cellspacing="0" cellpadding="0" border="0">
				    <tr>
					  <td class="cobll" bgcolor="#FFFFFF" width="16" height="26" align="right" valign="bottom">&nbsp;</td>
					  <td class="cobll" bgcolor="#FFFFFF" width="100%" align="center"><input type="image" src="images/checkout.gif" border="0" /></td>
					  <td class="cobll" bgcolor="#FFFFFF" width="16" height="26" align="right" valign="bottom"><img src="images/tablebr.gif" alt="" /></td>
					</tr>
				  </table></td>
			  </tr>
			</table>
	</form>
<?php
	} // success
}elseif(@$_POST["mode"]=="authorize"){
	$blockuser=checkuserblock("");
	$ordID = mysql_escape_string(str_replace("'","",@$_POST["ordernumber"]));
	$vsRESULT="x";
	$vsRESPMSG="";
	$vsAVSADDR="";
	$vsAVSZIP="";
	$vsTRANSID="";
	if(@$_POST["method"]=="payflowpro"){
		$sSQL = "SELECT payProvData1,payProvDemo FROM payprovider WHERE payProvID=7";
		$result = mysql_query($sSQL) or print(mysql_error());
		$rs = mysql_fetch_assoc($result);
		$vsdetails = $rs["payProvData1"];
		$demomode=((int)$rs["payProvDemo"]==1);
		mysql_free_result($result);
		if(is_null($vsdetails)) $vsdetails="";
		$vsdetails = split("&", $vsdetails);
		$vs1=@$vsdetails[0];
		$vs2=@$vsdetails[1];
		$vs3=@$vsdetails[2];
		$vs4=@$vsdetails[3];
		$sSQL = "SELECT ordZip,ordShipping,ordStateTax,ordCountryTax,ordHandling,ordTotal,ordDiscount,ordAddress,ordAuthNumber FROM orders WHERE ordID='" . $ordID . "'";
		$result = mysql_query($sSQL) or print(mysql_error());
		$rs = mysql_fetch_assoc($result);
		$vsAUTHCODE = $rs["ordAuthNumber"];
		if(@$pathtopfpro==""){
			$parmList = array( "TRXTYPE"=>"S",
								"TENDER"=>"C",
								"ZIP" => $rs["ordZip"],
								"STREET" => $rs["ordAddress"],
								"NAME" => @$_POST["cardname"],
								"COMMENT1" => $ordID,
								"ACCT" => @$_POST["ACCT"],
								"PWD" => $vs4,
								"USER" => $vs1,
								"VENDOR" => $vs2,
								"PARTNER" => $vs3,
								"CVV2" => trim(@$_POST["CVV2"]),
								"EXPDATE" => @$_POST["EXMON"] . substr(@$_POST["EXYEAR"], -2),
								"AMT" => number_format(($rs["ordShipping"]+$rs["ordStateTax"]+$rs["ordCountryTax"]+$rs["ordTotal"]+$rs["ordHandling"])-$rs["ordDiscount"],2,'.','')
							);
		}else{
			$parmList = "TRXTYPE=S&TENDER=C";
			$parmList .= "&ZIP[" . strlen($rs["ordZip"]) . "]=" . $rs["ordZip"];
			$parmList .= "&STREET[" . strlen($rs["ordAddress"]) . "]=" . $rs["ordAddress"];
			$parmList .= "&NAME[" . strlen(@$_POST["cardname"]) . "]=" . @$_POST["cardname"];
			$parmList .= "&COMMENT1=" . $ordID;
			$parmList .= "&ACCT=" . @$_POST["ACCT"];
			$parmList .= "&PWD=" . $vs4;
			$parmList .= "&USER=" . $vs1;
			$parmList .= "&VENDOR=" . $vs2;
			$parmList .= "&PARTNER=" . $vs3;
			$parmList .= "&CVV2=" . trim(@$_POST["CVV2"]);
			$parmList .= "&EXPDATE=" . @$_POST["EXMON"] . substr(@$_POST["EXYEAR"], -2);
			$parmList .= "&AMT=" . number_format(($rs["ordShipping"]+$rs["ordStateTax"]+$rs["ordCountryTax"]+$rs["ordTotal"]+$rs["ordHandling"])-$rs["ordDiscount"],2,'.','');
		}
		mysql_free_result($result);
		function process_pfpro($str, $server, $port, $timeout){
			global $pathtopfpro,$pathtopfprocert,$pathtopfprolib,$parmList;
			if(@$pathtopfprocert!="")
				putenv("PFPRO_CERT_PATH=$pathtopfprocert");
			if(@$pathtopfpro=="COM"){
				$objCOM = new COM("PFProCOMControl.PFProCOMControl.1");
				$ctx1 = $objCOM->CreateContext($server, $port, $timeout, "", 0, "", "");
				$pfret = $objCOM->SubmitTransaction($ctx1, $str, strlen($str));
				$objCOM->DestroyContext($ctx1);
			}elseif(@$pathtopfpro!=""){
				if(@$pathtopfprolib!="")
					putenv("LD_LIBRARY_PATH=$pathtopfprolib");
				$sendstr = $pathtopfpro . ' ' . $server . ' ' . $port . ' "' . $str . '" ' . $timeout;
				exec ($sendstr, $pfret, $retvar);
				$pfret = implode("\n",$pfret);
			}else{
				$pfret = pfpro_process($parmList, $server);
			}
			return $pfret;
		}
		if($vsAUTHCODE==""){
			if($vs3=="VSA")
				if($demomode) $theurl = "payflow-test.verisign.com.au"; else $theurl = "payflow.verisign.com.au";
			else
				if($demomode) $theurl = "test-payflow.verisign.com"; else $theurl = "payflow.verisign.com";
			$curString = process_pfpro($parmList, $theurl, 443, 30);
			if(!is_array($curString)){
				$curStringArr = array();
				while(strlen($curString) != 0){
					if(strpos($curString,"&")!==FALSE)
						$varString = substr($curString, 0, strpos($curString , "&" ));
					else
						$varString = $curString;
					$name = substr($varString, 0, strpos($varString, "=" ));
					$curStringArr[$name] = substr($varString, (strlen($name)+1) - strlen($varString));
					if(strlen($curString) != strlen($varString))
						$curString = substr($curString,  (strlen($varString)+1) - strlen($curString));
					else
						$curString = "";
				}
				$curString = $curStringArr;
			}
			$vsRESULT=$curString["RESULT"];
			$vsPNREF=@$curString["PNREF"];
			$vsRESPMSG=@$curString["RESPMSG"];
			$vsAUTHCODE=@$curString["AUTHCODE"];
			$vsAVSADDR=@$curString["AVSADDR"];
			$vsAVSZIP=@$curString["AVSZIP"];
			$vsIAVS=@$curString["IAVS"];
			if($vsRESULT=="0"){
				do_stock_management($ordID);
				$sSQL="UPDATE cart SET cartCompleted=1 WHERE cartOrderID='" . $ordID . "'";
				mysql_query($sSQL) or print(mysql_error());
				$sSQL="UPDATE orders SET ordStatus=3,ordAuthNumber='" . mysql_escape_string($vsAVSADDR . $vsAVSZIP . "-" . $vsAUTHCODE) . "' WHERE ordID='" . $ordID . "'";
				mysql_query($sSQL) or print(mysql_error());
			}
		}else{
			$vsRESULT="0";
			$vsRESPMSG="Approved";
			if(strpos($vsAUTHCODE,"-") > 0) $vsAUTHCODE = substr($vsAUTHCODE, strpos($vsAUTHCODE,"-"));
		}
	}elseif(@$_POST["method"]=="authnetaim"){
		$sSQL = "SELECT payProvDemo,payProvData1,payProvData2,payProvMethod FROM payprovider WHERE payProvID=13";
		$result = mysql_query($sSQL) or print(mysql_error());
		$rs = mysql_fetch_array($result);
		$demomode = ((int)$rs["payProvDemo"]==1);
		$login = $rs["payProvData1"];
		$trankey = $rs["payProvData2"];
		if(@$secretword != ""){
			$login = upsdecode($login, $secretword);
			$trankey = upsdecode($trankey, $secretword);
		}
		$ppmethod = (int)$rs["payProvMethod"];
		mysql_free_result($result);
		$sSQL = "SELECT ordID,ordName,ordCity,ordState,ordCountry,ordPhone,ordHandling,ordZip,ordEmail,ordShipping,ordStateTax,ordCountryTax,ordTotal,ordDiscount,ordAddress,ordIP,ordAuthNumber,ordShipName,ordShipAddress,ordShipCity,ordShipState,ordShipCountry,ordShipZip FROM orders WHERE ordID='" . $ordID . "'";
		$result = mysql_query($sSQL) or print(mysql_error());
		$rs = mysql_fetch_array($result);
		mysql_free_result($result);
		$vsAUTHCODE = trim($rs["ordAuthNumber"]);
		$parmList = "x_version=3.1&x_delim_data=True&x_relay_response=False&x_delim_char=|";
		$parmList .= "&x_login=" . $login;
		$parmList .= "&x_tran_key=" . $trankey;
		$parmList .= "&x_cust_id=" . $rs["ordID"];
		$parmList .= "&x_Invoice_Num=" . $rs["ordID"];
		$parmList .= "&x_amount=" . number_format(($rs["ordShipping"]+$rs["ordStateTax"]+$rs["ordCountryTax"]+$rs["ordTotal"]+$rs["ordHandling"])-$rs["ordDiscount"],2,'.','');
		$parmList .= "&x_currency_code=" . $countryCurrency;
		$parmList .= "&x_Description=" . urlencode(@$_POST["description"]);
		if(trim(@$_POST["accountnum"]) != ""){
			$parmList .= "&x_method=ECHECK&x_echeck_type=WEB&x_recurring_billing=NO";
			$parmList .= "&x_bank_acct_name=" . urlencode(trim(@$_POST["accountname"]));
			$parmList .= "&x_bank_acct_num=" . urlencode(trim(@$_POST["accountnum"]));
			$parmList .= "&x_bank_name=" . urlencode(trim(@$_POST["bankname"]));
			$parmList .= "&x_bank_aba_code=" . urlencode(trim(@$_POST["routenumber"]));
			$parmList .= "&x_bank_acct_type=" . urlencode(trim(@$_POST["accounttype"]));
			$parmList .= "&x_type=AUTH_CAPTURE";
			if(@$wellsfargo=true){
				$parmList .= "&x_customer_organization_type=" . trim(@$_POST["orgtype"]);
				if(trim(@$_POST["taxid"]) != ""){
					$parmList .= "&x_customer_tax_id=" . urlencode(trim(@$_POST["taxid"]));
				}else{
					$parmList .= "&x_drivers_license_num=" . urlencode(trim(@$_POST["licensenumber"]));
					$parmList .= "&x_drivers_license_state=" . urlencode(trim(@$_POST["licensestate"]));
					$parmList .= "&x_drivers_license_dob=" . urlencode(trim(@$_POST["dldobyear"]) . "/" . trim(@$_POST["dldobmon"]) . "/" . trim(@$_POST["dldobday"]));
				}
			}
		}else{
			$parmList .= "&x_card_num=" . urlencode(@$_POST["ACCT"]);
			$parmList .= "&x_exp_date=" . @$_POST["EXMON"] . @$_POST["EXYEAR"];
			if(trim(@$_POST["CVV2"]) != "") $parmList .= "&x_card_code=" . trim(@$_POST["CVV2"]);
			if($ppmethod==1) $parmList .= "&x_type=AUTH_ONLY"; else $parmList .= "&x_type=AUTH_CAPTURE";
		}
		$thename = trim(@$_POST["cardname"]);
		if($thename != ""){
			if(strstr($thename," ")){
				$namearr = split(" ",$thename,2);
				$parmList .= "&x_first_name=" . urlencode($namearr[0]);
				$parmList .= "&x_last_name=" . urlencode($namearr[1]);
			}else
				$parmList .= "&x_last_name=" . urlencode($thename);
		}
		$parmList .= "&x_address=" . urlencode($rs["ordAddress"]);
		$parmList .= "&x_city=" . urlencode($rs["ordCity"]);
		$parmList .= "&x_state=" . urlencode($rs["ordState"]);
		$parmList .= "&x_zip=" . urlencode($rs["ordZip"]);
		$parmList .= "&x_country=" . urlencode($rs["ordCountry"]);
		$parmList .= "&x_phone=" . urlencode($rs["ordPhone"]);
		$parmList .= "&x_email=" . urlencode($rs["ordEmail"]);
		$thename = trim($rs["ordShipName"]);
		if($thename != "" || $rs["ordShipAddress"] != ""){
			if($thename != ""){
				if(strstr($thename," ")){
					$namearr = split(" ",$thename,2);
					$parmList .= "&x_ship_to_first_name=" . urlencode($namearr[0]);
					$parmList .= "&x_ship_to_last_name=" . urlencode($namearr[1]);
				}else
					$parmList .= "&x_ship_to_last_name=" . urlencode($thename);
			}
			$parmList .= "&x_ship_to_address=" . urlencode($rs["ordShipAddress"]);
			$parmList .= "&x_ship_to_city=" . urlencode($rs["ordShipCity"]);
			$parmList .= "&x_ship_to_state=" . urlencode($rs["ordShipState"]);
			$parmList .= "&x_ship_to_zip=" . urlencode($rs["ordShipZip"]);
			$parmList .= "&x_ship_to_country=" . urlencode($rs["ordShipCountry"]);
		}
		if(trim($rs["ordIP"]) != "") $parmList .= "&x_customer_ip=" . urlencode(trim($rs["ordIP"]));
		if($demomode) $parmList .= "&x_test_request=TRUE";
		if($vsAUTHCODE==""){
			$success=true;
			if($blockuser){
				$success=FALSE;
			}else{
				if(@$pathtocurl != ""){
					exec($pathtocurl . ' --data-binary \'' . str_replace("'","\'",$parmList) . '\' https://secure.authorize.net/gateway/transact.dll', $res, $retvar);
					$res = implode("\n",$res);
				}else{
					if (!$ch = curl_init()) {
						$vsRESPMSG = "cURL package not installed in PHP";
						$success=false;
					}else{
						curl_setopt($ch, CURLOPT_URL,'https://secure.authorize.net/gateway/transact.dll'); 
						curl_setopt($ch, CURLOPT_POST, 1);
						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
						curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
						curl_setopt($ch, CURLOPT_POSTFIELDS, $parmList);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						$res = curl_exec($ch);
						if(curl_error($ch) != ""){
							$vsRESULT="x";
							$vsRESPMSG= "Error with cURL installation: " . curl_error($ch) . "<br />";
							$success=false;
						}else{
							curl_close($ch);
						}
					}
				}
			}
			if($success){
				$varString = split('\|', $res);
				$vsRESULT=$varString[0];
				$vsRESPMSG=$varString[3];
				$vsAUTHCODE=$varString[4];
				$vsAVSADDR=$varString[5];
				$vsTRANSID=$varString[6];
				$vsCVV2=$varString[38];
				if((int)$vsRESULT==1){
					$vsRESULT="0"; // Keep in sync with Payflow Pro
					do_stock_management($ordID);
					$sSQL="UPDATE cart SET cartCompleted=1 WHERE cartOrderID='" . $ordID . "'";
					mysql_query($sSQL) or print(mysql_error());
					$sSQL="UPDATE orders SET ordStatus=3,ordAuthNumber='" . mysql_escape_string($vsAVSADDR . $vsCVV2 . "-" . $vsAUTHCODE) . "',ordTransID='" . mysql_escape_string($vsTRANSID) . "' WHERE ordID='" . $ordID . "'";
					//$sSQL="UPDATE orders SET ordStatus=3,ordAuthNumber='" . mysql_escape_string($vsAVSADDR . $vsCVV2 . "-" . $vsAUTHCODE) . "' WHERE ordID='" . $ordID . "'";
					mysql_query($sSQL) or print(mysql_error());
				}
			}
		}else{
			$vsRESULT="0";
			$vsRESPMSG="This transaction has been approved.";
			$pos = strpos($vsAUTHCODE, "-");
			if (! ($pos === false))
				$vsAUTHCODE = substr($vsAUTHCODE, $pos + 1);
		}
	}
?>
	<br />
	<form method="post" action="thanks.php" name="checkoutform">
	<input type="hidden" name="xxpreauth" value="<?php print $ordID?>" />
            <table class="cobtbl" width="<?php print $maintablewidth?>" border="0" bordercolor="#B1B1B1" cellspacing="1" cellpadding="3" bgcolor="#B1B1B1">
<?php	if($vsRESULT=="0"){ ?>
			  <tr height="30">
			    <td class="cobhl" bgcolor="#EBEBEB" align="center" colspan="2"><strong><?php print $xxTnxOrd?></strong></td>
			  </tr>
			  <tr height="30">
			    <td class="cobhl" bgcolor="#EBEBEB" align="right" width="50%"><strong><?php print $xxTrnRes?>:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" width="50%"><strong><?php print $vsRESPMSG?></strong></td>
			  </tr>
			  <tr height="30">
			    <td class="cobhl" bgcolor="#EBEBEB" align="right" width="50%"><strong><?php print $xxOrdNum?>:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" width="50%"><strong><?php print $ordID?></strong></td>
			  </tr>
			  <tr height="30">
			    <td class="cobhl" bgcolor="#EBEBEB" align="right" width="50%"><strong><?php print $xxAutCod?>:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" width="50%"><strong><?php print $vsAUTHCODE?></strong></td>
			  </tr>
			  <tr height="30">
				<td class="cobll" bgcolor="#FFFFFF" colspan="2">
				  <table width="100%" cellspacing="0" cellpadding="0" border="0">
				    <tr>
					  <td width="16" height="26" align="right" valign="bottom">&nbsp;</td>
					  <td class="cobll" bgcolor="#FFFFFF" width="100%" align="center">&nbsp;<br />
					  <input type="submit" value="Click to Confirm Order and View Receipt" /><br />&nbsp;
					  </td>
					  <td width="16" height="26" align="right" valign="bottom"><img src="images/tablebr.gif" alt="" /></td>
					</tr>
				  </table>
				</td>
			  </tr>
<?php		if(@$forcesubmit==TRUE){
				if(@$forcesubmittimeout=="") $forcesubmittimeout=5000;
				print '<script language="javascript" type="text/javascript">setTimeout("document.checkoutform.submit()",'.$forcesubmittimeout.');</script>\r\n';
			}
		}else{ ?>
			  <tr height="30">
			    <td class="cobhl" bgcolor="#EBEBEB" align="center" colspan="2"><strong><?php print $xxSorTrn?></strong></td>
			  </tr>
			  <tr height="30">
			    <td class="cobhl" bgcolor="#EBEBEB" align="right" width="50%"><strong><?php print $xxTrnRes?>:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" width="50%"><strong><?php print $vsRESPMSG?></strong></td>
			  </tr>
			  <tr height="30">
				<td class="cobll" bgcolor="#FFFFFF" colspan="2">
				  <table width="100%" cellspacing="0" cellpadding="0" border="0">
				    <tr>
					  <td width="16" height="26" align="right" valign="bottom">&nbsp;</td>
					  <td class="cobll" bgcolor="#FFFFFF" width="100%" align="center">&nbsp;<br />
					  <input type="button" value="<?php print $xxGoBack?>" onclick="javascript:history.go(-1)" /><br />&nbsp;
					  </td>
					  <td width="16" height="26" align="right" valign="bottom"><img src="images/tablebr.gif" alt="" /></td>
					</tr>
				  </table>
				</td>
			  </tr>
<?php	} ?>
			</table>
	</form>
<?php
}else{
	mysql_query("UPDATE orders SET ordTotal=0,ordShipping=0,ordStateTax=0,ordCountryTax=0,ordHSTTax=0,ordHandling=0,ordShipType='',ordDiscount=0,ordDiscountText=0 WHERE ordSessionID='" . session_id() . "' AND ordAuthNumber=''") or print(mysql_error());
	$alldata="";
	$sSQL = "SELECT cartID,cartProdID,cartProdName,cartProdPrice,cartQuantity,pSection,topSection FROM cart LEFT JOIN products ON cart.cartProdID=products.pID LEFT OUTER JOIN sections ON products.pSection=sections.sectionID WHERE cartCompleted=0 AND cartSessionID='" .  session_id() . "'";
	$result = mysql_query($sSQL) or print(mysql_error());
?>
	<br />
	<form method="post" action="cart.php" name="checkoutform">
	<input type="hidden" name="mode" value="update" />
            <table class="cobtbl" width="<?php print $maintablewidth?>" border="0" bordercolor="#B1B1B1" cellspacing="1" cellpadding="3" bgcolor="#B1B1B1">
<?php
	if(mysql_num_rows($result) > 0){
		if(! $isInStock){
?>
			  <tr height="30">
			    <td class="cobll" bgcolor="#FFFFFF" colspan="6" align="center"><font color="#FF0000"><strong><?php print $xxNoStok?></strong></font></td>
			  </tr>
<?php
		}
?>
			  <tr height="30">
			    <td class="cobhl" bgcolor="#EBEBEB" width="15%"><strong><?php print $xxCODets?></strong></td>
			    <td class="cobhl" bgcolor="#EBEBEB" width="33%"><strong><?php print $xxCOName?></strong></td>
				<td class="cobhl" bgcolor="#EBEBEB" width="14%" align="center"><strong><?php print $xxCOUPri?></strong></td>
				<td class="cobhl" bgcolor="#EBEBEB" width="14%" align="center"><strong><?php print $xxQuant?></strong></td>
				<td class="cobhl" bgcolor="#EBEBEB" width="14%" align="center"><strong><?php print $xxTotal?></strong></td>
				<td class="cobhl" bgcolor="#EBEBEB" width="10%" align="center"><strong><?php print $xxCOSel?></strong></td>
			  </tr>
<?php
		$grandtotal=0.0;
		$totaldiscounts = 0;
		$totquant = 0;
		$changechecker = "";
		while($alldata=mysql_fetch_assoc($result)){
			$changechecker .= 'if(document.checkoutform.quant' . $alldata["cartID"] . ".value!=" . $alldata["cartQuantity"] . ") dowarning=true;\n";
			$theoptions = "";
			$theoptionspricediff = 0;
			$sSQL = "SELECT coOptGroup,coCartOption,coPriceDiff FROM cartoptions WHERE coCartID=" . $alldata["cartID"] . " ORDER BY coID";
			$opts = mysql_query($sSQL) or print(mysql_error());
			$optPriceDiff=0;
			while($rs=mysql_fetch_assoc($opts)){
				$theoptionspricediff += $rs["coPriceDiff"];
				$theoptions .= '<tr height="25">';
				$theoptions .= '<td class="cobhl" bgcolor="#EBEBEB" align="right"><font size="1"><strong>' . $rs["coOptGroup"] . ':</strong></font></td>';
				$theoptions .= '<td class="cobll" bgcolor="#FFFFFF"><font size="1">&nbsp;- ' . $rs["coCartOption"] . '</font></td>';
				$theoptions .= '<td class="cobll" bgcolor="#FFFFFF" align="right"><font size="1">' . ($rs["coPriceDiff"]==0 || @$hideoptpricediffs==TRUE ? "- " : FormatEuroCurrency($rs["coPriceDiff"])) . '</font></td>';
				$theoptions .= '<td class="cobll" bgcolor="#FFFFFF" align="right">&nbsp;</td>';
				$theoptions .= '<td class="cobll" bgcolor="#FFFFFF" align="right"><font size="1">' . ($rs["coPriceDiff"]==0 || @$hideoptpricediffs==TRUE ? "- " : FormatEuroCurrency($rs["coPriceDiff"]*$alldata["cartQuantity"])) . '</font></td>';
				$theoptions .= '<td class="cobll" bgcolor="#FFFFFF" align="center">&nbsp;</td>';
				$theoptions .= "</tr>\n";
				$grandtotal += ($rs["coPriceDiff"]*(int)$alldata["cartQuantity"]);
			}
			mysql_free_result($opts);
?>
              <tr height="30">
			    <td class="cobhl" bgcolor="#EBEBEB"><strong><?php print $alldata["cartProdID"]?></strong></td>
			    <td class="cobll" bgcolor="#FFFFFF"><?php print $alldata["cartProdName"] ?></td>
				<td class="cobll" bgcolor="#FFFFFF" align="right"><?php print (@$hideoptpricediffs==TRUE ? FormatEuroCurrency($alldata["cartProdPrice"] + $theoptionspricediff) : FormatEuroCurrency($alldata["cartProdPrice"]))?></td>
				<td class="cobll" bgcolor="#FFFFFF" align="center"><input type="text" name="quant<?php print $alldata["cartID"]?>" value="<?php print $alldata["cartQuantity"]?>" size="2" maxlength="5" /></td>
				<td class="cobll" bgcolor="#FFFFFF" align="right"><?php print (@$hideoptpricediffs==TRUE ? FormatEuroCurrency(($alldata["cartProdPrice"] + $theoptionspricediff)*$alldata["cartQuantity"]) : FormatEuroCurrency($alldata["cartProdPrice"]*$alldata["cartQuantity"]))?></td>
				<td class="cobll" bgcolor="#FFFFFF" align="center"><input type="checkbox" name="delet<?php print $alldata["cartID"]?>" /></td>
			  </tr>
<?php		print $theoptions;
			$grandtotal += ($alldata["cartProdPrice"]*(int)$alldata["cartQuantity"]);
			$totquant += (int)$alldata["cartQuantity"];
		}
		calculatediscounts($grandtotal, false, "");
		if($totaldiscounts>0){
			if($totaldiscounts > $grandtotal) $totaldiscounts = $grandtotal; ?>
              <tr height="30">
				<td class="cobhl" bgcolor="#EBEBEB" rowspan="4">&nbsp;</td>
				<td class="cobll" bgcolor="#FFFFFF" align="right" colspan="3"><strong><?php print $xxSubTot?>:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" align="right"><?php print FormatEuroCurrency($grandtotal)?></td>
				<td class="cobll" bgcolor="#FFFFFF" align="center">&nbsp;</td>
			  </tr>
			  <tr height="30">
				<td class="cobll" bgcolor="#FFFFFF" align="right" colspan="3"><font color="#FF0000"><strong><?php print $xxDsApp?></strong></font></td>
				<td class="cobll" bgcolor="#FFFFFF" align="right"><font color="#FF0000"><?php print FormatEuroCurrency($totaldiscounts)?></font></td>
				<td class="cobll" bgcolor="#FFFFFF" align="center">&nbsp;</td>
			  </tr>
<?php	} ?>
              <tr height="30">
			  <?php	if($totaldiscounts==0){ ?>
				<td class="cobhl" bgcolor="#EBEBEB" rowspan="2">&nbsp;</td>
			  <?php } ?>
				<td class="cobll" bgcolor="#FFFFFF" align="right" colspan="3"><strong><?php print $xxGndTot?>:</strong></td>
				<td class="cobll" bgcolor="#FFFFFF" align="right"><?php print FormatEuroCurrency($grandtotal-$totaldiscounts)?></td>
				<td class="cobll" bgcolor="#FFFFFF" align="center"><a href="javascript:document.checkoutform.submit()"><strong><?php print $xxDelete?></strong></a></td>
			  </tr>
			  <tr height="30">
				<td class="cobll" bgcolor="#FFFFFF" colspan="5">
				  <table width="100%" cellspacing="0" cellpadding="0" border="0">
				    <tr>
					  <td class="cobll" bgcolor="#FFFFFF" width="50%" align="center"><a href="<?php if(trim(@$_SESSION["frompage"])!="" && (@$actionaftercart==2 || @$actionaftercart==3)) print $_SESSION["frompage"]; else print $xxHomeURL?>"><strong><?php print $xxCntShp?></strong></a></td>
					  <td class="cobll" bgcolor="#FFFFFF" width="50%" align="center"><a href="javascript:document.checkoutform.submit()"><strong><?php print $xxUpdTot?></strong></a></td>
					  <td class="cobll" bgcolor="#FFFFFF" width="16" height="26" align="right" valign="bottom"><img src="images/tablebr.gif" alt="" /></td>
					</tr>
				  </table>
				</td>
			  </tr>
<script language="JavaScript" type="text/javascript">
<!--
function changechecker(){
dowarning=false;
<?php print $changechecker?>
if(dowarning){
	if(confirm('<?php print str_replace("'","\'",$xxWrnChQ)?>')){
		document.checkoutform.submit();
		return false;
	}else
		return(true);
}
return true;
}
//--></script>
<?php
	}else{
		$cartEmpty=TRUE;
?>
              <tr>
			    <td class="cobll" bgcolor="#FFFFFF" colspan="6" align="center">
				  <p>&nbsp;</p>
				  <p><?php print $xxSryEmp?></p>
				  <p>&nbsp;</p>
<script language="JavaScript" type="text/javascript">
<!--
if(document.cookie=="") document.write("<?php print str_replace('"', '\"', $xxNoCk . " " . $xxSecWar)?>");
//--></script>
<noscript><?php print $xxNoJS . " " . $xxSecWar?></noscript>
				  <p><a href="<?php if(trim(@$_SESSION["frompage"])!="" && (@$actionaftercart==2 || @$actionaftercart==3)) print $_SESSION["frompage"]; else print $xxHomeURL?>"><strong><?php print $xxCntShp?></strong></a></p>
				  <p>&nbsp;</p>
				</td>
			  </tr>
<?php
	}
?>
			</table>
	</form>
<?php
}

if(@$_POST["mode"] != "go" && @$_POST["mode"] != "checkout" && @$_POST["mode"] != "add" && @$_POST["mode"] != "authorize" && ! $cartEmpty){
	$requiressl = FALSE;
	$sSQL = "SELECT payProvID FROM payprovider WHERE payProvEnabled=1 AND (payProvID IN (7,10,12,13) OR (payProvID=16 AND payProvData2='1'))"; // All the ones that require SSL
	$result = mysql_query($sSQL) or print(mysql_error());
	if(mysql_num_rows($result) > 0) $requiressl = TRUE;
	mysql_free_result($result);
	if($requiressl || @$pathtossl != ""){
		if(@$pathtossl != ""){
			if(substr($pathtossl,-1) != "/") $pathtossl .= "/";
			$cartpath = $pathtossl . "cart.php";
		}else
			$cartpath = str_replace("http:","https:",$storeurl) . "cart.php";
	}else
		$cartpath="cart.php";
?>
	  <table border="0" cellspacing="<?php print $maintablespacing?>" cellpadding="<?php print $maintablepadding?>" width="<?php print $maintablewidth?>" bgcolor="<?php print $maintablebg?>" align="center">
        <tr> 
          <td width="100%">
		    <form method="post" action="<?php print $cartpath?>" onsubmit="return changechecker(this)">
			  <input type="hidden" name="mode" value="checkout" />
			  <input type="hidden" name="sessionid" value="<?php print session_id();?>" />
			  <input type="hidden" name="PARTNER" value="<?php print trim(@$_COOKIE["PARTNER"]) ?>" />
<?php			if(trim(@$_SESSION["clientUser"]) != ""){
					mysql_query("DELETE FROM tmplogin WHERE tmplogindate < '" . date("Y-m-d H:i:s", time()-(3*60*60*24)) . "' OR tmploginid='" . session_id() . "'") or print(mysql_error());
					mysql_query("INSERT INTO tmplogin (tmploginid, tmploginname, tmplogindate) VALUES ('" . session_id() . "','" . trim($_SESSION["clientUser"]) . "','" . date("Y-m-d H:i:s", time()) . "')") or print(mysql_error());
					print '<input type="hidden" name="checktmplogin" value="1" />';
					if(($_SESSION["clientActions"] & 8) == 8 || ($_SESSION["clientActions"] & 16) == 16){
						if(@$minwholesaleamount!="") $minpurchaseamount=$minwholesaleamount;
						if(@$minwholesalemessage!="") $minpurchasemessage=$minwholesalemessage;
					}
				}
?>
			  <table width="<?php print $innertablewidth?>" border="0" cellspacing="<?php print $innertablespacing?>" cellpadding="<?php print $innertablepadding?>" bgcolor="<?php print $innertablebg?>">
		<?php if($grandtotal < @$minpurchaseamount){ ?>
				<tr>
				  <td width="100%" align="center" colspan="2"><?php print @$minpurchasemessage?></td>
				</tr>
		<?php }else{ ?>
				<tr>
				  <td width="100%" align="center" colspan="2"><strong><?php print $xxPrsChk?></strong></td>
				</tr>
				<tr>
				  <td align="center" colspan="2"><input type="image" src="images/checkout.gif" border="0" /></td>
				</tr>
		<?php } ?>
			  </table>
			</form>
		  </td>
        </tr>
      </table>
<?php
}
?>