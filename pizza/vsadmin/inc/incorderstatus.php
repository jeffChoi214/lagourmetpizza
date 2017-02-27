<?php
//This code is copyright (c) Internet Business Solutions SL, all rights reserved.
//The contents of this file are protect under law as the intellectual property of Internet
//Business Solutions SL. Any use, reproduction, disclosure or copying of any kind 
//without the express and written permission of Internet Business Solutions SL is forbidden.
//Author: Vince Reid, vince@virtualred.net
if(@$dateformatstr == "") $dateformatstr = "m/d/Y";
$success = true;
$alreadygotadmin = getadminsettings();
if(@$_POST["posted"]=="1"){
	$email = mysql_escape_string(unstripslashes(trim(@$_POST["email"])));
	$ordid = mysql_escape_string(unstripslashes(trim(@$_POST["ordid"])));
	if(! is_numeric($ordid)){
		$success = false;
		$errormsg = $xxStaEr1;
	}elseif($email != "" && $ordid != ""){
		$sSQL = "SELECT ordStatus,ordStatusDate,".getlangid("statPublic",64).",ordStatusInfo FROM orders INNER JOIN orderstatus ON orders.ordStatus=orderstatus.statID WHERE ordID=" . $ordid . " AND ordEmail='" . $email . "'";
		$result = mysql_query($sSQL) or print(mysql_error());
		if(mysql_num_rows($result)>0){
			$rs = mysql_fetch_array($result);
			$ordStatus = $rs["ordStatus"];
			$ordStatusDate = strtotime($rs["ordStatusDate"]);
			$statPublic = $rs[getlangid("statPublic",64)];
			$ordStatusInfo = trim($rs["ordStatusInfo"]);
			// if(@$dateadjust != "") $ordStatusDate += $dateadjust*60*60;
		}else{
			$success = false;
			$errormsg = $xxStaEr2;
		}
		mysql_free_result($result);
	}else{
		$success = false;
		$errormsg = $xxStaEnt;
	}
}
?>
<br />
		<form method="post" name="statusform" action="orderstatus.php">
		  <input type="hidden" name="posted" value="1" />
<?php	if(@$_POST["posted"]=="1" && $success){ ?>
			<table class="cobtbl" width="<?php print $maintablewidth?>" border="0" bordercolor="#B1B1B1" cellspacing="1" cellpadding="3" bgcolor="#B1B1B1">
			  <tr>
				<td class="cobhl" colspan="2" bgcolor="#EBEBEB" height="34" align="center"><font size="4"><strong><?php print $xxStaVw?></strong></font></td>
			  </tr>
			  <tr>
			    <td class="cobhl" bgcolor="#EBEBEB" height="34" align="center" colspan="2"><strong><?php print $xxStaCur . " " . $ordid?></strong></td>
			  </tr>
			  <tr>
			    <td class="cobhl" bgcolor="#EBEBEB" height="34" align="right" width="40%"><strong><?php print $xxStatus?> : </strong></td>
				<td class="cobll" bgcolor="#FFFFFF" height="34"><?php print $statPublic?></td>
			  </tr>
			  <tr>
			    <td class="cobhl" bgcolor="#EBEBEB" height="34" align="right" width="40%"><strong><?php print $xxDate?> : </strong></td>
				<td class="cobll" bgcolor="#FFFFFF" height="34"><?php print date($dateformatstr, $ordStatusDate)?></td>
			  </tr>
			  <tr>
			    <td class="cobhl" bgcolor="#EBEBEB" height="34" align="right" width="40%"><strong><?php print $xxTime?> : </strong></td>
				<td class="cobll" bgcolor="#FFFFFF" height="34"><?php print date("H:i", $ordStatusDate)?></td>
			  </tr>
			  <?php if($ordStatusInfo != ""){ ?>
			  <tr>
			    <td class="cobhl" bgcolor="#EBEBEB" height="34" align="right" width="40%"><strong><?php print $xxAddInf?> : </strong></td>
				<td class="cobll" bgcolor="#FFFFFF" height="34"><?php print $ordStatusInfo?></td>
			  </tr>
			  <?php } ?>
			  <tr>
				<td class="cobll" colspan="2" bgcolor="#FFFFFF" align="center">-</td>
			  </tr>
<?php	}else{ ?>
            <table class="cobtbl" width="<?php print $maintablewidth?>" border="0" bordercolor="#B1B1B1" cellspacing="1" cellpadding="3" bgcolor="#B1B1B1">
			  <tr>
				<td class="cobhl" colspan="2" bgcolor="#EBEBEB" align="center">&nbsp;<br /><font size="4"><strong><?php print $xxStaVw?></strong></font><br />&nbsp;</td>
			  </tr>
<?php	} ?>
			  <tr>
			    <td class="cobhl" colspan="2" bgcolor="#EBEBEB" height="34" align="center"><strong><?php print $xxStaEnt?></strong></td>
			  </tr>
<?php	if($success==false){ ?>
			  <tr>
			    <td class="cobhl" width="40%" bgcolor="#EBEBEB" height="34" align="right"><strong><?php print $xxStaErr?> : </strong></td>
				<td class="cobll" bgcolor="#FFFFFF" height="34"><font color="#FF0000"><?php print $errormsg?></font></td>
			  </tr>
<?php	} ?>
			  <tr>
			    <td class="cobhl" width="40%" bgcolor="#EBEBEB" height="34" align="right"><strong><?php print $xxOrdId?> : </strong></td>
				<td class="cobll" bgcolor="#FFFFFF" height="34"><input type="text" size="<?php print atb(20)?>" name="ordid" value="<?php print trim(@$_POST["ordid"])?>" /></td>
			  </tr>
			  <tr>
			    <td class="cobhl" width="40%" bgcolor="#EBEBEB" height="34" align="right"><strong><?php print $xxEmail?> : </strong></td>
				<td class="cobll" bgcolor="#FFFFFF" height="34"><input type="text" size="<?php print atb(30)?>" name="email" value="<?php print trim(@$_POST["email"])?>" /></td>
			  </tr>
			  <tr>
				<td class="cobll" bgcolor="#FFFFFF" height="34" colspan="2" align="center" valign="middle"><input type="submit" value="<?php print $xxStaVw?>" /></td>
			  </tr>
			</form>
			</table>
		  <br />&nbsp;
