<?php
//This code is copyright (c) Internet Business Solutions SL, all rights reserved.
//The contents of this file are protect under law as the intellectual property of Internet
//Business Solutions SL. Any use, reproduction, disclosure or copying of any kind 
//without the express and written permission of Internet Business Solutions SL is forbidden.
//Author: Vince Reid, vince@virtualred.net
$success=0;
if(@$storesessionvalue=="") $storesessionvalue="virtualstore".time();
if(@$_SESSION["loggedon"] != $storesessionvalue && trim(@$_COOKIE["WRITECKL"])!=""){
	$sSQL="SELECT adminID FROM admin WHERE adminUser='" . mysql_escape_string(unstripslashes(trim(@$_COOKIE["WRITECKL"]))) . "' AND adminPassword='" . mysql_escape_string(unstripslashes(trim(@$_COOKIE["WRITECKP"]))) . "' AND adminID=1";
	$result = mysql_query($sSQL) or print(mysql_error());
	if(mysql_num_rows($result)>0)
		@$_SESSION["loggedon"] = $storesessionvalue;
	else
		$success=2;
	mysql_free_result($result);
}
if(@$_SESSION["loggedon"] != $storesessionvalue && $success!=2) exit;
$sSQL = "SELECT adminEmail,adminStoreURL,adminShipping,adminVersion,adminUser,adminPassword FROM admin WHERE adminID=1";
$result = mysql_query($sSQL) or print(mysql_error());
$rs = mysql_fetch_assoc($result);
mysql_free_result($result);
if(@$_GET["writeck"]=="yes"){
	print "<script src='savecookie.php?WRITECKL=" . $rs["adminUser"] . "&WRITECKP=" . $rs["adminPassword"] . "'></script>";
	print "<meta http-equiv=\"Refresh\" content=\"3; URL=admin.php\">";
	$success=1;
}elseif(@$_GET["writeck"]=="no"){
	print "<script src='savecookie.php?DELCK=yes'></script>";
	print "<meta http-equiv=\"Refresh\" content=\"3; URL=admin.php\">";
	$success=1;
}
?>
      <table border="0" cellspacing="0" cellpadding="0" width="100%" bgcolor="" align="center">
        <tr> 
          <td width="100%">
            <table width="<?php print $innertablewidth?>" border="0" cellspacing="0" cellpadding="3" bgcolor="">
			  <tr> 
                <td colspan="2" width="100%" align="center"><strong><?php print $yyChsLst?></strong><br /><font size="1">(<?php print $yyVers?>: <?php print $rs["adminVersion"]?>)</font><br />&nbsp;
                </td>
			  </tr>
<?php		if($success==1){ ?>
			  <tr> 
				<td colspan="2" width="100%" align="center"><p>&nbsp;</p><p>&nbsp;</p>
				  <p><strong><?php print $yyOpSuc?></strong></p><p>&nbsp;</p>
				  <p><font size="1"><?php print $yyNowFrd?><br /><br /><?php print $yyNoAuto?> <a href="admin.php"><?php print $yyClkHer?></a>.</font></td>
			  </tr>
<?php		}elseif($success==2){ ?>
			  <tr> 
				<td colspan="2" width="100%" align="center"><p>&nbsp;</p><p>&nbsp;</p>
				  <p><strong><?php print $yyOpFai?></strong></p><p>&nbsp;</p>
				  <p><?php print $yyCorCoo?> <?php print $yyCorLI?> <a href="login.php"><?php print $yyClkHer?></a>.</p></td>
			  </tr>
<?php		}else{ ?>
			  <tr> 
				<td valign="top" width="50%" align="left">&nbsp;&nbsp;<a href="adminorders.php"><strong><?php print $yyVwOrd?> </strong></a><br />
                        &nbsp;
                </td>
				<td valign="top" width="50%"><a href="<?php print helpbaseurl?>help.asp#orders" target="ttshelp"><strong><?php print $yyOnlHlp?></strong></a></td>
			  </tr>
			  <tr> 
				<td width="50%" align="left">&nbsp;&nbsp;<a href="adminlogin.php"><strong><?php print $yyCngPw?></strong></a> </td>
				<td width="50%"><a href="<?php print helpbaseurl?>help.asp#uname" target="ttshelp"><strong><?php print $yyOnlHlp?></strong></a></td>
			  </tr>
              <tr> 
				<td width="50%" align="left">&nbsp;&nbsp;<a href="adminmain.php"><strong><?php print $yyEdAdm?></strong></a> </td>
				<td width="50%"><a href="<?php print helpbaseurl?>help.asp#admin" target="ttshelp"><strong><?php print $yyOnlHlp?></strong></a></td>
			  </tr>
			  <tr>
				<td width="50%" align="left">&nbsp;&nbsp;<a href="adminaffil.php"><strong><?php print $yyVwAff?></strong></a> </td>
				<td width="50%"><a href="<?php print helpbaseurl?>help.asp#affiliate" target="ttshelp"><strong><?php print $yyOnlHlp?></strong></a></td>
			  </tr>
			  <tr> 
				<td width="50%" align="left">&nbsp;&nbsp;<a href="adminprods.php"><strong><?php print $yyEdPrd?></strong></a> </td>
				<td width="50%"><a href="<?php print helpbaseurl?>help.asp#prods" target="ttshelp"><strong><?php print $yyOnlHlp?></strong></a></td>
			  </tr>
			  <tr> 
				<td width="50%" align="left">&nbsp;&nbsp;<a href="adminprodopts.php"><strong><?php print $yyEdOpt?></strong></a> </td>
				<td width="50%"><a href="<?php print helpbaseurl?>help.asp#prodopt" target="ttshelp"><strong><?php print $yyOnlHlp?></strong></a></td>
			  </tr>
			  <tr> 
				<td width="50%" align="left">&nbsp;&nbsp;<a href="adminpricebreak.php"><strong><?php print $yyEdPrBk?></strong></a> </td>
				<td width="50%"><a href="<?php print helpbaseurl?>help.asp#pricebreak" target="ttshelp"><strong><?php print $yyOnlHlp?></strong></a></td>
			  </tr>
			  <tr> 
				<td width="50%" align="left">&nbsp;&nbsp;<a href="admincats.php"><strong><?php print $yyEdCat?></strong></a> </td>
				<td width="50%"><a href="<?php print helpbaseurl?>help.asp#cats" target="ttshelp"><strong><?php print $yyOnlHlp?></strong></a></td>
			  </tr>
			  <tr> 
				<td width="50%" align="left">&nbsp;&nbsp;<a href="admindiscounts.php"><strong><?php print $yyDisCou?></strong></a> </td>
				<td width="50%"><a href="<?php print helpbaseurl?>help.asp#discounts" target="ttshelp"><strong><?php print $yyOnlHlp?></strong></a></td>
			  </tr>
			  <tr> 
				<td width="50%" align="left">&nbsp;&nbsp;<a href="adminclientlog.php"><strong><?php print $yyCliLog?></strong></a> </td>
				<td width="50%"><a href="<?php print helpbaseurl?>help.asp#clientlogin" target="ttshelp"><strong><?php print $yyOnlHlp?></strong></a></td>
			  </tr>
			  <tr> 
				<td width="50%" align="left">&nbsp;&nbsp;<a href="adminstate.php"><strong><?php print $yyEdSta?></strong></a> </td>
				<td width="50%"><a href="<?php print helpbaseurl?>help.asp#state" target="ttshelp"><strong><?php print $yyOnlHlp?></strong></a></td>
			  </tr>
			  <tr> 
				<td width="50%" align="left">&nbsp;&nbsp;<a href="admincountry.php"><strong><?php print $yyEdCnt?></strong></a> </td>
				<td width="50%"><a href="<?php print helpbaseurl?>help.asp#country" target="ttshelp"><strong><?php print $yyOnlHlp?></strong></a></td>
			  </tr>
<?php if($rs["adminShipping"]==2 || $rs["adminShipping"]==5){ ?>
			  <tr> 
				<td width="50%" align="left">&nbsp;&nbsp;<a href="adminzones.php"><strong><?php print $yyEdPzon?></strong></a> </td>
				<td width="50%"><a href="<?php print helpbaseurl?>help.asp#pzone" target="ttshelp"><strong><?php print $yyOnlHlp?></strong></a></td>
			  </tr>
<?php }elseif($rs["adminShipping"]==3 || $rs["adminShipping"]==4 || $rs["adminShipping"]==6){
			$shipmet = "USPS";
			if($rs["adminShipping"]==4) $shipmet = "UPS";
			if($rs["adminShipping"]==6) $shipmet = $yyCanPos;
?>
			  <tr> 
				<td width="50%" align="left">&nbsp;&nbsp;<a href="adminuspsmeths.php"><strong><?php print $yyEdit . " " . $shipmet . " " . $yyShpMet?></strong></a> </td>
				<td width="50%"><a href="<?php print helpbaseurl?>help.asp#shipmeth" target="ttshelp"><strong><?php print $yyOnlHlp?></strong></a></td>
			  </tr>
<?php } ?>
			  <tr> 
                <td width="50%" align="left">&nbsp;&nbsp;<a href="adminpayprov.php"><strong><?php print $yyEdPPro?></strong></a></td>
				<td width="50%"><a href="<?php print helpbaseurl?>help.asp#payprov" target="ttshelp"><strong><?php print $yyOnlHlp?></strong></a></td>
			  </tr>
			  <tr> 
                <td width="50%" align="left">&nbsp;&nbsp;<a href="adminordstatus.php"><strong><?php print $yyEdOSta?></strong></a></td>
				<td width="50%"><a href="<?php print helpbaseurl?>help.asp#ordstat" target="ttshelp"><strong><?php print $yyOnlHlp?></strong></a></td>
			  </tr>
			  <tr> 
                <td width="50%" align="left">&nbsp;&nbsp;<a href="admindropship.php"><strong><?php print $yyEdDrSp?></strong></a></td>
				<td width="50%"><a href="<?php print helpbaseurl?>help.asp#droshp" target="ttshelp"><strong><?php print $yyOnlHlp?></strong></a></td>
			  </tr>
			  <tr> 
                <td width="50%" align="left">&nbsp;&nbsp;<a href="adminupslicense.php"><strong><?php print $yyRegUPS?></strong></a></td>
				<td width="50%"><a href="<?php print helpbaseurl?>help.asp#upsreg" target="ttshelp"><strong><?php print $yyOnlHlp?></strong></a></td>
			  </tr>
			  <tr> 
                <td width="50%" align="left">&nbsp;&nbsp;<a href="adminipblock.php"><strong><?php print $yyIPBlock?></strong></a></td>
				<td width="50%"><a href="<?php print helpbaseurl?>help.asp#ipblock" target="ttshelp"><strong><?php print $yyOnlHlp?></strong></a></td>
			  </tr>
			  <tr> 
				<td colspan="2" width="100%" align="left">&nbsp;&nbsp;<a href="logout.php"><strong><?php print $yyLOut?></strong></a> </td>
			  </tr>
			  <tr> 
				<td colspan="2" width="100%" align="center"><p>&nbsp;</p>
				<?php	if(trim(@$_COOKIE["WRITECKL"])!=""){ ?>
					<a href="admin.php?writeck=no"><?php print $yyDelCoo?></a><br />
				<?php	}else{ ?>
					<a href="admin.php?writeck=yes"><?php print $yyWrCoo?></a><br /><font size="1"><?php print $yyNoRec?></font>
				<?php	} ?>
				</td>
			  </tr>
<?php		} ?>
			  <tr> 
                <td colspan="2" width="100%" align="left"><img src="../images/clearpixel.gif" width="300" height="5" alt="" />
                </td>
			  </tr>
            </table>
          </td>
        </tr>
      </table>