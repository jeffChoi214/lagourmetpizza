<?php
//This code is copyright (c) Internet Business Solutions SL, all rights reserved.
//The contents of this file are protect under law as the intellectual property of Internet
//Business Solutions SL. Any use, reproduction, disclosure or copying of any kind 
//without the express and written permission of Internet Business Solutions SL is forbidden.
//Author: Vince Reid, vince@virtualred.net
if($_SESSION["loggedon"] != "virtualstore") exit;
$success=TRUE;
$sSQL = "";
$alldata="";
$noptions=0;
$sSQL = "SELECT adminShipping,adminStockManage FROM admin WHERE adminID=1";
$result = mysql_query($sSQL) or print(mysql_error());
$rs = mysql_fetch_array($result);
$shipType = (int)$rs["adminShipping"];
$stockManage = (int)$rs["adminStockManage"];
$useStockManagement = ($stockManage != 0);
mysql_free_result($result);
if(@$_POST["posted"]=="1"){
	if(@$_POST["act"]=="delete"){
		$sSQL = "SELECT poID FROM prodoptions WHERE poOptionGroup=" . @$_POST["id"];
		$result = mysql_query($sSQL) or print(mysql_error());
		if(mysql_num_rows($result) > 0){
			$success=FALSE;
			$errmsg = $yyPOErr . "<br>";
			$errmsg .= $yyPOUse;
		}
		mysql_free_result($result);
		if($success){
			$sSQL = "DELETE FROM options WHERE optGroup=" . @$_POST["id"];
			mysql_query($sSQL) or print(mysql_error());
			$sSQL = "DELETE FROM optiongroup WHERE optGrpID=" . @$_POST["id"];
			mysql_query($sSQL) or print(mysql_error());
			print '<meta http-equiv="refresh" content="3; url=adminprodopts.php">';
		}
	}elseif(@$_POST["act"]=="domodify" || @$_POST["act"]=="doaddnew"){
		$sSQL = "";
		$bOption=FALSE;
		$optFlags = 0;
		if(@$_POST["pricepercent"]=="1") $optFlags=1;
		if(@$_POST["weightpercent"]=="1") $optFlags += 2;
		for($rowcounter=0; $rowcounter < maxprodopts; $rowcounter++){
			if(trim(@$_POST["opt" . $rowcounter]) != ""){
				$bOption=TRUE;
				$aOption[$rowcounter][0]=mysql_escape_string(unstripslashes(trim(@$_POST["opt" . $rowcounter])));
				if(is_numeric(trim(@$_POST["pri" . $rowcounter])))
					$aOption[$rowcounter][1]=trim(@$_POST["pri" . $rowcounter]);
				else
					$aOption[$rowcounter][1]=0;
				if(is_numeric(trim(@$_POST["wsp" . $rowcounter])))
					$aOption[$rowcounter][4]=trim(@$_POST["wsp" . $rowcounter]);
				else
					$aOption[$rowcounter][4]=0;
				if($shipType == 3 && (($optFlags & 2) != 2)){
					if(is_numeric(trim(@$_POST["wei" . $rowcounter]))){
						$iOunces=(double)@$_POST["wei" . $rowcounter];
						$aOption[$rowcounter][2]=(int)($iOunces/16) + (($iOunces-((int)($iOunces/16)*16))/100.0);
					}else
						$aOption[$rowcounter][2]="0";
				}else{
					if(is_numeric(trim(@$_POST["wei" . $rowcounter])))
						$aOption[$rowcounter][2]=trim(@$_POST["wei" . $rowcounter]);
					else
						$aOption[$rowcounter][2]=0;
				}
				if(is_numeric(trim(@$_POST["optStock" . $rowcounter])))
					$aOption[$rowcounter][3]=trim(@$_POST["optStock" . $rowcounter]);
				else
					$aOption[$rowcounter][3]=0;
				$noptions++;
			}else
				$aOption[$rowcounter][0]="";
		}
		if((trim(@$_POST["secname"])=="" || ! $bOption) && @$_POST["optType"] != "3"){
			$success=FALSE;
			$errmsg = $yyPOErr . "<br>";
			$errmsg .= $yyPOOne;
		}else{
			if(@$_POST["optType"]=="3"){
				$fieldDims = trim(@$_POST["pri0"]) . ".";
				if((int)@$_POST["fieldheight"] < 10) $fieldDims .= "0";
				$fieldDims .= trim(@$_POST["fieldheight"]);
				if(@$_POST["act"]=="doaddnew"){
					$sSQL = "INSERT INTO optiongroup (optGrpName,optType,optGrpWorkingName,optFlags) VALUES (";
					$sSQL .= "'" . mysql_escape_string(unstripslashes(trim(@$_POST["secname"]))) . "',";
					if(trim(@$_POST["forceselec"])=="ON")
						$sSQL .= "'3',";
					else
						$sSQL .= "'-3',";
					if(trim(@$_POST["workingname"])=="")
						$sSQL .= "'" . mysql_escape_string(unstripslashes(trim(@$_POST["secname"])));
					else
						$sSQL .= "'" . mysql_escape_string(unstripslashes(trim(@$_POST["workingname"])));
					$sSQL .= "'," . $optFlags . ")";
					mysql_query($sSQL) or print(mysql_error());
					$iID  = mysql_insert_id();
					$sSQL = "INSERT INTO options (optGroup,optName,optPriceDiff,optWeightDiff) VALUES (" . $iID . ",'" . mysql_escape_string(unstripslashes(trim(@$_POST["opt0"]))) . "'," . $fieldDims . ",0)";
					mysql_query($sSQL) or print(mysql_error());
				}else{
					$iID = @$_POST["id"];
					$sSQL = "UPDATE optiongroup SET optGrpName='" . mysql_escape_string(unstripslashes(trim(@$_POST["secname"]))) . "'";
					if(trim(@$_POST["forceselec"])=="ON")
						$sSQL .= ",optType='3'";
					else
						$sSQL .= ",optType='-3'";
					if(trim(@$_POST["workingname"])=="")
						$sSQL .= ",optGrpWorkingName='" . mysql_escape_string(unstripslashes(trim(@$_POST["secname"]))) . "',";
					else
						$sSQL .= ",optGrpWorkingName='" . mysql_escape_string(unstripslashes(trim(@$_POST["workingname"]))) . "',";
					$sSQL .= "optFlags=" . $optFlags;
					$sSQL .= " WHERE optGrpID=" . $iID;
					mysql_query($sSQL) or print(mysql_error());
					$sSQL = "UPDATE options SET optName='" . mysql_escape_string(unstripslashes(trim(@$_POST["opt0"]))) . "',optPriceDiff=" . $fieldDims . " WHERE optGroup=" . $iID;
					mysql_query($sSQL) or print(mysql_error());
				}
			}else{
				if(@$_POST["act"]=="doaddnew"){
					$sSQL = "INSERT INTO optiongroup (optGrpName,optType,optGrpWorkingName,optFlags) VALUES (";
					$sSQL .= "'" . mysql_escape_string(unstripslashes(trim(@$_POST["secname"]))) . "',";
					if(trim(@$_POST["forceselec"])=="ON")
						$sSQL .= "'2',";
					else
						$sSQL .= "'-2',";
					if(trim(@$_POST["workingname"])=="")
						$sSQL .= "'" . mysql_escape_string(unstripslashes(trim(@$_POST["secname"])));
					else
						$sSQL .= "'" . mysql_escape_string(unstripslashes(trim(@$_POST["workingname"])));
					$sSQL .= "'," . $optFlags . ")";
					mysql_query($sSQL) or print(mysql_error());
					$iID  = mysql_insert_id();
				}else{
					$iID = @$_POST["id"];
					$sSQL = "UPDATE optiongroup SET optGrpName='" . mysql_escape_string(unstripslashes(trim(@$_POST["secname"]))) . "'";
					if(trim(@$_POST["forceselec"])=="ON")
						$sSQL .= ",optType='2'";
					else
						$sSQL .= ",optType='-2'";
					if(trim(@$_POST["workingname"])=="")
						$sSQL .= ",optGrpWorkingName='" . mysql_escape_string(unstripslashes(trim(@$_POST["secname"]))) . "',";
					else
						$sSQL .= ",optGrpWorkingName='" . mysql_escape_string(unstripslashes(trim(@$_POST["workingname"]))) . "',";
					$sSQL .= "optFlags=" . $optFlags;
					$sSQL .= " WHERE optGrpID=" . $iID;
					mysql_query($sSQL) or print(mysql_error());
				}
				$sSQL = "SELECT optID FROM options WHERE optGroup=" . $iID . " ORDER BY optID";
				$alldata = mysql_query($sSQL) or print(mysql_error());
				for($rowcounter=0; $rowcounter < $noptions; $rowcounter++){
					if(trim($aOption[$rowcounter][0]) != ""){
						if($rs = mysql_fetch_assoc($alldata)){
							$sSQL = "UPDATE options SET optName='" . $aOption[$rowcounter][0] . "',optPriceDiff=" . $aOption[$rowcounter][1] . ",optWeightDiff=" . $aOption[$rowcounter][2] . ",optStock=" . $aOption[$rowcounter][3];
							if(@$wholesaleoptionpricediff==TRUE) $sSQL .= ",optWholesalePriceDiff=" . $aOption[$rowcounter][4];
							$sSQL .= " WHERE optID=" . $rs["optID"];
							mysql_query($sSQL) or print(mysql_error());
						}else{
							if(@$wholesaleoptionpricediff==TRUE)
								$sSQL = "INSERT INTO options (optGroup,optName,optPriceDiff,optWeightDiff,optStock,optWholesalePriceDiff) VALUES (" . $iID . ",'" . $aOption[$rowcounter][0] . "'," . $aOption[$rowcounter][1] . "," . $aOption[$rowcounter][2] . "," . $aOption[$rowcounter][3] .  "," . $aOption[$rowcounter][4] . ")";
							else
								$sSQL = "INSERT INTO options (optGroup,optName,optPriceDiff,optWeightDiff,optStock) VALUES (" . $iID . ",'" . $aOption[$rowcounter][0] . "'," . $aOption[$rowcounter][1] . "," . $aOption[$rowcounter][2] . "," . $aOption[$rowcounter][3] . ")";
							mysql_query($sSQL) or print(mysql_error());
						}
					}
				}
				while($rs = mysql_fetch_assoc($alldata)){
					$sSQL = "DELETE FROM options WHERE optID=" . $rs["optID"];
					mysql_query($sSQL) or print(mysql_error());
				}
			}
		}
		if($success)
			print '<meta http-equiv="refresh" content="3; url=adminprodopts.php">';
	}
}
?>
<script Language="JavaScript">
<!--
function formvalidator(theForm){
  if (theForm.secname.value == "")
  {
    alert("<?php print $yyPlsEntr?> \"<?php print $yyPOName?>\".");
    theForm.secname.focus();
    return (false);
  }
  return (true);
}
function changeunits(){
	var nopercentchar="<?php if($shipType==3) print "&nbsp;Oz"; else print "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" ?>";
	for(index=0;index<<?php print maxprodopts?>;index++){
		wel = document.getElementById("wunitspan" + index);
		pel = document.getElementById("punitspan" + index);
<?php if(@$wholesaleoptionpricediff==TRUE){ ?>
		wspel = document.getElementById("pwspunitspan" + index);
		if(document.forms.mainform.pricepercent.checked){
			wspel.innerHTML='&nbsp;%&nbsp;';
		}else{
			wspel.innerHTML='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		}
<?php } ?>
		if(document.forms.mainform.weightpercent.checked){
			wel.innerHTML='&nbsp;%&nbsp;';
		}else{
			wel.innerHTML=nopercentchar;
		}
		if(document.forms.mainform.pricepercent.checked){
			pel.innerHTML='&nbsp;%&nbsp;';
		}else{
			pel.innerHTML='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		}
	}
}
//-->
</script>
      <table border="0" cellspacing="<?php print $maintablespacing?>" cellpadding="<?php print $maintablepadding?>" width="<?php print $maintablewidth?>" bgcolor="<?php print $maintablebg?>" align="center">
<?php
if(@$_POST["posted"]=="1" && (@$_POST["act"]=="modify" || @$_POST["act"]=="clone")){
	$sSQL = "SELECT optID,optName,optGrpName,optGrpWorkingName,optPriceDiff,optType,optWeightDiff,optFlags,optStock,optWholesalePriceDiff FROM options LEFT JOIN optiongroup ON optiongroup.optGrpID=options.optGroup WHERE optGroup=" . @$_POST["id"] . " ORDER BY optID";
	$result = mysql_query($sSQL) or print(mysql_error());
	$noptions=0;
	while($rs = mysql_fetch_assoc($result)){
		$alldata[$noptions++] = $rs;
	}
?>
        <tr>
		  <form name="mainform" method="POST" action="adminprodopts.php" onSubmit="return formvalidator(this)">
			<td width="100%" align="center">
			<input type="hidden" name="posted" value="1">
			<?php if(@$_POST["act"]=="clone"){ ?>
			<input type="hidden" name="act" value="doaddnew">
			<?php }else{ ?>
			<input type="hidden" name="act" value="domodify">
			<input type="hidden" name="id" value="<?php print @$_POST["id"]?>">
			<?php } ?>
			<input type="hidden" name="optType" value="<?php print abs($alldata[0]["optType"])?>">
            <table width="100%" border="0" cellspacing="0" cellpadding="3" bgcolor="">
<?php	if(abs((int)$alldata[0]["optType"])==3){ ?>
			  <tr> 
                <td width="100%" colspan="3" align="center"><b><?php print $yyPOAdm?></b><br>&nbsp;</td>
			  </tr>
			  <tr>
				<td width="50%" align="center"><p><b><?php print $yyPOName?></b><br>
				  <input type="text" name="secname" size="30" value="<?php print str_replace('"',"&quot;",$alldata[0]["optGrpName"])?>"></p>
				  <p><b><?php print $yyWrkNam?></b><br>
				  <input type="text" name="workingname" size="30" value="<?php print str_replace('"',"&quot;",$alldata[0]["optGrpWorkingName"])?>"></p>
                </td>
				<td width="30%" align="center"><p><b><?php print $yyDefTxt?></b><br>
				<input type="text" name="opt0" size="25" value="<?php print str_replace('"',"&quot;",$alldata[0]["optName"])?>"></p>
				<p>&nbsp;<br><input type="checkbox" name="forceselec" value="ON" <?php if($alldata[0]["optType"]>0) print "checked"?>> <b><?php print $yyForSel?></b></p>
                </td>
				<td width="20%" align="center"><p><b><?php print $yyFldWdt?></b><br>
				<select name="pri0" size="1">
				<?php
					for($rowcounter=1; $rowcounter <= 35; $rowcounter++){
						print "<option value='" . $rowcounter . "'";
						if($rowcounter==(int)$alldata[0]["optPriceDiff"]) print " selected";
						print ">&nbsp; " . $rowcounter . " </option>\n";
					}
				?>
				</select></p>
				<p><b><?php print $yyFldHgt?></b><br>
				<select name="fieldheight" size="1">
				<?php
					$fieldHeight = round(((double)($alldata[0]["optPriceDiff"])-floor($alldata[0]["optPriceDiff"]))*100.0);
					for($rowcounter=1; $rowcounter <= 15; $rowcounter++){
						print "<option value='" . $rowcounter . "'";
						if($rowcounter==$fieldHeight) print " selected";
						print ">&nbsp; " . $rowcounter . " </option>\n";
					}
				?>
				</select></p>
				</td>
			  </tr>
			  <tr>
				<td colspan="3">
				  <ul>
				  <li><font size="1"><?php print $yyPOEx1?></li>
				  <li><font size="1"><?php print $yyPOEx2?></li>
				  <li><font size="1"><?php print $yyPOEx3?></li>
				  </ul>
                </td>
			  </tr>
<?php	}else{ ?>
			  <tr>
				<td width="30%" align="center"><p><b><?php print $yyPOName?></b><br>
				  <input type="text" name="secname" size="30" value="<?php print str_replace('"',"&quot;",$alldata[0]["optGrpName"])?>"></p>
				  <p><b><?php print $yyWrkNam?></b><br>
				  <input type="text" name="workingname" size="30" value="<?php print str_replace('"',"&quot;",$alldata[0]["optGrpWorkingName"])?>"></p>
				  <p><input type="checkbox" name="forceselec" value="ON" <?php if($alldata[0]["optType"]>0) print "checked"?>> <b><?php print $yyForSel?></b></p>
                </td>
				<td colspan="2">
				  <p align="center"><b><?php print $yyPOAdm?></b></p>
				  <ul>
				  <li><font size="1"><?php print $yyPOEx1?></font></li>
				  <li><font size="1"><?php print $yyPOEx4?></font></li>
				  <li><font size="1"><?php print $yyPOEx5?></font></li>
				  <?php if($useStockManagement){ ?>
				  <li><font size="1"><?php print $yyPOEx6?></font></li>
				  <?php } ?>
				  <ul>
                </td>
			  </tr>
			</table>
			<table width="500" border="0" cellspacing="0" cellpadding="3" bgcolor="">
			  <tr>
				<td align="center"><b><?php print $yyPOOpts?></b></td>
				<td width="5%" align="center">&nbsp;</td>
				<td align="center" nowrap><b><?php print $yyPOPrDf?>&nbsp;(%<input class="noborder" type="checkbox" name="pricepercent" value="1" onClick="javascript:changeunits();" <?php if(($alldata[0]["optFlags"] & 1) == 1) print "checked"?>>)</b></td>
				<td width="5%" align="center">&nbsp;</td>
				<?php if(@$wholesaleoptionpricediff==TRUE){ ?>
				<td align="center" nowrap><b><?php print $yyWholPr?></b></td>
				<td width="5%" align="center">&nbsp;</td>
				<?php } ?>
				<td align="center" nowrap><b><?php print $yyPOWtDf?>&nbsp;(%<input class="noborder" type="checkbox" name="weightpercent" value="1" onClick="javascript:changeunits();" <?php if(($alldata[0]["optFlags"] & 2) == 2) print "checked"?>>)</b></td>
				<?php if($useStockManagement){ ?>
				<td width="5%" align="center">&nbsp;</td>
				<td align="center" nowrap><b><?php print $yyStkLvl?></b></td>
				<?php } ?>
			  </tr>
<?php		if(($alldata[0]["optFlags"] & 1) == 1) $pdUnits="&nbsp;%&nbsp;"; else $pdUnits="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			if(($alldata[0]["optFlags"] & 2) == 2) $wdUnits="&nbsp;%&nbsp;"; else $wdUnits="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			for($rowcounter=0; $rowcounter < maxprodopts; $rowcounter++){ ?>
			  <tr>
				<td align="center"><?php
					print "<input type=\"text\" name=\"opt" . $rowcounter . "\" size=\"20\" value=\"";
					if($rowcounter < $noptions) print str_replace('"', "&quot;",$alldata[$rowcounter]["optName"]);
					print "\"><br>\n";
				?></td>
				<td align="center"><b>&raquo;</b></td>
				<td align="center"><?php
					print "&nbsp;&nbsp;&nbsp;&nbsp;<input type='text' name='pri" . $rowcounter . "' size='5' value='";
					if($rowcounter < $noptions) print (double)$alldata[$rowcounter]["optPriceDiff"];
					print "'><span name='punitspan" . $rowcounter . "' id='punitspan" . $rowcounter . "'>" . $pdUnits . "</span><br>\n";
				?></td>
				<td align="center"><b>&raquo;</b></td>
				<?php if(@$wholesaleoptionpricediff==TRUE){?>
				<td align="center"><?php
					print "&nbsp;&nbsp;&nbsp;&nbsp;<input type='text' name='wsp" . $rowcounter . "' size='5' value='";
					if($rowcounter < $noptions) print (double)$alldata[$rowcounter]["optWholesalePriceDiff"];
					print "'><span name='pwspunitspan" . $rowcounter . "' id='pwspunitspan" . $rowcounter . "'>" . $pdUnits . "</span><br>\n";
				?></td>
				<td align="center"><b>&raquo;</b></td>
				<?php } ?>
				<td align="center" nowrap><?php
					print "&nbsp;&nbsp;&nbsp;&nbsp;<input type='text' name='wei" . $rowcounter . "' size='5' value='";
					if($shipType==3){
						$wdUnits=" Oz";
						if($rowcounter < $noptions){
							$iPounds=floor($alldata[$rowcounter]["optWeightDiff"]);
							print $iPounds*16+round(((double)$alldata[$rowcounter]["optWeightDiff"]-(double)$iPounds)*100,2);
						}
					}else{
						if($rowcounter < $noptions) print $alldata[$rowcounter]["optWeightDiff"];
					}
					print "'><span name='wunitspan" . $rowcounter . "' id='wunitspan" . $rowcounter . "'>" . $wdUnits . "</span><br>\n";
				?></td>
				<?php	if($useStockManagement){ ?>
				<td align="center"><b>&raquo;</b></td>
				<td align="center"><input type="text" name="optStock<?php print $rowcounter?>" size="4" value="<?php if($rowcounter < $noptions) print $alldata[$rowcounter]["optStock"]?>"></td>
				<?php	}else{
							if($rowcounter < $noptions){ ?>
								<input type="hidden" name="optStock<?php print $rowcounter?>" value="<?php print $alldata[$rowcounter]["optStock"]?>">
				<?php		}
						} ?>
			  </tr>
<?php		} ?>
			</table>
			<table width="100%" border="0" cellspacing="0" cellpadding="3" bgcolor="">
<?php	} ?>
			  <tr>
                <td width="100%" colspan="3" align="center"><br><input type="submit" value="<?php print $yySubmit?>"><br>&nbsp;</td>
			  </tr>
			  <tr> 
                <td width="100%" colspan="3" align="center"><br>
                          <a href="admin.php"><b><?php print $yyAdmHom?></b></a><br>
                          &nbsp;</td>
			  </tr>
            </table></td>
		  </form>
        </tr>
<?php
}elseif(@$_POST["posted"]=="1" && @$_POST["act"]=="addnew"){ ?>
        <tr>
		  <form name="mainform" method="POST" action="adminprodopts.php" onSubmit="return formvalidator(this)">
			<td width="100%" align="center">
			<input type="hidden" name="posted" value="1">
			<input type="hidden" name="act" value="doaddnew">
			<input type="hidden" name="optType" value="<?php print @$_POST["optType"]?>">
            <table width="100%" border="0" cellspacing="0" cellpadding="3" bgcolor="">
<?php	if(@$_POST["optType"]=="3"){ ?>
			  <tr>
                <td width="100%" colspan="3" align="center"><b><?php print $yyPONewT?></b><br>&nbsp;</td>
			  </tr>
			  <tr>
				<td width="50%" align="center"><p><b><?php print $yyPOName?></b><br>
				  <input type="text" name="secname" size="30" value=""></p>
				  <p><b><?php print $yyWrkNam?></b><br>
				  <input type="text" name="workingname" size="30" value=""></p>
                </td>
				<td width="30%" align="center"><p><b><?php print $yyDefTxt?></b><br>
				<input type="text" name="opt0" size="25"></p>
				<p>&nbsp;<br><input type="checkbox" name="forceselec" value="ON" checked> <b><?php print $yyForSel?></b></p>
                </td>
				<td width="20%" align="center"><p><b><?php print $yyFldWdt?></b><br>
				<select name="pri0" size="1">
				<?php
					for($rowcounter=1; $rowcounter <= 35; $rowcounter++){
						print "<option value='" . $rowcounter . "'";
						if($rowcounter==15) print " selected";
						print ">&nbsp; " . $rowcounter . " </option>\n";
					}
				?>
				</select></p>
				<p><b><?php print $yyFldHgt?></b><br>
				<select name="fieldheight" size="1">
				<?php
					for($rowcounter=1; $rowcounter <= 15; $rowcounter++){
						print "<option value='" . $rowcounter . "'>&nbsp; " . $rowcounter . " </option>\n";
					}
				?>
				</select></p>
				</td>
			  </tr>
			  <tr>
				<td colspan="3">
				  <ul>
				  <li><font size="1"><?php print $yyPOEx1?></li>
				  <li><font size="1"><?php print $yyPOEx2?></li>
				  <li><font size="1"><?php print $yyPOEx3?></li>
				  </ul>
                </td>
			  </tr>
<?php	}else{ ?>
			  <tr>
				<td width="30%" align="center"><p><b><?php print $yyPOName?></b><br>
				  <input type="text" name="secname" size="30" value=""></p>
				  <p><b><?php print $yyWrkNam?></b><br>
				  <input type="text" name="workingname" size="30" value=""></p>
				  <p><input type="checkbox" name="forceselec" value="ON" checked> <b><?php print $yyForSel?></b></p>
                </td>
				<td colspan="2">
				  <p align="center"><b><?php print $yyPOAdm?></b></p>
				  <ul>
				  <li><font size="1"><?php print $yyPOEx1?></font></li>
				  <li><font size="1"><?php print $yyPOEx4?></font></li>
				  <li><font size="1"><?php print $yyPOEx5?></font></li>
				  <?php if($useStockManagement){ ?>
				  <li><font size="1"><?php print $yyPOEx6?></font></li>
				  <?php } ?>
				  <ul>
                </td>
			  </tr>
			</table>
			<table width="500" border="0" cellspacing="0" cellpadding="3" bgcolor="">
			  <tr>
				<td align="center"><b><?php print $yyPOOpts?></b></td>
				<td width="5%" align="center">&nbsp;</td>
				<td align="center"><b><?php print $yyPOPrDf?>&nbsp;(%<input class="noborder" type="checkbox" name="pricepercent" value="1" onClick="javascript:changeunits();">)</b></td>
				<td width="5%" align="center">&nbsp;</td>
				<?php if(@$wholesaleoptionpricediff==TRUE){ ?>
				<td align="center" nowrap><b><?php print $yyWholPr?></b></td>
				<td width="5%" align="center">&nbsp;</td>
				<?php } ?>
				<td align="center"><b><?php print $yyPOWtDf?>&nbsp;(%<input class="noborder" type="checkbox" name="weightpercent" value="1" onClick="javascript:changeunits();">)</b></td>
				<?php if($useStockManagement){ ?>
				<td width="5%" align="center">&nbsp;</td>
				<td align="center" nowrap><b><?php print $yyStkLvl?></b></td>
				<?php } ?>
			  </tr>
<?php		$pdUnits="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			$wdUnits="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			for($rowcounter=0; $rowcounter < maxprodopts; $rowcounter++){ ?>
			  <tr>
				<td align="center"><?php
					print "<input type=\"text\" name=\"opt" . $rowcounter . "\" size=\"20\"><br>\n";
				?>
                </td>
				<td align="center"><b>&raquo;</b></td>
				<td align="center"><?php
					print "&nbsp;&nbsp;&nbsp;&nbsp;<input type='text' name='pri" . $rowcounter . "' size='5'><span name='punitspan" . $rowcounter . "' id='punitspan" . $rowcounter . "'>" . $pdUnits . "</span><br>\n";
				?>
				</td>
				<?php if(@$wholesaleoptionpricediff==TRUE){ ?>
				<td align="center"><b>&raquo;</b></td>
				<td align="center"><?php
					print "&nbsp;&nbsp;&nbsp;&nbsp;<input type='text' name='wsp" . $rowcounter . "' size='5'><span name='pwspunitspan" . $rowcounter . "' id='pwspunitspan" . $rowcounter . "'>" . $pdUnits . "</span><br>\n";
				?>
				</td>
				<?php } ?>
				<td align="center"><b>&raquo;</b></td>
				<td align="center"><?php
					if($shipType==3) $wdUnits=" Oz";
					print "&nbsp;&nbsp;&nbsp;&nbsp;<input type='text' name='wei" . $rowcounter . "' size='5'><span name='wunitspan" . $rowcounter . "' id='wunitspan" . $rowcounter . "'>" . $wdUnits . "</span><br>\n";
				?></td>
				<?php	if($useStockManagement){ ?>
				<td align="center"><b>&raquo;</b></td>
				<td align="center"><input type="text" name="optStock<?php print $rowcounter?>" size="4" value=""></td>
				<?php	} ?>
			  </tr>
<?php		} ?>
			</table>
			<table width="100%" border="0" cellspacing="0" cellpadding="3" bgcolor="">
<?php	} ?>
			  <tr>
                <td width="100%" colspan="3" align="center"><br><input type="submit" value="<?php print $yySubmit?>"><br>&nbsp;</td>
			  </tr>
			  <tr>
                <td width="100%" colspan="3" align="center"><br>
                          <a href="admin.php"><b><?php print $yyAdmHom?></b></a><br>
                          &nbsp;</td>
			  </tr>
            </table></td>
		  </form>
        </tr>
<?php
}elseif(@$_POST["posted"]=="1" && $success){ ?>
        <tr>
          <td width="100%">
			<table width="100%" border="0" cellspacing="0" cellpadding="3" bgcolor="">
			  <tr> 
                <td width="100%" colspan="2" align="center"><br><b><?php print $yyUpdSuc?></b><br><br><?php print $yyNowFrd?><br><br>
                        <?php print $yyNoAuto?> <A href="adminprodopts.php"><b><?php print $yyClkHer?></b></a>.<br>
                        <br>
				<img src="../images/clearpixel.gif" width="300" height="3">
                </td>
			  </tr>
			</table></td>
        </tr>
<?php
}elseif(@$_POST["posted"]=="1"){ ?>
        <tr>
          <td width="100%">
			<table width="100%" border="0" cellspacing="0" cellpadding="3" bgcolor="">
			  <tr> 
                <td width="100%" colspan="2" align="center"><br><font color="#FF0000"><b><?php print $yyOpFai?></b></font><br><br><?php print $errmsg?><br><br>
				<a href="javascript:history.go(-1)"><b><?php print $yyClkBac?></b></a></td>
			  </tr>
			</table></td>
        </tr>
<?php
}else{
?>
<script language="JavaScript">
<!--
function modrec(id) {
	document.mainform.id.value = id;
	document.mainform.act.value = "modify";
	document.mainform.submit();
}
function clone(id) {
	document.mainform.id.value = id;
	document.mainform.act.value = "clone";
	document.mainform.submit();
}
function newtextrec(id) {
	document.mainform.id.value = id;
	document.mainform.act.value = "addnew";
	document.mainform.optType.value = "3";
	document.mainform.submit();
}
function newrec(id) {
	document.mainform.id.value = id;
	document.mainform.act.value = "addnew";
	document.mainform.optType.value = "2";
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
        <tr>
		<form name="mainform" method="POST" action="adminprodopts.php">
		  <td width="100%">
			<input type="hidden" name="posted" value="1">
			<input type="hidden" name="act" value="xxxxx">
			<input type="hidden" name="id" value="xxxxx">
			<input type="hidden" name="optType" value="xxxxx">
            <table width="100%" border="0" cellspacing="0" cellpadding="1" bgcolor="">
			  <tr> 
                <td width="100%" colspan="5" align="center"><b><?php print $yyPOAdm?></b><br>&nbsp;</td>
			  </tr>
			  <tr>
				<td width="32%"><b><?php print $yyPOName?></b></td>
				<td width="50%"><b><?php print $yyWrkNam?></b></td>
				<td width="6%" align="center"><b><?php print $yyClone?></b></td>
				<td width="6%" align="center"><b><?php print $yyModify?></b></td>
				<td width="6%" align="center"><b><?php print $yyDelete?></b></td>
			  </tr>
<?php
	$sSQL = "SELECT optGrpID,optGrpName,optGrpWorkingName FROM optiongroup ORDER BY optGrpName,optGrpWorkingName";
	$result = mysql_query($sSQL) or print(mysql_error());
	if(mysql_num_rows($result) > 0){
		$bgcolor="";
		while($rs = mysql_fetch_assoc($result)){
			if($bgcolor=="#E7EAEF") $bgcolor="#FFFFFF"; else $bgcolor="#E7EAEF"; ?>
			  <tr bgcolor="<?php print $bgcolor?>">
				<td><?php print $rs["optGrpName"]?></td>
				<td><?php print $rs["optGrpWorkingName"]?></td>
				<td align="center"><input type=button value="<?php print $yyClone?>" onClick="clone('<?php print $rs["optGrpID"]?>')"></td>
				<td align="center"><input type=button value="<?php print $yyModify?>" onClick="modrec('<?php print $rs["optGrpID"]?>')"></td>
				<td align="center"><input type=button value="<?php print $yyDelete?>" onClick="delrec('<?php print $rs["optGrpID"]?>')"></td>
			  </tr>
<?php	}
	}else{
?>
			  <tr>
                <td width="100%" colspan="5" align="center"><br><?php print $yyPONon?><br>&nbsp;</td>
			  </tr>
<?php
	}
?>
			  <tr>
                <td width="100%" colspan="5" align="center"><br><b><?php print $yyPOClk?> </b>&nbsp;&nbsp;<input type="button" value="<?php print $yyPONew?>" onClick="newrec()">&nbsp;<b><?php print $yyOr?></b>&nbsp;<input type="button" value="<?php print $yyPONewT?>" onClick="newtextrec()"><br>&nbsp;</td>
			  </tr>
<?php
	if($useStockManagement){
		// $sSQL = "SELECT DISTINCT optGrpID,optGrpName,optGrpWorkingName FROM optiongroup INNER JOIN (options INNER JOIN (prodoptions INNER JOIN products ON prodoptions.poProdID=products.pID) ON options.optGroup=prodoptions.poOptionGroup) ON optiongroup.optGrpID=options.optGroup WHERE options.optStock<=0 AND products.pSell>1 AND (optType=2 OR optType=-2) ORDER BY optGrpName,optGrpWorkingName";
		$sSQL = "SELECT DISTINCT optGrpID,optGrpName,optGrpWorkingName FROM optiongroup INNER JOIN options ON optiongroup.optGrpID=options.optGroup INNER JOIN prodoptions ON options.optGroup=prodoptions.poOptionGroup INNER JOIN products ON prodoptions.poProdID=products.pID WHERE options.optStock<=0 AND products.pSell>1 AND (optType=2 OR optType=-2) ORDER BY optGrpName,optGrpWorkingName";
		$result = mysql_query($sSQL) or print(mysql_error());
		if(mysql_num_rows($result)>0) print '<tr><td colspan="5" align="center"><strong>The following options contain at least 1 item that is out of stock</strong></td></tr>';
		while($rs = mysql_fetch_array($result)){
			if($bgcolor=="#E7EAEF") $bgcolor="#FFFFFF"; else $bgcolor="#E7EAEF"; ?>
			  <tr bgcolor="<?php print $bgcolor?>">
				<td><?php print $rs["optGrpName"]?></td>
				<td><?php print $rs["optGrpWorkingName"]?></td>
				<td align="center">&nbsp;</td>
				<td align="center"><input type=button value="<?php print $yyModify?>" onClick="modrec('<?php print $rs["optGrpID"]?>')"></td>
				<td align="center"><input type=button value="<?php print $yyDelete?>" onClick="delrec('<?php print $rs["optGrpID"]?>')"></td>
			  </tr><?
		}
		mysql_free_result($result);
	} ?>
			  <tr>
                <td width="100%" colspan="5" align="center"><br>
                          <a href="admin.php"><b><?php print $yyAdmHom?></b></a><br>
				<img src="../images/clearpixel.gif" width="300" height="3"></td>
			  </tr>
            </table></td>
		  </form>
        </tr>
<?php
}
?>
      </table>