<?php
//This code is copyright (c) Internet Business Solutions SL, all rights reserved.
//The contents of this file are protect under law as the intellectual property of Internet
//Business Solutions SL. Any use, reproduction, disclosure or copying of any kind 
//without the express and written permission of Internet Business Solutions SL is forbidden.
//Author: Vince Reid, vince@virtualred.net
$alreadygotadmin = getadminsettings();
?>
      <table width="130" bgcolor="#FFFFFF">
        <tr> 
          <td class="mincart" bgcolor="#F0F0F0" align="center"><img src="images/minipadlock.gif" align="top" alt="<?php print $xxMLLIS?>" /> 
            &nbsp;<strong><?php print $xxMLLIS?></strong></td>
        </tr>
<?php	if(@$enableclientlogin != TRUE){ ?>
		<tr>
		  <td class="mincart" bgcolor="#F0F0F0" align="center">
		  <p>Client login not enabled</p>
		  </td>
		</tr>
<?php	}elseif(@$_SESSION["clientUser"] != ""){ ?>
		<tr>
		  <td class="mincart" bgcolor="#F0F0F0" align="center">
		  <p><?php print $xxMLLIA?><strong><?php print $_SESSION["clientUser"]?></strong></p>
		  </td>
		</tr>
		<tr> 
          <td class="mincart" bgcolor="#F0F0F0" align="center"><font face='Verdana'>&raquo;</font> <a href="<?php print $storeurl?>clientlogin.php?action=logout"><strong>Logout</strong></a></td>
        </tr>
<?php	}else{ ?>
		<tr>
		  <td class="mincart" bgcolor="#F0F0F0" align="center">
		  <p><?php print $xxMLNLI?></p>
		  </td>
		</tr>
		<tr> 
          <td class="mincart" bgcolor="#F0F0F0" align="center"><font face='Verdana'>&raquo;</font> <a href="<?php print $storeurl?>clientlogin.php"><strong>Login</strong></a></td>
        </tr>
<?php	} ?>
      </table>