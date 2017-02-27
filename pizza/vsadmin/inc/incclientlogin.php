<?php
//This code is copyright (c) Internet Business Solutions SL, all rights reserved.
//The contents of this file are protect under law as the intellectual property of Internet
//Business Solutions SL. Any use, reproduction, disclosure or copying of any kind 
//without the express and written permission of Internet Business Solutions SL is forbidden.
//Author: Vince Reid, vince@virtualred.net
$success=TRUE;
if(@$enableclientlogin != TRUE){
	$success=FALSE;
	$errmsg="Client login not enabled";
}
if($success && @$_POST["posted"]=="1"){
	$theuser = trim(mysql_escape_string(@$_POST["user"]));
	$thepass = trim(mysql_escape_string(@$_POST["pass"]));
	$sSQL = "SELECT clientUser,clientActions,clientLoginLevel,clientPercentDiscount FROM clientlogin WHERE clientUser=BINARY '" . $theuser . "' AND clientPW=BINARY '" . $thepass . "'";
	$result = mysql_query($sSQL) or print(mysql_error());
	if($rs = mysql_fetch_array($result)){
		$sSQL = "DELETE FROM cart WHERE cartCompleted=0 AND cartOrderID=0 AND cartSessionID='" . session_id() . "'";
		mysql_query($sSQL) or print(mysql_error());
		$_SESSION["clientUser"]=$theuser;
		$_SESSION["clientActions"]=$rs["clientActions"];
		$_SESSION["clientLoginLevel"]=$rs["clientLoginLevel"];
		$_SESSION["clientPercentDiscount"]=(100.0-(double)$rs["clientPercentDiscount"])/100.0;
		print '<script src="vsadmin/savecookie.php?WRITECLL=' . $theuser . '&WRITECLP=' . $thepass;
		if(@$_POST["cook"]=="ON") print '&permanent=Y';
		print '"></script>';
	}else{
		$success=FALSE;
		$errmsg=$xxNoLog;
	}
	mysql_free_result($result);
	eval('$theref = @$clientloginref' . @$_SESSION["clientLoginLevel"] . ';');
	if($theref != ""){
		if(strtolower($theref) == "referer")
			if(trim(@$_POST["refurl"]) !="") $refURL = trim(@$_POST["refurl"]); else $refURL = $xxHomeURL;
		else
			$refURL = $theref;
	}elseif(@$clientloginref != ""){
		if($clientloginref=="referer")
			if(trim(@$_POST["refurl"]) !="") $refURL = trim(@$_POST["refurl"]); else $refURL = $xxHomeURL;
		else
			$refURL = $clientloginref;
	}else
		$refURL = $xxHomeURL;
	if($success) print '<meta http-equiv="refresh" content="3; url=' . $refURL . '">';
}
?>
      &nbsp;<br />
	  <table border="0" cellspacing="0" cellpadding="0" width="<?php print $maintablewidth?>" bgcolor="#B1B1B1" align="center">
<?php
	if(@$_GET["action"]=="logout"){
		$sSQL = "DELETE FROM cart WHERE cartCompleted=0 AND cartOrderID=0 AND cartSessionID='" . session_id() . "'";
		mysql_query($sSQL) or print(mysql_error());
		$_SESSION["clientUser"]="";
		$_SESSION["clientActions"]="";
		$_SESSION["clientLoginLevel"]="";
		$_SESSION["clientPercentDiscount"]="";
		print '<script src="vsadmin/savecookie.php?DELCLL=true"></script>';
		if(@$clientlogoutref != "")
			$refURL = $clientlogoutref;
		else
			$refURL = $xxHomeURL;
		print '<meta http-equiv="refresh" content="3; url=' . $refURL . '">';
?>
        <tr>
          <td width="100%">
            <table width="100%" border="0" bordercolor="#B1B1B1" cellspacing="1" cellpadding="3" bgcolor="#B1B1B1">
			  <tr>
				<td colspan="2" bgcolor="#FFFFFF">
				  <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="">
					<tr> 
					  <td width="100%" colspan="2" align="center"><br /><strong><?php print $xxLOSuc?></strong><br /><br /><?php print $xxAutFo?><br /><br />
                        <?php print $xxForAut?> <A href="<?php print $refURL?>"><strong><?php print $xxClkHere?></strong></a>.<br />
                        <br />
						<img src="../images/clearpixel.gif" width="300" height="3" alt="" />
					  </td>
					</tr>
				  </table>
                </td>
			  </tr>
			</table>
		  </td>
        </tr>
<?php
	}else{
		if(@$_POST["posted"]=="1" && $success){ ?>
        <tr>
          <td width="100%">
            <table width="100%" border="0" bordercolor="#B1B1B1" cellspacing="1" cellpadding="3" bgcolor="#B1B1B1">
			  <tr>
				<td colspan="2" bgcolor="#FFFFFF">
				  <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="">
					<tr> 
					  <td width="100%" colspan="2" align="center"><br /><strong><?php print $xxLISuc?></strong><br /><br /><?php print $xxAutFo?><br /><br />
                        <?php print $xxForAut?> <A href="<?php print $refURL?>"><strong><?php print $xxClkHere?></strong></a>.<br />
                        <br />
						<img src="../images/clearpixel.gif" width="300" height="3" alt="" />
					  </td>
					</tr>
				  </table>
                </td>
			  </tr>
			</table>
		  </td>
        </tr>
<?php	}else{ ?>
        <tr>
		  <form method="post" action="clientlogin.php">
		  <td width="100%">
			<input type="hidden" name="posted" value="1" />
			<input type="hidden" name="refurl" value="<?php print (@$_GET["refurl"] != "" ? $_GET["refurl"] : @$_POST["refurl"]) ?>" />
            <table class="cobtbl" width="100%" border="0" bordercolor="#B1B1B1" cellspacing="1" cellpadding="3" bgcolor="#B1B1B1">
			  <tr>
				<td class="cobll" colspan="2" bgcolor="#FFFFFF" height="34">
				  <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="">
					<tr>
					  <td width="14%" align="center"><img src="images/minipadlock.gif" alt="<?php print $xxMLLIS?>" /></td><td width="72%" align="center"><font size="4"><strong><?php print $xxPlEnt?></strong></font></td><td width="14%" align="center" height="30"><img src="images/minipadlock.gif" alt="<?php print $xxMLLIS?>" /></td>
					</tr>
				  </table>
				</td>
			  </tr>
<?php		if(! $success){ ?>
			  <tr> 
                <td class="cobll" width="100%" bgcolor="#FFFFFF" height="34" colspan="2" align="center"><font color="#FF0000"><?php print $errmsg?></font></td>
			  </tr>
<?php		} ?>
              <tr> 
                <td class="cobhl" width="40%" bgcolor="#EBEBEB" align="right" height="34"><strong><?php print $xxLogin?>: </strong></td>
				<td class="cobll" align="left" bgcolor="#FFFFFF" height="34"><input type="text" name="user" size="20" value="<?php print @$_POST["user"]?>" /> </td>
			  </tr>
			  <tr> 
                <td class="cobhl" bgcolor="#EBEBEB" align="right" height="34"><strong><?php print $xxPwd?>: </strong></td>
				<td class="cobll" align="left" bgcolor="#FFFFFF" height="34"><input type="password" name="pass" size="20" value="<?php print @$_POST["pass"]?>" /> </td>
			  </tr>
			  <tr> 
                <td class="cobll" align="center" colspan="2" bgcolor="#FFFFFF" height="34"><input type="checkbox" name="cook" value="ON"<?php if(@$_POST["cook"]=="ON") print "checked"?> /> <font size="1"><?php print $xxWrCk?></font></td>
			  </tr>
			  <tr> 
                <td class="cobll" width="100%" colspan="2" align="center" bgcolor="#FFFFFF" height="34"><input type="submit" value="<?php print $xxSubmt?>" /><br />
				<img src="../images/clearpixel.gif" width="300" height="3" alt="" /></td>
			  </tr>
            </table>
		  </td>
		  </form>
        </tr>
<?php	}
	} ?>
      </table><br />&nbsp;