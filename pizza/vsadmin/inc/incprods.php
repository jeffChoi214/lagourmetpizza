<?php
//This code is copyright (c) Internet Business Solutions SL, all rights reserved.
//The contents of this file are protect under law as the intellectual property of Internet
//Business Solutions SL. Any use, reproduction, disclosure or copying of any kind 
//without the express and written permission of Internet Business Solutions SL is forbidden.
//Author: Vince Reid, vince@virtualred.net
if(@$storesessionvalue=="") $storesessionvalue="virtualstore".time();
if($_SESSION["loggedon"] != $storesessionvalue) exit;
$success=TRUE;
$nprodoptions=0;
$nprodsections=0;
$nalloptions=0;
$nallsections=0;
$nalldropship=0;
$alreadygotadmin = getadminsettings();
$simpleOptions = (($adminTweaks & 2)==2);
$simpleSections = (($adminTweaks & 4)==4);
$dorefresh=FALSE;
if(@$maxprodsects=="") $maxprodsects=20;
if(@$_POST["posted"]=="1"){
	$pExemptions=0;
	if(is_array(@$_POST["pExemptions"])){
		foreach(@$_POST["pExemptions"] as $pExemptObj)
			$pExemptions += $pExemptObj;
	}
	if(@$_POST["act"]=="delete"){
		$sSQL = "DELETE FROM pricebreaks WHERE pbProdID='" . mysql_escape_string(@$_POST["id"]) . "'";
		mysql_query($sSQL) or print(mysql_error());
		$sSQL = "DELETE FROM cpnassign WHERE cpaType=2 AND cpaAssignment='" . mysql_escape_string(@$_POST["id"]) . "'";
		mysql_query($sSQL) or print(mysql_error());
		$sSQL = "DELETE FROM products WHERE pID='" . mysql_escape_string(@$_POST["id"]) . "'";
		mysql_query($sSQL) or print(mysql_error());
		$sSQL = "DELETE FROM prodoptions WHERE poProdID='" . mysql_escape_string(@$_POST["id"]) . "'";
		mysql_query($sSQL) or print(mysql_error());
		$sSQL = "DELETE FROM multisections WHERE pID='" . mysql_escape_string(@$_POST["id"]) . "'";
		mysql_query($sSQL) or print(mysql_error());
		$dorefresh=TRUE;
	}elseif(@$_POST["act"]=="domodify"){
		if(trim(@$_POST["newid"]) != trim(@$_POST["id"])){
			$sSQL = "SELECT * FROM products WHERE pID='" . trim(@$_POST["newid"]) . "'";
			$result = mysql_query($sSQL) or print(mysql_error());
			$success = (mysql_num_rows($result)==0);
			mysql_free_result($result);
			if($success){
				mysql_query("UPDATE pricebreaks SET pbProdID='" . trim(@$_POST["newid"]) . "' WHERE pbProdID='" . trim(@$_POST["id"]) . "'") or print(mysql_error());
				mysql_query("UPDATE cpnassign SET cpaAssignment='" . trim(@$_POST["newid"]) . "' WHERE cpaType=2 AND cpaAssignment='" . trim(@$_POST["id"]) . "'") or print(mysql_error());
			}
		}
		if($success){
			$sSQL = "UPDATE products SET ";
						$sSQL .= "pID='" . mysql_escape_string(trim(unstripslashes(@$_POST["newid"]))) . "', ";
						$sSQL .= "pName='" . mysql_escape_string(unstripslashes(trim(@$_POST["pName"]))) . "', ";
						$sSQL .= "pSection=" . trim(@$_POST["pSection"]) . ", ";
						$sSQL .= "pDropship=" . trim(@$_POST["pDropship"]) . ", ";
						$sSQL .= "pExemptions=" . $pExemptions . ", ";
						$sSQL .= "pDescription='" . mysql_escape_string(unstripslashes(trim(@$_POST["pDescription"]))) . "', ";
						$sSQL .= "pImage='" . mysql_escape_string(unstripslashes(trim(@$_POST["pImage"]))) . "', ";
						$sSQL .= "pLongDescription='" . mysql_escape_string(unstripslashes(trim(@$_POST["pLongDescription"]))) . "', ";
						for($index=2; $index <= $adminlanguages+1; $index++){
							if(($adminlangsettings & 1)==1) $sSQL .= "pName" . $index . "='" . mysql_escape_string(unstripslashes(trim(@$_POST["pName" . $index]))) . "', ";
							if(($adminlangsettings & 2)==2) $sSQL .= "pDescription" . $index . "='" . mysql_escape_string(unstripslashes(trim(@$_POST["pDescription" . $index]))) . "', ";
							if(($adminlangsettings & 4)==4) $sSQL .= "pLongDescription" . $index . "='" . mysql_escape_string(unstripslashes(trim(@$_POST["pLongDescription" . $index]))) . "', ";
						}
						$sSQL .= "pLargeImage='" . mysql_escape_string(unstripslashes(trim(@$_POST["pLargeImage"]))) . "', ";
						if(trim(@$_POST["pDisplay"]) == "ON")
							$sSQL .= "pDisplay=1,";
						else
							$sSQL .= "pDisplay=0,";
						if(@$perproducttaxrate==TRUE)
							$sSQL .= "pTax=" . trim(@$_POST["pTax"]) . ",";
						if($stockManage != 0 && is_numeric(trim(@$_POST["inStock"])))
							$sSQL .= "pInStock=" . trim(@$_POST["inStock"]) . ",";
						if(trim(@$_POST["pSell"]) == "ON") $pSell=1; else $pSell=0;
						if(trim(@$_POST["stockOptions"]) == "1") $pSell += 2;
						$sSQL .= "pSell=" . $pSell . ",";
						if(@$productdimensions==TRUE)
							$sSQL .= "pDims='" . trim(@$_POST["plen"]) . "x" . trim(@$_POST["pwid"]) . "x" . trim(@$_POST["phei"]) . "',";
						if(@$digidownloads==TRUE)
							$sSQL .= "pDownload='" . mysql_escape_string(unstripslashes(trim(@$_POST["pDownload"]))) . "',";
						if($shipType==1){
							if(! is_numeric(trim(@$_POST["pShipping"])))
								$sSQL .= "pShipping=0,";
							else
								$sSQL .= "pShipping=" . trim(@$_POST["pShipping"]) . ",";
							if(! is_numeric(trim(@$_POST["pShipping2"])))
								$sSQL .= "pShipping2=0,";
							else
								$sSQL .= "pShipping2=" . trim(@$_POST["pShipping2"]) . ",";
						}elseif($shipType==2 || $shipType==3 || $shipType==4 || $shipType==6){
							if(! is_numeric(trim(@$_POST["pShipping"])))
								$sSQL .= "pWeight=0,";
							else
								$sSQL .= "pWeight=" . trim(@$_POST["pShipping"]) . ",";
						}
						if(trim(@$_POST["pWholesalePrice"]) != "")
							$sSQL .= "pWholesalePrice=" . trim(@$_POST["pWholesalePrice"]) . ",";
						else
							$sSQL .= "pWholesalePrice=0,";
						if(trim(@$_POST["pListPrice"]) != "")
							$sSQL .= "pListPrice=" . trim(@$_POST["pListPrice"]) . ",";
						else
							$sSQL .= "pListPrice=0,";
						$sSQL .= "pPrice=" . trim(@$_POST["pPrice"]) . " ";
						$sSQL .= "WHERE pID='" . @$_POST["id"] . "'";
			mysql_query($sSQL) or print(mysql_error());
			$sSQL = "DELETE FROM prodoptions WHERE poProdID='" . @$_POST["id"] . "'";
			mysql_query($sSQL) or print(mysql_error());
			for($rowcounter=0; $rowcounter < maxprodopts; $rowcounter++){
				if(@$_POST["pOption" . $rowcounter] != "" && @$_POST["pOption" . $rowcounter] != 0){
					$sSQL = "INSERT INTO prodoptions (poProdID,poOptionGroup) VALUES ('" . @$_POST["newid"] . "'," . @$_POST["pOption" . $rowcounter] . ")";
					mysql_query($sSQL) or print(mysql_error());
				}
			}
			$sSQL = "DELETE FROM multisections WHERE pID='" . @$_POST["id"] . "'";
			mysql_query($sSQL) or print(mysql_error());
			for($rowcounter=0; $rowcounter < $maxprodsects; $rowcounter++){
				if(@$_POST["pSection" . $rowcounter] != "" && @$_POST["pSection" . $rowcounter] != 0 && @$_POST["pSection"] != @$_POST["pSection" . $rowcounter]){
					$sSQL = "INSERT INTO multisections (pID,pSection) VALUES ('" . @$_POST["newid"] . "'," . @$_POST["pSection" . $rowcounter] . ")";
					mysql_query($sSQL) or print(mysql_error());
				}
			}
			$dorefresh=TRUE;
		}else
			$errmsg = $yyPrDup;
	}elseif(@$_POST["act"]=="doaddnew"){
		$sSQL = "SELECT * FROM products WHERE pID='" . trim(@$_POST["newid"]) . "'";
		$result = mysql_query($sSQL) or print(mysql_error());
		$success = (mysql_num_rows($result)==0);
		mysql_free_result($result);
		if($success){
			$sSQL = "INSERT INTO products (pID,pName,pSection,pDropship,pExemptions,pDescription,pImage,pLongDescription,";
			for($index=2; $index <= $adminlanguages+1; $index++){
				if(($adminlangsettings & 1)==1) $sSQL .= "pName" . $index . ",";
				if(($adminlangsettings & 2)==2) $sSQL .= "pDescription" . $index . ",";
				if(($adminlangsettings & 4)==4) $sSQL .= "pLongDescription" . $index . ",";
			}
			$sSQL .= "pLargeImage,pPrice,pWholesalePrice,pListPrice,";
			if($shipType==1) $sSQL .= "pShipping,pShipping2,";
			$sSQL .= "pDisplay,";
			if(@$perproducttaxrate==TRUE) $sSQL .= "pTax,";
			if($stockManage != 0 && is_numeric(trim(@$_POST["inStock"]))) $sSQL .= "pInStock,";
			if(@$productdimensions==TRUE) $sSQL .= 'pDims,';
			if(@$digidownloads==TRUE) $sSQL .= 'pDownload,';
			$sSQL .= "pSell,pWeight) VALUES (";
						$sSQL .= "'" . trim(unstripslashes(@$_POST["newid"])) . "',";
						$sSQL .= "'" . mysql_escape_string(unstripslashes(@$_POST["pName"])) . "',";
						$sSQL .= @$_POST["pSection"] . ",";
						$sSQL .= @$_POST["pDropship"] . ",";
						$sSQL .= $pExemptions . ",";
						$sSQL .= "'" . mysql_escape_string(unstripslashes(@$_POST["pDescription"])) . "',";
						$sSQL .= "'" . mysql_escape_string(unstripslashes(@$_POST["pImage"])) . "',";
						$sSQL .= "'" . mysql_escape_string(unstripslashes(@$_POST["pLongDescription"])) . "',";
						for($index=2; $index <= $adminlanguages+1; $index++){
							if(($adminlangsettings & 1)==1) $sSQL .= "'" . mysql_escape_string(unstripslashes(@$_POST["pName" . $index])) . "',";
							if(($adminlangsettings & 2)==2) $sSQL .= "'" . mysql_escape_string(unstripslashes(@$_POST["pDescription" . $index])) . "',";
							if(($adminlangsettings & 4)==4) $sSQL .= "'" . mysql_escape_string(unstripslashes(@$_POST["pLongDescription" . $index])) . "',";
						}
						$sSQL .= "'" . mysql_escape_string(unstripslashes(@$_POST["pLargeImage"])) . "',";
						$sSQL .= trim(@$_POST["pPrice"]) . ",";
						if(trim(@$_POST["pWholesalePrice"]) != "")
							$sSQL .= trim(@$_POST["pWholesalePrice"]) . ",";
						else
							$sSQL .= "0,";
						if(trim(@$_POST["pListPrice"]) != "")
							$sSQL .= trim(@$_POST["pListPrice"]) . ",";
						else
							$sSQL .= "0,";
						if($shipType==1){
							if(! is_numeric(trim(@$_POST["pShipping"])))
								$sSQL .= "0,";
							else
								$sSQL .= trim(@$_POST["pShipping"]) . ",";
							if(! is_numeric(trim(@$_POST["pShipping2"])))
								$sSQL .= "0,";
							else
								$sSQL .= trim(@$_POST["pShipping2"]) . ",";
						}
						if(trim(@$_POST["pDisplay"]) == "ON")
							$sSQL .= "1,";
						else
							$sSQL .= "0,";
						if(@$perproducttaxrate==TRUE) $sSQL .= "'" . @$_POST["pTax"] . "',";
						if($stockManage != 0 && is_numeric(trim(@$_POST["inStock"])))
							$sSQL .= trim(@$_POST["inStock"]) . ",";
						if(@$productdimensions==TRUE)
							$sSQL .= "'" . trim(@$_POST["plen"]) . "x" . trim(@$_POST["pwid"]) . "x" . trim(@$_POST["phei"]) . "',";
						if(@$digidownloads==TRUE)
							$sSQL .= "'" . mysql_escape_string(unstripslashes(trim(@$_POST["pDownload"]))) . "',";
						if(trim(@$_POST["pSell"]) == "ON") $pSell=1; else $pSell=0;
						if(trim(@$_POST["stockOptions"]) == "1") $pSell += 2;
						$sSQL .= $pSell . ",";
						if($shipType <= 1 || ! is_numeric(trim(@$_POST["pShipping"])))
							$sSQL .= "0";
						elseif($shipType==2 || $shipType==3 || $shipType==4 || $shipType==6)
							$sSQL .= trim(@$_POST["pShipping"]) . "";
						else{
							$sSQL .= trim(@$_POST["pShipping"]) . ".";
							if((int)trim(@$_POST["pShipping2"]) < 10) $sSQL .= "0";
							$sSQL .= trim(@$_POST["pShipping2"]);
						}
						$sSQL .= ")";
			mysql_query($sSQL) or print(mysql_error());
			for($rowcounter=0; $rowcounter < maxprodopts; $rowcounter++){
				if(@$_POST["pOption" . $rowcounter] != "" && @$_POST["pOption" . $rowcounter] != 0){
					$sSQL = "INSERT INTO prodoptions (poProdID,poOptionGroup) VALUES ('" . @$_POST["newid"] . "'," . @$_POST["pOption" . $rowcounter] . ")";
					mysql_query($sSQL) or print(mysql_error());
				}
			}
			$sSQL = "DELETE FROM multisections WHERE pID='" . @$_POST["newid"] . "'";
			mysql_query($sSQL) or print(mysql_error());
			for($rowcounter=0; $rowcounter < $maxprodsects; $rowcounter++){
				if(@$_POST["pSection" . $rowcounter] != "" && @$_POST["pSection" . $rowcounter] != 0 && @$_POST["pSection"] != @$_POST["pSection" . $rowcounter]){
					$sSQL = "INSERT INTO multisections (pID,pSection) VALUES ('" . @$_POST["newid"] . "'," . @$_POST["pSection" . $rowcounter] . ")";
					mysql_query($sSQL) or print(mysql_error());
				}
			}
			$dorefresh=TRUE;
		}else
			$errmsg = "Sorry, that product reference is already in use. Please use your browser back button to return and correct the problem.";
	}elseif(@$_POST["act"]=="dodiscounts"){
		$sSQL = "INSERT INTO cpnassign (cpaCpnID,cpaType,cpaAssignment) VALUES (" . @$_POST["assdisc"] . ",2,'" . @$_POST["id"] . "')";
		mysql_query($sSQL) or print(mysql_error());
		$dorefresh=TRUE;
	}elseif(@$_POST["act"]=="deletedisc"){
		$sSQL = "DELETE FROM cpnassign WHERE cpaID=" . @$_POST["id"];
		mysql_query($sSQL) or print(mysql_error());
		$dorefresh=TRUE;
	}
	if(@$_POST["act"]=="modify" || @$_POST["act"]=="clone" || @$_POST["act"]=="addnew"){
		$sSQL = "SELECT optGrpID, optGrpWorkingName FROM optiongroup ORDER BY optGrpWorkingName";
		$nalloptions=0;
		$result = mysql_query($sSQL) or print(mysql_error());
		while($rs = mysql_fetch_row($result))
			$alloptions[$nalloptions++] = $rs;
		mysql_free_result($result);
		if(@$_POST["act"]=="modify" || @$_POST["act"]=="clone"){
			$sSQL = "SELECT poID, poOptionGroup FROM prodoptions WHERE poProdID='" . trim(@$_POST["id"]) . "' ORDER BY poID";
			$nprodoptions=0;
			$result = mysql_query($sSQL) or print(mysql_error());
			while($rs = mysql_fetch_row($result))
				$prodoptions[$nprodoptions++] = $rs;
			$sSQL = "SELECT pSection FROM multisections WHERE pID='" . trim(@$_POST["id"]) . "'";
			$result = mysql_query($sSQL) or print(mysql_error());
			while($rs = mysql_fetch_row($result))
				$prodsections[$nprodsections++] = $rs;
		}
		$sSQL = "SELECT sectionID, sectionWorkingName FROM sections WHERE rootSection=1 ORDER BY sectionWorkingName";
		$result = mysql_query($sSQL) or print(mysql_error());
		while($rs = mysql_fetch_assoc($result))
			$allsections[$nallsections++] = $rs;
		mysql_free_result($result);
		$sSQL = "SELECT dsID,dsName FROM dropshipper ORDER BY dsName";
		$result = mysql_query($sSQL) or print(mysql_error());
		while($rs = mysql_fetch_assoc($result))
			$alldropship[$nalldropship++] = $rs;
		mysql_free_result($result);
	}
}
if($dorefresh){
	print '<meta http-equiv="refresh" content="2; url=adminprods.php';
	if(trim(@$_POST["ts"]) != "")
		print "?ts=" . trim(@$_POST["ts"]);
	elseif(trim(@$_POST["tt"]) != "")
		print "?tt=" . trim(@$_POST["tt"]);
	print '">';
}
?>
<script language="javascript" type="text/javascript">
<!--
var ie=(document.all != (void 0));
var ns6=(document.getElementById&&!document.all);
<?php if(@$_POST["act"]=="addnew" || @$_POST["act"]=="modify" || @$_POST["act"]=="clone"){ ?>
function checkastring(thestr,validchars){
  for (i=0; i < thestr.length; i++){
    ch = thestr.charAt(i);
    for (j = 0;  j < validchars.length;  j++)
      if (ch == validchars.charAt(j))
        break;
    if (j == validchars.length)
	  return(false);
  }
  return(true);
}
function formvalidator(theForm)
{
  if (theForm.newid.value == ""){
    alert("<?php print $yyPlsEntr?> \"<?php print $yyPrRef?>\".");
    theForm.newid.focus();
    return (false);
  }
  if (theForm.pSection.options[theForm.pSection.selectedIndex].value == ""){
    alert("<?php print $yyPlsSel?> \"<?php print $yySection?>\".");
    theForm.pSection.focus();
    return (false);
  }
  if (theForm.pName.value == ""){
    alert("<?php print $yyPlsEntr?> \"<?php print $yyPrNam?>\".");
    theForm.pName.focus();
    return (false);
  }
<?php	for($index=2; $index <= $adminlanguages+1; $index++){
			if(($adminlangsettings & 1)==1){ ?>
  if (theForm.pName<?php print $index?>.value == ""){
    alert("<?php print $yyPlsEntr?> \"<?php print $yyPrNam . " " . $index?>\".");
    theForm.pName<?php print $index?>.focus();
    return (false);
  }
<?php		}
		} ?>
  if (theForm.pPrice.value == ""){
    alert("<?php print $yyPlsEntr?> \"<?php print $yyPrPri?>\".");
    theForm.pPrice.focus();
    return (false);
  }
  var checkOK = "'\" ";
  var checkStr = theForm.newid.value;
  var allValid = true;
  for (i = 0;  i < checkStr.length;  i++){
    ch = checkStr.charAt(i);
    for (j = 0;  j < checkOK.length;  j++)
      if (ch == checkOK.charAt(j)){
	    allValid = false;
        break;
	  }
  }
  if (!allValid){
    alert("<?php print $yyQuoSpa?> \"<?php print $yyPrRef?>\".");
    theForm.newid.focus();
    return (false);
  }
  if (!checkastring(theForm.pPrice.value,"0123456789.")){
    alert("<?php print $yyOnlyDec?> \"<?php print $yyPrPri?>\".");
    theForm.pPrice.focus();
    return (false);
  }
  if (!checkastring(theForm.pWholesalePrice.value,"0123456789.")){
    alert("<?php print $yyOnlyDec?> \"<?php print $yyWhoPri?>\".");
    theForm.pWholesalePrice.focus();
    return (false);
  }
  if (!checkastring(theForm.pListPrice.value,"0123456789.")){
    alert("<?php print $yyOnlyDec?> \"<?php print $yyListPr?>\".");
    theForm.pListPrice.focus();
    return (false);
  }
<?php	if(@$productdimensions==TRUE){ ?>
  if (theForm.elements['pExemptions[]'].options[2].selected!=true){
  if (theForm.plen.value == ""){
	alert("<?php print $yyPlsEntr?> \"<?php print $yyDims?>\".");
	theForm.plen.focus();
	return(false);
  }
  if (theForm.pwid.value == ""){
	alert("<?php print $yyPlsEntr?> \"<?php print $yyDims?>\".");
	theForm.pwid.focus();
	return(false);
  }
  if (theForm.phei.value == ""){
	alert("<?php print $yyPlsEntr?> \"<?php print $yyDims?>\".");
	theForm.phei.focus();
	return(false);
  }
  }
  var checkOK = "0123456789.";
  if (!checkastring(theForm.plen.value,checkOK)){
	alert("<?php print $yyOnlyDec?> \"<?php print $yyDims?>\".");
	theForm.plen.focus();
	return(false);
  }
  if (!checkastring(theForm.pwid.value,checkOK)){
	alert("<?php print $yyOnlyDec?> \"<?php print $yyDims?>\".");
	theForm.pwid.focus();
	return(false);
  }
  if (!checkastring(theForm.phei.value,checkOK)){
	alert("<?php print $yyOnlyDec?> \"<?php print $yyDims?>\".");
	theForm.phei.focus();
	return(false);
  }
<?php	}
		if(($shipType > 0 && $shipType < 5) || $shipType==6){ ?>
  var checkOK = "0123456789.";
  if (!checkastring(theForm.pShipping.value,checkOK)){
<?php		if($shipType==1){ ?>
    alert("<?php print $yyOnlyDec?> \"<?php print $yyShip . ": " . $yyFirShi?>\".");
<?php		}else{ ?>
    alert("<?php print $yyOnlyDec?> \"<?php print $yyPrWght?>\".");
<?php		} ?>
    theForm.pShipping.focus();
    return (false);
  }
<?php	} ?>
<?php	if($shipType==1){ ?>
  if (!checkastring(theForm.pShipping2.value,"0123456789.")){
    alert("<?php print $yyOnlyDec?> \"<?php print $yyShip . ": " . $yySubShi?>\".");
    theForm.pShipping2.focus();
    return (false);
  }
<?php	} ?>
<?php	if($stockManage != 0){ ?>
  if (!(theForm.stockOptions.selectedIndex==1) && theForm.inStock.value == ""){
    alert("<?php print $yyPlsEntr?> \"<?php print $yyInStk?>\".");
    theForm.inStock.focus();
    return (false);
  }
  if (!(theForm.stockOptions.selectedIndex==1) && !checkastring(theForm.inStock.value,"0123456789")){
    alert("<?php print $yyOnlyNum?> \"<?php print $yyInStk?>\".");
    theForm.inStock.focus();
    return (false);
  }
  if(theForm.stockOptions.selectedIndex==1 && theForm.pNumOptions.selectedIndex==0){
    alert("<?php print $yyStkWrn?>");
    theForm.stockOptions.focus();
    return (false);
  }
<?php	} ?>
<?php	if(@$perproducttaxrate==TRUE){ ?>
  if (theForm.pTax.value == ""){
	alert("<?php print $yyPlsEntr?> \"<?php print $yyTax?>\".");
	theForm.pTax.focus();
	return(false);
  }
  if (!checkastring(theForm.pTax.value,"0123456789.")){
    alert("<?php print $yyOnlyDec?> \"<?php print $yyTax?>\".");
    theForm.pTax.focus();
    return (false);
  }
<?php	} ?>
  return (true);
}
<?php } ?>
var prodOptGrpArr = new Array();
var prodSectGrpArr = new Array();
<?php
$rowcounter=0;
for($rowcounter=0;$rowcounter < $nprodoptions;$rowcounter++)
	print "prodOptGrpArr[" . $rowcounter . "]=" . $prodoptions[$rowcounter][1] . ";\r\n";
print "for(ii=" . $rowcounter . ";ii<" . maxprodopts . ";ii++) prodOptGrpArr[ii]=0;\r\n";
for($rowcounter=0;$rowcounter < $nprodsections;$rowcounter++)
	print "prodSectGrpArr[" . $rowcounter . "]=" . $prodsections[$rowcounter][0] . ";\r\n";
print "for(ii=" . $rowcounter . ";ii<" . $maxprodsects . ";ii++) prodSectGrpArr[ii]=0;\r\n";
?>
function update_opts(index){
	var thisOption;
	if(ns6)
		thisOption = document.getElementById('pOption'+index);
	else
		thisOption = eval('document.forms.mainform.pOption'+index);
	prodOptGrpArr[index] = thisOption.options[thisOption.selectedIndex].value;
}
function update_sects(index){
	var thisSection;
	if(ns6)
		thisSection = document.getElementById('pSection'+index);
	else
		thisSection = eval('document.forms.mainform.pSection'+index);
	prodSectGrpArr[index] = thisSection.options[thisSection.selectedIndex].value;
}
function setprodoptions(){
	var noOpts = document.forms.mainform.pNumOptions.selectedIndex;
	var theElm;
	var theHTMLHead,theHTML="";
	var index=0;
	if(ns6)
		theElm = document.getElementById('prodoptions');
	else
		theElm = document.all.prodoptions;

	theHTMLHead = '<table width="100%" border="0" cellspacing="0" cellpadding="3">';
	theHTML = theHTML + '<select size="1" id="pOptionGGREPLACEMExx" name="pOptionGGREPLACEMExx" onChange="update_opts(GGREPLACEMExx);"><option value="0"><?php print $yyNone?></option>';
	<?php
		for($rowcounter=0;$rowcounter < $nalloptions;$rowcounter++)
			print "theHTML = theHTML +'<option value=\"" . $alloptions[$rowcounter][0] . "\">" . str_replace("'","\'",$alloptions[$rowcounter][1]) . "</option>';\n";
	?>
	theHTML = theHTML + '</select>';

	for (index=0;index<noOpts;index++) {
		if(index % 2 == 0) theHTMLHead = theHTMLHead + '<tr>';
		theHTMLHead = theHTMLHead + '<td width="25%" align="right"><?php print $yyPrdOpt?> '+(index+1)+':</td><td width="25%">'+theHTML.replace(/GGREPLACEMExx/g,index)+'</td>';
		if(index % 2 != 0) theHTMLHead = theHTMLHead + '</tr>';
	}
	if(index % 2 != 0) theHTMLHead = theHTMLHead + '<td width="50%" colspan="2">&nbsp;</td></tr>';

	theHTMLHead = theHTMLHead + '</table>';
	theElm.innerHTML=theHTMLHead;
	for (index=0;index<noOpts;index++) {
		var thisOption;
		if(ns6)
			thisOption = document.getElementById('pOption'+index);
		else
			thisOption = eval('document.forms.mainform.pOption'+index);
		for (index2=0;index2<thisOption.length;index2++) {
			if (thisOption[index2].value==prodOptGrpArr[index]) {
				thisOption.selectedIndex=index2;
				thisOption.options[index2].selected = true;
			}
			else
				thisOption.options[index2].selected = false;
		}
	}
}
function setprodsections(){
	var noSects = document.forms.mainform.pNumSections.selectedIndex;
	var theElm;
	var theHTMLHead,theHTML="";
	var index=0;
	if(ns6)
		theElm = document.getElementById('prodsections');
	else
		theElm = document.all.prodsections;

	theHTMLHead = '<table width="100%" border="0" cellspacing="0" cellpadding="3">';
	theHTML = theHTML + '<select size="1" id="pSectionGGREPLACEMExx" name="pSectionGGREPLACEMExx" onChange="update_sects(GGREPLACEMExx);"><option value="0">None</option>';
	<?php
		for($rowcounter=0;$rowcounter < $nallsections;$rowcounter++)
			print "theHTML = theHTML +'<option value=\"" . $allsections[$rowcounter]["sectionID"] . "\">" . str_replace("'","\'",$allsections[$rowcounter]["sectionWorkingName"]) . "</option>';\n";
	?>
	theHTML = theHTML + '</select>';

	for (index=0;index<noSects;index++) {
		if(index % 2 == 0) theHTMLHead = theHTMLHead + '<tr>';
		theHTMLHead = theHTMLHead + '<td width="25%" align="right">Prod. Section '+(index+1)+':</td><td width="25%">'+theHTML.replace(/GGREPLACEMExx/g,index)+'</td>';
		if(index % 2 != 0) theHTMLHead = theHTMLHead + '</tr>';
	}
	if(index % 2 != 0) theHTMLHead = theHTMLHead + '<td width="50%" colspan="2">&nbsp;</td></tr>';

	theHTMLHead = theHTMLHead + '</table>';
	theElm.innerHTML=theHTMLHead;
	for (index=0;index<noSects;index++) {
		var thisSection;
		if(ns6)
			thisSection = document.getElementById('pSection'+index);
		else
			thisSection = eval('document.forms.mainform.pSection'+index);
		for (index2=0;index2<thisSection.length;index2++) {
			if (thisSection[index2].value==prodSectGrpArr[index]) {
				thisSection.selectedIndex=index2;
				thisSection.options[index2].selected = true;
			}
			else
				thisSection.options[index2].selected = false;
		}
	}
}
function setstocktype(){
var si = document.forms.mainform.stockOptions.selectedIndex;
document.forms.mainform.inStock.disabled=(si==1);
}
//-->
</script>
<?php
function show_info(){
	global $yyPrEx1, $yyPrEx2;
?>
		<p><a name="info"></a><ul>
		  <li><font size="1"><?php print $yyPrEx1?></font></li>
		  <li><font size="1"><?php print $yyPrEx2?></font></li>
		</ul></p>
<?php
}
if(@$_POST["posted"]=="1" && (@$_POST["act"]=="modify" || @$_POST["act"]=="clone" || @$_POST["act"]=="addnew")){
		if(@$_POST["act"]=="modify" || @$_POST["act"]=="clone"){
			$doaddnew = FALSE;
			$sSQL = "SELECT pId,pName,pName2,pName3,pSection,pDescription,pDescription2,pDescription3,pImage,pPrice,pWholesalePrice,pListPrice,pDisplay,pSell,pShipping,pShipping2,pLargeImage,pWeight,pLongDescription,pLongDescription2,pLongDescription3,pExemptions,pInStock,pDims,pTax,pDropship";
			if(@$digidownloads==TRUE) $sSQL .= ",pDownload";
			$sSQL .= " FROM products WHERE pId='" . mysql_escape_string(unstripslashes(@$_POST["id"])) . "'";
			$result = mysql_query($sSQL) or print(mysql_error());
			$alldata = mysql_fetch_assoc($result);
			$pId = $alldata["pId"];
			$pName = $alldata["pName"];
			for($index=2; $index <= $adminlanguages+1; $index++){
				$pNames[$index] = $alldata["pName" . $index];
				$pDescriptions[$index] = $alldata["pDescription" . $index];
				$pLongDescriptions[$index] = $alldata["pLongDescription" . $index];
			}
			$pSection = $alldata["pSection"];
			$pDescription = $alldata["pDescription"];
			$pImage = $alldata["pImage"];
			$pPrice = $alldata["pPrice"];
			$pWholesalePrice = $alldata["pWholesalePrice"];
			$pListPrice = $alldata["pListPrice"];
			$pDisplay = $alldata["pDisplay"];
			$pSell = $alldata["pSell"];
			$pShipping = $alldata["pShipping"];
			$pShipping2 = $alldata["pShipping2"];
			$pLargeImage = $alldata["pLargeImage"];
			$pWeight = $alldata["pWeight"];
			$pLongDescription = $alldata["pLongDescription"];
			$pExemptions = $alldata["pExemptions"];
			$pInStock = $alldata["pInStock"];
			$pDims = $alldata["pDims"];
			$pTax = $alldata["pTax"];
			$pDropship = $alldata["pDropship"];
			if(@$digidownloads==TRUE) $pDownload = $alldata["pDownload"];
		}else{
			$doaddnew = TRUE;
			$pId = "";
			$pName = "";
			for($index=2; $index <= $adminlanguages+1; $index++){
				$pNames[$index] = "";
				$pDescriptions[$index] = "";
				$pLongDescriptions[$index] = "";
			}
			if(trim(@$_POST["ts"]) != "") $pSection=(int)trim(@$_POST["ts"]); else $pSection = 0;
			$pDescription = "";
			$pImage = "prodimages/";
			$pPrice = "";
			$pWholesalePrice = "";
			$pListPrice = 0;
			$pDisplay = 1;
			$pSell = 1;
			$pShipping = "";
			$pShipping2 = "";
			$pLargeImage = "prodimages/";
			$pWeight = "";
			$pLongDescription = "";
			$pExemptions = 0;
			$pInStock = "";
			$pDims = "";
			$pTax = "";
			$pDropship = 0;
			$pDownload = "";
		}
?>
	<form name="mainform" method="post" action="adminprods.php" onsubmit="return formvalidator(this)">
      <table border="0" cellspacing="0" cellpadding="0" width="100%" bgcolor="" align="center">
		<tr>
		  <td width="100%">
			<input type="hidden" name="posted" value="1" />
			<?php if(@$_POST["act"]=="modify"){ ?>
			<input type="hidden" name="act" value="domodify" />
			<input type="hidden" name="id" value="<?php print str_replace('"',"&quot;",$pId)?>" />
			<?php }else{ ?>
			<input type="hidden" name="act" value="doaddnew" />
			<?php } ?>
			<input type="hidden" name="tt" value="<?php print @$_POST["tt"];?>" />
			<input type="hidden" name="ts" value="<?php print @$_POST["ts"];?>" />
            <table width="100%" border="0" cellspacing="0" cellpadding="3" bgcolor="">
			  <tr> 
                <td width="100%" colspan="4" align="center"><strong><?php
					if(@$_POST["act"]=="modify")
						print $yyYouMod . " &quot;" . $pName . "&quot;";
					elseif(@$_POST["act"]=="addnew")
						print $yyPrUpd;
					else
						print $yyYouCln . " &quot;" . $pName . "&quot;";
				?></strong><br />&nbsp;</td>
			  </tr>
			  <tr>
			    <td align="right"><font color="#FF0000">*</font><?php print $yyPrRef?>:</td><td><input type="text" name="newid" size="15" value="<?php print str_replace('"',"&quot;",$pId)?>" /></td>
			    <td align="right"><font color="#FF0000">*</font><?php print $yySection?>:</td><td><select size="1" name="pSection"><option value=""><?php print $yySelect?></option><?php
						for($index=0;$index<$nallsections;$index++){
							print "<option value='" . $allsections[$index]["sectionID"] . "'";
							if($allsections[$index]["sectionID"]==$pSection) print " selected";
							print ">" . $allsections[$index]["sectionWorkingName"] . "</option>\n";
						} ?></select></td>
			  </tr>
			  <tr>
			    <td align="right"><font color="#FF0000">*</font><?php print $yyPrNam?>:</td><td><input type="text" name="pName" size="25" value="<?php print str_replace('"',"&quot;",$pName)?>" /></td>
			    <td align="right"><font color="#FF0000">*</font><?php print $yyPrPri?>:</td><td><input type="text" name="pPrice" size="15" value="<?php print $pPrice?>" /></td>
			  </tr>
<?php		for($index=2; $index <= $adminlanguages+1; $index++){
				if(($adminlangsettings & 1)==1){
			?><tr>
			    <td align="right"><font color="#FF0000">*</font><?php print $yyPrNam . " " . $index?>:</td><td colspan="3"><input type="text" name="pName<?php print $index?>" size="25" value="<?php print str_replace('"','&quot;',$pNames[$index])?>" /></td>
			  </tr><?php
				}
			} ?>
			  <tr>
			    <?php if($useStockManagement){ ?>
				<td align="right">
				<input type="hidden" name="pSell" value="<?php if(((int)$pSell & 1)==1) print "ON" ?>" />
				<select name="stockOptions" size="1" onChange="setstocktype();">
				<option value="0">&nbsp;&nbsp;&nbsp;<?php print $yyInStk?>:</option>
				<option value="1"<?php if(((int)$pSell & 2)==2) print "selected" ?>><?php print $yyByOpt?>:</option></select>
				</td><td><input type="text" name="inStock" size="10" value="<?php print $pInStock?>" /></td>
				<?php }else{ ?>
				<input type="hidden" name="stockOptions" value="<?php if(((int)$pSell & 2)==2) print "1" ?>" />
				<td align="right"><?php print $yySellBut?>:</td><td><input type="checkbox" name="pSell" value="ON" <?php if(((int)$pSell & 1)==1) print "checked" ?> /></td>
				<?php } ?>
				<td align="right"><?php print $yyWhoPri?> <font size="1">(<a href="#info">info</a>)</font>:</td><td><input type="text" name="pWholesalePrice" size="15" value="<?php print $pWholesalePrice?>" /></td>
			  </tr>
			  <tr>
			    <td align="right"><?php print $yyDisPro?>:</td><td><input type="checkbox" name="pDisplay" value="ON" <?php if((int)$pDisplay != 0) print "checked" ?> /></td>
				<td align="right"><?php print $yyListPr?> <font size="1">(<a href="#info">info</a>)</font>:</td><td><input type="text" name="pListPrice" size="15" value="<?php if((double)$pListPrice<>0.0) print $pListPrice ?>" /></td>
			  </tr>
			  <tr>
				<td align="right"><?php print $yyImage?>:</td><td><input type="text" name="pImage" size="25" value="<?php print str_replace('"',"&quot;",$pImage)?>" /></td>
				<?php	if(@$productdimensions==TRUE){
							$proddims = split("x", $pDims) ?>
				<td align="right"><font color="#FF0000">*</font><?php print $yyDims?>:</td>
				<td><input type="text" name="plen" size="4" value="<?php print @$proddims[0]?>" /> <strong>X</strong> 
				<input type="text" name="pwid" size="4" value="<?php print @$proddims[1]?>" /> <strong>X</strong> 
				<input type="text" name="phei" size="4" value="<?php print @$proddims[2]?>" /></td>
				<?php	}else{ ?>
			    <td align="center" colspan="2"><strong><?php
				if(($shipType > 0 && $shipType < 5) || $shipType==6)
					print $yyShpInf;
				else
					print "&nbsp;"; ?></strong></td>
				<?php	} ?>
			  </tr>
			  <tr>
                <td width="25%" align="right"><?php print $yyLgeImg?>:</td>
                <td width="25%" align="left"><input type="text" name="pLargeImage" size="25" value="<?php print str_replace('"',"&quot;",$pLargeImage)?>" /></td>
                <td width="25%" align="right"><?php
				if($shipType==1)
					print $yyShip . ":<br />" . $yyFirShi;
				elseif($shipType==2 || $shipType==3 || $shipType==4 || $shipType==6)
					print $yyPrWght . ":";
				else
					print "&nbsp;";
				  ?></td>
                <td width="25%" align="left"><?php
				if($shipType==1)
					print "<input type=text name='pShipping' size='15' value='" . $pShipping . "' />";
				elseif($shipType==2 || $shipType==3 || $shipType==4 || $shipType==6)
					print "<input type=text name='pShipping' size='15' value='" . $pWeight . "' />";
				else
					print "&nbsp;"; ?></td>
			  </tr>
			  <tr>
			<?php	if($simpleOptions){ ?>
				<td colspan="2">&nbsp;</td>
			<?php	}else{ ?>
                <td align="right"><?php print $yyNumOpt?>:</td>
                <td>
				  <select size="1" name="pNumOptions" onChange="setprodoptions();">
					<option value='0'><?php print $yyNone?></option>
					<?php	for($rowcounter=1; $rowcounter <= maxprodopts; $rowcounter++)
								print "<option value='" . $rowcounter . "'>" . $rowcounter . "</option>"; ?>
				  </select></td>
			<?php	} ?>
				<td width="25%" align="right"><?php
				if($shipType==1)
					print $yyShip . ":<br />" . $yySubShi;
				else
					print "&nbsp;"; ?></td>
                <td width="25%" align="left"><?php
				if($shipType==1)
					print "<input type=text name='pShipping2' size='15' value='" . (double)$pShipping2 . "' />";
				else
					print "&nbsp;"; ?></td>
			  </tr>
<?php	if($simpleOptions){
			for($index=0;$index < maxprodopts; $index++){
				if(($index % 2)==0) print "<tr>";
				print '<td align="right">' . $yyPrdOpt . ' ' . ($index+1) . ':</td><td><select size="1" id="pOption' . $index . '" name="pOption' . $index . '"><option value="0">None</option>';
				for($rowcounter=0;$rowcounter < $nalloptions;$rowcounter++){
					print '<option value="' . $alloptions[$rowcounter][0] . '"';
					if($index < $nprodoptions){
						if($prodoptions[$index][1]==$alloptions[$rowcounter][0]) print " selected";
					}
					print ">" . $alloptions[$rowcounter][1] . "</option>";
				}
				print "</td>";
				if(($index % 2) != 0) print "</tr>\n";
			}
			if(($index % 2)==0)
				print "</tr>\n";
			else
				print "<td colspan=\"2\">&nbsp;</td></tr>\n";
		}else{ ?>
			</table>
			<div name="prodoptions" id="prodoptions">
			</div>
			<table width="100%" border="0" cellspacing="0" cellpadding="3" bgcolor="">
<?php	}
		if(@$digidownloads==TRUE){ ?>
			  <tr>
                <td width="25%" align="right"><?php print $yyDownl?>:</td>
                <td colspan="3" align="left"><input type="text" size="60" name="pDownload" value="<?php print $pDownload?>" /></td>
			  </tr>
<?php	} ?>
			  <tr> 
                <td align="right"><?php print $yyDesc?>:</td>
                <td colspan="2"><textarea name="pDescription" cols="30" rows="6" wrap=virtual><?php print $pDescription?></textarea></td>
				<td align="center"><?php print $yyDrSppr?>: <select name="pDropship" size="1">
				  <option value="0"><?php print $yyNone?></option><?php
						for($index=0;$index<$nalldropship;$index++){
							print "<option value='" . $alldropship[$index]["dsID"] . "'";
							if($alldropship[$index]["dsID"]==$pDropship) print " selected";
							print ">" . $alldropship[$index]["dsName"] . "</option>\n";
						} ?>
				  </select>
				<br /><br />
				<?php print $yyExemp?> <font size="1">&lt;Ctrl>+Click</font><br />
					<select name="pExemptions[]" size="3" multiple>
					<option value="1" <?php if(($pExemptions&1)==1) print "selected"?>><?php print $yyExStat?></option>
					<option value="2" <?php if(($pExemptions&2)==2) print "selected"?>><?php print $yyExCoun?></option>
					<option value="4" <?php if(($pExemptions&4)==4) print "selected"?>><?php print $yyExShip?></option>
					</select><br /><img src="images/clearpixel.gif" width="20" height="3" alt="" />
<?php			if(@$perproducttaxrate==TRUE){ ?>
					<br /><?php print $yyTax?>: <input type="text" style="text-align:right" size="6" name="pTax" value="<?php print $pTax?>" />%
<?php			} ?>
				</td>
			  </tr>
<?php	for($index=2; $index <= $adminlanguages+1; $index++){
			if(($adminlangsettings & 2)==2){ ?>
			  <tr>
				<td align="right"><?php print $yyDesc . " " . $index?>:</td>
                <td colspan="3"><textarea name="pDescription<?php print $index?>" cols="30" rows="6" wrap=virtual><?php print $pDescriptions[$index]?></textarea></td>
			  </tr>
<?php		}
		}
?>
			  <tr>
                <td width="25%" align="right"><?php print $yyLnDesc?>:</td>
                <td colspan="3" align="left"><textarea name="pLongDescription" cols="45" rows="6" wrap=virtual><?php print $pLongDescription?></textarea></td>
			  </tr>
<?php	for($index=2; $index <= $adminlanguages+1; $index++){
			if(($adminlangsettings & 4)==4){ ?>
			  <tr>
				<td align="right"><?php print $yyLnDesc . " " . $index?>:</td>
                <td colspan="3"><textarea name="pLongDescription<?php print $index?>" cols="45" rows="6" wrap=virtual><?php print $pLongDescriptions[$index]?></textarea></td>
			  </tr>
<?php		}
		}
?>
			  <tr>
				<td width="25%" align="right"><strong><?php print $yyAddSec?>:</strong></td>
                <td colspan="4" align="left">
<?php		if(! $simpleSections){
				print '<select size="1" name="pNumSections" onChange="setprodsections();"><option value="0">' . $yyNone . '</option>';
				for($rowcounter=1;$rowcounter <= $maxprodsects; $rowcounter++)
					print "<option value='" . $rowcounter . "'>" . $rowcounter . "</option>";
				print "</select>";
			} ?>&nbsp;</td>
			  </tr>
<?php	if($simpleSections){
			for($index=0;$index < $maxprodsects; $index++){
				if(($index % 2)==0) print "<tr>";
				print '<td align="right">' . $yyPrdSec . ' ' . ($index+1) . ':</td><td><select size="1" id="pSection' . $index . '" name="pSection' . $index . '"><option value="0">' . $yyNone . '</option>';
				for($rowcounter=0;$rowcounter < $nallsections;$rowcounter++){
					print '<option value="' . $allsections[$rowcounter]["sectionID"] . '"';
					if($index < $nprodsections){
						if($prodsections[$index][0]==$allsections[$rowcounter]["sectionID"]) print " selected";
					}
					print ">" . $allsections[$rowcounter]["sectionWorkingName"] . "</option>";
				}
				print "</td>";
				if(($index % 2) != 0) print "</tr>\n";
			}
			if(($index % 2)==0)
				print "</tr>\n";
			else
				print "<td colspan=\"2\">&nbsp;</td></tr>\n";
		}else{ ?>
			</table>
			<div name="prodsections" id="prodsections">
			</div>
			<table width="100%" border="0" cellspacing="0" cellpadding="3" bgcolor="">
<?php	} ?>
			  <tr> 
                <td width="100%" colspan="4">
                  <p align="center"><input type="submit" value="<?php print $yySubmit?>" />&nbsp;&nbsp;<input type="reset" value="<?php print $yyReset?>" /></p>
<?php	show_info() ?>
                </td>
			  </tr>
            </table>
		  </td>
        </tr>
      </table>
	</form>
<?php	if(! $doaddnew){ ?>
<script language="javascript" type="text/javascript">
<!--
<?php	if(! $simpleOptions){ ?>
document.forms.mainform.pNumOptions.selectedIndex=<?php print $nprodoptions ?>;
document.forms.mainform.pNumOptions.options[<?php print $nprodoptions ?>].selected = true;
setprodoptions();
<?php	}
		if(! $simpleSections){ ?>
document.forms.mainform.pNumSections.selectedIndex=<?php print $nprodsections ?>;
document.forms.mainform.pNumSections.options[<?php print $nprodsections ?>].selected = true;
setprodsections();
<?php	}
		if($useStockManagement){ ?>
setstocktype();
<?php	} ?>
//-->
</script>
<?php	}
}elseif(@$_POST["act"]=="discounts"){
		$sSQL = "SELECT pName FROM products WHERE pID='" . @$_POST["id"] . "'";
		$result = mysql_query($sSQL) or print(mysql_error());
		$rs = mysql_fetch_assoc($result);
		$thisname=$rs["pName"];
		mysql_free_result($result);
		$numassigns=0;
		$sSQL = "SELECT cpaID,cpaCpnID,cpnWorkingName,cpnSitewide,cpnEndDate,cpnType FROM cpnassign LEFT JOIN coupons ON cpnassign.cpaCpnID=coupons.cpnID WHERE cpaType=2 AND cpaAssignment='" . @$_POST["id"] . "'";
		$result = mysql_query($sSQL) or print(mysql_error());
		while($rs=mysql_fetch_assoc($result))
			$alldata[$numassigns++]=$rs;
		mysql_free_result($result);
		$numcoupons=0;
		$sSQL = "SELECT cpnID,cpnWorkingName,cpnSitewide FROM coupons WHERE cpnSitewide=0 AND cpnEndDate >='" . date("Y-m-d",time()) ."'";
		$result = mysql_query($sSQL) or print(mysql_error());
		while($rs=mysql_fetch_assoc($result))
			$alldata2[$numcoupons++]=$rs;
		mysql_free_result($result);
?>
<script language="javascript" type="text/javascript">
<!--
function delrec(id) {
cmsg = "<?php print $yyConAss?>\n"
if (confirm(cmsg)) {
	document.mainform.id.value = id;
	document.mainform.act.value = "deletedisc";
	document.mainform.submit();
}
}
// -->
</script>
        <tr>
		<form name="mainform" method="post" action="adminprods.php">
		  <td width="100%">
			<input type="hidden" name="posted" value="1" />
			<input type="hidden" name="act" value="dodiscounts" />
			<input type="hidden" name="id" value="<?php print @$_POST["id"]?>" />
			<input type="hidden" name="tt" value="<?php print @$_POST["tt"];?>" />
			<input type="hidden" name="ts" value="<?php print @$_POST["ts"];?>" />
            <table width="100%" border="0" cellspacing="0" cellpadding="3" bgcolor="">
			  <tr> 
                <td width="100%" colspan="4" align="center"><strong><?php print $yyAssPrd?> &quot;<?php print $thisname?>&quot;.</strong><br />&nbsp;</td>
			  </tr>
<?php
	$gotone=FALSE;
	if($numcoupons>0){
		$thestr = '<tr><td colspan="4" align="center">' . $yyAsDsCp . ': <select name="assdisc" size="1">';
		for($index=0;$index < $numcoupons;$index++){
			$alreadyassign=FALSE;
			if($numassigns>0){
				for($index2=0;$index2<$numassigns;$index2++){
					if($alldata2[$index]["cpnID"]==$alldata[$index2]["cpaCpnID"]) $alreadyassign=TRUE;
				}
			}
			if(! $alreadyassign){
				$thestr .= "<option value='" . $alldata2[$index]["cpnID"] . "'>" . $alldata2[$index]["cpnWorkingName"] . "</option>\n";
				$gotone=TRUE;
			}
		}
		$thestr .= "</select> <input type='submit' value='Go' /></td></tr>";
	}
	if($gotone){
		print $thestr;
	}else{
?>
			  <tr> 
                <td width="100%" colspan="4" align="center"><br /><strong><?php print $yyNoDis?></td>
			  </tr>
<?php
	}
	if($numassigns>0){
?>
			  <tr> 
                <td width="100%" colspan="4" align="center"><br /><strong><?php print $yyCurDis?> &quot;<?php print $thisname?>&quot;.</strong><br />&nbsp;</td>
			  </tr>
			  <tr> 
                <td><strong><?php print $yyWrkNam?></strong></td>
				<td><strong><?php print $yyDisTyp?></strong></td>
				<td><strong><?php print $yyExpire?></strong></td>
				<td align="center"><strong><?php print $yyDelete?></strong></td>
			  </tr>
<?php
		for($index=0;$index<$numassigns;$index++){
			$prefont = "";
			$postfont = "";
			if((int)$alldata[$index]["cpnSitewide"]==1 || ($alldata[$index]["cpnEndDate"] != '3000-01-01 00:00:00' && strtotime($alldata[$index]["cpnEndDate"])-time() < 0)){
				$prefont = '<font color="#FF0000">';
				$postfont = "</font>";
			}
?>
			  <tr> 
                <td><?php	print $prefont . $alldata[$index]["cpnWorkingName"] . $postfont ?></td>
				<td><?php	if($alldata[$index]["cpnType"]==0)
								print $prefont . $yyFrSShp . $postfont;
							elseif($alldata[$index]["cpnType"]==1)
								print $prefont . $yyFlatDs . $postfont;
							elseif($alldata[$index]["cpnType"]==2)
								print $prefont . $yyPerDis . $postfont; ?></td>
				<td><?php	if($alldata[$index]["cpnEndDate"] == '3000-01-01 00:00:00')
								print $yyNever;
							elseif(strtotime($alldata[$index]["cpnEndDate"])-time() < 0)
								print '<font color="#FF0000">' . $yyExpird . '</font>';
							else
								print $prefont . date("Y-m-d",strtotime($alldata[$index]["cpnEndDate"])) . $postfont?></td>
				<td align="center"><input type="button" name="discount" value="Delete Assignment" onclick="delrec('<?php print $alldata[$index]["cpaID"]?>')" /></td>
			  </tr>
<?php
		}
	}else{
?>
			  <tr> 
                <td width="100%" colspan="4" align="center"><br /><strong><?php print $yyNoAss?></td>
			  </tr>
<?php
	}
?>
			  <tr>
                <td width="100%" colspan="4" align="center"><br />&nbsp;</td>
			  </tr>
			  <tr> 
                <td width="100%" colspan="4" align="center"><br />
                          <a href="admin.php"><strong><?php print $yyAdmHom?></strong></a><br />
                          &nbsp;</td>
			  </tr>
            </table></td>
		  </form>
        </tr>
<?php
}elseif(@$_POST["posted"]=="1" && $success){ ?>
      <table border="0" cellspacing="0" cellpadding="0" width="100%" bgcolor="" align="center">
        <tr>
          <td width="100%">
			<table width="100%" border="0" cellspacing="0" cellpadding="3" bgcolor="">
			  <tr> 
                <td width="100%" colspan="2" align="center"><br /><strong><?php print $yyUpdSuc?></strong><br /><br /><?php print $yyNowFrd?><br /><br />
                        <?php print $yyNoAuto?> <A href="adminprods.php<?php
							if(trim(@$_POST["ts"]) != "")
								print "?ts=" . trim(@$_POST["ts"]);
							elseif(trim(@$_POST["tt"]) != "")
								print "?tt=" . trim(@$_POST["tt"]);
							?>"><strong>click here</strong></a>.<br />
                        <br />
				<img src="../images/clearpixel.gif" width="300" height="3" alt="" />
                </td>
			  </tr>
			</table>
		  </td>
        </tr>
      </table>
<?php
}elseif(@$_POST["posted"]=="1"){ ?>
      <table border="0" cellspacing="0" cellpadding="0" width="100%" bgcolor="" align="center">
        <tr>
          <td width="100%">
			<table width="100%" border="0" cellspacing="0" cellpadding="3" bgcolor="">
			  <tr> 
                <td width="100%" colspan="2" align="center"><br /><font color="#FF0000"><strong><?php print $yyOpFai?></strong></font><br /><br /><?php print $errmsg?><br /><br />
				<a href="javascript:history.go(-1)"><strong><?php print $yyClkBac?></strong></a></td>
			  </tr>
			</table>
		  </td>
        </tr>
      </table>
<?php
}elseif(@$_GET["tt"] != "" || @$_GET["ts"] != ""){ ?>
<script language="javascript" type="text/javascript">
<!--
function modrec(id,evt) {
	document.mainform.id.value = id;
	<?php if(strstr(@$HTTP_SERVER_VARS["HTTP_USER_AGENT"], "Gecko")){ ?>
	if(evt.ctrlKey || evt.altKey)
	<?php }else{ ?>
	theevnt=window.event;
	if(theevnt.ctrlKey)
	<?php } ?>
		document.mainform.act.value = "clone";
	else
		document.mainform.act.value = "modify";
	document.mainform.submit();
}
function newrec(id) {
	document.mainform.id.value = id;
	document.mainform.act.value = "addnew";
	document.mainform.submit();
}
function discounts(id) {
	document.mainform.id.value = id;
	document.mainform.act.value = "discounts";
	document.mainform.submit();
}
function delrec(id) {
cmsg = "<?php print $yyConDel?>\n"
if (confirm(cmsg)) {
	document.mainform.id.value = id;
	document.mainform.act.value = "delete";
	document.mainform.submit();
}
}
// -->
</script>
      <table border="0" cellspacing="0" cellpadding="0" width="100%" bgcolor="" align="center">
        <tr>
		<form name="mainform" method="post" action="adminprods.php">
		  <td width="100%">
			<input type="hidden" name="posted" value="1" />
			<input type="hidden" name="act" value="xxxxx" />
			<input type="hidden" name="id" value="xxxxx" />
			<input type="hidden" name="tt" value="<?php print @$_GET["tt"];?>" />
			<input type="hidden" name="ts" value="<?php print @$_GET["ts"];?>" />
            <table width="100%" border="0" cellspacing="0" cellpadding="1" bgcolor="">
			  <tr> 
                <td width="100%" colspan="5" align="center"><strong><?php print $yyPrUpd?></strong>&nbsp;&nbsp;<input type="button" value="<?php print $yyNewPr?>" onclick="newrec()" /><br />&nbsp;</td>
			  </tr>
			  <tr>
				<td><strong><?php print $yyPrId?></strong></td>
				<td><strong><?php print $yyPrName?></strong></td>
				<td width="5%" align="center"><font size="1"><strong><?php print $yyDiscnt?></strong></font></td>
				<td width="5%" align="center"><font size="1"><strong><?php print $yyModify?></strong></font></td>
				<td width="5%" align="center"><font size="1"><strong><?php print $yyDelete?></strong></font></td>
			  </tr>
<?php
	$allcoupon="";
	$numcoupons=0;
	$sSQL = "SELECT DISTINCT cpaAssignment FROM cpnassign WHERE cpaType=2";
	$result = mysql_query($sSQL) or print(mysql_error());
	while($rs=mysql_fetch_array($result))
		$allcoupon[$numcoupons++]=$rs;
	mysql_free_result($result);
	if($stockManage != 0)
		$sSQL = "SELECT pID,pName,pDisplay,pInStock,rootSection,pSell FROM products LEFT OUTER JOIN sections ON products.pSection=sections.sectionID ";
	else
		$sSQL = "SELECT pID,pName,pDisplay,pSell,rootSection FROM products LEFT OUTER JOIN sections ON products.pSection=sections.sectionID ";
	if(@$_GET["tt"]=="ALL")
		; // Nothing
	else
		$sSQL .= "WHERE pSection=" . $_GET["ts"];
	if(@$adminsortorder!="") $sSQL .= " ORDER BY " . $adminsortorder; else $sSQL .= " ORDER BY pName";
	$result = mysql_query($sSQL) or print(mysql_error());
	$haveerrprods=FALSE;
	$bgcolor="#FFFFFF";
	if(mysql_num_rows($result) > 0){
		while($alldata = mysql_fetch_row($result)){
			if($bgcolor=="#E7EAEF") $bgcolor="#FFFFFF"; else $bgcolor="#E7EAEF"; ?>
			  <tr bgcolor="<?php print $bgcolor?>">
				<td><strong><?php print $alldata[0]?></strong></td>
				<td><strong><?php
					if(is_null($alldata[4]) || $alldata[4] != 1){
						print "<font color='#FF0000'>*</font> ";
						$haveerrprods=TRUE;
					}
					$stockbyoptions=false;
					if($stockManage != 0)
						if(($alldata[5] & 2) == 2) $stockbyoptions=true;
					if(((int)$alldata[3] <= 0 || (int)$alldata[2] == 0) && ! $stockbyoptions) print "<font color='#FF0000'>";
					if((int)$alldata[2] == 0) print "<strike>";
					print $alldata[1];
					if((int)$alldata[2] == 0) print "</strike>";
					if(((int)$alldata[3] <= 0 || (int)$alldata[2] == 0) && ! $stockbyoptions) print "</font>";
					?></strong><?php if($stockManage>0) print " (" . ($stockbyoptions?"-":$alldata[3]) . ")"?></td>
				<td align="center"><input <?php
					for($index=0;$index<$numcoupons;$index++){
						if($allcoupon[$index][0]==$alldata[0]){
							print 'style="color: #FF0000"';
							break;
						}
					}
				?> type="button" value="<?php print $yyAssign?>" onclick="discounts('<?php print str_replace(array("\\","'"),array("\\\\","\'"),$alldata[0])?>')" /></td>
				<td><input type=button value="<?php print $yyModify?>" onclick="modrec('<?php print str_replace(array("\\","'"),array("\\\\","\'"),$alldata[0])?>',event)" /></td>
				<td><input type=button value="<?php print $yyDelete?>" onclick="delrec('<?php print str_replace(array("\\","'"),array("\\\\","\'"),$alldata[0])?>')" /></td>
			  </tr>
<?php
		}
		if($haveerrprods){
?>
			  <tr> 
                <td width="100%" colspan="6"><br /><strong><font color='#FF0000'>* </font></strong><?php print $yySeePr?></td>
			  </tr>
<?php
		}
	}else{
?>
			  <tr> 
                <td width="100%" colspan="5" align="center"><br /><?php print $yyPrNone?><br />&nbsp;</td>
			  </tr>
<?php
	}
?>
			  <tr> 
                <td width="100%" colspan="5" align="center"><br /><strong><?php print $yyANewPr?></strong>&nbsp;&nbsp;<input type="button" value="<?php print $yyNewPr?>" onclick="newrec()" /><br />&nbsp;</td>
			  </tr>
			  <tr> 
                <td width="100%" colspan="5" align="center"><br />
                          <a href="admin.php"><strong><?php print $yyAdmHom?></strong></a><br />
				<img src="../images/clearpixel.gif" width="300" height="3" alt="" /></td>
			  </tr>
            </table></td>
		  </form>
        </tr>
      </table>
<?php
}else{ ?>
<script language="javascript" type="text/javascript">
<!--
function modrec(id) {
	document.mainform.action="adminprods.php";
	document.mainform.id.value = id;
	document.mainform.act.value = "modify";
	document.mainform.submit();
}
function newrec(id) {
	document.mainform.action="adminprods.php";
	document.mainform.id.value = id;
	document.mainform.act.value = "addnew";
	document.mainform.submit();
}
function dumpinventory(){
	document.mainform.action="dumporders.php";
	document.mainform.act.value = "dumpinventory";
	document.mainform.submit();
}
function dump2COinventory(){
	document.mainform.action="dumporders.php";
	document.mainform.act.value = "dump2COinventory";
	document.mainform.submit();
}
function delrec(id) {
cmsg = "<?php print $yyConDel?>\n"
if (confirm(cmsg)) {
	document.mainform.action="adminprods.php";
	document.mainform.id.value = id;
	document.mainform.act.value = "delete";
	document.mainform.submit();
}
}
// -->
</script>
      <table border="0" cellspacing="0" cellpadding="0" width="100%" bgcolor="" align="center">
        <tr>
		<form name="mainform" method="post" action="adminprods.php">
		  <td width="100%">
			<input type="hidden" name="posted" value="1" />
			<input type="hidden" name="act" value="xxxxx" />
			<input type="hidden" name="id" value="xxxxx" />
            <table width="100%" border="0" cellspacing="0" cellpadding="1" bgcolor="">
			  <tr> 
                <td width="100%" colspan="4" align="center"><br /><strong><?php print $yyPrClk?></strong><br />&nbsp;</td>
			  </tr>
			  <tr>
				<td colspan='4'><input type="button" value="<?php print $yyNewPr?>" onclick="newrec()" />&nbsp;&nbsp;<strong><a href="adminprods.php?tt=ALL"><?php print $yyPrAll?></a></td>
			  </tr>
<?php
	$sSQL = "SELECT sectionID,sectionWorkingName FROM sections WHERE rootSection=1 ORDER BY sectionWorkingName";
	$result = mysql_query($sSQL) or print(mysql_error());
	if(mysql_num_rows($result) > 0){
		$success=TRUE;
		$lasttsid = -1;
		while($alldata = mysql_fetch_row($result)){
			if(FALSE){
				if($lasttsid != $alldata[2]){
					$lasttsid = $alldata[2];
					print "<tr><td colspan='4'><strong><a href='adminprods.php?tt=" . $alldata[2];
					print "'>" . $alldata[3] . "</a></strong></td></tr>";
				}
			}
			print "<tr><td colspan='4'><strong>&nbsp;&nbsp;&raquo;&nbsp;<a href='adminprods.php?ts=" . $alldata[0] . "'";
			print ">" . $alldata[1] . "</a></strong></td></tr>";
		}
	}else
		$success=FALSE;
	mysql_free_result($result);
	$sSQL = "SELECT payProvEnabled,payProvData1 FROM payprovider WHERE payProvID=2";
	$result = mysql_query($sSQL) or print(mysql_error());
	$rs = mysql_fetch_assoc($result);
	if($rs["payProvEnabled"]==1 AND trim($rs["payProvData1"]) != ""){
?>
			  <tr> 
                <td width="100%" colspan="4" align="center"><input type="button" value="2Checkout Inventory" onclick="dump2COinventory()" /></td>
			  </tr>
<?php
	}
	$alldata="";
	if($stockManage != 0){
		$sSQL = "SELECT pID,pName,pDisplay,pInStock,pSell FROM products WHERE pInStock<=0";
		if(@$adminsortorder!="") $sSQL .= " ORDER BY " . $adminsortorder; else $sSQL .= " ORDER BY pName";
		$sSQL .= " LIMIT 100";
		$result = mysql_query($sSQL) or print(mysql_error());
?>
			  <tr> 
                <td width="100%" colspan="4" align="center"><br /><strong><?php print $yyPrOOS?></strong><br />&nbsp;</td>
			  </tr>
			  <tr> 
                <td width="100%" colspan="4" align="center"><input type="button" value="<?php print $yyDmpInv?>" onclick="dumpinventory()" /></td>
			  </tr>
<?php
		if(mysql_num_rows($result) > 0){
			while($alldata = mysql_fetch_row($result)){
				if(($alldata[4] & 2) != 2){ ?>
			  <tr>
				<td><strong><?php print $alldata[0]?></strong></td>
				<td><strong><?php
					if((int)$alldata[3] <= 0 || (int)$alldata[2] == 0) print "<font color='#FF0000'>";
					if((int)$alldata[2] == 0) print "<strike>";
					print $alldata[1];
					if((int)$alldata[2] == 0) print "</strike>";
					if((int)$alldata[3] <= 0 || (int)$alldata[2] == 0) print "</font>";
					?></strong></td>
				<td><input type=button value="<?php print $yyModify?>" onclick="modrec('<?php print str_replace(array("\\","'"),array("\\\\","\'"),$alldata[0])?>')" /></td>
				<td><input type=button value="<?php print $yyDelete?>" onclick="delrec('<?php print str_replace(array("\\","'"),array("\\\\","\'"),$alldata[0])?>')" /></td>
			  </tr>
<?php			}
			}
		}else{
?>
			  <tr> 
                <td width="100%" colspan="4" align="center"><br /><strong><?php print $yyPrNoOS?></strong><br />&nbsp;</td>
			  </tr>
<?php
		}
	}
?>
			  <tr> 
                <td width="100%" colspan="4" align="center"><br /><strong><?php print $yyANewPr?></strong>&nbsp;&nbsp;<input type="button" value="<?php print $yyNewPr?>" onclick="newrec()" /><br />&nbsp;</td>
			  </tr>
			  <tr> 
                <td width="100%" colspan="4" align="center"><br />
                          <a href="admin.php"><strong><?php print $yyAdmHom?></strong></a><br />
				<img src="../images/clearpixel.gif" width="300" height="3" alt="" /></td>
			  </tr>
            </table></td>
		  </form>
        </tr>
      </table>
<?php
}
?>
