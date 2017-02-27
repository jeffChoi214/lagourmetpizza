<?php
ob_start();
session_cache_limiter('none');
session_start();
//=========================================
//This code is copyright (c) Internet Business Solutions SL, all rights reserved.
//The contents of this file are protect under law as the intellectual property
//of Internet Business Solutions SL. Any use, reproduction, disclosure or copying
//of any kind without the express and written permission of Internet Business 
//Solutions SL is forbidden.
//Author: Vince Reid, vince@virtualred.net
include "db_conn_open.php";
include "includes.php";
include "inc/incfunctions.php";
if(@$storesessionvalue=="") $storesessionvalue="virtualstore";
if(@$_SESSION["loggedon"] != $storesessionvalue){
	if(@$_SERVER["HTTPS"] == "on" || @$_SERVER["SERVER_PORT"] == "443")$prot='https://';else $prot='http://';
	header('Location: '.$prot.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/login.php');
	exit;
}
header("Content-type: unknown/exe");
if(@$_POST["act"]=="dumpinventory" || @$_POST["act"]=="dump2COinventory")
	header("Content-Disposition: attachment;filename=dumpinventory.csv");
elseif(@$_POST["act"]=="dumpaffiliate")
	header("Content-Disposition: attachment;filename=affilreport.csv");
else
	header("Content-Disposition: attachment;filename=dumporders.csv");
$admindatestr="Y-m-d";
if(@$admindateformat=="") $admindateformat=0;
if($admindateformat==1)
	$admindatestr="m/d/Y";
elseif($admindateformat==2)
	$admindatestr="d/m/Y";
if(@$_POST["sd"] != "")
	$sd = @$_POST["sd"];
elseif(@$_GET["sd"] != "")
	$sd = @$_GET["sd"];
else
	$sd = date($admindatestr);
if(@$_POST["ed"] != "")
	$ed = @$_POST["ed"];
elseif(@$_GET["ed"] != "")
	$ed = @$_GET["ed"];
else
	$ed = date($admindatestr);
$sd = parsedate($sd);
$ed = parsedate($ed);
$hasdetails = (@$_POST["details"]=="true");
$sslok=TRUE;
if(@$_SERVER["HTTPS"] != "on" && (@$_SERVER["SERVER_PORT"] != "443") && @$nochecksslserver != TRUE) $sslok = FALSE;
if(@$_POST["act"]=="dumpaffiliate"){
	print "Affiliate report for " . date($admindatestr, $sd) . " to " . date($admindatestr, $ed) . "\r\n";
	print '"ID","Name","Address","City","State","Zip","Country","Email","Total"' . "\r\n";
	$sSQL = "SELECT affilID,affilName,affilAddress,affilCity,affilState,affilZip,affilCountry,affilEmail FROM affiliates ORDER BY affilID";
	$result = mysql_query($sSQL) or print(mysql_error());
	while($rs = mysql_fetch_assoc($result)){
		print '"' . str_replace('"','""',$rs["affilID"]) . '",';
		print '"' . str_replace('"','""',$rs["affilName"]) . '",';
		print '"' . str_replace('"','""',$rs["affilAddress"]) . '",';
		print '"' . str_replace('"','""',$rs["affilCity"]) . '",';
		print '"' . str_replace('"','""',$rs["affilState"]) . '",';
		print '"' . str_replace('"','""',$rs["affilZip"]) . '",';
		print '"' . str_replace('"','""',$rs["affilCountry"]) . '",';
		print '"' . str_replace('"','""',$rs["affilEmail"]) . '",';
		$sSQL2 = "SELECT SUM(ordTotal-ordDiscount) FROM affiliates LEFT JOIN orders ON affiliates.affilID=orders.ordAffiliate WHERE affilID='" . $rs["affilID"] . "' AND ordStatus>=3 AND ordDate BETWEEN '" . date("Y-m-d", $sd) . "' AND '" . date("Y-m-d", $ed) . " 23:59:59'";
		$alldata2 = mysql_query($sSQL2) or print(mysql_error());
		$rs2=mysql_fetch_array($alldata2);
		print $rs2[0] . "\r\n";
		mysql_free_result($alldata2);
	}
	mysql_free_result($result);
}elseif(@$_POST["act"]=="dumpinventory"){
	$sSQL2 = "SELECT pID,pName,pPrice,pInStock,pSell FROM products";
	$result = mysql_query($sSQL2) or print(mysql_error());
	print "\"ProductID\",\"ProductName\",\"Price\",\"InStock\",\"OptionGroup\",\"Options\"\r\n";
	while($rs = mysql_fetch_assoc($result)){
		if(($rs["pSell"] & 2) == 2){
			$result2 = mysql_query("SELECT optGrpName,optName,optStock FROM optiongroup INNER JOIN options ON optiongroup.optGrpID=options.optGroup INNER JOIN prodoptions ON options.optGroup=prodoptions.poOptionGroup WHERE prodoptions.poProdID='" . mysql_escape_string($rs["pID"]) . "'") or print(mysql_error());
			while($rs2 = mysql_fetch_assoc($result2)){
				print '"' . str_replace('"','""',$rs["pID"]) . '",';
				print '"' . str_replace('"','""',$rs["pName"]) . '",';
				print '"' . $rs["pPrice"] . '",';
				print $rs2["optStock"] . ",";
				print '"' . str_replace('"','""',$rs2["optGrpName"]) . '",';
				print '"' . str_replace('"','""',$rs2["optName"]) . '"'  . "\r\n";
			}
		}else{
			print '"' . str_replace('"','""',$rs["pID"]) . '",';
			print '"' . str_replace('"','""',$rs["pName"]) . '",';
			print '"' . $rs["pPrice"] . '",';
			print $rs["pInStock"] . "\r\n";
		}
	}
	mysql_free_result($result);
}elseif(@$_POST["act"]=="dump2COinventory"){
	$sSQL2 = "SELECT payProvData1 FROM payprovider WHERE payProvID=2";
	$result = mysql_query($sSQL2) or print(mysql_error());
	$rs = mysql_fetch_assoc($result);
	print $rs["payProvData1"] . "\r\n";
	mysql_free_result($result);
	$sSQL2 = "SELECT pID,pName,pPrice," . (@$digidownloads==TRUE ? "pDownload," : "") . "pDescription FROM products";
	$result = mysql_query($sSQL2) or print(mysql_error());
	while($rs = mysql_fetch_assoc($result)){
		print str_replace(',', '\\,', $rs["pID"]) . ",";
		print preg_replace("(\r\n|\n|\r)",' ',str_replace(',', '\\,', $rs["pName"])) . ",";
		print ",";
		print $rs["pPrice"] . ",";
		print ",,";
		if(@$digidownloads==TRUE)
			print (trim($rs["pDownload"]) != "" ? "N" : "Y") . ",";
		else
			print 'Y,';
		print preg_replace("(\r\n|\n|\r)",'\\n',str_replace(',','\\,',strip_tags($rs["pDescription"]))) . "\r\n";
	}
	mysql_free_result($result);
}else{
	if($hasdetails)
		$sSQL2 = "SELECT ordID,ordName,ordAddress,ordCity,ordState,ordZip,ordCountry,ordEmail,ordPhone,ordExtra1,ordExtra2,ordShipName,ordShipAddress,ordShipCity,ordShipState,ordShipZip,ordShipCountry,payProvName,ordAuthNumber,ordTotal,ordDate,ordStateTax,ordCountryTax,ordHSTTax,ordShipping,ordDiscount,ordAddInfo,ordShipType,cartProdId,cartProdName,cartProdPrice,cartQuantity,cartID FROM cart LEFT JOIN orders ON cart.cartOrderId=orders.ordID LEFT JOIN payprovider ON payprovider.payProvID=orders.ordPayProvider";
	else
		$sSQL2 = "SELECT ordID,ordName,ordAddress,ordCity,ordState,ordZip,ordCountry,ordEmail,ordPhone,ordExtra1,ordExtra2,ordShipName,ordShipAddress,ordShipCity,ordShipState,ordShipZip,ordShipCountry,payProvName,ordAuthNumber,ordTotal,ordDate,ordStateTax,ordCountryTax,ordHSTTax,ordShipping,ordDiscount,ordAddInfo,ordShipType FROM orders LEFT JOIN payprovider ON payprovider.payProvID=orders.ordPayProvider";
	if(@$_POST["powersearch"]=="1"){
		$fromdate = trim(@$_POST["fromdate"]);
		$todate = trim(@$_POST["todate"]);
		$ordid = trim(str_replace('"','',str_replace("'","",@$_POST["ordid"])));
		$searchtext = trim(mysql_escape_string(unstripslashes(@$_POST["searchtext"])));
		$ordstatus = "";
		$addcomma = "";
		if(is_array(@$_POST["ordstatus"])){
			foreach($_POST["ordstatus"] as $objValue){
				$ordstatus .= $addcomma . $objValue;
				$addcomma = ",";
			}
		}else
			$ordstatus = trim(@$_POST["ordstatus"]);
		$sSQL2 .= " WHERE ordStatus>0";
		if($ordid != ""){
			if(is_numeric($ordid)){
				$sSQL2 .= " AND ordID=" . $ordid;
			}else{
				$success=FALSE;
				$errmsg="The order id you specified seems to be invalid - " . $ordid;
				$sSQL2 .= " AND ordID=0";
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
				$sSQL2 .= " AND ordDate BETWEEN '" . date("Y-m-d", $sd) . "' AND '" . date("Y-m-d", $ed) . " 23:59:59'";
			}
			if($ordstatus != "" && strpos($ordstatus,"9999")===FALSE) $sSQL2 .= " AND ordStatus IN (" . $ordstatus . ")";
			if($searchtext != "") $sSQL2 .= " AND (ordAuthNumber LIKE '%" . $searchtext . "%' OR ordName LIKE '%" . $searchtext . "%' OR ordEmail LIKE '%" . $searchtext . "%' OR ordAddress LIKE '%" . $searchtext . "%' OR ordCity LIKE '%" . $searchtext . "%' OR ordState LIKE '%" . $searchtext . "%' OR ordZip LIKE '%" . $searchtext . "%' OR ordPhone LIKE '%" . $searchtext . "%')";
		}
		$sSQL2 .= " ORDER BY ordID";
	}else{
		$sSQL2 .= " WHERE ordDate BETWEEN '" . date("Y-m-d", $sd) . "' AND '" . date("Y-m-d", $ed) . " 23:59:59' ORDER BY ordID";
	}
	$sSQL = "SELECT countryLCID, countryCurrency, adminStockManage FROM admin LEFT JOIN countries ON admin.adminCountry=countries.countryID WHERE adminID=1";
	$result = mysql_query($sSQL) or print(mysql_error());
	$rs = mysql_fetch_array($result);
	$useEuro = ($rs["countryCurrency"]=="EUR");
	$stockManage = (int)$rs["adminStockManage"];
	mysql_free_result($result);
	$result = mysql_query($sSQL2) or print(mysql_error());
	print '"OrderID",';
	if(@$extraorderfield1 != '') print '"' . str_replace('"','""',$extraorderfield1) . '",';
	print '"CustomerName","Address","City","State","Zip","Country","Email","Phone",';
	if(@$extraorderfield2 != '') print '"' . str_replace('"','""',$extraorderfield2) . '",';
	print '"ShipName","ShipAddress","ShipCity","ShipState","ShipZip","ShipCountry","PaymentMethod","AuthNumber","Total","Date","StateTax","CountryTax",';
	if(@$canadataxsystem==true) print '"HST",';
	print '"Shipping","Discounts","AddInfo","ShipingMethod"';
	if(@$dumpccnumber) print ',"Card Number","Expiry Date","CVV Code","Issue Number"';
	if($hasdetails) print ',"ProductID","ProductName","ProductPrice","Quantity","Options"';
	print "\r\n";
	while($rs = mysql_fetch_assoc($result)){
			print $rs["ordID"] . ",";
			if(@$extraorderfield1 != '') print '"' . str_replace('"','""',$rs["ordExtra1"]) . '",';
			print '"' . str_replace('"','""',$rs["ordName"]) . '",';
			print '"' . str_replace('"','""',$rs["ordAddress"]) . '",';
			print '"' . str_replace('"','""',$rs["ordCity"]) . '",';
			print '"' . str_replace('"','""',$rs["ordState"]) . '",';
			print '"' . str_replace('"','""',$rs["ordZip"]) . '",';
			print '"' . str_replace('"','""',$rs["ordCountry"]) . '",';
			print '"' . str_replace('"','""',$rs["ordEmail"]) . '",';
			print '"' . str_replace('"','""',$rs["ordPhone"]) . '",';
			if(@$extraorderfield2 != '') print '"' . str_replace('"','""',$rs["ordExtra2"]) . '",';
			print '"' . str_replace('"','""',$rs["ordShipName"]) . '",';
			print '"' . str_replace('"','""',$rs["ordShipAddress"]) . '",';
			print '"' . str_replace('"','""',$rs["ordShipCity"]) . '",';
			print '"' . str_replace('"','""',$rs["ordShipState"]) . '",';
			print '"' . str_replace('"','""',$rs["ordShipZip"]) . '",';
			print '"' . str_replace('"','""',$rs["ordShipCountry"]) . '",';
			print '"' . str_replace('"','""',$rs["payProvName"]) . '",';
			print '"' . str_replace('"','""',$rs["ordAuthNumber"]) . '",';
			print '"' . $rs["ordTotal"] . '",';
			print '"' . $rs["ordDate"] . '",';
			print '"' . $rs["ordStateTax"] . '",';
			print '"' . $rs["ordCountryTax"] . '",';
			if(@$canadataxsystem==true) print '"' . $rs["ordHSTTax"] . '",';
			print '"' . $rs["ordShipping"] . '",';
			print '"' . $rs["ordDiscount"] . '",';
			print '"' . str_replace('"','""',$rs["ordAddInfo"]) . '",';
			print '"' . str_replace('"','""',$rs["ordShipType"]) . '"';
			if(@$dumpccnumber){
				if($sslok==FALSE){
					print "No SSL,No SSL,No SSL,No SSL";
				}else{
					$result2 = mysql_query("SELECT ordCNum FROM orders WHERE ordID=" . $rs["ordID"]) or print(mysql_error());
					$rs2 = mysql_fetch_array($result2);
					$ordCNum = $rs2["ordCNum"];
					if(trim($ordCNum)=="" || is_null($ordCNum)){
						print ',"(no data)","","",""';
					}elseif($encryptmethod=="none"){
						$cnumarr = explode("&",$ordCNum);
						if(is_array($cnumarr)){
							print ',"""' . $cnumarr[0] . '"""';
							print ',"""' . @$cnumarr[1] . '"""';
							print ',"' . @$cnumarr[2] . '"';
							print ',"' . @$cnumarr[3] . '"';
						}else
							print ',"(no data)","","",""';
					}
					mysql_free_result($result2);
				}
			}
			if($hasdetails){
				$theOptions = "";
				$thePriceDiff = 0;
				$result2 = mysql_query("SELECT coPriceDiff,coOptGroup,coCartOption FROM cartoptions WHERE coCartID=" . $rs["cartID"]) or print(mysql_error());
				while($rs2 = mysql_fetch_assoc($result2)){
					$theOptions .= "," . '"' . str_replace('"','""',$rs2["coOptGroup"]) . " - " . str_replace('"','""',$rs2["coCartOption"]) . '"';
					$thePriceDiff += $rs2["coPriceDiff"];
				}
				print ',"' . str_replace('"','""',$rs["cartProdId"]) . '"';
				print ',"' . str_replace('"','""',$rs["cartProdName"]) . '"';
				print ',' . ($rs["cartProdPrice"] + $thePriceDiff);
				print ',' . $rs["cartQuantity"];
				print $theOptions;
				mysql_free_result($result2);
			}
			print "\r\n";
	}
}
?>