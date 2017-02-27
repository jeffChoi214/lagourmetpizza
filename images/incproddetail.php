<?php
//This code is copyright (c) Internet Business Solutions SL, all rights reserved.
//The contents of this file are protect under law as the intellectual property of Internet
//Business Solutions SL. Any use, reproduction, disclosure or copying of any kind 
//without the express and written permission of Internet Business Solutions SL is forbidden.
//Author: Vince Reid, vince@virtualred.net
if(trim(@$explicitid) != "") $prodid=trim($explicitid); else $prodid=trim(@$_GET["prod"]);
$prodlist = "'" . mysql_escape_string($prodid) . "'";
$WSP = "";
$OWSP = "";
if(@$_SESSION["clientUser"] != ""){
	if(($_SESSION["clientActions"] & 8) == 8){
		$WSP = "pWholesalePrice AS ";
		if(@$wholesaleoptionpricediff==TRUE) $OWSP = 'optWholesalePriceDiff AS ';
	}
	if(($_SESSION["clientActions"] & 16) == 16){
		$WSP = $_SESSION["clientPercentDiscount"] . "*pPrice AS ";
		if(@$wholesaleoptionpricediff==TRUE) $OWSP = $_SESSION["clientPercentDiscount"] . '*optPriceDiff AS ';
	}
}
for($rowcounter=0; $rowcounter < maxprodopts; $rowcounter++){
	$aOption[0][$rowcounter]=0;
}
$Count=0;
$previousid="";
$nextid="";
$alreadygotadmin = getadminsettings();
checkCurrencyRates($currConvUser,$currConvPw,$currLastUpdate,$currRate1,$currSymbol1,$currRate2,$currSymbol2,$currRate3,$currSymbol3);
$_SESSION["frompage"] = @$_SERVER['PHP_SELF'] . (trim(@$_SERVER['QUERY_STRING'])!= "" ? "?" : "") . @$_SERVER['QUERY_STRING'];
$sSQL = "SELECT pId,".getlangid("pName",1).",".getlangid("pDescription",2).",pImage,".$WSP."pPrice,pSection,pListPrice,pSell,pInStock,pExemptions,".(@$detailslink != "" ? "'' AS " : "")."pLargeImage,".getlangid("pLongDescription",4)." FROM products WHERE pDisplay<>0 AND pId='" . mysql_escape_string($prodid) . "'";
$result = mysql_query($sSQL) or print(mysql_error());
if(! ($rs = mysql_fetch_array($result))){
	print '<p align="center">&nbsp;<br />Sorry, this product is not currently available.<br />&nbsp;</p>';
}else{
$tslist = "";
$catid = $rs["pSection"];
if(trim(@$_GET["cat"]) != "" && is_numeric(@$_GET["cat"]) && trim(@$_GET["cat"]) != "0") $catid = $_GET["cat"];
$thetopts = $catid;
$topsectionids = $catid;
$isrootsection=FALSE;
for($index=0; $index <= 10; $index++){
	if($thetopts==0){
		if($catid=="0")
			$tslist = $xxHome . " " . $tslist;
		else
			$tslist = '<a href="categories.php">' . $xxHome . "</a> " . $tslist;
		break;
	}elseif($index==10){
		$tslist = "<strong>Loop</strong>" . $tslist;
	}else{
		$sSQL = "SELECT sectionID,topSection,".getlangid("sectionName",256).",rootSection,sectionurl FROM sections WHERE sectionID=" . $thetopts;
		$result2 = mysql_query($sSQL) or print(mysql_error());
		if(mysql_num_rows($result2) > 0){
			$rs2 = mysql_fetch_assoc($result2);
			if($rs2["sectionurl"] != "")
				$tslist = ' &raquo; <a href="' . $rs2["sectionurl"] . '">' . $rs2[getlangid("sectionName",256)] . "</a>" . $tslist;
			elseif($rs2["rootSection"]==1)
				$tslist = ' &raquo; <a href="products.php?cat=' . $rs2["sectionID"] . '">' . $rs2[getlangid("sectionName",256)] . "</a>" . $tslist;
			else
				$tslist = ' &raquo; <a href="categories.php?cat=' . $rs2["sectionID"] . '">' . $rs2[getlangid("sectionName",256)] . "</a>" . $tslist;
			$thetopts = $rs2["topSection"];
			$topsectionids .= "," . $thetopts;
		}else{
			$tslist = "Top Section Deleted" . $tslist;
			break;
		}
		mysql_free_result($result2);
	}
}
$nextid="";
$previousid="";
$sectionids = getsectionids($catid, FALSE);
$sSQL = "SELECT products.pId FROM products LEFT JOIN multisections ON products.pId=multisections.pId WHERE (products.pSection IN (" . $sectionids . ") OR multisections.pSection IN (" . $sectionids . ")) AND pDisplay<>0 AND products.pId > '" . mysql_escape_string($prodid) . "' ORDER BY products.pId ASC LIMIT 1";
$result2 = mysql_query($sSQL) or print(mysql_error());
if($rs2=mysql_fetch_assoc($result2))
	$nextid = urlencode($rs2["pId"]);
mysql_free_result($result2);
$sSQL = "SELECT products.pId FROM products LEFT JOIN multisections ON products.pId=multisections.pId WHERE (products.pSection IN (" . $sectionids . ") OR multisections.pSection IN (" . $sectionids . ")) AND pDisplay<>0 AND products.pId < '" . mysql_escape_string($prodid) . "' ORDER BY products.pId DESC LIMIT 1";
$result2 = mysql_query($sSQL) or print(mysql_error());
if($rs2=mysql_fetch_assoc($result2))
	$previousid = urlencode($rs2["pId"]);
mysql_free_result($result2);
$prodoptions="";
productdisplayscript(TRUE);
// mod
print "
<script language=\"javascript\" type=\"text/javascript\">
<!--
function addall()
{
if(!isW3)
return;var pDiv = document.getElementById('pricediv0');

totAdd3 = totAddoriginal;
if (totAdd1 != 'unset')
totAdd3 = totAdd1+ totAdd3;
if (totAdd1x != 'unset')
totAdd3 = totAdd1x + totAdd3;

pDiv.innerHTML=formatprice(Math.round(totAdd3*100.0)/100.0, '', '');
}
-->
</script>";
print "<script language=\"javascript\" type=\"text/javascript\">var totAdd2 = 0; var totAddoriginal=" . $rs["pPrice"] . "; var totAdd1; totAdd1 = 'unset'; var totAdd1x; totAdd1x = 'unset';</script>";
updatepricescript(TRUE);
?>
      <table border="0" cellspacing="<?php print $maintablespacing?>" cellpadding="<?php print $maintablepadding?>" width="<?php print $maintablewidth?>" bgcolor="<?php print $maintablebg?>" align="center">
        <tr> 
          <td width="100%">
		    <form method="post" name="tForm<?php print $Count?>" action="cart.php" onsubmit="return formvalidator<?php print $Count?>(this)">
<?php if(! (@isset($showcategories) && @$showcategories==FALSE)){ ?>
			<table width="<?php print $innertablewidth?>" border="0" cellspacing="<?php print $innertablespacing?>" cellpadding="<?php print $innertablepadding?>" bgcolor="<?php print $innertablebg?>">
              <tr> 
                <td class="prodnavigation" colspan="3" align="<?php print $headeralign?>" valign="top"><strong><p class="prodnavigation"><?php print $tslist ?><br />
                  <img src="images/clearpixel.gif" width="300" height="8" alt="" /></p></strong></td>
                <td align="right" valign="top">&nbsp;<?php if(@$nobuyorcheckout != TRUE){ ?><a href="cart.php"><img src="images/checkout.gif" border="0" alt="<?php print $xxCOTxt?>" /></a><?php } ?></td>
              </tr>
			</table>
<?php }
		$alldiscounts = "";
		if(@$nowholesalediscounts==TRUE && @$_SESSION["clientUser"]!="")
			if((($_SESSION["clientActions"] & 8) == 8) || (($_SESSION["clientActions"] & 16) == 16)) $noshowdiscounts=TRUE;
		if(@$noshowdiscounts != TRUE){
			$sSQL = "SELECT DISTINCT cpnWorkingName FROM coupons LEFT OUTER JOIN cpnassign ON coupons.cpnID=cpnassign.cpaCpnID WHERE cpnNumAvail>0 AND cpnEndDate>='" . date("Y-m-d",time()) ."' AND cpnIsCoupon=0 AND ";
			$sSQL .= "((cpnSitewide=1 OR cpnSitewide=2) ";
			$sSQL .= "OR (cpnSitewide=0 AND cpaType=2 AND cpaAssignment='" . $rs["pId"] . "') ";
			$sSQL .= "OR ((cpnSitewide=0 OR cpnSitewide=3) AND cpaType=1 AND cpaAssignment IN ('" . str_replace(",","','",$topsectionids) . "')))";
			$result2 = mysql_query($sSQL) or print(mysql_error());
			while($rs2=mysql_fetch_assoc($result2))
				$alldiscounts .= $rs2["cpnWorkingName"] . "<br />";
			mysql_free_result($result2);
		}

		if(@$usedetailbodyformat==1 || @$usedetailbodyformat==""){ ?>
            <table width="<?php print $innertablewidth?>" border="0" cellspacing="<?php print $innertablespacing?>" cellpadding="<?php print $innertablepadding?>" bgcolor="<?php print $innertablebg?>">
              <tr> 
                <td width="100%" colspan="4"> 
                  <p><?php if(@$showproductid==TRUE) print "<strong>" . $xxPrId . ":</strong> " . $rs["pId"] . "<br />"; ?><strong><?php print $rs[getlangid("pName",1)] . $xxDot;?></strong><?php if($alldiscounts!="") print ' <font color="#FF0000"><strong>' . $xxDsApp . '</strong><br /><font size="1">' . $alldiscounts . "</font></font>" ?></p>
                </td>
              </tr>
              <tr> 
                <td width="100%" colspan="4" align="center" class="detailimage"> <?php
					if(! (trim($rs["pLargeImage"])=="" || is_null($rs["pLargeImage"]) || trim($rs["pLargeImage"])=="prodimages/")){ ?> 
						<img class="prodimage" src="<?php print $rs["pLargeImage"]?>" border="0" alt="<?php print str_replace('"','&nbsp;',$rs[getlangid("pName",1)]) ?>" /> <?php
					}elseif(! (trim($rs["pImage"])=="" || is_null($rs["pImage"]) || trim($rs["pImage"])=="prodimages/")) { ?> 
						<img class="prodimage" src="<?php print $rs["pImage"]?>" border="0" alt="<?php print str_replace('"','&nbsp;',$rs[getlangid("pName",1)]) ?>" /> <?php
					}else
						print "&nbsp;"; ?> 
                </td>
              </tr>
              <tr> 
                <td width="100%" colspan="4"> 
                  <p><?php $longdesc = trim($rs[getlangid("pLongDescription",4)]);
				if($longdesc != "")
					print $longdesc;
				elseif(trim($rs[getlangid("pDescription",2)]) != "")
					print $rs[getlangid("pDescription",2)];
				else
					print "&nbsp;"; ?></p>
<p align="center">
<?php
$optionshavestock=true;
if(is_array($prodoptions)){
	print "<table border='0' cellspacing='1' cellpadding='1'>";
	$rowcounter=0;
	foreach($prodoptions as $rowcounter => $theopt){
		$index=0;
		$gotSelection=FALSE;
		$cacheThis=! $useStockManagement;
		while($index < (maxprodopts-1) && ((int)($aOption[0][$index])!=0)){
			if($aOption[0][$index]==(int)($theopt["poOptionGroup"])){
				$gotSelection=TRUE;
				break;
			}
			$index++;
		}
		if(!$gotSelection){
			$aOption[2][$index]=false;
			$sSQL="SELECT optID,".getlangid("optName",32).",".getlangid("optGrpName",16)."," . $OWSP . "optPriceDiff,optType,optFlags,optStock,optPriceDiff AS optDims FROM options LEFT JOIN optiongroup ON options.optGroup=optiongroup.optGrpID WHERE optGroup=" . $theopt["poOptionGroup"] . " ORDER BY optID";
			$result = mysql_query($sSQL) or print(mysql_error());
			if($rs2=mysql_fetch_array($result)){
				if(abs((int)$rs2["optType"])==3){
					$aOption[2][$index]=true;
					$fieldHeight = round(((double)($rs2["optDims"])-(int)($rs2["optDims"]))*100.0);
					$aOption[1][$index] = "<tr><td align='right' width='30%'><strong>" . $rs2[getlangid("optGrpName",16)] . ":</strong></td><td align='left'> <input type='hidden' name='optnPLACEHOLDER' value='" . $rs2["optID"] . "' />";
					if($fieldHeight != 1){
						$aOption[1][$index] .= "<textarea wrap='virtual' name='voptnPLACEHOLDER' cols='" . atb((int)($rs2["optDims"])) . "' rows='$fieldHeight'>";
						$aOption[1][$index] .= $rs2[getlangid("optName",32)] . "</textarea>";
					}else
						$aOption[1][$index] .= "<input maxlength='255' type='text' name='voptnPLACEHOLDER' size='" . atb($rs2["optDims"]) . "' value=\"" . str_replace('"',"&quot;",$rs2[getlangid("optName",32)]) . "\" />";
					$aOption[1][$index] .= "</td></tr>";
				}else{
// mod
				  $modSQL="SELECT checkbox FROM optiongroup WHERE optGrpID=" . $theopt["poOptionGroup"];
				  $modresult = mysql_query($modSQL) or print(mysql_error());
				  $modcheck = mysql_fetch_array($modresult);
				  if ($modcheck["checkbox"] == 1) {


			   					$aOption[1][$index] = "<tr><td align='right' width='30%'><strong>" . $rs2[getlangid("optGrpName",16)] . ':</strong></td><td align="left">';
$modnum=0;

					if((int)$rs2["optType"]>0) $aOption[1][$index] .= "";
					do {

print "
<script language=\"javascript\" type=\"text/javascript\">
<!--
function checkupdateprice999$modnum(){
var totAdd3;
if(document.forms.tForm0.optn999$modnum.checked == true)
totAdd2=totAdd2+pricechecker(document.forms.tForm0.optn999$modnum.value);
else
totAdd2=totAdd2-pricechecker(document.forms.tForm0.optn999$modnum.value);
totAdd1x = totAdd2;

addall();
}
-->
</script>";
if ($modnum == 0)
{
$aOption[1][$index] .= "<table>";
}
if ($modnum == 0 || $modnum == 2 || $modnum == 4 || $modnum == 6 || $modnum == 8 || $modnum == 10 || $modnum == 12 || $modnum == 14 || $modnum == 16 || $modnum == 18 || $modnum == 20 || $modnum == 22 || $modnum == 24)
{
$aOption[1][$index] .= "<tr>";
}
$aOption[1][$index] .= "<td>";

						$aOption[1][$index] .= '<input type="checkbox" onclick="checkupdateprice999' . $modnum . '()" name="optn999' . $modnum . '" id="optn999' . $modnum . '" size="1" ';

						if($useStockManagement && (($rs["pSell"] & 2)==2) && $rs2["optStock"] <= 0) $aOption[1][$index] .= 'class="oostock" '; else $aOption[2][$index]=true;
						$aOption[1][$index] .= "value='" . $rs2["optID"] . "'>" . $rs2[getlangid("optName",32)];
						if(@$hideoptpricediffs != TRUE && (double)($rs2["optPriceDiff"]) != 0){
							$aOption[1][$index] .= " (";
							if((double)($rs2["optPriceDiff"]) > 0) $aOption[1][$index] .= "+";
							if(($rs2["optFlags"]&1)==1){
								$cacheThis=FALSE;
								$aOption[1][$index] .= FormatEuroCurrency(($rs["pPrice"]*$rs2["optPriceDiff"])/100.0) . ")";
							}else
								$aOption[1][$index] .= FormatEuroCurrency($rs2["optPriceDiff"]) . ")";
						}
$aOption[1][$index] .= "</td>";
if ($modnum == 1 || $modnum == 3 || $modnum == 5 || $modnum == 7 || $modnum == 9 || $modnum == 11 || $modnum == 13 || $modnum == 15 || $modnum == 17 || $modnum == 19 || $modnum == 21 || $modnum == 23 || $modnum == 25)
{
$aOption[1][$index] .= "</tr>";
}
						$aOption[1][$index] .= "\n";
												$modnum++;
				}
				 while($rs2=mysql_fetch_array($result));
				 				$aOption[1][$index] .= "</table>";
					$aOption[1][$index] .= "</td></tr>";
				    }
				    else
				    {
					$aOption[1][$index] = "<tr><td align='right' width='30%'><strong>" . $rs2[getlangid("optGrpName",16)] . ':</strong></td><td align="left"> <select class="prodoption" onChange="updatepricePLACEHOLDER();" name="optnPLACEHOLDER" size="1">';

					if((int)$rs2["optType"]>0) $aOption[1][$index] .= "<option value=''>".$xxPlsSel."</option>";
					do {
						$aOption[1][$index] .= '<option ';
						if($useStockManagement && (($rs["pSell"] & 2)==2) && $rs2["optStock"] <= 0) $aOption[1][$index] .= 'class="oostock" '; else $aOption[2][$index]=true;
						$aOption[1][$index] .= "value='" . $rs2["optID"] . "'>" . $rs2[getlangid("optName",32)];
						if(@$hideoptpricediffs != TRUE && (double)($rs2["optPriceDiff"]) != 0){
							$aOption[1][$index] .= " (";
							if((double)($rs2["optPriceDiff"]) > 0) $aOption[1][$index] .= "+";
							if(($rs2["optFlags"]&1)==1){
								$cacheThis=FALSE;
								$aOption[1][$index] .= FormatEuroCurrency(($rs["pPrice"]*$rs2["optPriceDiff"])/100.0) . ")";
							}else
								$aOption[1][$index] .= FormatEuroCurrency($rs2["optPriceDiff"]) . ")";
						}
						$aOption[1][$index] .= "</option>\n";
					} while($rs2=mysql_fetch_array($result));
					$aOption[1][$index] .= "</select></td></tr>";
    }
				}
			}
			if($cacheThis) $aOption[0][$index] = (int)($theopt["poOptionGroup"]);
		}
		print str_replace("updatepricePLACEHOLDER", ($rs["pPrice"]==0 && @$pricezeromessage != ""?"dummyfunc":"updateprice" . $Count), str_replace("optnPLACEHOLDER","optn" . $rowcounter, $aOption[1][$index]));
		$optionshavestock = ($optionshavestock && $aOption[2][$index]);
		$rowcounter++;
	}
	print "</table>";
}
?></p>
                </td>
              </tr>
              <tr>
			    <td width="20%"><?php if(@$useemailfriend){ ?>
<p align="center"><a href="javascript:openEFWindow('<?php print urlencode($prodid)?>')"><strong><?php print $xxEmFrnd?></strong></a></p>
<?php } else { ?>
&nbsp;
<?php } ?></td><td width="60%" align="center" colspan="2">
			<?php	if(@$noprice==TRUE){
						print '&nbsp;';
					}else{
						if((double)$rs["pListPrice"]!=0.0) print str_replace("%s", FormatEuroCurrency($rs["pListPrice"]), $xxListPrice) . "<br />";
						if($rs["pPrice"]==0 && @$pricezeromessage != "")
							print $pricezeromessage;
						else{
							print '<strong>' . $xxPrice . ':</strong> <span class="price" id="pricediv' . $Count . '" name="pricediv' . $Count . '">' . FormatEuroCurrency($rs["pPrice"]) . '</span> ';
							if(@$showtaxinclusive && ($rs["pExemptions"] & 2)!=2) printf($ssIncTax,'<span id="pricedivti' . $Count . '" name="pricedivti' . $Count . '">' . FormatEuroCurrency($rs["pPrice"]+($rs["pPrice"]*$countryTax/100.0)) . '</span> ');
							print "<br />";
							if(@$currencyseparator=="") $currencyseparator=" ";
							$extracurr = "";
							if($currRate1!=0 && $currSymbol1!="") $extracurr = str_replace("%s",number_format($rs["pPrice"]*$currRate1,checkDPs($currSymbol1),".",","),$currFormat1) . $currencyseparator;
							if($currRate2!=0 && $currSymbol2!="") $extracurr .= str_replace("%s",number_format($rs["pPrice"]*$currRate2,checkDPs($currSymbol2),".",","),$currFormat2) . $currencyseparator;
							if($currRate3!=0 && $currSymbol3!="") $extracurr .= str_replace("%s",number_format($rs["pPrice"]*$currRate3,checkDPs($currSymbol3),".",","),$currFormat3) . "</strong>";
							if($extracurr!='') print '<span class="extracurr" id="pricedivec' . $Count . '" name="pricedivec' . $Count . '">' . $extracurr . "</strong></span>";
						}
					} ?>
				</td><td width="20%" align="right">
<?php
if(@$nobuyorcheckout == TRUE)
	print "&nbsp;";
else{
	if($useStockManagement)
		if(($rs["pSell"] & 2) == 2) $isInStock = $optionshavestock; else $isInStock = ((int)($rs["pInStock"]) > 0);
	else
		$isInStock = (((int)$rs["pSell"] & 1) != 0);
	if($isInStock){
?>
<input type="hidden" name="id" value="<?php print $rs["pId"]?>" />
<input type="hidden" name="mode" value="add" />
<input type="hidden" name="frompage" value="<?php print @$_SERVER['PHP_SELF'] . (trim(@$_SERVER['QUERY_STRING'])!= "" ? "?" : "") . @$_SERVER['QUERY_STRING']?>" />
<?php	if(@$showquantondetail==TRUE){ ?><input type="text" name="quant" size="2" maxlength="5" value="1" /><?php } ?><input align="middle" type="image" src="images/buy.gif" border="0" /><?php
	}else{
		print "<strong>" . $xxOutStok . "</strong>";
	}
}			?></td>
            </tr>
<?php
if($previousid != "" || $nextid != ""){
	print '<tr><td align="center" colspan="4" class="pagenums"><p class="pagenums">&nbsp;<br />';
	if($previousid != "") print '<a href="proddetail.php?prod=' . $previousid . (@$_GET["cat"] != "" ? '&cat=' . @$_GET["cat"] : "") . '">';
	print '<strong>&laquo; ' . $xxPrev . '</strong>';
	if($previousid != "") print '</a>';
	print ' | ';
	if($nextid != "") print '<a href="proddetail.php?prod=' . $nextid . (@$_GET["cat"] != "" ? '&cat=' . @$_GET["cat"] : "") . '">';
	print '<strong>' . $xxNext . ' &raquo;</strong>';
	if($nextid != "") print '</a>';
	print '</p></td></tr>';
} ?>
            </table>
<?php }else{ // if($usedetailbodyformat==2) ?>
			<table width="<?php print $innertablewidth?>" border="0" cellspacing="<?php print $innertablespacing?>" cellpadding="<?php print $innertablepadding?>" bgcolor="<?php print $innertablebg?>">
              <tr> 
                <td width="30%" align="center" class="detailimage"> <?php
					if(! (trim($rs["pLargeImage"])=="" || is_null($rs["pLargeImage"]) || trim($rs["pLargeImage"])=="prodimages/")){ ?> 
						<img class="prodimage" src="<?php print $rs["pLargeImage"]?>" border="0" alt="<?php print str_replace('"','&nbsp;',$rs[getlangid("pName",1)]) ?>" /> <?php
					}elseif(! (trim($rs["pImage"])=="" || is_null($rs["pImage"]) || trim($rs["pImage"])=="prodimages/")) { ?> 
						<img class="prodimage" src="<?php print $rs["pImage"]?>" border="0" alt="<?php print str_replace('"','&nbsp;',$rs[getlangid("pName",1)]) ?>" /> <?php
					}else
						print "&nbsp;"; ?> 
                </td>
				<td>&nbsp;</td>
				<td width="70%" valign="top"> 
                  <p><?php if(@$showproductid==TRUE) print "<strong>" . $xxPrId . ":</strong> " . $rs["pId"] . "<br />"; ?><strong><?php print $rs[getlangid("pName",1)] . $xxDot;?></strong><?php if($alldiscounts!="") print ' <font color="#FF0000"><strong>' . $xxDsApp . '</strong><br /><font size="1">' . $alldiscounts . "</font></font>" ?><br />
				  <?php $longdesc = trim($rs[getlangid("pLongDescription",4)]);
				if($longdesc != "")
					print $longdesc . "<br />";
				elseif(trim($rs[getlangid("pDescription",2)]) != "")
					print $rs[getlangid("pDescription",2)] . "<br />";
				if(@$noprice==TRUE){
					print '&nbsp;';
				}else{
					if((double)$rs["pListPrice"]!=0.0) print str_replace("%s", FormatEuroCurrency($rs["pListPrice"]), $xxListPrice) . "<br />";
					if($rs["pPrice"]==0 && @$pricezeromessage != "")
						print $pricezeromessage;
					else{
						print '<strong>' . $xxPrice . ':</strong> <span class="price" id="pricediv' . $Count . '" name="pricediv' . $Count . '">' . FormatEuroCurrency($rs["pPrice"]) . '</span> ';
						if(@$showtaxinclusive && ($rs["pExemptions"] & 2)!=2) printf($ssIncTax,'<span id="pricedivti' . $Count . '" name="pricedivti' . $Count . '">' . FormatEuroCurrency($rs["pPrice"]+($rs["pPrice"]*$countryTax/100.0)) . '</span> ');
						print "<br />";
						if(@$currencyseparator=="") $currencyseparator=" ";
						$extracurr = "";
						if($currRate1!=0 && $currSymbol1!="") $extracurr = str_replace("%s",number_format($rs["pPrice"]*$currRate1,checkDPs($currSymbol1),".",","),$currFormat1) . $currencyseparator;
						if($currRate2!=0 && $currSymbol2!="") $extracurr .= str_replace("%s",number_format($rs["pPrice"]*$currRate2,checkDPs($currSymbol2),".",","),$currFormat2) . $currencyseparator;
						if($currRate3!=0 && $currSymbol3!="") $extracurr .= str_replace("%s",number_format($rs["pPrice"]*$currRate3,checkDPs($currSymbol3),".",","),$currFormat3) . "</strong>";
						if($extracurr!='') print '<span class="extracurr" id="pricedivec' . $Count . '" name="pricedivec' . $Count . '">' . $extracurr . "</strong></span>";
					}
					print '<hr width="80%">';
				} ?>
				</p>
				  <p align="center">
<?php
$optionshavestock=true;
if(is_array($prodoptions)){
	print "<table border='0' cellspacing='1' cellpadding='1' width='100%'>";
	$rowcounter=0;
	foreach($prodoptions as $rowcounter => $theopt){
		$index=0;
		$gotSelection=FALSE;
		$cacheThis=! $useStockManagement;
		while($index < (maxprodopts-1) && ((int)($aOption[0][$index])!=0)){
			if($aOption[0][$index]==(int)($theopt["poOptionGroup"])){
				$gotSelection=TRUE;
				break;
			}
			$index++;
		}
		if(!$gotSelection){
			$aOption[2][$index]=false;
			$sSQL="SELECT optID,".getlangid("optName",32).",".getlangid("optGrpName",16)."," . $OWSP . "optPriceDiff,optType,optFlags,optStock,optPriceDiff AS optDims FROM options LEFT JOIN optiongroup ON options.optGroup=optiongroup.optGrpID WHERE optGroup=" . $theopt["poOptionGroup"] . " ORDER BY optID";
			$result = mysql_query($sSQL) or print(mysql_error());
			if($rs2=mysql_fetch_array($result)){
				if(abs((int)$rs2["optType"])==3){
					$aOption[2][$index]=true;
					$fieldHeight = round(((double)($rs2["optDims"])-(int)($rs2["optDims"]))*100.0);
					$aOption[1][$index] = "<tr><td align='right' width='30%'>" . $rs2[getlangid("optGrpName",16)] . ":</td><td align='left'> <input type='hidden' name='optnPLACEHOLDER' value='" . $rs2["optID"] . "' />";
					if($fieldHeight != 1){
						$aOption[1][$index] .= "<textarea wrap='virtual' name='voptnPLACEHOLDER' cols='" . atb((int)($rs2["optDims"])) . "' rows='$fieldHeight'>";
						$aOption[1][$index] .= $rs2[getlangid("optName",32)] . "</textarea>";
					}else
						$aOption[1][$index] .= "<input maxlength='255' type='text' name='voptnPLACEHOLDER' size='" . atb($rs2["optDims"]) . "' value=\"" . str_replace('"',"&quot;",$rs2[getlangid("optName",32)]) . "\" />";
					$aOption[1][$index] .= "</td></tr>";
				}else{
					$aOption[1][$index] = "<tr><td align='right' width='30%'>" . $rs2[getlangid("optGrpName",16)] . ':</strong></td><td align="left"> <select class="prodoption" onChange="updatepricePLACEHOLDER();" name="optnPLACEHOLDER" size="1">';
					if((int)$rs2["optType"]>0) $aOption[1][$index] .= "<option value=''>".$xxPlsSel."</option>";
					do {
						$aOption[1][$index] .= '<option ';
						if($useStockManagement && (($rs["pSell"] & 2)==2) && $rs2["optStock"] <= 0) $aOption[1][$index] .= 'class="oostock" '; else $aOption[2][$index]=true;
						$aOption[1][$index] .= "value='" . $rs2["optID"] . "'>" . $rs2[getlangid("optName",32)];
						if(@$hideoptpricediffs != TRUE && (double)($rs2["optPriceDiff"]) != 0){
							$aOption[1][$index] .= " (";
							if((double)($rs2["optPriceDiff"]) > 0) $aOption[1][$index] .= "+";
							if(($rs2["optFlags"]&1)==1){
								$cacheThis=FALSE;
								$aOption[1][$index] .= FormatEuroCurrency(($rs["pPrice"]*$rs2["optPriceDiff"])/100.0) . ")";
							}else
								$aOption[1][$index] .= FormatEuroCurrency($rs2["optPriceDiff"]) . ")";
						}
						$aOption[1][$index] .= "</option>\n";
					} while($rs2=mysql_fetch_array($result));
					$aOption[1][$index] .= "</select></td></tr>";
				}
			}
			if($cacheThis) $aOption[0][$index] = (int)($theopt["poOptionGroup"]);
		}
		print str_replace("updatepricePLACEHOLDER", ($rs["pPrice"]==0 && @$pricezeromessage != ""?"dummyfunc":"updateprice" . $Count), str_replace("optnPLACEHOLDER","optn" . $rowcounter, $aOption[1][$index]));
		$optionshavestock = ($optionshavestock && $aOption[2][$index]);
		$rowcounter++;
	}
	if(@$nobuyorcheckout != true && (@$showquantondetail==TRUE || ! @isset($showquantondetail))){
?>
	<tr><td align="right"><?php print $xxQuant?>:</td><td align="left"><input type="text" name="quant" size="<?php print atb(4);?>" maxlength="5" value="1" /></td></tr>
<?php
	}
	print "</table>";
}else{
	if(@$nobuyorcheckout != true && (@$showquantondetail==TRUE || ! @isset($showquantondetail))){
?>
	<table border='0' cellspacing='1' cellpadding='1' width='100%'>
	<tr><td align="right"><?php print $xxQuant?>:</td><td><input type="text" name="quant" size="<?php print atb(4);?>" maxlength="5" value="1" /></td></tr>
	</table>
<?php
	}
}
?></p>
<p align="center">
<?php
if(@$nobuyorcheckout == TRUE)
	print "&nbsp;";
else{
	if($useStockManagement)
		if(($rs["pSell"] & 2) == 2) $isInStock = $optionshavestock; else $isInStock = ((int)($rs["pInStock"]) > 0);
	else
		$isInStock = (((int)$rs["pSell"] & 1) != 0);
	if($isInStock){
?>
<input type="hidden" name="id" value="<?php print $rs["pId"]?>" />
<input type="hidden" name="mode" value="add" />
<input type="hidden" name="frompage" value="<?php print @$_SERVER['PHP_SELF'] . (trim(@$_SERVER['QUERY_STRING'])!= "" ? "?" : "") . @$_SERVER['QUERY_STRING']?>" />
<input type="image" src="images/buy.gif" border="0" /><br /><?php
	}else{
		print "<strong>" . $xxOutStok . "</strong><br />";
	}
}
if($previousid != "" || $nextid != ""){
	print '</p><p class="pagenums" align="center">';
	if($previousid != "") print '<a href="proddetail.php?prod=' . $previousid . (@$_GET["cat"] != "" ? '&cat=' . @$_GET["cat"] : "") . '">';
	print '<strong>&laquo; ' . $xxPrev . '</strong>';
	if($previousid != "") print '</a>';
	print ' | ';
	if($nextid != "") print '<a href="proddetail.php?prod=' . $nextid . (@$_GET["cat"] != "" ? '&cat=' . @$_GET["cat"] : "") . '">';
	print '<strong>' . $xxNext . ' &raquo;</strong>';
	if($nextid != "") print '</a>';
	print '<br />';
} ?>
<hr width="80%"></p>
<?php if(@$useemailfriend){ ?>
<p align="center"><a href="javascript:openEFWindow('<?php print urlencode($prodid)?>')"><strong><?php print $xxEmFrnd?></strong></a></p>
<?php } ?>
</td>
            </tr>
            </table>
<?php } ?>
			</form>
          </td>
        </tr>
      </table>
<?php } // EOF ?>
