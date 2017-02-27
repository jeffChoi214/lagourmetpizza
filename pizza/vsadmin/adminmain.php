<SCRIPT language="php">
session_cache_limiter('none');
session_start();
//This code is copyright (c) Internet Business Solutions SL, all rights reserved.
//The contents of this file are protect under law as the intellectual property
//of Internet Business Solutions SL. Any use, reproduction, disclosure or copying
//of any kind without the express and written permission of Internet Business 
//Solutions SL is forbidden.
//Author: Vince Reid, vince@virtualred.net
include "db_conn_open.php";
include "includes.php";
include "inc/languageadmin.php";
include "inc/incfunctions.php";
if(@$storesessionvalue=="") $storesessionvalue="virtualstore";
if(@$_SESSION["loggedon"] != $storesessionvalue){
	if(@$_SERVER["HTTPS"] == "on" || @$_SERVER["SERVER_PORT"] == "443")$prot='https://';else $prot='http://';
	header('Location: '.$prot.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/login.php');
	exit;
}
$isprinter=FALSE;
</SCRIPT>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html><!-- InstanceBegin template="/Templates/admintemplate.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
<!-- InstanceBeginEditable name="doctitle" -->
<title>Admin Main Settings</title>
<!-- InstanceEndEditable --><link rel="stylesheet" type="text/css" href="adminstyle.css">
<meta http-equiv="Content-Type" content="text/html; charset=<?php print $adminencoding ?>">
</head>
<body <?php if($isprinter) print 'class="printbody"'?>>
<?php if(! $isprinter){ ?>

<!-- Header section -->
<div id="header1" align="right"><a class="topbar" href="http://www.ecommercetemplates.com/help.asp" target="_blank">help</a> &middot;
  <a href="http://www.ecommercetemplates.com/support/default.asp" target="_blank" class="topbar">forum</a> &middot; <a href="http://www.ecommercetemplates.com/support/search.asp" target="_blank" class="topbar">search forum</a> &middot; <a href="http://www.ecommercetemplates.com/updaters.asp" target="_blank" class="topbar">updaters</a> &middot; <a class="topbar" href="logout.php">log-out</a>&nbsp;&nbsp;</div>
<div id="header"><img src="adminimages/ecommerce_templates.gif" width="278" height="53" alt=""/></div>

<!-- Left menus -->
<div id="left1">
<img src="adminimages/administration.jpg" width="150" height="31" alt=""/><br />
  &nbsp;&middot; <a class="topbar" href="admin.php">home</a><img src="adminimages/hr.gif" alt=""/><br />
  &nbsp;&middot; <a class="topbar" href="adminmain.php">main settings</a><img src="adminimages/hr.gif" alt=""/><br />
  &nbsp;&middot; <a class="topbar" href="adminorders.php">view orders</a><img src="adminimages/hr.gif" alt=""/><br />
  &nbsp;&middot; <a class="topbar" href="adminlogin.php">change password</a><img src="adminimages/hr.gif" alt=""/><br />
  &nbsp;&middot; <a class="topbar" href="adminpayprov.php">payment providers</a><img src="adminimages/hr.gif" alt=""/><br />
&nbsp;&middot; <a class="topbar" href="adminaffil.php">affiliates</a></div>

<div id="left2">
<img src="adminimages/product_admin.jpg" width="150" height="31" alt=""/><br />
  &nbsp;&middot; <a class="topbar" href="adminprods.php">product admin</a><img src="adminimages/hr.gif" alt=""/><br />
  &nbsp;&middot; <a class="topbar" href="adminprodopts.php">product options</a><img src="adminimages/hr.gif" alt=""/><br />
  &nbsp;&middot; <a class="topbar" href="admincats.php">categories</a><img src="adminimages/hr.gif" alt=""/><br />
  &nbsp;&middot; <a class="topbar" href="admindiscounts.php">discounts</a><img src="adminimages/hr.gif" alt=""/><br />
&nbsp;&middot; <a class="topbar" href="adminpricebreak.php">quantity pricing</a></div>

<div id="left3"><img src="adminimages/shipping_admin.jpg" width="150" height="31" alt=""/><br />
  &nbsp;&middot; <a class="topbar" href="adminstate.php">states</a><img src="adminimages/hr.gif" alt=""/><br />
  &nbsp;&middot; <a class="topbar" href="admincountry.php">countries</a><img src="adminimages/hr.gif" alt=""/><br />
&nbsp;&middot; <a class="topbar" href="adminzones.php">postal zones</a></div>

<div id="left4"><img src="adminimages/extras.jpg" width="150" height="31" alt=""/><br />
  &nbsp;&middot; <a href="http://www.ecommercetemplates.com/affiliateinfo.asp" target="_blank" class="topbar">affiliate program</a><img src="adminimages/hr.gif" alt=""/><br />
  &nbsp;&middot; <a href="http://www.ecommercetemplates.com/addsite.asp" target="_blank" class="topbar">submit your store</a><img src="adminimages/hr.gif" alt=""/><br />
&nbsp;&middot; <a class="topbar" href="http://www.ecommercetemplates.com/support/default.asp">support forum</a></div>
<?php } ?>
<!-- main content -->
<!-- InstanceBeginEditable name="Body" -->
<div id="main">
<?php include "inc/incmain.php"; ?></div>
<!-- InstanceEndEditable -->


</body>
<!-- InstanceEnd --></html>
