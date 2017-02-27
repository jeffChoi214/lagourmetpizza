<?php
//This code is copyright (c) Internet Business Solutions SL, all rights reserved.
//The contents of this file are protect under law as the intellectual property of Internet
//Business Solutions SL. Any use, reproduction, disclosure or copying of any kind 
//without the express and written permission of Internet Business Solutions SL is forbidden.
//Author: Vince Reid, vince@virtualred.net
$numfirstclass=0;
$firstclasscost=0;
function ParseXMLOutput($sXML, $international, &$totalCost, &$errormsg, &$intShipping){
	global $iTotItems, $numfirstclass, $firstclasscost, $xxDay,$xxDays;
	$noError = TRUE;
	$totalCost = 0;
	$packCost = 0;
	$errormsg = "";
	$xmlDoc = new vrXMLDoc($sXML);

	if($xmlDoc->nodeList->nodeName[0] == "Error"){ // Top-level Error
		$noError = FALSE;
		$nodeList = $xmlDoc->nodeList->childNodes[0];
		for($i = 0; $i < $nodeList->length; $i++){
			if($nodeList->nodeName[$i]=="Description"){
				$errormsg = $nodeList->nodeValue[$i];
			}
		}
	}else{ // no Top-level Error
		$nodeList = $xmlDoc->nodeList->childNodes[0];
		for($i = 0; $i < $nodeList->length; $i++){
			if($nodeList->nodeName[$i]=="Package"){
				$tmpArr = split('\|', getattributes($nodeList->attributes[$i], 'ID'));
				$quantity = (int)$tmpArr[1];
				$e = $nodeList->childNodes[$i];
				for($j = 0; $j < $nodeList->childNodes[$i]->length; $j++){
					if($e->nodeName[$j] == "Error"){ // Lower-level error
						$noError = FALSE;
						$t = $e->childNodes[$j];
						for($k = 0; $k < $t->length; $k++){
							if($t->nodeName[$k] == "Description")
								$errormsg = $t->nodeValue[$k];
						}
					}else{
						if($e->nodeName[$j] == "Postage"){
							//$packCost += $e->nodeValue[$j];
							if($international == ""){
								$l = 0;
								while($intShipping[$l][0] != $thisService && $intShipping[$l][0] != "")
									$l++;
								$intShipping[$l][0] = $thisService;
								if($thisService=="PARCEL")
									$intShipping[$l][1] = "2-7 " . $xxDays;
								elseif($thisService=="EXPRESS")
									$intShipping[$l][1] = "Overnight to most areas";
								elseif($thisService=="PRIORITY")
									$intShipping[$l][1] = "1-2 " . $xxDays;
								elseif($thisService=="BPM")
									$intShipping[$l][1] = "2-7 " . $xxDays;
								elseif($thisService=="Media")
									$intShipping[$l][1] = "2-7 " . $xxDays;
								$intShipping[$l][2] = $intShipping[$l][2] + ($e->nodeValue[$j] * $quantity);
								$intShipping[$l][3] = $intShipping[$l][3] + 1;
							}
						}elseif($e->nodeName[$j] == "Service"){
							if($international != ""){
								$t = $e->childNodes[$j];
								for($k = 0; $k < $t->length; $k++){
									if($t->nodeName[$k] == "SvcDescription")
										$SvcDescription = $t->nodeValue[$k];
									elseif($t->nodeName[$k] == "SvcCommitments")
										$SvcCommitments = $t->nodeValue[$k];
									elseif($t->nodeName[$k] == "Postage")
										$Postage = $t->nodeValue[$k];
								}
								$l = 0;
								while($intShipping[$l][0] != "" && $intShipping[$l][0] != $SvcDescription)
									$l++;
								$intShipping[$l][0] = $SvcDescription;
								$intShipping[$l][1] = $SvcCommitments;
								$intShipping[$l][2] += ($Postage * $quantity);
								$intShipping[$l][3]++;
							}
							else
								$thisService = $e->nodeValue[$j];
						}
					}
				}
				$totalCost += $packCost;
				$packCost = 0;
			}
		}
		if($iTotItems==$numfirstclass){
			$l = 0;
			while($intShipping[$l][0] != "")
				$l++;
			$intShipping[$l][0] = "FIRSTCLASS";
			$intShipping[$l][1] = "1-3 " . $xxDays;
			$intShipping[$l][2] = $firstclasscost;
			$intShipping[$l][3] = $numfirstclass;
		}
	}
	return $noError;
}
function checkUPSShippingMeth($method, &$discountsApply){
	global $numuspsmeths, $uspsmethods;
	for($index=0; $index < $numuspsmeths; $index++){
		if($method==$uspsmethods[$index][0]){
			$discountsApply = $uspsmethods[$index][1];
			return(TRUE);
		}
	}
	return(FALSE);
}
function ParseUPSXMLOutput($sXML, $international, &$totalCost, &$errormsg, &$errorcode, &$intShipping){
	global $xxDay,$xxDays;
	$noError = TRUE;
	$totalCost = 0;
	$packCost = 0;
	$errormsg = "";
	$l = 0;
	$discntsApp = "";

	$xmlDoc = new vrXMLDoc($sXML);
	$nodeList = $xmlDoc->nodeList->childNodes[0];
	for($i = 0; $i < $nodeList->length; $i++){
		if($nodeList->nodeName[$i]=="Response"){
			$e = $nodeList->childNodes[$i];
			for($j = 0; $j < $e->length; $j++){
				if($e->nodeName[$j]=="ResponseStatusCode"){
					$noError = ((int)$e->nodeValue[$j])==1;
				}
				if($e->nodeName[$j]=="Error"){
					$errormsg = "";
					$t = $e->childNodes[$j];
					for($k = 0; $k < $t->length; $k++){
						if($t->nodeName[$k]=="ErrorCode"){
							$errorcode = $t->nodeValue[$k];
						}elseif($t->nodeName[$k]=="ErrorSeverity"){
							if($t->nodeValue[$k]=="Transient")
								$errormsg = "This is a temporary error. Please wait a few moments then refresh this page.<br />" . $errormsg;
						}elseif($t->nodeName[$k]=="ErrorDescription"){
							$errormsg .= $t->nodeValue[$k];
						}
					}
				}
				// print "The Nodename is : " . e.nodeName . ":" . e.firstChild.nodeValue . "<br />";
			}
		}elseif($nodeList->nodeName[$i]=="RatedShipment"){ // no Top-level Error
			$wantthismethod=TRUE;
			$nodeList = $xmlDoc->nodeList->childNodes[0];
			$e = $nodeList->childNodes[$i];
			for($j = 0; $j < $e->length; $j++){
				if($e->nodeName[$j] == "Service"){ // Lower-level error
					$t = $e->childNodes[$j];
					for($k = 0; $k < $t->length; $k++){
						if($t->nodeName[$k]=="Code"){
							if($t->nodeValue[$k]=="01")
								$intShipping[$l][0] = "UPS Next Day Air&reg;";
							elseif($t->nodeValue[$k]=="02")
								$intShipping[$l][0] = "UPS 2nd Day Air&reg;";
							elseif($t->nodeValue[$k]=="03")
								$intShipping[$l][0] = "UPS Ground";
							elseif($t->nodeValue[$k]=="07")
								$intShipping[$l][0] = "UPS Worldwide Express";
							elseif($t->nodeValue[$k]=="08")
								$intShipping[$l][0] = "UPS Worldwide Expedited";
							elseif($t->nodeValue[$k]=="11")
								$intShipping[$l][0] = "UPS Standard";
							elseif($t->nodeValue[$k]=="12")
								$intShipping[$l][0] = "UPS 3 Day Select&reg;";
							elseif($t->nodeValue[$k]=="13")
								$intShipping[$l][0] = "UPS Next Day Air Saver&reg;";
							elseif($t->nodeValue[$k]=="14")
								$intShipping[$l][0] = "UPS Next Day Air&reg; Early A.M.&reg;";
							elseif($t->nodeValue[$k]=="54")
								$intShipping[$l][0] = "UPS Worldwide Express Plus";
							elseif($t->nodeValue[$k]=="59")
								$intShipping[$l][0] = "UPS 2nd Day Air A.M.&reg;";
							elseif($t->nodeValue[$k]=="65")
								$intShipping[$l][0] = "UPS Express Saver";
							$wantthismethod = checkUPSShippingMeth($t->nodeValue[$k], $discntsApp);
							$intShipping[$l][4] = $discntsApp;
						}
					}
				}elseif($e->nodeName[$j] == "TotalCharges"){
					$t = $e->childNodes[$j];
					for($k = 0; $k < $t->length; $k++){
						if($t->nodeName[$k]=="MonetaryValue"){
							$intShipping[$l][2] = (double)$t->nodeValue[$k];
						}
					}
				}elseif($e->nodeName[$j] == "GuaranteedDaysToDelivery"){
					if(strlen($e->nodeValue[$j]) > 0){
						if($e->nodeValue[$j]=="1")
							$intShipping[$l][1] = "1 " . $xxDay . $intShipping[$l][1];
						else
							$intShipping[$l][1] = $e->nodeValue[$j] . " " . $xxDays . $intShipping[$l][1];
					}
				}elseif($e->nodeName[$j] == "ScheduledDeliveryTime"){
					if(strlen($e->nodeValue[$j]) > 0){
						$intShipping[$l][1] .= " by " . $e->nodeValue[$j];
					}
				}
			}
			if($wantthismethod){
				$intShipping[$l][3] = TRUE;
				$l++;
			}else
				$intShipping[$l][1] = "";
			$wantthismethod=TRUE;
		}
	}
	return $noError;
}

function ParseCanadaPostXMLOutput($sXML, $international, &$totalCost, &$errormsg, &$errorcode, &$intShipping){
	global $xxDay,$xxDays;
	$noError = TRUE;
	$totalCost = 0;
	$packCost = 0;
	$errormsg = "";
	$discntsApp = "";
	$l = strpos($sXML, ']>');
	if($l > 0) $sXML = substr($sXML, $l+2);
	$l = 0;

	$xmlDoc = new vrXMLDoc($sXML);
	$nodeList = $xmlDoc->nodeList->childNodes[0];
	for($i = 0; $i < $nodeList->length; $i++){
		if($nodeList->nodeName[$i]=="error"){
			$noError = FALSE;
			$e = $nodeList->childNodes[$i];
			for($j = 0; $j < $e->length; $j++){
				if($e->nodeName[$j]=="statusCode"){
					$errorcode = $e->nodeValue[$j];
				}elseif($e->nodeName[$j]=="statusMessage"){
					$errormsg = $e->nodeValue[$j];
				}
			}
		}elseif($nodeList->nodeName[$i]=="ratesAndServicesResponse"){ // no Top-level Error
			$wantthismethod=TRUE;
			$nodeList = $xmlDoc->nodeList->childNodes[0];
			$e = $nodeList->childNodes[$i];
			for($j = 0; $j < $e->length; $j++){
				if($e->nodeName[$j] == "product"){
					$wantthismethod = checkUPSShippingMeth(getattributes($e->attributes[$j], 'id'), $discntsApp);
					$intShipping[$l][4] = $discntsApp;
					$wantthismethod=TRUE;
					$t = $e->childNodes[$j];
					for($k = 0; $k < $t->length; $k++){
						if($t->nodeName[$k]=="name"){
							$intShipping[$l][0] = $t->nodeValue[$k];
						}elseif($t->nodeName[$k]=="rate"){
							$intShipping[$l][2] = (double)$t->nodeValue[$k];
						}elseif($t->nodeName[$k]=="deliveryDate"){
							$today = getdate();
							$daytoday = $today["yday"];
							if(($ttimeval = strtotime($t->nodeValue[$k])) < 0){
								$intShipping[$l][1] = $t->nodeValue[$k] . $intShipping[$l][1];
							}else{
								$deldate = getdate($ttimeval);
								$daydeliv = $deldate["yday"];
								if($daydeliv < $daytoday) $daydeliv+=365;
								$intShipping[$l][1] = ($daydeliv - $daytoday) . " " . ($daydeliv - $daytoday < 2?$xxDay:$xxDays) . $intShipping[$l][1];
							}
						}elseif($t->nodeName[$k]=="nextDayAM"){
							if($t->nodeValue[$k]=="true")
								$intShipping[$l][1] = $intShipping[$l][1] . " AM";
						}
					}
					if($wantthismethod){
						$intShipping[$l][3] = TRUE;
						$l++;
					}else
						$intShipping[$l][1] = "";
					$wantthismethod=TRUE;
				}
			}
		}
	}
	return $noError;
}

function addDomestic($id,$service,$orig,$dest,$iWeight,$quantity,$container,$size,$machinable){
	global $numuspsmeths,$uspsmethods,$numfirstclass,$firstclasscost;
	$sXML="";
	$pounds = (int)$iWeight;
	$ounces = round(($iWeight-$pounds)*16.0);
	if($pounds==0 && $ounces==0) $ounces=1;
	for($index=0;$index<$numuspsmeths;$index++){
		if($uspsmethods[$index][0]=="FIRSTCLASS"){
			if($pounds==0 && $ounces<=13){
				$numfirstclass++;
				$firstclasscost += (((double)$ounces*0.23)+0.14);
			}
		}else{
			$sXML .= "<Package ID=\"" . $uspsmethods[$index][0] . $id . '|' . $quantity . "\">";
			$sXML .= "<Service>" . $uspsmethods[$index][0] . "</Service>";
			$sXML .= "<ZipOrigination>" . $orig . "</ZipOrigination>";
			$sXML .= "<ZipDestination>" . $dest . "</ZipDestination>";
			$sXML .= "<Pounds>" . $pounds . "</Pounds>";
			$sXML .= "<Ounces>" . $ounces . "</Ounces>";
			$sXML .= "<Container>" . $container . "</Container>";
			$sXML .= "<Size>" . $size . "</Size>";
			$sXML .= "<Machinable>" . $machinable . "</Machinable>";
			$sXML .= "</Package>";
		}
	}
	return $sXML;
}

function addInternational($id,$iWeight,$quantity,$mailtype,$country){
	$pounds = (int)$iWeight;
	$ounces = round(($iWeight-$pounds)*16.0);
	if($pounds==0 && $ounces==0) $ounces=1;
	$sXML = "<Package ID=\"" . $id . '|' . $quantity . "\">";
		$sXML .= "<Pounds>" . $pounds . "</Pounds>";
		$sXML .= "<Ounces>" . $ounces . "</Ounces>";
		$sXML .= "<MailType>" . $mailtype . "</MailType>";
		$sXML .= "<Country>" . $country . "</Country>";
	return $sXML . "</Package>";
}

function addUPSInternational($iWeight,$adminUnits,$packTypeCode,$country,$packcost){
	global $addshippinginsurance, $countryCurrency;
	if($iWeight<0.1) $iWeight=0.1;
	$sXML = "<Package><PackagingType><Code>" . $packTypeCode . "</Code><Description>Package</Description></PackagingType>";
	$sXML .= "<Description>Rate Shopping</Description><PackageWeight><UnitOfMeasurement><Code>" . $adminUnits . "</Code></UnitOfMeasurement><Weight>" . $iWeight . "</Weight></PackageWeight>";
	if(abs(@$addshippinginsurance)==1 || (abs(@$addshippinginsurance)==2 && trim(@$_POST["wantinsurance"])=="Y")){
		if($packcost > 50000) $packcost=50000;
		$sXML .= "<PackageServiceOptions><InsuredValue><CurrencyCode>" . $countryCurrency . "</CurrencyCode><MonetaryValue>" . number_format($packcost,2,'.','') . "</MonetaryValue></InsuredValue></PackageServiceOptions>";
	}
	return $sXML . "</Package>";
}

function addCanadaPostPackage($iWeight,$adminUnits,$packTypeCode,$country,$packcost,$dimens){
	global $addshippinginsurance, $countryCurrency, $packtogether, $productdimensions;
	if($iWeight<0.1) $iWeight=0.1;
	if($packtogether) $thesize = 1; else $thesize = 19;
	if(@$productdimensions==TRUE){
		$proddims = split("x", $dimens);
		if(@$proddims[0] != '') $thelength = $proddims[0]; else $thelength = $thesize;
		if(@$proddims[1] != '') $thewidth = $proddims[1]; else $thewidth = $thesize;
		if(@$proddims[2] != '') $theheight =  $proddims[2]; else $theheight = $thesize;
	}else{
		$thelength = $thesize;
		$thewidth = $thesize;
		$theheight =  $thesize;
	}
	$sXML = "<item><quantity> 1 </quantity><weight> " . $iWeight . " </weight><length> ".$thelength." </length><width> ".$thewidth." </width><height> ".$theheight." </height><description> Goods for shipping rates selection </description></item>";
	return $sXML;
}

function USPSCalculate($sXML,$international,&$totalCost,&$errormsg,&$intShipping){
	$sXML = "API=" . $international . "Rate&XML=" . $sXML;
	$header = "POST /ShippingAPI.dll HTTP/1.0\r\n";
	$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$header .= 'Content-Length: ' . strlen($sXML) . "\r\n\r\n";
	$fp = fsockopen ('production.shippingapis.com', 80, $errno, $errstr, 30);
	if (!$fp){
		echo "$errstr ($errno)"; // HTTP error handling
		return FALSE;
	}else{
		$res = "";
		fputs ($fp, $header . $sXML);
		while (!feof($fp)) {
			$res .= fgets ($fp, 1024);
		}
		fclose ($fp);
		// print str_replace("<","<br />&lt;",$res) . "<br />\n";
		return ParseXMLOutput($res, $international, $totalCost, $errormsg, $intShipping);
	}
	//for i=0 to 9
	//	response.write intShipping(0,i)&":"&intShipping(1,i)&":"&intShipping(2,i)&":"&intShipping(3,i)&"<br />"
	//next
}

function UPSCalculate($sXML,$international,&$totalCost, &$errormsg, &$intShipping){
	global $pathtocurl;
	$success = true;
	if(@$pathtocurl != ""){
		exec($pathtocurl . ' --data-binary \'' . str_replace("'","\'",$sXML) . '\' https://www.ups.com/ups.app/xml/Rate', $res, $retvar);
		$res = implode("\n",$res);
	}else{
		if (!$ch = curl_init()) {
			$success = false;
			$errormsg = "cURL package not installed in PHP";
		}else{
			curl_setopt($ch, CURLOPT_URL,'https://www.ups.com/ups.app/xml/Rate'); 
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $sXML);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$res = curl_exec($ch);
			if(curl_error($ch) != ""){
				$vsRESPMSG= "Error with cURL installation: " . curl_error($ch) . "<br />";
				flush();
			}
			curl_close($ch);
		}
	}
	if($success){
		// print str_replace("<","<br />&lt;",$res) . "<br />\n";
		$success = ParseUPSXMLOutput($res, $international, $totalCost, $errormsg, $errorcode, $intShipping);
		if($errorcode == 111210) $errormsg = "The destination zip / postal code is invalid.";
	}
	return $success;
}

function CanadaPostCalculate($sXML,$international,&$totalCost, &$errormsg, &$intShipping){
	global $pathtocurl, $canadaposttest;
	$success = true;
	// print str_replace("<","<br />&lt;",$sXML) . "<HR>\n";
	$header = "POST / HTTP/1.0\r\n";
	$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$header .= 'Content-Length: ' . strlen($sXML) . "\r\n\r\n";
	if(@$canadaposttest==TRUE)
		$fp = fsockopen ('206.191.4.228', 30000, $errno, $errstr, 30);
	else
		$fp = fsockopen ('216.191.36.73', 30000, $errno, $errstr, 30);

	if (!$fp){
		echo "$errstr ($errno)"; // HTTP error handling
		return FALSE;
	}else{
		$res = "";
		fputs ($fp, $header . $sXML);
		while (!feof($fp)) {
			$res .= fgets ($fp, 1024);
		}
		fclose ($fp);
		// print str_replace("<","<br />&lt;",$res) . "<br />\n";
		$success = ParseCanadaPostXMLOutput($res, $international, $totalCost, $errormsg, $errorcode, $intShipping);
	}
	return $success;
}
?>
