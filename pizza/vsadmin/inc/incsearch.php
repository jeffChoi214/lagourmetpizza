<?php
$iNumOfPages = 0;
$showcategories=FALSE;
$gotcriteria=FALSE;
$numcats=0;
$catid=0;
$nobox="";
$isrootsection=FALSE;
$topsectionids="0";
for($rowcounter=0; $rowcounter < maxprodopts; $rowcounter++){
	$aOption[0][$rowcounter]=0;
}
if(! @is_numeric($_GET["pg"]))
	$CurPage = 1;
else
	$CurPage = (int)($_GET["pg"]);
if(@$_GET["nobox"]=="true" || @$_POST["nobox"]=="true")
	$nobox='true';
$WSP = "";
$OWSP = "";
$TWSP = "pPrice";
if(@$_SESSION["clientUser"] != ""){
	if(($_SESSION["clientActions"] & 8) == 8){
		$WSP = "pWholesalePrice AS ";
		$TWSP = "pWholesalePrice";
		if(@$wholesaleoptionpricediff==TRUE) $OWSP = 'optWholesalePriceDiff AS ';
	}
	if(($_SESSION["clientActions"] & 16) == 16){
		$WSP = $_SESSION["clientPercentDiscount"] . "*pPrice AS ";
		$TWSP = $_SESSION["clientPercentDiscount"] . "*pPrice";
		if(@$wholesaleoptionpricediff==TRUE) $OWSP = $_SESSION["clientPercentDiscount"] . '*optPriceDiff AS ';
	}
}
$tsID="";
$scat="";
$stext="";
$stype="";
$sprice="";
$minprice="";
if(@$_POST["scat"] != "") $scat=@$_POST["scat"];
if(@$_POST["stext"] != "") $stext=@$_POST["stext"];
if(@$_POST["stype"] != "") $stype=@$_POST["stype"];
if(@$_POST["sprice"] != "") $sprice=@$_POST["sprice"];
if(@$_POST["sminprice"] != "") $minprice=@$_POST["sminprice"];
if(@$_GET["scat"] != "") $scat=@$_GET["scat"];
if(@$_GET["stext"] != "") $stext=@$_GET["stext"];
if(@$_GET["stype"] != "") $stype=@$_GET["stype"];
if(@$_GET["sprice"] != "") $sprice=@$_GET["sprice"];
if(@$_GET["sminprice"] != "") $minprice=@$_GET["sminprice"];
if(substr($scat,0,2)=="ms") $thecat = substr($scat,2); else $thecat=$scat;
if(! is_numeric($thecat)) $thecat=""; else $thecat=(int)$thecat;
$Count = 0;
function writemenulevel($id,$itlevel){
	global $allcatsa,$numcats,$thecat;
	if($itlevel<10){
		for($wmlindex=0; $wmlindex < $numcats; $wmlindex++){
			if($allcatsa[$wmlindex][2]==$id){
				print "<option value='" . $allcatsa[$wmlindex][0] . "'";
				if($thecat==$allcatsa[$wmlindex][0]) print " selected>"; else print ">";
				for($index = 0; $index < $itlevel-1; $index++)
					print '&nbsp;&nbsp;&raquo;&nbsp;';
				print $allcatsa[$wmlindex][1] . "</option>\n";
				if($allcatsa[$wmlindex][3]==0) writemenulevel($allcatsa[$wmlindex][0],$itlevel+1);
			}
		}
	}
}
function writepagebar($CurPage, $iNumPages){
	global $nobox,$scat,$stext,$stype,$sprice,$minprice,$xxNext,$xxPrev;
	$sLink = "<a href='search.php?nobox=" . $nobox . '&scat=' . $scat . '&stext=' . str_replace(' ', '+', unstripslashes($stext)) . '&stype=' . $stype . '&sprice=' . str_replace(' ', '+', unstripslashes($sprice)) . ($minprice!=""?"&sminprice=".$minprice:"") . '&pg=';
	$startPage = max(1,round(floor((double)$CurPage/10.0)*10));
	$endPage = min($iNumPages,round(floor((double)$CurPage/10.0)*10)+10);
	if($CurPage > 1)
		$sStr = $sLink . "1" . "'><strong><font face='Verdana'>&laquo;</font></strong></a> " . $sLink . ($CurPage-1) . "'>".$xxPrev."</a> | ";
	else
		$sStr = "<strong><font face='Verdana'>&laquo;</font></strong> ".$xxPrev." | ";
	for($i=$startPage;$i <= $endPage; $i++){
		if($i==$CurPage)
			$sStr .= $i . " | ";
		else{
			$sStr .= $sLink . $i . "'>";
			if($i==$startPage && $i > 1) $sStr .= "...";
			$sStr .= $i;
			if($i==$endPage && $i < $iNumPages) $sStr .= "...";
			$sStr .= "</a> | ";
		}
	}
	if($CurPage < $iNumPages)
		return $sStr . $sLink . ($CurPage+1) . "'>".$xxNext."</a> " . $sLink . $iNumPages . "'><strong><font face='Verdana'>&raquo;</font></strong></a>";
	else
		return $sStr . " ".$xxNext." <strong><font face='Verdana'>&raquo;</font></strong>";
}
$alreadygotadmin = getadminsettings();
checkCurrencyRates($currConvUser,$currConvPw,$currLastUpdate,$currRate1,$currSymbol1,$currRate2,$currSymbol2,$currRate3,$currSymbol3);
if(@$_SESSION["clientLoginLevel"] != "") $minloglevel=$_SESSION["clientLoginLevel"]; else $minloglevel=0;
$sSQL = "SELECT sectionID,".getlangid("sectionName",256).",topSection,rootSection FROM sections WHERE sectionDisabled<=" . $minloglevel . " ";
if(@$onlysubcats==TRUE)
	$sSQL .= "AND rootSection=1 ORDER BY ".getlangid("sectionName",256);
else
	$sSQL .= "ORDER BY sectionOrder";
$allcats = mysql_query($sSQL) or print(mysql_error());
if(mysql_num_rows($allcats)==0)
	$success=FALSE;
else
	$success=TRUE;
if(@$_POST["posted"]=="1" || @$_GET["pg"] != ""){
	if($thecat != ""){
		$sSQL = "SELECT DISTINCT products.pId FROM products LEFT JOIN multisections ON products.pId=multisections.pId WHERE pDisplay<>0 ";
		$gotcriteria=TRUE;
		$sectionids = getsectionids($thecat, FALSE);
		if($sectionids != "") $sSQL .= "AND (products.pSection IN (" . $sectionids . ") OR multisections.pSection IN (" . $sectionids . ")) ";
	}else
		$sSQL = "SELECT DISTINCT products.pId FROM products WHERE pDisplay<>0 ";
	if(is_numeric($sprice)){
		$gotcriteria=TRUE;
		$sSQL .= "AND ".$TWSP."<='" . mysql_escape_string(unstripslashes($sprice)) . "' ";
	}
	if(is_numeric($minprice)){
		$gotcriteria=TRUE;
		$sSQL .= "AND ".$TWSP.">='" . mysql_escape_string(unstripslashes($minprice)) . "' ";
	}
	if(trim($stext) != ""){
		$gotcriteria=TRUE;
		$Xstext = mysql_escape_string(unstripslashes(trim($stext)));
		$aText = split(" ",$Xstext);
		$aFields[0]="products.pId";
		$aFields[1]=getlangid("pName",1);
		$aFields[2]=getlangid("pDescription",2);
		$aFields[3]=getlangid("pLongDescription",4);
		if($stype=="exact")
			$sSQL .= "AND (products.pId LIKE '%" . $Xstext . "%' OR ".getlangid("pName",1)." LIKE '%" . $Xstext . "%' OR ".getlangid("pDescription",2)." LIKE '%" . $Xstext . "%' OR ".getlangid("pLongDescription",4)." LIKE '%" . $Xstext . "%') ";
		else{
			$sJoin="AND ";
			if($stype=="any") $sJoin="OR ";
			$sSQL .= "AND (";
			for($index=0;$index<=3;$index++){
				$sSQL .= "(";
				$rowcounter=0;
				$arrelms=count($aText);
				foreach($aText as $theopt){
					if(is_array($theopt))$theopt=$theopt[0];
					$sSQL .= $aFields[$index] . " LIKE '%" . $theopt . "%' ";
					if(++$rowcounter < $arrelms) $sSQL .= $sJoin;
				}
				$sSQL .= ") ";
				if($index < 3) $sSQL .= "OR ";
			}
			$sSQL .= ") ";
		}
	}
	if($sortBy==2)
		$sSortBy = " ORDER BY products.pId";
	elseif($sortBy==3)
		$sSortBy = " ORDER BY ".$TWSP;
	elseif($sortBy==4)
		$sSortBy = " ORDER BY ".$TWSP." DESC";
	elseif($sortBy==5)
		$sSortBy = "";
	else
		$sSortBy = " ORDER BY ".getlangid("pName",1);
	$disabledsections = "";
	$addcomma="";
	$result2 = mysql_query("SELECT sectionID FROM sections WHERE sectionDisabled>".$minloglevel) or print(mysql_error());
	while($rs2 = mysql_fetch_assoc($result2)){
		$disabledsections .= $addcomma . $rs2["sectionID"];
		$addcomma=",";
	}
	mysql_free_result($result2);
	if($gotcriteria)
		$tmpSQL = preg_replace("/DISTINCT products.pId/","COUNT(DISTINCT products.pId) AS bar",$sSQL, 1);
	else{
		$sSQL = "SELECT products.pId FROM products WHERE pDisplay<>0";
		$tmpSQL = preg_replace("/products.pId/","COUNT(*) AS bar",$sSQL, 1);
	}
	if($disabledsections!="") $delsectionsql = " AND NOT (products.pSection IN (" . getsectionids($disabledsections, TRUE) . "))"; else $delsectionsql = "";
	$sSQL .= $delsectionsql;
	$tmpSQL .= $delsectionsql;
	$allprods = mysql_query($tmpSQL) or print(mysql_error());
	$iNumOfPages = ceil(mysql_result($allprods,0,"bar")/$adminProdsPerPage);
	mysql_free_result($allprods);
	$sSQL .= $sSortBy . " LIMIT " . ($adminProdsPerPage*($CurPage-1)) . ", $adminProdsPerPage";
	$allprods = mysql_query($sSQL) or print(mysql_error());
	if(mysql_num_rows($allprods) == 0)
		$success=FALSE;
	else{
		$success=TRUE;
		$prodlist = "";
		$addcomma="";
		while($rs = mysql_fetch_array($allprods)){
			$prodlist .= $addcomma . "'" . $rs["pId"] . "'";
			$addcomma=",";
		}
		mysql_free_result($allprods);
		$sSQL = "SELECT pId,".getlangid("pName",1).",pImage,".$WSP."pPrice,pListPrice,pSection,pSell,pInStock,pExemptions,pLargeImage,".getlangid("pDescription",2).",".getlangid("pLongDescription",4)." FROM products WHERE pId IN (" . $prodlist . ")" . $sSortBy;
		$allprods = mysql_query($sSQL) or print(mysql_error());
	}
}
$_SESSION["frompage"] = @$_SERVER['PHP_SELF'] . (trim(@$_SERVER['QUERY_STRING'])!= "" ? "?" : "") . @$_SERVER['QUERY_STRING'];
if($nobox==''){
?>
	  <br />
	  <form method="post" action="search.php">
		  <input type="hidden" name="posted" value="1" />
            <table class="cobtbl" width="<?php print $maintablewidth?>" border="0" bordercolor="#B1B1B1" cellspacing="1" cellpadding="3" bgcolor="#B1B1B1">
			  <tr> 
                <td class="cobhl" align="center" colspan="4" bgcolor="#EBEBEB" height="30">
                  <strong><?php print $xxSrchPr?></strong>
                </td>
              </tr>
			  <tr> 
                <td class="cobhl" width="25%" align="right" bgcolor="#EBEBEB"><?php print $xxSrchFr?>:</td>
				<td class="cobll" width="25%" bgcolor="#FFFFFF"><input type="text" name="stext" size="<?php print atb(20)?>" value="<?php print str_replace("\"","&quot;",unstripslashes($stext))?>" /></td>
				<td class="cobhl" width="25%" align="right" bgcolor="#EBEBEB"><?php print $xxSrchMx?>:</td>
				<td class="cobll" width="25%" bgcolor="#FFFFFF"><input type="text" name="sprice" size="<?php print atb(10)?>" value="<?php print unstripslashes($sprice)?>" /></td>
			  </tr>
			  <tr>
			    <td class="cobhl" width="25%" align="right" bgcolor="#EBEBEB"><?php print $xxSrchTp?>:</td>
				<td class="cobll" width="25%" bgcolor="#FFFFFF"><select name="stype" size="1">
					<option value=""><?php print $xxSrchAl?></option>
					<option value="any" <?php if($stype=="any") print "selected"?>><?php print $xxSrchAn?></option>
					<option value="exact" <?php if($stype=="exact") print "selected"?>><?php print $xxSrchEx?></option>
					</select>
				</td>
				<td class="cobhl" width="25%" align="right" bgcolor="#EBEBEB"><?php print $xxSrchCt?>:</td>
				<td class="cobll" width="25%" bgcolor="#FFFFFF">
				  <select name="scat" size="1">
				  <option value=""><?php print $xxSrchAC?></option>
<?php
		$lasttsid = -1;
		while($row = mysql_fetch_row($allcats)){
			$allcatsa[$numcats++]=$row;
		}
		if($numcats > 0) writemenulevel(0,1);
?>
				  </select>
				</td>
              </tr>
			  <tr>
			    <td class="cobhl" bgcolor="#EBEBEB">&nbsp;</td>
			    <td class="cobll" bgcolor="#FFFFFF" colspan="3"><table width="100%" cellspacing="0" cellpadding="0" border="0">
				    <tr>
					  <td class="cobll" bgcolor="#FFFFFF" width="66%" align="center"><input type="submit" value="<?php print $xxSearch?>" /></td>
					  <td class="cobll" bgcolor="#FFFFFF" width="34%" height="26" align="right" valign="bottom"><img src="images/tablebr.gif" alt="" /></td>
					</tr>
				  </table></td>
			  </tr>
			</table>
		</form>
<?php
}
if(@$_POST["posted"]=="1" || @$_GET["pg"] != ""){
?>
		<table border="0" cellspacing="<?php print $maintablespacing?>" cellpadding="<?php print $maintablepadding?>" width="<?php print $maintablewidth?>" bgcolor="<?php print $maintablebg?>" align="center">
<?php
	if(!$success){
?>
		<tr> 
		  <td align="center"> 
		    <p>&nbsp;</p>
		    <p><strong><?php print $xxSrchNM?></strong></p>
		  </td>
		</tr>
<?php
	}else{
?>
        <tr> 
          <td width="100%">
<?php	if($usesearchbodyformat==2)
			include "./vsadmin/inc/incproductbody2.php";
		else
			include "./vsadmin/inc/incproductbody.php"; ?>
          </td>
        </tr>
<?php
	}
?>
      </table>
<?php
}
?>
