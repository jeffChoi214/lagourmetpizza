<?php	$prodoptions="";
		productdisplayscript(@$noproductoptions!=TRUE); ?>
			<table width="<?php print $innertablewidth;?>" border="0" cellspacing="<?php print $innertablespacing;?>" cellpadding="<?php print $innertablepadding;?>" bgcolor="<?php print $innertablebg;?>">
<?php if(! (@isset($showcategories) && @$showcategories==FALSE)){ ?>
			  <tr>
				<td class="prodnavigation" colspan="2" align="<?php print $headeralign; ?>"><strong><p class="prodnavigation"><?php print $tslist ?></p></strong></td>
				<td align="right">&nbsp;<?php if(@$nobuyorcheckout != TRUE){ ?><a href="cart.php"><img src="images/checkout.gif" border="0" alt="<?php print $xxCOTxt?>" /></a><?php } ?></td>
			  </tr>
<?php }
if(@$nowholesalediscounts==TRUE && @$_SESSION["clientUser"]!="")
	if((($_SESSION["clientActions"] & 8) == 8) || (($_SESSION["clientActions"] & 16) == 16)) $noshowdiscounts=TRUE;
if(@$noshowdiscounts != TRUE){
	$sSQL = "SELECT DISTINCT ".getlangid("cpnName",1024)." FROM coupons LEFT OUTER JOIN cpnassign ON coupons.cpnID=cpnassign.cpaCpnID WHERE (";
	$addor = "";
	if($catid != "0"){
		$sSQL .= $addor . "((cpnSitewide=0 OR cpnSitewide=3) AND cpaType=1 AND cpaAssignment IN ('" . str_replace(",","','",$topsectionids) . "'))";
		$addor = " OR ";
	}
	$sSQL .= $addor . "(cpnSitewide=1 OR cpnSitewide=2)) AND cpnNumAvail>0 AND cpnEndDate>='" . date("Y-m-d",time()) ."' AND cpnIsCoupon=0 ORDER BY cpnID";
	$result2 = mysql_query($sSQL) or print(mysql_error());
	if(mysql_num_rows($result2) > 0){ ?>
			  <tr>
				<td align="left" colspan="3">
				  <p><strong><?php print $xxDsProd?></strong><br /><font color="#FF0000" size="1">
				  <?php	while($rs2=mysql_fetch_row($result2)){
							print $rs2[0] . "<br />";
						} ?></font></p>
				</td>
			  </tr>
<?php
	}
	mysql_free_result($result2);
}
?>
			  <tr>
				<td colspan="3" align="center" class="pagenums"><p class="pagenums"><?php
					If($iNumOfPages > 1 && @$pagebarattop==1) print writepagebar($CurPage, $iNumOfPages) . "<br />"; ?>
				  <img src="images/clearpixel.gif" width="300" height="8" alt="" /></p></td>
			  </tr>
<?php
	while($rs = mysql_fetch_array($allprods)){
		if(trim($rs[getlangid("pLongDescription",4)])!="" || ! (trim($rs["pLargeImage"])=="" || is_null($rs["pLargeImage"]) || trim($rs["pLargeImage"])=="prodimages/")){
			if(@$detailslink != ""){
				$startlink=str_replace('%pid%', $rs["pId"], str_replace('%largeimage%', $rs["pLargeImage"], $detailslink));
				$endlink=@$detailsendlink;
			}else{
				$startlink='<a href="proddetail.php?prod=' . urlencode($rs["pId"]) . (@$catid != "" && @$catid != "0" && $catid != $rs["pSection"] ? '&cat=' . $catid : "") . '">';
				$endlink="</a>";
			}
		}else{
			$startlink="";
			$endlink="";
		}
		for($cpnindex=0; $cpnindex < $adminProdsPerPage; $cpnindex++) $aDiscSection[$cpnindex][0] = "";
		if(! $isrootsection){
			$thetopts = $rs["pSection"];
			$gotdiscsection = FALSE;
			for($cpnindex=0; $cpnindex < $adminProdsPerPage; $cpnindex++){
				if($aDiscSection[$cpnindex][0]==$thetopts){
					$gotdiscsection = TRUE;
					break;
				}elseif($aDiscSection[$cpnindex][0]=="")
					break;
			}
			$aDiscSection[$cpnindex][0] = $thetopts;
			if(! $gotdiscsection){
				$topcpnids = $thetopts;
				for($index=0; $index<= 10; $index++){
					if($thetopts==0)
						break;
					else{
						$sSQL = "SELECT topSection FROM sections WHERE sectionID=" . $thetopts;
						$result2 = mysql_query($sSQL) or print(mysql_error());
						if(mysql_num_rows($result2) > 0){
							$rs2 = mysql_fetch_assoc($result2);
							$thetopts = $rs2["topSection"];
							$topcpnids .= "," . $thetopts;
						}else
							break;
					}
				}
				$aDiscSection[$cpnindex][1] = $topcpnids;
			}else
				$topcpnids = $aDiscSection[$cpnindex][1];
		}
		$alldiscounts = "";
		if(@$noshowdiscounts != TRUE){
			$sSQL = "SELECT DISTINCT ".getlangid("cpnName",1024)." FROM coupons LEFT OUTER JOIN cpnassign ON coupons.cpnID=cpnassign.cpaCpnID WHERE (cpnSitewide=0 OR cpnSitewide=3) AND cpnNumAvail>0 AND cpnEndDate>='" . date("Y-m-d",time()) ."' AND cpnIsCoupon=0 AND ((cpaType=2 AND cpaAssignment='" . $rs["pId"] . "')";
			if(! $isrootsection) $sSQL .= " OR (cpaType=1 AND cpaAssignment IN ('" . str_replace(",","','",$topcpnids) . "') AND NOT cpaAssignment IN ('" . str_replace(",","','",$topsectionids) . "'))";
			$sSQL .= ") ORDER BY cpnID";
			$result2 = mysql_query($sSQL) or print(mysql_error());
			while($rs2=mysql_fetch_row($result2))
				$alldiscounts .= $rs2[0] . "<br />";
			mysql_free_result($result2);
		} ?>
              <tr> 
                <td width="26%" rowspan="3" align="center" valign="middle">
				<?php
					if(trim($rs["pImage"])=="" || is_null($rs["pImage"]) || trim($rs["pImage"])=="prodimages/"){
						print "&nbsp;";
					}else{
						print $startlink . '<img class="prodimage" src="' . $rs["pImage"] . '" border="0" alt="' . str_replace('"','&nbsp;',$rs[getlangid("pName",1)]) . '" />' . $endlink;
					}
				?>
                </td>
				<td width="59%">
				  <p><?php if(@$showproductid==TRUE) print "<strong>" . $xxPrId . ":</strong> " . $rs["pId"] . "<br />"; ?><strong><?php print $rs[getlangid("pName",1)] . $xxDot; ?></strong><?php if($alldiscounts != "") print ' <font color="#FF0000"><strong>' . $xxDsApp . '</strong><br /><font size="1">' . $alldiscounts . "</font></font>" ?></p>
                </td>
				<td width="15%" align="right"><?php
            		if($startlink != "")
                		print "<p>" . $startlink . "<strong>".$xxPrDets."</strong></a>&nbsp;</p>";
                	else
                		print "&nbsp;";
              ?></td>
			  </tr>
<?php
// mod
print "
<script language=\"javascript\" type=\"text/javascript\">
<!--
var testnum = '';
function addall$Count()
{
if(!isW3)
return;var pDiv = document.getElementById('pricediv' + testnum);

totAdd3$Count = totAddoriginal$Count;
if (totAdd1$Count != 'unset')
totAdd3$Count = totAdd1$Count + totAdd3$Count;
if (totAdd1x$Count != 'unset')
totAdd3$Count = totAdd1x$Count + totAdd3$Count;

pDiv.innerHTML=formatprice(Math.round(totAdd3$Count*100.0)/100.0, '', '');
}
-->
</script>";
print "<script language=\"javascript\" type=\"text/javascript\">var totAdd2$Count = 0; var totAdd1$Count; totAdd1$Count = 'unset'; var totAdd1x$Count; totAdd1x$Count = 'unset';</script>";

updatepricescript(@$noproductoptions!=TRUE); ?>
	<form method="post" name="tForm<?php print $Count; ?>" action="cart.php" onsubmit="return formvalidator<?php print $Count; ?>(this)">
			  <tr>
			    <td colspan="2">
			      <p><?php print $rs[getlangid("pDescription",2)]; ?></p>
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

			   					print "<tr><td align='right' width='30%'><strong>" . $rs2[getlangid("optGrpName",16)] . ':</strong></td><td align="left">';
$modnum=0;

					if((int)$rs2["optType"]>0) $aOption[1][$index] .= "";
					do {

print "
<script language=\"javascript\" type=\"text/javascript\">
<!--
var totAddoriginal$Count=" . $rs["pPrice"] . ";
function check" . $rowcounter . "updateprice999$modnum$Count(){
var totAdd3$Count;
if(document.forms.tForm$Count.optn" . $rowcounter . "999$modnum.checked == true)
totAdd2$Count=totAdd2$Count+pricechecker(document.forms.tForm$Count.optn" . $rowcounter . "999$modnum.value);
else
totAdd2$Count=totAdd2$Count-pricechecker(document.forms.tForm$Count.optn" . $rowcounter . "999$modnum.value);
totAdd1x$Count = totAdd2$Count;
testnum = '$Count';
addall$Count();
}
-->
</script>";

if ($modnum == 0)
{
print "<table>";
}
if ($modnum == 0 || $modnum == 2 || $modnum == 4 || $modnum == 6 || $modnum == 8 || $modnum == 10 || $modnum == 12 || $modnum == 14 || $modnum == 16 || $modnum == 18 || $modnum == 20 || $modnum == 22 || $modnum == 24)
{
print "<tr>";
}
print "<td valign=top>";

						print '<input type="checkbox" onclick="check' . $rowcounter . 'updateprice999' . $modnum . $Count . '()" name="optn' . $rowcounter . '999' . $modnum . '" id="optn' . $rowcounter . '999' . $modnum . '" size="1" ';

						if($useStockManagement && (($rs["pSell"] & 2)==2) && $rs2["optStock"] <= 0) print 'class="oostock" '; else $aOption[2][$index]=true;
						print "value='" . $rs2["optID"] . "'>" . $rs2[getlangid("optName",32)];
						if(@$hideoptpricediffs != TRUE && (double)($rs2["optPriceDiff"]) != 0){
							print " (";
							if((double)($rs2["optPriceDiff"]) > 0) print "+";
							if(($rs2["optFlags"]&1)==1){
								$cacheThis=FALSE;
								print FormatEuroCurrency(($rs["pPrice"]*$rs2["optPriceDiff"])/100.0) . ")";
							}else
								print FormatEuroCurrency($rs2["optPriceDiff"]) . ")";
						}
print "</td>";
      if ($modnum == 1 || $modnum == 3 || $modnum == 5 || $modnum == 7 || $modnum == 9 || $modnum == 11 || $modnum == 13 || $modnum == 15 || $modnum == 17 || $modnum == 19 || $modnum == 21 || $modnum == 23 || $modnum == 25)
{
print "</tr>";
}
						print "\n";
												$modnum++;
				} while($rs2=mysql_fetch_array($result));
								 				print "</table>";
					print "</td></tr>";
				    }
				    else
				    {

// mod
print "
<script language=\"javascript\" type=\"text/javascript\">
<!--
var testnum = '';
function addall$Count()
{
if(!isW3)
return;var pDiv = document.getElementById('pricediv' + testnum);

totAdd3$Count = totAddoriginal$Count;
if (totAdd1$Count != 'unset')
totAdd3$Count = totAdd1$Count + totAdd3$Count;
if (totAdd1x$Count != 'unset')
totAdd3$Count = totAdd1x$Count + totAdd3$Count;

pDiv.innerHTML=formatprice(Math.round(totAdd3$Count*100.0)/100.0, '', '');
}
-->
</script>";


print "
<script language=\"javascript\" type=\"text/javascript\">
<!--
var totAddoriginal$Count=" . $rs["pPrice"] . ";
function check" . $rowcounter . "updateprice999$modnum$Count(){
var totAdd3$Count;
if(document.forms.tForm$Count.optn" . $rowcounter . "999$modnum.checked == true)
totAdd2$Count=totAdd2$Count+pricechecker(document.forms.tForm$Count.optn" . $rowcounter . "999$modnum.value);
else
totAdd2$Count=totAdd2$Count-pricechecker(document.forms.tForm$Count.optn" . $rowcounter . "999$modnum.value);
totAdd1x$Count = totAdd2$Count;
testnum = '$Count';
addall$Count();
}
-->
</script>";

print "<script language=\"javascript\" type=\"text/javascript\">var totAdd2$Count = 0; var totAdd1$Count; totAdd1$Count = 'unset'; var totAdd1x$Count; totAdd1x$Count = 'unset';</script>";

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
?>
                </td>
			  </tr>
			  <tr>
				<td width="59%" align="center" valign="middle">
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
                </td>
			    <td align="right" valign="bottom">
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
<?php	if(@$showquantonproduct==TRUE){ ?><input type="text" name="quant" size="2" maxlength="5" value="1" /><?php } ?><input align="middle" type="image" src="images/buy.gif" border="0" /><?php
	}else{
		print "<strong>".$xxOutStok."</strong>";
	}
}			  ?></td>
			  </tr>
			</form>
			  <tr>
				<td colspan="3" align="center">
				  <hr width="70%" align="center">
				</td>
			  </tr>
<?php
		$Count++;
	}
?>
			  <tr>
				<td colspan="3" align="center" class="pagenums"><p class="pagenums"><?php
					if($iNumOfPages > 1 && @$nobottompagebar<>TRUE) print writepagebar($CurPage, $iNumOfPages); ?><br />
				  <img src="images/clearpixel.gif" width="300" height="1" alt="" /></p></td>
			  </tr>
			</table>
