<?php
//This code is copyright (c) Internet Business Solutions SL, all rights reserved.
//The contents of this file are protect under law as the intellectual property of Internet
//Business Solutions SL. Any use, reproduction, disclosure or copying of any kind 
//without the express and written permission of Internet Business Solutions SL is forbidden.
//Author: Vince Reid, vince@virtualred.net
@set_magic_quotes_runtime(0);
$magicq = (get_magic_quotes_gpc()==1);
if(@$emailencoding=="") $emailencoding="iso-8859-1";
if(@$adminencoding=="") $adminencoding="iso-8859-1";
function parsedate($tdat){
	global $admindateformat;
	if($admindateformat==0)
		list($year, $month, $day) = sscanf($tdat, "%d-%d-%d");
	elseif($admindateformat==1)
		list($month, $day, $year) = sscanf($tdat, "%d/%d/%d");
	elseif($admindateformat==2)
		list($day, $month, $year) = sscanf($tdat, "%d/%d/%d");
	if(! is_numeric($year))
		$year = date("Y");
	elseif((int)$year < 39)
		$year = (int)$year + 2000;
	elseif((int)$year < 100)
		$year = (int)$year + 1900;
	if($year < 1970 || $year > 2038) $year = date("Y");
	if(! is_numeric($month))
		$month = date("m");
	if(! is_numeric($day))
		$day = date("d");
	return(mktime(0, 0, 0, $month, $day, $year));
}
function unstripslashes($slashedText){
	global $magicq;
	if($magicq)
		return stripslashes($slashedText);
	else
		return $slashedText;
}
function getattributes($attlist,$attid){
	$pos = strpos($attlist, $attid.'=');
	if($pos === false)
		return '';
	$pos += strlen($attid) + 1;
	$quote = $attlist[$pos];
	$pos2 = strpos($attlist, $quote, $pos + 1);
	$retstr = substr($attlist, $pos + 1, $pos2 - ($pos + 1));
	return($retstr); 
}
class vrNodeList{
	var $length;
	var $childNodes;
	var $nodeName;
	var $nodeValue;
	var $attributes;

	function createNodeList($xmlStr){
		$xLen = strlen($xmlStr);
		for($i=0; $i < $xLen; $i++){
			if(substr($xmlStr, $i, 1)=="<" && substr($xmlStr, $i+1, 1) != "/" && substr($xmlStr, $i+1, 1) != "?"){ // Got a tag
				$j = strpos($xmlStr,">",$i);
				$l = strpos($xmlStr," ",$i);
				if(is_integer($l) && $l < $j){
					$this->nodeName[$this->length]=substr($xmlStr,$i+1,$l-($i+1));
					$this->attributes[$this->length] = substr($xmlStr,$l+1,($j-$l)-1);
				}else
					$this->nodeName[$this->length]=substr($xmlStr,$i+1,$j-($i+1));
				// print "Got Node: " . $this->nodeName[$this->length] . "<BR>\n";
				$k = $i+1;
				$nodeNameLen=strlen($this->nodeName[$this->length]);
				$currLev=0;
				while($k < $xLen && $currLev >= 0){
					if(substr($xmlStr, $k, 2)=="</"){
						if($currLev==0 && substr($xmlStr, $k+2, $nodeNameLen)==$this->nodeName[$this->length])
							break;
						$currLev--;
					}elseif(substr($xmlStr, $k, 1)=="<")
						$currLev++;
					elseif(substr($xmlStr, $k, 2)=="/>")
						$currLev--;
					$k++;
				}
				$this->nodeValue[$this->length]=substr($xmlStr,$j+1,$k-($j+1));
				// print "Got Value: xxx" . str_replace("<","<br>&lt;",$this->nodeValue[$this->length]) . "xxx<BR>\n";
				$this->childNodes[$this->length] = new vrNodeList($this->nodeValue[$this->length]);
				$this->length++;
				$i = $k;
			}
		}
	}
	function vrNodeList($xmlStr){
		$this->length=0;
		$this->childNodes="";
		$this->createNodeList($xmlStr);
	}
}
class vrXMLDoc{
	var $tXMLStr;
	var $nodeList;

	function vrXMLDoc($xmlStr){
		$this->tXMLStr = $xmlStr;
		$this->nodeList = new vrNodeList($xmlStr);
	}

	function getElementsByTagName($tagname){
		$currlevel=0;
		$taglen = strlen($tagname);
	}
}
$netnav = TRUE;
if(strstr(@$HTTP_SERVER_VARS["HTTP_USER_AGENT"], "compatible") || strstr(@$HTTP_SERVER_VARS["HTTP_USER_AGENT"], "Gecko")) $netnav = FALSE;
function atb($size){
	global $netnav;
	if($netnav)
		return round($size / 2 + 1);
	else
		return $size;
}
$codestr="2952710692840328509902143349209039553396765";
function upsencode($thestr, $propcodestr){
	global $codestr;
	if($propcodestr=="") $localcodestr=$codestr; else $localcodestr=$propcodestr;
	$newstr="";
	for($index=0; $index < strlen($localcodestr); $index++){
		$thechar = substr($localcodestr,$index,1);
		if(! is_numeric($thechar)){
			$thechar = ord($thechar) % 10;
		}
		$newstr .= $thechar;
	}
	$localcodestr = $newstr;
	while(strlen($localcodestr) < 40)
		$localcodestr .= $localcodestr;
	$newstr="";
	for($index=0; $index < strlen($thestr); $index++){
		$thechar = substr($thestr,$index,1);
		$newstr .= chr(ord($thechar)+(int)substr($localcodestr,$index,1));
	}
	return $newstr;
}
function upsdecode($thestr, $propcodestr){
	global $codestr;
	if($propcodestr=="") $localcodestr=$codestr; else $localcodestr=$propcodestr;
	$newstr="";
	for($index=0; $index < strlen($localcodestr); $index++){
		$thechar = substr($localcodestr,$index,1);
		if(! is_numeric($thechar)){
			$thechar = ord($thechar) % 10;
		}
		$newstr .= $thechar;
	}
	$localcodestr = $newstr;
	while(strlen($localcodestr) < 40)
		$localcodestr .= $localcodestr;
	if(is_null($thestr)){
		return "";
	}else{
		$newstr="";
		for($index=0; $index < strlen($thestr); $index++){
			$thechar = substr($thestr,$index,1);
			$newstr .= chr(ord($thechar)-(int)substr($localcodestr,$index,1));
		}
		return($newstr);
	}
}
$locale_info = "";
function FormatEuroCurrency($amount){
	global $useEuro, $adminLocale, $locale_info, $overridecurrency, $orcsymbol, $orcdecplaces, $orcdecimals, $orcthousands, $orcpreamount;

	if(@$overridecurrency==TRUE){
		if($orcpreamount)
			return $orcsymbol . number_format($amount,$orcdecplaces,$orcdecimals,$orcthousands);
		else
			return number_format($amount,$orcdecplaces,$orcdecimals,$orcthousands) . $orcsymbol;
	}else{
		if(! is_array($locale_info)){
			setlocale(LC_ALL,$adminLocale);
			$locale_info = localeconv();
			setlocale(LC_ALL,"en_US");
		}
		if($useEuro)
			return number_format($amount,2,$locale_info["decimal_point"],$locale_info["thousands_sep"]) . " &euro;";
		else
			return $locale_info["currency_symbol"] . number_format($amount,2,$locale_info["decimal_point"],$locale_info["thousands_sep"]);
	}
}
function FormatEmailEuroCurrency($amount){
	global $useEuro, $adminLocale, $locale_info, $overridecurrency, $orcemailsymbol, $orcdecplaces, $orcdecimals, $orcthousands, $orcpreamount;

	if(@$overridecurrency==TRUE){
		if($orcpreamount)
			return $orcemailsymbol . number_format($amount,$orcdecplaces,$orcdecimals,$orcthousands);
		else
			return number_format($amount,$orcdecplaces,$orcdecimals,$orcthousands) . $orcemailsymbol;
	}else{
		if(! is_array($locale_info)){
			setlocale(LC_ALL,$adminLocale);
			$locale_info = localeconv();
			setlocale(LC_ALL,"en_US");
		}
		if($useEuro)
			return number_format($amount,2,$locale_info["decimal_point"],$locale_info["thousands_sep"]) . " Euro";
		else
			return $locale_info["currency_symbol"] . number_format($amount,2,$locale_info["decimal_point"],$locale_info["thousands_sep"]);
	}
}
if(trim(@$_GET["PARTNER"]) != ""){
	if(@$expireaffiliate == "") $expireaffiliate=30;
	print "<script src='vsadmin/savecookie.php?PARTNER=" . trim(@$_GET["PARTNER"]) . "&EXPIRES=" . $expireaffiliate . "'></script>";
}
$stockManage=0;
function do_stock_management($smOrdId){
	global $stockManage;
	if($stockManage != 0){
		$sSQL="SELECT cartID,cartProdID,cartQuantity,pSell FROM cart INNER JOIN products ON cart.cartProdID=products.pID WHERE cartCompleted=0 AND cartOrderID='" . mysql_escape_string(unstripslashes($smOrdId)) . "'";
		$result1 = mysql_query($sSQL) or print(mysql_error());
		while($rs1 = mysql_fetch_array($result1)){
			if(($rs1["pSell"] & 2) == 2){
				$sSQL = "SELECT coOptID FROM cartoptions INNER JOIN options ON cartoptions.coOptID=options.optID INNER JOIN optiongroup ON options.optGroup=optiongroup.optGrpID WHERE (optType=2 OR optType=-2) AND coCartID=" . $rs1["cartID"];
				$result2 = mysql_query($sSQL) or print(mysql_error());
				while($rs2 = mysql_fetch_array($result2)){
					$sSQL = "UPDATE options SET optStock=optStock-" . $rs1["cartQuantity"] . " WHERE optID=" . $rs2["coOptID"];
					mysql_query($sSQL) or print(mysql_error());
				}
				mysql_free_result($result2);
			}else{
				$sSQL = "UPDATE products SET pInStock=pInStock-" . $rs1["cartQuantity"] . " WHERE pID='" . $rs1["cartProdID"] . "'";
				mysql_query($sSQL) or print(mysql_error());
			}
		}
		mysql_free_result($result1);
	}
}
function productdisplayscript($doaddprodoptions){
global $prodoptions, $countryTax, $xxPrdEnt, $xxPrdChs, $xxPrd255, $xxOptOOS, $useStockManagement, $prodlist, $OWSP;
global $currSymbol1,$currFormat1,$currSymbol2,$currFormat2,$currSymbol3,$currFormat3;
if($currSymbol1!="" && $currFormat1=="") $currFormat1='%s <b>' . $currSymbol1 . '</b>';
if($currSymbol2!="" && $currFormat2=="") $currFormat2='%s <b>' . $currSymbol2 . '</b>';
if($currSymbol3!="" && $currFormat3=="") $currFormat3='%s <b>' . $currSymbol3 . '</b>';
?>
<script Language="JavaScript">
<!--
var aPC = new Array();
<?php if($useStockManagement) print "var aPS = new Array();\r\n" ?>
var isW3 = (document.getElementById&&true);
var tax=<?php print $countryTax ?>;
function dummyfunc(){};
<?php
$prodoptions="";
if($doaddprodoptions && $prodlist != ""){
	$sSQL = "SELECT DISTINCT optID," . $OWSP . "optPriceDiff,optStock FROM options INNER JOIN prodoptions ON options.optGroup=prodoptions.poOptionGroup WHERE prodoptions.poProdID IN (" . $prodlist . ")";
	$result = mysql_query($sSQL) or print(mysql_error());
	$rowcounter=0;
	while($row = mysql_fetch_array($result)){
		if($useStockManagement) print 'aPS[' . $row["optID"] . ']=' . $row["optStock"] . ';';
		print "aPC[". $row["optID"] . "]=" . $row["optPriceDiff"] . ";";
		if(($rowcounter % 10)==9) print "\r\n";
		$rowcounter++;
	}
	print "\r\n";
}
?>
function pricechecker(i){
	if(i!='')
		return(aPC[i]);
	return(0);
}
function enterValue(theObj){
	alert('<?php print str_replace("'","\'",$xxPrdEnt)?>');
	theObj.focus();
	return(false);
}
function checkStock(theObj,i){
	if(i!='' && aPS[i] > 0)
		return(true);
	alert('<?php print str_replace("'","\'",$xxOptOOS)?>');
	theObj.focus();
	return(false);
}
function chooseOption(theObj){
	alert('<?php print str_replace("'","\'",$xxPrdChs)?>');
	theObj.focus();
	return(false);
}
function dataLimit(theObj){
	alert('<?php print str_replace("'","\'",$xxPrd255)?>');
	theObj.focus();
	return(false);
}
function formatprice(i, currcode, currformat){
<?php
	$tempStr = FormatEuroCurrency(0);
	$tempStr2 = number_format(0,2,".",",");
	print "var pTemplate='" . $tempStr . "';\n";
	print "if(currcode!='') pTemplate=' " . $tempStr2 . "' + (currcode!=' '?'<b>'+currcode+'</b>':'');\n";
	if(strstr($tempStr,",") || strstr($tempStr,".")){ ?>
if(currcode==' JPY')
	i = Math.round(i).toString();
else if(i==Math.round(i))
	i=i.toString()+".00";
else if(i*10.0==Math.round(i*10.0))
	i=i.toString()+"0";
else if(i*100.0==Math.round(i*100.0))
	i=i.toString();
<?php }
	print 'if(currcode!="")pTemplate = currformat.toString().replace(/%s/,i.toString());';
	print 'else pTemplate = pTemplate.toString().replace(/\d[,.]*\d*/,i.toString());';
	if(strstr($tempStr,","))
		print "return(pTemplate.replace(/\./,','));";
	else
		print "return(pTemplate);";
?>
}
//-->
</script><?php
}
function updatepricescript($doaddprodoptions){
global $prodoptions,$Count,$rs,$noprice,$pricezeromessage,$showtaxinclusive,$currRate1,$currRate2,$currRate3,$currSymbol1,$currSymbol2,$currSymbol3,$currFormat1,$currFormat2,$currFormat3,$useStockManagement,$currencyseparator; ?>
<script Language="JavaScript">
<!--
function formvalidator<?php print $Count?>(theForm)
{
<?php
$prodoptions="";
$hasonepriceoption=FALSE;
if($doaddprodoptions){
	$sSQL = "SELECT poOptionGroup,optType,optFlags FROM prodoptions LEFT JOIN optiongroup ON optiongroup.optGrpID=prodoptions.poOptionGroup WHERE poProdID='" . $rs["pId"] . "' ORDER BY poID";
	$result = mysql_query($sSQL) or print(mysql_error());
	for($rowcounter=0;$rowcounter<mysql_num_rows($result);$rowcounter++){
		$prodoptions[$rowcounter] = mysql_fetch_array($result);
	}
	if(is_array($prodoptions)){
		foreach($prodoptions as $rowcounter => $theopt){
			if($theopt["optType"]==3){
				print "if(theForm.voptn" . $rowcounter . ".value=='')return(enterValue(theForm.voptn" . $rowcounter . "));\n";
				print "if(theForm.voptn" . $rowcounter . ".value.length>255)return(dataLimit(theForm.voptn" . $rowcounter . "));\n";
			}elseif(abs($theopt["optType"])==2){
				$hasonepriceoption=TRUE;
				if($theopt["optType"]==2)
					print 'if(theForm.optn' . $rowcounter . '.selectedIndex==0)return(chooseOption(theForm.optn' . $rowcounter . "));\n";
				if($useStockManagement && (($rs["pSell"] & 2) == 2)) print 'if(!checkStock(theForm.optn' . $rowcounter . ',theForm.optn' . $rowcounter . '.options[theForm.optn' . $rowcounter . '.selectedIndex].value))return(false);' . "\r\n";
			}
		}
	}
}
?>
return (true);
}
<?php
if(@$noprice!=TRUE && ! ($rs["pPrice"]==0 && @$pricezeromessage != "") && $hasonepriceoption){
	print 'function updateprice' . $Count . "(){\r\n";
	print 'var totAdd=' . $rs["pPrice"] . ";\r\n";
	print 'if(!isW3) return;';
	print 'var pDiv = document.getElementById(\'pricediv' . $Count . "');\r\n";
	foreach($prodoptions as $rowcounter => $theopt){
		if(abs($theopt["optType"])!=3){
			if(($theopt["optFlags"]&1)==1)
				print 'totAdd=totAdd+((' . $rs["pPrice"] . "*pricechecker(document.forms.tForm" . $Count . ".optn" . $rowcounter . ".options[document.forms.tForm" . $Count . ".optn" . $rowcounter . ".selectedIndex].value))/100.0);\n";
			else
				print 'totAdd=totAdd+pricechecker(document.forms.tForm' . $Count . ".optn" . $rowcounter . ".options[document.forms.tForm" . $Count . ".optn" . $rowcounter . ".selectedIndex].value);\n";
		}
	}
	print "pDiv.innerHTML=formatprice(Math.round(totAdd*100.0)/100.0, '', '');";
	if(@$showtaxinclusive && ($rs["pExemptions"] & 2)!=2) print "document.getElementById('pricedivti" . $Count . "').innerHTML=formatprice(Math.round((totAdd+(totAdd*tax/100.0))*100.0)/100.0, '', '');\n";
	$extracurr = "";
	if($currRate1!=0 && $currSymbol1!="") $extracurr = "extracurr+=formatprice(Math.round((totAdd*" . $currRate1 . ")*100.0)/100.0, ' " . $currSymbol1 . "','" . str_replace("'","\'",$currFormat1) . "')+'".str_replace("'","\'",$currencyseparator)."';\n";
	if($currRate2!=0 && $currSymbol2!="") $extracurr .= "extracurr+=formatprice(Math.round((totAdd*" . $currRate2 . ")*100.0)/100.0, ' " . $currSymbol2 . "','" . str_replace("'","\'",$currFormat2) . "')+'".str_replace("'","\'",$currencyseparator)."';\n";
	if($currRate3!=0 && $currSymbol3!="") $extracurr .= "extracurr+=formatprice(Math.round((totAdd*" . $currRate3 . ")*100.0)/100.0, ' " . $currSymbol3 . "','" . str_replace("'","\'",$currFormat3) . "');\n";
	if($extracurr!="") print "extracurr = '';\r\n" . $extracurr . "document.getElementById('pricedivec" . $Count . "').innerHTML=extracurr;\n";
	print "}";
}
?>
function openEFWindow(id) {
  popupWin = window.open('emailfriend.php?id='+id,'email_friend','menubar=no, scrollbars=no, width=400, height=400, directories=no,location=no,resizable=yes,status=no,toolbar=no')
}
//-->
</script><?php
}
function checkDPs($currcode){
	if($currcode=="JPY") return(0); else return(2);
}
function checkCurrencyRates($currConvUser,$currConvPw,$currLastUpdate,&$currRate1,$currSymbol1,&$currRate2,$currSymbol2,&$currRate3,$currSymbol3){
	global $countryCurrency;
	$ccsuccess = true;
	if($currConvUser!="" && $currConvPw!="" && (strtotime($currLastUpdate) < time()-(60*60*24))){
		$str = "";
		if($currSymbol1!="") $str .= "&curr=" . $currSymbol1;
		if($currSymbol2!="") $str .= "&curr=" . $currSymbol2;
		if($currSymbol3!="") $str .= "&curr=" . $currSymbol3;
		if($str==""){
			mysql_query("UPDATE admin SET currLastUpdate='" . date("Y-m-d H:i:s", time()) . "'") or print(mysql_error());
			return;
		}
		$str = "?source=" . $countryCurrency . "&user=" . $currConvUser . "&pw=" . $currConvPw . $str;
		$header = "POST /currencyxml.asp" . $str . " HTTP/1.0\r\n";
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$header .= "Content-Length: 1\r\n\r\n";
		$fp = fsockopen ('www.ecommercetemplates.com', 80, $errno, $errstr, 30);
		if (!$fp){
			echo "$errstr ($errno)"; // HTTP error handling
		}else{
			fputs ($fp, $header . "X");
			$sXML="";
			while (!feof($fp))
				$sXML .= fgets ($fp, 1024);
			// print str_replace("<","<br>&lt;",$sXML) . "<BR>\n";
			$xmlDoc = new vrXMLDoc($sXML);
			$nodeList = $xmlDoc->nodeList->childNodes[0];
			for($j = 0; $j < $nodeList->length; $j++){
				if($nodeList->nodeName[$j]=="currError"){
					print $nodeList->nodeValue[$j];
					$ccsuccess = false;
				}elseif($nodeList->nodeName[$j]=="selectedCurrency"){
					$e = $nodeList->childNodes[$j];
					$currRate = 0;
					for($i = 0; $i < $e->length; $i++){
						if($e->nodeName[$i]=="currSymbol")
							$currSymbol = $e->nodeValue[$i];
						elseif($e->nodeName[$i]=="currRate")
							$currRate = $e->nodeValue[$i];
					}
					if($currSymbol1 == $currSymbol){
						$currRate1 = $currRate;
						mysql_query("UPDATE admin SET currRate1=" . $currRate . " WHERE adminID=1") or print(mysql_error());
					}
					if($currSymbol2 == $currSymbol){
						$currRate2 = $currRate;
						mysql_query("UPDATE admin SET currRate2=" . $currRate . " WHERE adminID=1") or print(mysql_error());
					}
					if($currSymbol3 == $currSymbol){
						$currRate3 = $currRate;
						mysql_query("UPDATE admin SET currRate3=" . $currRate . " WHERE adminID=1") or print(mysql_error());
					}
				}
			}
			if($ccsuccess) mysql_query("UPDATE admin SET currLastUpdate='" . date("Y-m-d H:i:s", time()) . "'");
		}
	}
}
function getsectionids($thesecid, $delsections){
	$secid = $thesecid;
	$iterations = 0;
	$iteratemore = TRUE;
	if($delsections) $nodel = ""; else $nodel = 'sectionDisabled<>1 AND ';
	while($iteratemore && $iterations<10){
		$sSQL2 = "SELECT DISTINCT sectionID,rootSection FROM sections WHERE " . $nodel . "(topSection IN (" . $secid . ") OR (sectionID IN (" . $secid . ") AND rootSection=1))";
		$secid = "";
		$iteratemore = FALSE;
		$result2 = mysql_query($sSQL2) or print(mysql_error());
		$addcomma = "";
		while($rs2 = mysql_fetch_assoc($result2)){
			if($rs2["rootSection"]==0) $iteratemore = TRUE;
			$secid .= $addcomma . $rs2["sectionID"];
			$addcomma = ",";
		}
		$iterations++;
	}
	if($secid=="") $secid = "0";
	return($secid);
}
if(@$enableclientlogin==TRUE){
	if(@$_SESSION["clientUser"] != ""){
	}elseif(@$_POST["checktmplogin"]=="1" && @$_POST["sessionid"] != ""){
		$sSQL = "SELECT tmploginname FROM tmplogin WHERE tmploginid='" . trim(@$_POST["sessionid"]) . "'";
		$result = mysql_query($sSQL) or print(mysql_error());
		if($rs = mysql_fetch_array($result)){
			$_SESSION["clientUser"]=$rs["tmploginname"];
			mysql_free_result($result);
			mysql_query("DELETE FROM tmplogin WHERE tmploginid='" . trim(@$_POST["sessionid"]) . "'") or print(mysql_error());
			$sSQL = "SELECT clientActions,clientLoginLevel,clientPercentDiscount FROM clientlogin WHERE clientUser='" . $_SESSION["clientUser"] . "'";
			$result = mysql_query($sSQL) or print(mysql_error());
			if($rs = mysql_fetch_array($result)){
				$_SESSION["clientActions"]=$rs["clientActions"];
				$_SESSION["clientLoginLevel"]=$rs["clientLoginLevel"];
				$_SESSION["clientPercentDiscount"]=(100.0-(double)$rs["clientPercentDiscount"])/100.0;
			}
		}
		mysql_free_result($result);
	}elseif(@$_COOKIE["WRITECLL"] != ""){
		$sSQL = "SELECT clientUser,clientActions,clientLoginLevel,clientPercentDiscount FROM clientlogin WHERE clientUser='" . trim($_COOKIE["WRITECLL"]) . "' AND clientPW='" . trim($_COOKIE["WRITECLP"]) . "'";
		$result = mysql_query($sSQL) or print(mysql_error());
		if($rs = mysql_fetch_array($result)){
			$_SESSION["clientUser"]=$rs["clientUser"];
			$_SESSION["clientActions"]=$rs["clientActions"];
			$_SESSION["clientLoginLevel"]=$rs["clientLoginLevel"];
			$_SESSION["clientPercentDiscount"]=(100.0-(double)$rs["clientPercentDiscount"])/100.0;
		}
		mysql_free_result($result);
	}
	if(@$requiredloginlevel != ""){
		if((int)$requiredloginlevel > @$_SESSION["clientLoginLevel"]){
			ob_end_clean();
			if(@$_SERVER["HTTPS"] == "on" || @$_SERVER["SERVER_PORT"] == "443")$prot='https://';else $prot='http://';
			header('Location: '.$prot.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/clientlogin.php');
			exit;
		}
	}
}
?>