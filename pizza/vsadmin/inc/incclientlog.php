<?php
//This code is copyright (c) Internet Business Solutions SL, all rights reserved.
//The contents of this file are protect under law as the intellectual property of Internet
//Business Solutions SL. Any use, reproduction, disclosure or copying of any kind 
//without the express and written permission of Internet Business Solutions SL is forbidden.
//Author: Vince Reid, vince@virtualred.net
if(@$storesessionvalue=="") $storesessionvalue="virtualstore".time();
if($_SESSION["loggedon"] != $storesessionvalue) exit;
$success=TRUE;
$sSQL = "";
$alldata="";
if(@$maxloginlevels=="") $maxloginlevels=5;
if(@$_POST["posted"]=="1"){
	if(@$_POST["act"]=="delete"){
		$sSQL = "DELETE FROM clientlogin WHERE clientUser='" . @$_POST["id"] . "'";
		mysql_query($sSQL) or print(mysql_error());
		print '<meta http-equiv="refresh" content="3; url=adminclientlog.php">';
	}elseif(@$_POST["act"]=="domodify"){
		$sSQL = "UPDATE clientlogin SET clientUser='" . mysql_escape_string(@$_POST["clientUser"]) . "'";
		$sSQL .= ",clientPW='" . mysql_escape_string(@$_POST["clientPW"]) . "'";
		$sSQL .= ",clientLoginLevel=" . @$_POST["clientLoginLevel"];
		$cpd = trim(@$_POST["clientPercentDiscount"]);
		$sSQL .= "," . "clientPercentDiscount=" . (is_numeric($cpd) ? $cpd : 0);
		$clientActions=0;
		if(is_array(@$_POST["clientActions"])){
			foreach(@$_POST["clientActions"] as $objValue){
				if(is_array($objValue)) $objValue = $objValue[0];
				$clientActions += $objValue;
			}
		}
		$sSQL .= ",clientActions=" . $clientActions;
		$sSQL .= " WHERE clientUser='" . @$_POST["id"] . "'";
		mysql_query($sSQL) or print(mysql_error());
		print '<meta http-equiv="refresh" content="3; url=adminclientlog.php">';
	}elseif(@$_POST["act"]=="doaddnew"){
		$sSQL = "SELECT clientUser FROM clientlogin WHERE clientUser='" . mysql_escape_string(@$_POST["clientUser"]) . "'";
		$result = mysql_query($sSQL) or print(mysql_error());
		if($rs = mysql_fetch_array($result)){
			$success=FALSE;
			$errmsg="The login &quot;" . @$_POST["clientUser"] . "&quot; is already in use. Please choose another.";
		}
		mysql_free_result($result);
		if($success){
			$sSQL = "INSERT INTO clientlogin (clientUser,clientPW,clientLoginLevel,clientPercentDiscount,clientActions) VALUES (";
			$sSQL .= "'" . mysql_escape_string(@$_POST["clientUser"]) . "'";
			$sSQL .= ",'" . mysql_escape_string(@$_POST["clientPW"]) . "'";
			$sSQL .= "," . @$_POST["clientLoginLevel"];
			$cpd = trim(@$_POST["clientPercentDiscount"]);
			$sSQL .= "," . (is_numeric($cpd) ? $cpd : 0);
			$clientActions=0;
			if(is_array(@$_POST["clientActions"])){
				foreach(@$_POST["clientActions"] as $objValue){
					if(is_array($objValue)) $objValue = $objValue[0];
					$clientActions += $objValue;
				}
			}
			$sSQL .= "," . $clientActions . ")";
			mysql_query($sSQL) or print(mysql_error());
			print '<meta http-equiv="refresh" content="3; url=adminclientlog.php">';
		}
	}
}
?>
<script language="javascript" type="text/javascript">
<!--
function formvalidator(theForm){
if (theForm.clientUser.value == "")
{
alert("<?php print $yyPlsEntr?> \"<?php print $yyLiName?>\".");
theForm.clientUser.focus();
return (false);
}
var checkOK = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_@.-";
var checkStr = theForm.clientUser.value;
var allValid = true;
for (i = 0;  i < checkStr.length;  i++)
{
    ch = checkStr.charAt(i);
    for (j = 0;  j < checkOK.length;  j++)
      if (ch == checkOK.charAt(j))
        break;
    if (j == checkOK.length)
    {
      allValid = false;
      break;
    }
}
if (!allValid)
{
    alert('<?php print $yyAlpha3?> "<?php print $yyLiName?>" field.');
    theForm.clientUser.focus();
    return (false);
}
var checkStr = theForm.clientPW.value;
var allValid = true;
for (i = 0;  i < checkStr.length;  i++)
{
    ch = checkStr.charAt(i);
    for (j = 0;  j < checkOK.length;  j++)
      if (ch == checkOK.charAt(j))
        break;
    if (j == checkOK.length)
    {
      allValid = false;
      break;
    }
}
if (!allValid)
{
    alert("<?php print $yyOnlyAl?> \"<?php print $yyPass?>\".");
    theForm.clientPW.focus();
    return (false);
}
if(document.mainform.elements['clientActions[]'].options[3].selected && document.mainform.elements['clientActions[]'].options[4].selected){
    alert("<?php print $yyWSDsc?>");
    theForm.elements['clientActions[]'].focus();
    return (false);
}
document.mainform.clientPercentDiscount.disabled=false;
return (true);
}
function checkperdisc(){
	document.mainform.clientPercentDiscount.disabled=!document.mainform.elements['clientActions[]'].options[4].selected;
}
//-->
</script>
      <table border="0" cellspacing="0" cellpadding="0" width="100%" bgcolor="" align="center">
<?php	if(@$_POST["posted"]=="1" && @$_POST["act"]=="modify"){
			$sSQL = "SELECT clientUser,clientPW,clientLoginLevel,clientActions,clientPercentDiscount FROM clientlogin WHERE clientUser='" . @$_POST["id"] . "'";
			$result = mysql_query($sSQL) or print(mysql_error());
			$rs = mysql_fetch_array($result);
?>
        <tr>
		  <form name="mainform" method="post" action="adminclientlog.php" onsubmit="return formvalidator(this)">
			<td width="100%" align="center">
			<input type="hidden" name="posted" value="1" />
			<input type="hidden" name="act" value="domodify" />
			<input type="hidden" name="id" value="<?php print @$_POST["id"]?>" />
            <table width="100%" border="0" cellspacing="2" cellpadding="2" bgcolor="">
			  <tr> 
                <td width="100%" colspan="4" align="center"><strong><?php print $yyLiAdm?></strong><br />&nbsp;</td>
			  </tr>
			  <tr>
				<td align="right"><strong><?php print $yyLiName?></strong></td>
				<td><input type="text" name="clientUser" size="20" value="<?php print str_replace('"','&quot;',$rs["clientUser"])?>" /></td>
				<td align="right" rowspan="4" valign="top"><strong><?php print $yyActns?></strong></td>
				<td rowspan="4" valign="top"><select name="clientActions[]" size="6" onChange="checkperdisc()" multiple>
				<option value="1"<?php if(($rs["clientActions"] & 1) == 1) print " selected" ?>><?php print $yyExStat?></option>
				<option value="2"<?php if(($rs["clientActions"] & 2) == 2) print " selected" ?>><?php print $yyExCoun?></option>
				<option value="4"<?php if(($rs["clientActions"] & 4) == 4) print " selected" ?>><?php print $yyExShip?></option>
				<option value="8"<?php if(($rs["clientActions"] & 8) == 8) print " selected" ?>><?php print $yyWholPr?></option>
				<option value="16"<?php if(($rs["clientActions"] & 16) == 16) print " selected" ?>><?php print $yyPerDis?></option>
				</select></td>
			  </tr>
			  <tr>
				<td align="right"><p><strong><?php print $yyPass?></strong></td>
				<td><input type="text" name="clientPW" size="20" value="<?php print str_replace('"','&quot;',$rs["clientPW"])?>" /></td>
			  </tr>
			  <tr>
				<td align="right"><strong><?php print $yyLiLev?></strong></td>
				<td><select name="clientLoginLevel" size="1">
				<?php	for($rowcounter=0; $rowcounter<=$maxloginlevels; $rowcounter++){
							print '<option value="' . $rowcounter . '"';
							if($rowcounter==(int)$rs["clientLoginLevel"]) print " selected";
							print '>&nbsp; ' . $rowcounter . " </option>\r\n";
						} ?>
				</select></td>
			  </tr>
			  <tr>
				<td align="right"><p><strong><?php print $yyPerDis?></strong></td>
				<td><input type="text" name="clientPercentDiscount" size="10" value="<?php print $rs["clientPercentDiscount"]?>" /></td>
			  </tr>
			  <tr>
                <td width="100%" colspan="4" align="center"><br /><input type="submit" value="<?php print $yySubmit?>" />&nbsp;<input type="reset" value="<?php print $yyReset?>" /><br />&nbsp;</td>
			  </tr>
			  <tr> 
                <td width="100%" colspan="4" align="center"><br />
                          <a href="admin.php"><strong><?php print $yyAdmHom?></strong></a><br />
                          &nbsp;</td>
			  </tr>
            </table></td>
		  </form>
        </tr>
<script language="javascript" type="text/javascript">
<!--
checkperdisc();
//-->
</script>
<?php	}elseif(@$_POST["posted"]=="1" && @$_POST["act"]=="addnew"){ ?>
        <tr>
		  <form name="mainform" method="post" action="adminclientlog.php" onsubmit="return formvalidator(this)">
			<td width="100%" align="center">
			<input type="hidden" name="posted" value="1" />
			<input type="hidden" name="act" value="doaddnew" />
            <table width="100%" border="0" cellspacing="2" cellpadding="2" bgcolor="">
			  <tr> 
                <td width="100%" colspan="4" align="center"><strong><?php print $yyLiAdm?></strong><br />&nbsp;</td>
			  </tr>
			  <tr>
				<td align="right"><strong><?php print $yyLiName?></strong></td>
				<td><input type="text" name="clientUser" size="20" value="" /></td>
				<td align="right" rowspan="4" valign="top"><strong><?php print $yyActns?></strong></td>
				<td rowspan="4" valign="top"><select name="clientActions[]" size="6" onChange="checkperdisc()" multiple>
				<option value="1"><?php print $yyExStat?></option>
				<option value="2"><?php print $yyExCoun?></option>
				<option value="4"><?php print $yyExShip?></option>
				<option value="8"><?php print $yyWholPr?></option>
				<option value="16"><?php print $yyPerDis?></option>
				</select></td>
			  </tr>
			  <tr>
				<td align="right"><p><strong><?php print $yyPass?></strong></td>
				<td><input type="text" name="clientPW" size="20" value="" /></td>
			  </tr>
			  <tr>
				<td align="right"><strong><?php print $yyLiLev?></strong></td>
				<td><select name="clientLoginLevel" size="1">
				<?php	for($rowcounter=0; $rowcounter<=$maxloginlevels; $rowcounter++){
							print '<option value="' . $rowcounter . '"';
							print '>&nbsp; ' . $rowcounter . " </option>\r\n";
						} ?>
				</select></td>
			  </tr>
			  <tr>
				<td align="right"><strong><?php print $yyPerDis?></strong></td>
				<td><input type="text" name="clientPercentDiscount" size="10" value="0" /></td>
			  </tr>
			  <tr>
                <td width="100%" colspan="4" align="center"><br /><input type="submit" value="<?php print $yySubmit?>" />&nbsp;<input type="reset" value="<?php print $yyReset?>" /><br />&nbsp;</td>
			  </tr>
			  <tr> 
                <td width="100%" colspan="4" align="center"><br />
                          <a href="admin.php"><strong><?php print $yyAdmHom?></strong></a><br />
                          &nbsp;</td>
			  </tr>
            </table></td>
		  </form>
        </tr>
<script language="javascript" type="text/javascript">
<!--
checkperdisc();
//-->
</script>
<?php	}elseif(@$_POST["posted"]=="1" && $success){ ?>
        <tr>
          <td width="100%">
			<table width="100%" border="0" cellspacing="0" cellpadding="2" bgcolor="">
			  <tr> 
                <td width="100%" colspan="2" align="center"><br /><strong><?php print $yyUpdSuc?></strong><br /><br /><?php print $yyNowFrd?><br /><br />
                        <?php print $yyNoAuto?> <A href="adminclientlog.php"><strong><?php print $yyClkHer?></strong></a>.<br />
                        <br />
				<img src="../images/clearpixel.gif" width="300" height="3" alt="" />
                </td>
			  </tr>
			</table></td>
        </tr>
<?php	}elseif(@$_POST["posted"]=="1"){ ?>
        <tr>
          <td width="100%">
			<table width="100%" border="0" cellspacing="0" cellpadding="2" bgcolor="">
			  <tr> 
                <td width="100%" colspan="2" align="center"><br /><font color="#FF0000"><strong><?php print $yyOpFai?></strong></font><br /><br /><?php print $errmsg?><br /><br />
				<a href="javascript:history.go(-1)"><strong><?php print $yyClkBac?></strong></a></td>
			  </tr>
			</table></td>
        </tr>
<?php	}else{
?>
<script language="javascript" type="text/javascript">
<!--
function modrec(id) {
	document.mainform.id.value = id;
	document.mainform.act.value = "modify";
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
		<form name="mainform" method="post" action="adminclientlog.php">
		  <td width="100%">
			<input type="hidden" name="posted" value="1" />
			<input type="hidden" name="act" value="xxxxx" />
			<input type="hidden" name="id" value="xxxxx" />
			<input type="hidden" name="optType" value="xxxxx" />
            <table width="100%" border="0" cellspacing="0" cellpadding="1" bgcolor="">
			  <tr> 
                <td width="100%" colspan="6" align="center"><strong><?php print $yyLiAdm?></strong><br />&nbsp;</td>
			  </tr>
			  <tr>
				<td><strong><?php print $yyLiName?></strong></td>
				<td><strong><?php print $yyPass?></strong></td>
				<td align="center"><strong><?php print $yyLiLev?></strong></td>
				<td><strong><?php print $yyActns?></strong></td>
				<td width="5%" align="center"><strong><?php print $yyModify?></strong></td>
				<td width="5%" align="center"><strong><?php print $yyDelete?></strong></td>
			  </tr>
<?php
		$sSQL = "SELECT clientUser,clientPW,clientLoginLevel,clientActions FROM clientlogin ORDER BY clientUser";
		$result = mysql_query($sSQL) or print(mysql_error());
		$gotone=FALSE;
		while($rs = mysql_fetch_array($result)){
			$gotone=TRUE; ?>
			  <tr>
				<td><?php print $rs["clientUser"]?></td>
				<td><?php print $rs["clientPW"]?></td>
				<td align="center"><?php print $rs["clientLoginLevel"]?></td>
				<td><?php	if(($rs["clientActions"] & 1) == 1) print "STE ";
							if(($rs["clientActions"] & 2) == 2) print "CTE ";
							if(($rs["clientActions"] & 4) == 4) print "SHE ";
							if(($rs["clientActions"] & 8) == 8) print "WSP ";
							if(($rs["clientActions"] & 16) == 16) print "PED ";
				?>&nbsp;</td>
				<td align="center"><input type=button value="<?php print $yyModify?>" onclick="modrec('<?php print $rs["clientUser"]?>')" /></td>
				<td align="center"><input type=button value="<?php print $yyDelete?>" onclick="delrec('<?php print $rs["clientUser"]?>')" /></td>
			  </tr>
<?php	}
		if(!$gotone){
?>
			  <tr>
                <td width="100%" colspan="6" align="center"><br /><?php print $yyCLNo?><br />&nbsp;</td>
			  </tr>
<?php
		}
?>
			  <tr>
                <td width="100%" colspan="6" align="center"><br /><strong><?php print $yyPOClk?> </strong>&nbsp;&nbsp;<input type="button" value="<?php print $yyCLNew?>" onclick="newrec()" /><br />&nbsp;</td>
			  </tr>
			  <tr>
                <td width="100%" colspan="6" align="center"><ul><li><?php print $yyCLTyp?></li></ul>
                          <a href="admin.php"><strong><?php print $yyAdmHom?></strong></a><br />
				<img src="../images/clearpixel.gif" width="300" height="3" alt="" /></td>
			  </tr>
            </table></td>
		  </form>
        </tr>
<?php
}
?>
      </table>