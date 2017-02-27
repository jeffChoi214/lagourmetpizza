<?php
// This code is copyright Internet Business Solutions SL.
// Unauthorized copying, use or transmittal without the
// express permission of Internet Business Solutions SL
// is strictly prohibited.
// Author: Vince Reid, vince@virtualred.net
$sVersion="PHP v4.8.8";
?><html>
<head>
<title>Update Ecommerce Plus mySQL database to version <?php print $sVersion ?></title>
<STYLE type="text/css">
<!--
p {  font: 10pt Verdana, Arial, Helvetica, sans-serif}
TD {  font: 10pt Verdana, Arial, Helvetica, sans-serif}
BODY {  font: 10pt Verdana, Arial, Helvetica, sans-serif}
-->
</STYLE>
</head>
<body>
<?php include "vsadmin/db_conn_open.php" ?>
<?php include "vsadmin/inc/languagefile.php" ?>
<?php
$haserrors=FALSE;
function print_sql_error(){
	global $haserrors;
	$haserrors=TRUE;
	print('<font color="#FF0000">' . mysql_error() . "</font><br>");
}

if(@$_POST["posted"]=="1"){

// mysql_query("DROP TABLE admin,affiliates,cart,cartoptions,countries,coupons,cpnassign,optiongroup,options,orders,payprovider,postalzones,prodoptions,products,sections,states,topsections,uspsmethods,zonecharges") or print_sql_error();

print('Checking for Client Login upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT * FROM clientlogin") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding Client Login table...<br>');
	mysql_query("CREATE TABLE clientlogin (clientUser VARCHAR(50) PRIMARY KEY,clientPW VARCHAR(50) NULL,clientLoginLevel TINYINT DEFAULT 0,clientActions INT DEFAULT 0,clientEmail VARCHAR(255) NULL, INDEX (clientUser), UNIQUE (clientUser))");
	mysql_query("CREATE TABLE tmplogin (tmploginid VARCHAR(100) PRIMARY KEY,tmploginname VARCHAR(50) NULL,tmplogindate DATETIME, INDEX (tmploginid), UNIQUE (tmploginid))");
	mysql_query("ALTER TABLE products ADD COLUMN pWholesalePrice DOUBLE DEFAULT 0");
	mysql_query("UPDATE products SET pWholesalePrice=0");
}

print('Checking for client login percentage discount upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT clientPercentDiscount FROM clientlogin") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding client login percentage discount column.<br>');
	mysql_query("ALTER TABLE clientlogin ADD COLUMN clientPercentDiscount DOUBLE DEFAULT 0") or print_sql_error();
	mysql_query("UPDATE clientlogin SET clientPercentDiscount=0") or print_sql_error();
}

print('Checking for Extra Order Fields upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT ordExtra1 FROM orders") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding Extra Order Fields columns.<br>');
	mysql_query("ALTER TABLE orders ADD COLUMN ordExtra1 VARCHAR(255) NULL") or print_sql_error();
	mysql_query("ALTER TABLE orders ADD COLUMN ordExtra2 VARCHAR(255) NULL") or print_sql_error();
	mysql_query("UPDATE orders SET ordExtra1='',ordExtra2=''") or print_sql_error();
}

print('Checking for HST Tax upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT ordHSTTax FROM orders") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding HST Tax column.<br>');
	mysql_query("ALTER TABLE orders ADD COLUMN ordHSTTax DOUBLE DEFAULT 0") or print_sql_error();
	mysql_query("UPDATE orders SET ordHSTTax=0") or print_sql_error();
}

print('Checking for Order Status upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT * FROM orderstatus") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding Order Status table.<br>');
	mysql_query("CREATE TABLE orderstatus (statID INT PRIMARY KEY,statPrivate VARCHAR(255) NULL,statPublic VARCHAR(255) NULL)") or print_sql_error();
	mysql_query("INSERT INTO orderstatus (statID,statPrivate,statPublic) VALUES (0,'Cancelled','Order Cancelled')");
	mysql_query("INSERT INTO orderstatus (statID,statPrivate,statPublic) VALUES (1,'Deleted','Order Deleted')");
	mysql_query("INSERT INTO orderstatus (statID,statPrivate,statPublic) VALUES (2,'Unauthorized','Awaiting Payment')");
	mysql_query("INSERT INTO orderstatus (statID,statPrivate,statPublic) VALUES (3,'Authorized','Payment Received')");
	mysql_query("INSERT INTO orderstatus (statID,statPrivate,statPublic) VALUES (4,'Packing','In Packing')");
	mysql_query("INSERT INTO orderstatus (statID,statPrivate,statPublic) VALUES (5,'Shipping','In Shipping')");
	mysql_query("INSERT INTO orderstatus (statID,statPrivate,statPublic) VALUES (6,'Shipped','Order Shipped')");
	mysql_query("INSERT INTO orderstatus (statID,statPrivate,statPublic) VALUES (7,'Completed','Order Completed')");
	mysql_query("INSERT INTO orderstatus (statID,statPrivate,statPublic) VALUES (8,'','')");
	mysql_query("INSERT INTO orderstatus (statID,statPrivate,statPublic) VALUES (9,'','')");
	mysql_query("INSERT INTO orderstatus (statID,statPrivate,statPublic) VALUES (10,'','')");
	mysql_query("INSERT INTO orderstatus (statID,statPrivate,statPublic) VALUES (11,'','')");
	mysql_query("INSERT INTO orderstatus (statID,statPrivate,statPublic) VALUES (12,'','')");
	mysql_query("INSERT INTO orderstatus (statID,statPrivate,statPublic) VALUES (13,'','')");
	mysql_query("INSERT INTO orderstatus (statID,statPrivate,statPublic) VALUES (14,'','')");
	mysql_query("INSERT INTO orderstatus (statID,statPrivate,statPublic) VALUES (15,'','')");
	mysql_query("INSERT INTO orderstatus (statID,statPrivate,statPublic) VALUES (16,'','')");
	mysql_query("INSERT INTO orderstatus (statID,statPrivate,statPublic) VALUES (17,'','')");
}
flush();

print('Checking for Order Status orders upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT ordStatus FROM orders") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding Order Status orders columns.<br>');
	mysql_query("ALTER TABLE orders ADD COLUMN ordStatus TINYINT DEFAULT 0") or print_sql_error();
	mysql_query("ALTER TABLE orders ADD COLUMN ordStatusDate DATETIME") or print_sql_error();
	mysql_query("ALTER TABLE orders ADD COLUMN ordStatusInfo TEXT NULL") or print_sql_error();
	mysql_query("UPDATE orders SET ordStatus=2") or print_sql_error();
	mysql_query("UPDATE orders SET ordStatus=3 WHERE ordAuthNumber<>''") or print_sql_error();
	mysql_query("UPDATE orders SET ordStatusDate=ordDate") or print_sql_error();
}

print('Checking for Options Percentage upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT optFlags FROM optiongroup") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding Options Percentage columns.<br>');
	mysql_query("ALTER TABLE optiongroup ADD COLUMN optFlags INT DEFAULT 0") or print_sql_error();
	mysql_query("UPDATE optiongroup SET optFlags=0") or print_sql_error();
	// This change can only be done once and is necessary for the v3.6.5 upgrade
	mysql_query("UPDATE products SET pExemptions=7 WHERE pExemptions=3") or print_sql_error();
	mysql_query("UPDATE products SET pExemptions=4 WHERE pExemptions=2") or print_sql_error();
	mysql_query("UPDATE products SET pExemptions=3 WHERE pExemptions=1") or print_sql_error();
}

print('Checking for Currency Conversions upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT currRate1 FROM admin") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding Currency Conversions columns.<br>');
	mysql_query("ALTER TABLE admin ADD COLUMN currRate1 DOUBLE DEFAULT 0") or print_sql_error();
	mysql_query("ALTER TABLE admin ADD COLUMN currRate2 DOUBLE DEFAULT 0") or print_sql_error();
	mysql_query("ALTER TABLE admin ADD COLUMN currRate3 DOUBLE DEFAULT 0") or print_sql_error();
	mysql_query("ALTER TABLE admin ADD COLUMN currSymbol1 VARCHAR(50) NULL") or print_sql_error();
	mysql_query("ALTER TABLE admin ADD COLUMN currSymbol2 VARCHAR(50) NULL") or print_sql_error();
	mysql_query("ALTER TABLE admin ADD COLUMN currSymbol3 VARCHAR(50) NULL") or print_sql_error();
	mysql_query("UPDATE admin SET currRate1=0,currRate2=0,currRate3=0,currSymbol1='',currSymbol2='',currSymbol3=''") or print_sql_error();
}

print('Checking for Auto Currency Conversions upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT currConvUser FROM admin") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding Auto Currency Conversions columns.<br>');
	mysql_query("ALTER TABLE admin ADD COLUMN currConvUser VARCHAR(50) NULL") or print_sql_error();
	mysql_query("ALTER TABLE admin ADD COLUMN currConvPw VARCHAR(50) NULL") or print_sql_error();
	mysql_query("ALTER TABLE admin ADD COLUMN currLastUpdate DATETIME") or print_sql_error();
	mysql_query("UPDATE admin SET currConvUser='',currConvPw='',currLastUpdate='" . date("Y-m-d H:i:s", time()-100000) . "'") or print_sql_error();
}

print('Checking for multisections upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT * FROM multisections") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding multisections table.<br>');
	$sSQL = "CREATE TABLE multisections (pID VARCHAR(128) NOT NULL,";
	$sSQL .= "pSection INT DEFAULT 0 NOT NULL,";
	$sSQL .= "PRIMARY KEY (pID, pSection))";
	mysql_query($sSQL) or print_sql_error();
}
flush();

print('Checking for pay provider method upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT payProvMethod FROM payprovider") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding pay provider method column.<br>');
	mysql_query("ALTER TABLE payprovider ADD COLUMN payProvMethod INT DEFAULT 0") or print_sql_error();
	$sSQL = "SELECT payProvID,payProvData2 FROM payprovider WHERE payProvID=11 OR payProvID=12";
	$result = mysql_query($sSQL) or print(mysql_error());
	while($rs = mysql_fetch_assoc($result)){
		if(trim($rs["payProvData2"]) != ""){
			$sSQL = "UPDATE payprovider SET payProvMethod=".$rs["payProvData2"]." WHERE payProvID=" . $rs["payProvID"];
			mysql_query($sSQL) or print_sql_error();
		}
	}
	mysql_query("UPDATE payprovider SET payProvData2='' WHERE payProvID=11 OR payProvID=12") or print_sql_error();
}

print('Checking for UPS upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT adminUPSUser FROM admin") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding UPS columns.<br>');
	mysql_query("ALTER TABLE admin ADD COLUMN adminUPSUser VARCHAR(255) NULL") or print_sql_error();
	mysql_query("ALTER TABLE admin ADD COLUMN adminUPSpw VARCHAR(255) NULL") or print_sql_error();
	mysql_query("ALTER TABLE admin ADD COLUMN adminUPSAccess VARCHAR(255) NULL") or print_sql_error();
	mysql_query("UPDATE admin SET adminUPSUser='',adminUPSpw='',adminUPSAccess=''") or print_sql_error();
}

print('Checking for Canada Post upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT adminCanPostUser FROM admin") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding Canada Post column.<br>');
	mysql_query("ALTER TABLE admin ADD COLUMN adminCanPostUser VARCHAR(255) NULL") or print_sql_error();
	mysql_query("UPDATE admin SET adminCanPostUser=''") or print_sql_error();
}

print('Checking for Commercial Location orders upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT ordComLoc FROM orders") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding Commercial Location orders column.<br>');
	mysql_query("ALTER TABLE orders ADD COLUMN ordComLoc TINYINT DEFAULT 0") or print_sql_error();
	mysql_query("UPDATE orders SET ordComLoc=0") or print_sql_error();
}
flush();

print('Checking for UPS License upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT adminUPSLicense FROM admin") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding UPS License column.<br>');
	mysql_query("ALTER TABLE admin ADD COLUMN adminUPSLicense TEXT") or print_sql_error();
}

print('Checking for Admin Units upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT adminUnits FROM admin") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding Admin Units column.<br>');
	mysql_query("ALTER TABLE admin ADD COLUMN adminUnits TINYINT DEFAULT 0") or print_sql_error();
	mysql_query("UPDATE admin SET adminUnits=1") or print_sql_error();
}

print('Checking for Capture Card admin upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT adminCert FROM admin") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding Capture Card admin columns.<br>');
	mysql_query("ALTER TABLE admin ADD COLUMN adminCert TEXT NULL") or print_sql_error();
	mysql_query("ALTER TABLE admin ADD COLUMN adminDelCC INT DEFAULT 0") or print_sql_error();
	mysql_query("UPDATE admin SET adminCert='',adminDelCC=7") or print_sql_error();
}
flush();

print('Checking for Card Capture orders upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT ordCNum FROM orders") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding Card Capture orders column.<br>');
	mysql_query("ALTER TABLE orders ADD COLUMN ordCNum TEXT NULL") or print_sql_error();
	mysql_query("UPDATE orders SET ordCNum=''") or print_sql_error();
}

print('Checking for admin tweaks upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT adminTweaks FROM admin") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding admin tweaks column.<br>');
	mysql_query("ALTER TABLE admin ADD COLUMN adminTweaks INT") or print_sql_error();
	mysql_query("UPDATE admin SET adminTweaks=0") or print_sql_error();
}
flush();

print('Checking for handling charge upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT adminHandling FROM admin") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding handling charge column.<br>');
	mysql_query("ALTER TABLE admin ADD COLUMN adminHandling DOUBLE DEFAULT 0") or print_sql_error();
	mysql_query("ALTER TABLE orders ADD COLUMN ordHandling DOUBLE DEFAULT 0") or print_sql_error();
	mysql_query("UPDATE admin SET adminHandling=0") or print_sql_error();
	mysql_query("UPDATE orders SET ordHandling=0") or print_sql_error();
}

print('Checking for discounts upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT * FROM coupons") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding coupons table.<br>');
	$sSQL = "CREATE TABLE coupons (cpnID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,";
	$sSQL .= "cpnWorkingName VARCHAR(255),";
	$sSQL .= "cpnNumber VARCHAR(255),";
	$sSQL .= "cpnType INT DEFAULT 0,";
	$sSQL .= "cpnEndDate DATETIME,";
	$sSQL .= "cpnDiscount DOUBLE DEFAULT 0,";
	$sSQL .= "cpnThreshold DOUBLE DEFAULT 0,";
	$sSQL .= "cpnQuantity INT DEFAULT 0,";
	$sSQL .= "cpnNumAvail INT DEFAULT 0,";
	$sSQL .= "cpnCntry TINYINT DEFAULT 0,";
	$sSQL .= "cpnIsCoupon TINYINT DEFAULT 0,";
	$sSQL .= "cpnSitewide TINYINT DEFAULT 0,";
	$sSQL .= "INDEX (cpnID), UNIQUE (cpnID))";
	mysql_query($sSQL) or print_sql_error();
}

print('Checking for discount max upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT cpnThresholdMax FROM coupons") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding discount max columns.<br>');
	mysql_query("ALTER TABLE coupons ADD COLUMN cpnThresholdMax DOUBLE DEFAULT 0") or print_sql_error();
	mysql_query("ALTER TABLE coupons ADD COLUMN cpnQuantityMax INT DEFAULT 0") or print_sql_error();
	mysql_query("UPDATE coupons SET cpnThresholdMax=0,cpnQuantityMax=0") or print_sql_error();
}
flush();

print('Checking for discount assignment upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT * FROM cpnassign") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding discount assignment table.<br>');
	$sSQL = "CREATE TABLE cpnassign (cpaID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,";
	$sSQL .= "cpaCpnID INT DEFAULT 0,";
	$sSQL .= "cpaType TINYINT DEFAULT 0,";
	$sSQL .= "cpaAssignment VARCHAR(255),";
	$sSQL .= "INDEX (cpaID), UNIQUE (cpaID))";
	mysql_query($sSQL) or print_sql_error();
}

print('Checking for order discounts upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT ordDiscount FROM orders") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding order discounts column.<br>');
	mysql_query("ALTER TABLE orders ADD COLUMN ordDiscount DOUBLE DEFAULT 0") or print_sql_error();
	mysql_query("ALTER TABLE orders ADD COLUMN ordDiscountText VARCHAR(255) NULL") or print_sql_error();
	mysql_query("UPDATE orders SET ordDiscount=0,ordDiscountText=''") or print_sql_error();
}

print('Checking for countries fsa upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT countryFreeShip FROM countries") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding countries fsa column.<br>');
	mysql_query("ALTER TABLE countries ADD COLUMN countryFreeShip TINYINT DEFAULT 0") or print_sql_error();
	mysql_query("UPDATE countries SET countryFreeShip=0") or print_sql_error();
}
flush();

print('Checking for states fsa upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT stateFreeShip FROM states") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding states fsa column.<br>');
	mysql_query("ALTER TABLE states ADD COLUMN stateFreeShip TINYINT DEFAULT 1") or print_sql_error();
	mysql_query("UPDATE states SET stateFreeShip=1") or print_sql_error();
}

$columnexists=TRUE;
$result = mysql_query("SELECT * FROM countries WHERE countryID=214") or print_sql_error();
if(mysql_num_rows($result)==0){
	mysql_query("INSERT INTO countries (countryID,countryName,countryEnabled,countryFreeShip,countryTax,countryOrder,countryZone,countryLCID,countryCurrency,countryCode) VALUES (214,'Channel Islands',0,0,0,0,3,0,'GBP','GB')") or print_sql_error();
}

$columnexists=TRUE;
$result = mysql_query("SELECT * FROM countries WHERE countryID=215") or print_sql_error();
if(mysql_num_rows($result)==0){
	mysql_query("INSERT INTO countries (countryID,countryName,countryEnabled,countryFreeShip,countryTax,countryOrder,countryZone,countryLCID,countryCurrency,countryCode) VALUES (215,'Puerto Rico',0,0,0,0,3,0,'USD','PR')") or print_sql_error();
}

print('Checking for USPS Methods upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT * FROM uspsmethods") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding USPS Methods table.<br>');
	$sSQL = "CREATE TABLE uspsmethods (uspsID INT PRIMARY KEY,";
	$sSQL .= "uspsMethod VARCHAR(150) NOT NULL,";
	$sSQL .= "uspsShowAs VARCHAR(150) NOT NULL,";
	$sSQL .= "uspsUseMethod TINYINT DEFAULT 0,";
	$sSQL .= "uspsLocal TINYINT DEFAULT 0)";
	mysql_query($sSQL);
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (1,'EXPRESS','Express Mail',0,1)");
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (2,'PRIORITY','Priority Mail',0,1)");
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (3,'PARCEL','Parcel Post',1,1)");
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (4,'Global Express Guaranteed Document Service','Global Express Guaranteed',0,0)");
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (5,'Global Express Guaranteed Non-Document Service','Global Express Guaranteed',0,0)");
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (6,'Global Express Mail (EMS)','Global Express Mail (EMS)',0,0)");
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (7,'Global Priority Mail - Flat-rate Envelope (Large)','Global Priority Mail',0,0)");
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (8,'Global Priority Mail - Flat-rate Envelope (Small)','Global Priority Mail',0,0)");
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (9,'Global Priority Mail - Variable Weight Envelope (Single)','Global Priority Mail',0,0)");
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (10,'Airmail Letter-post','Airmail Letter Post',0,0)");
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (11,'Airmail Parcel Post','Airmail Parcel Post',1,0)");
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (12,'Economy (Surface) Letter-post','Economy (Surface) Letter Post',0,0)");
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (13,'Economy (Surface) Parcel Post','Economy (Surface) Parcel Post',1,0)");
}

mysql_query("UPDATE uspsmethods SET uspsMethod='Global Priority Mail - Flat-rate Envelope (Large)' WHERE uspsID=7");
mysql_query("UPDATE uspsmethods SET uspsMethod='Global Priority Mail - Flat-rate Envelope (Small)' WHERE uspsID=8");
mysql_query("UPDATE uspsmethods SET uspsMethod='Global Priority Mail - Variable Weight Envelope (Single)' WHERE uspsID=9");
mysql_query("UPDATE uspsmethods SET uspsMethod='Airmail Letter-post' WHERE uspsID=10");
mysql_query("UPDATE uspsmethods SET uspsMethod='Economy (Surface) Letter-post' WHERE uspsID=12");

print('Checking for UPS Methods upgrade...<br>');
$columnexists=TRUE;
$result = mysql_query("SELECT uspsID FROM uspsmethods WHERE uspsID=101") or print_sql_error();
if(mysql_num_rows($result)==0){
	print('Adding UPS Methods info.<br>');
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (101,'01','UPS Next Day Air&reg;',1,1)");
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (102,'02','UPS 2nd Day Air&reg;',1,1)");
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (103,'03','UPS Ground',1,1)");
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (104,'07','UPS Worldwide Express',1,1)");
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (105,'08','UPS Worldwide Expedited',1,1)");
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (106,'11','UPS Standard',1,1)");
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (107,'12','UPS 3 Day Select&reg;',1,1)");
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (108,'13','UPS Next Day Air Saver&reg;',1,1)");
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (109,'14','UPS Next Day Air&reg; Early A.M.&reg;',1,1)");
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (110,'54','UPS Worldwide Express Plus',1,1)");
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (111,'59','UPS 2nd Day Air A.M.&reg;',1,1)");
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (112,'65','UPS Express Saver',1,1)");
}

print('Checking for Media Mail upgrade...<br>');
$columnexists=TRUE;
$result = mysql_query("SELECT uspsID FROM uspsmethods WHERE uspsID=14") or print_sql_error();
if(mysql_num_rows($result)==0){
	print('Adding Media Mail info.<br>');
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (14,'Media','Media Mail',0,1)") or print_sql_error();
}

print('Checking for Bound Printed Matter upgrade...<br>');
$columnexists=TRUE;
$result = mysql_query("SELECT uspsID FROM uspsmethods WHERE uspsID=15") or print_sql_error();
if(mysql_num_rows($result)==0){
	print('Adding Bound Printed Matter info.<br>');
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (15,'BPM','Bound Printed Matter',0,1)") or print_sql_error();
}

print('Checking for First-Class Mail upgrade...<br>');
$columnexists=TRUE;
$result = mysql_query("SELECT uspsID FROM uspsmethods WHERE uspsID=16") or print_sql_error();
if(mysql_num_rows($result)==0){
	print('Adding First-Class Mail info.<br>');
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (16,'FIRSTCLASS','First-Class Mail',0,1)") or print_sql_error();
}
flush();

print('Checking for U(S)PS FSA upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT uspsFSA FROM uspsmethods") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding U(S)PS FSA columns.<br>');
	mysql_query("ALTER TABLE uspsmethods ADD COLUMN uspsFSA TINYINT DEFAULT 0") or print_sql_error();
	mysql_query("UPDATE uspsmethods SET uspsFSA=0") or print_sql_error();
	mysql_query("UPDATE uspsmethods SET uspsFSA=1 WHERE uspsID=103 OR uspsID=3") or print_sql_error();
	mysql_query("ALTER TABLE postalzones ADD COLUMN pzFSA INT DEFAULT 1") or print_sql_error();
	mysql_query("UPDATE postalzones SET pzFSA=1") or print_sql_error();
}

print('Checking for List Price upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT COUNT(pListPrice) FROM products") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding List Price column.<br>');
	mysql_query("ALTER TABLE products ADD COLUMN pListPrice DOUBLE DEFAULT 0") or print_sql_error();
	mysql_query("UPDATE products SET pListPrice=0") or print_sql_error();
}

// These are additions for template versions v2.5.0

print('Checking for pay provider order upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT payProvOrder FROM payprovider") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding pay provider order column.<br>');
	mysql_query("ALTER TABLE payprovider ADD COLUMN payProvOrder INT DEFAULT 0") or print_sql_error();
	$sSQL = "SELECT payProvID FROM payprovider";
	$index=0;
	$result = mysql_query($sSQL) or print(mysql_error());
	while($rs = mysql_fetch_assoc($result)){
		$sSQL = "UPDATE payprovider SET payProvOrder=".$index." WHERE payProvID=" . $rs["payProvID"];
		mysql_query($sSQL) or print_sql_error();
		$index++;
	}
}

$columnexists=TRUE;
mysql_query("SELECT * FROM topsections") or $columnexists=FALSE;
if($columnexists){ // If it's there, have to add the column. If not then no worries as it is going to get deleted.
	print('Checking for top category order upgrade...<br>');
	$columnexists=TRUE;
	mysql_query("SELECT tsOrder FROM topsections") or $columnexists=FALSE;
	if($columnexists==FALSE){
		print('Adding top category order column.<br>');
		mysql_query("ALTER TABLE topsections ADD COLUMN tsOrder INT DEFAULT 0") or print_sql_error();
		$sSQL = "SELECT tsID FROM topsections ORDER BY tsName";
		$index=0;
		$result = mysql_query($sSQL) or print(mysql_error());
		while($rs = mysql_fetch_assoc($result)){
			$sSQL = "UPDATE topsections SET tsOrder=".$index." WHERE tsID=" . $rs["tsID"];
			mysql_query($sSQL) or print_sql_error();
			$index++;
		}
	}
}
flush();

print('Checking for category order upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT sectionOrder FROM sections") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding category order column.<br>');
	mysql_query("ALTER TABLE sections ADD COLUMN sectionOrder INT DEFAULT 0") or print_sql_error();
	$sSQL = "SELECT sectionID FROM sections ORDER BY sectionName";
	$index=0;
	$result = mysql_query($sSQL) or print(mysql_error());
	while($rs = mysql_fetch_assoc($result)){
		$sSQL = "UPDATE sections SET sectionOrder=".$index." WHERE sectionID=" . $rs["sectionID"];
		mysql_query($sSQL) or print_sql_error();
		$index++;
	}
}

print('Checking for disabled section upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT sectionDisabled FROM sections") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding disabled section column.<br>');
	mysql_query("ALTER TABLE sections ADD COLUMN sectionDisabled TINYINT DEFAULT 0") or print_sql_error();
	mysql_query("UPDATE sections SET sectionDisabled=0") or print_sql_error();
}

print('Checking for VeriSign PayFlow Link upgrade...<br>');
$columnexists=TRUE;
$result = mysql_query("SELECT payProvID FROM payprovider WHERE payProvID=8") or print_sql_error();
if(mysql_num_rows($result)==0){
	print('Adding VeriSign PayFlow Link info.<br>');
	mysql_query("INSERT INTO payprovider (payProvID,payProvName,payProvShow,payProvEnabled,payProvAvailable,payProvDemo,payProvData1,payProvData2,payProvOrder) VALUES (8,'Payflow Link','Credit Card',0,1,0,'','',8)") or print_sql_error();
}

print('Checking for SECPay upgrade...<br>');
$columnexists=TRUE;
$result = mysql_query("SELECT payProvID FROM payprovider WHERE payProvID=9") or print_sql_error();
if(mysql_num_rows($result)==0){
	print('Adding SECPay info.<br>');
	mysql_query("INSERT INTO payprovider (payProvID,payProvName,payProvShow,payProvEnabled,payProvAvailable,payProvDemo,payProvData1,payProvData2,payProvOrder) VALUES (9,'SECPay','Credit Card',0,1,0,'','',9)") or print_sql_error();
}
flush();

print('Checking for Capture Card upgrade...<br>');
$columnexists=TRUE;
$result = mysql_query("SELECT payProvID FROM payprovider WHERE payProvID=10") or print_sql_error();
if(mysql_num_rows($result)==0){
	print('Adding Capture Card info.<br>');
	mysql_query("INSERT INTO payprovider (payProvID,payProvName,payProvShow,payProvEnabled,payProvAvailable,payProvDemo,payProvData1,payProvData2,payProvOrder) VALUES (10,'Capture Card','Credit Card',0,1,0,'XXXXXOOOOOOO','',10)") or print_sql_error();
}

print('Checking for PSiGate upgrade...<br>');
$columnexists=TRUE;
$result = mysql_query("SELECT payProvID FROM payprovider WHERE payProvID=11") or print_sql_error();
if(mysql_num_rows($result)==0){
	print('Adding PSiGate info.<br>');
	mysql_query("INSERT INTO payprovider (payProvID,payProvName,payProvShow,payProvEnabled,payProvAvailable,payProvDemo,payProvData1,payProvData2,payProvOrder) VALUES (11,'PSiGate','Credit Card',0,1,0,'','',11)") or print_sql_error();
}

print('Checking for PSiGate SSL upgrade...<br>');
$columnexists=TRUE;
$result = mysql_query("SELECT payProvID FROM payprovider WHERE payProvID=12") or print_sql_error();
if(mysql_num_rows($result)==0){
	print('Adding PSiGate SSL info.<br>');
	mysql_query("INSERT INTO payprovider (payProvID,payProvName,payProvShow,payProvEnabled,payProvAvailable,payProvDemo,payProvData1,payProvData2,payProvOrder) VALUES (12,'PSiGate SSL','Credit Card',0,1,0,'','',12)") or print_sql_error();
}

print('Checking for Auth.NET AIM upgrade...<br>');
$columnexists=TRUE;
$result = mysql_query("SELECT payProvID FROM payprovider WHERE payProvID=13") or print_sql_error();
if(mysql_num_rows($result)==0){
	print('Adding Auth.NET AIM info.<br>');
	mysql_query("INSERT INTO payprovider (payProvID,payProvName,payProvShow,payProvEnabled,payProvAvailable,payProvDemo,payProvData1,payProvData2,payProvOrder) VALUES (13,'Auth.NET AIM','Credit Card',0,1,0,'','',13)") or print_sql_error();
}
flush();

print('Checking for Custom Pay Provider upgrade...<br>');
$columnexists=TRUE;
$result = mysql_query("SELECT payProvID FROM payprovider WHERE payProvID=14") or print_sql_error();
if(mysql_num_rows($result)==0){
	print('Adding Custom Pay Provider info.<br>');
	mysql_query("INSERT INTO payprovider (payProvID,payProvName,payProvShow,payProvEnabled,payProvAvailable,payProvDemo,payProvData1,payProvData2,payProvOrder) VALUES (14,'Custom','Credit Card',0,1,0,'','',14)") or print_sql_error();
}

print('Checking for Netbanx upgrade...<br>');
$columnexists=TRUE;
$result = mysql_query("SELECT payProvID FROM payprovider WHERE payProvID=15") or print_sql_error();
if(mysql_num_rows($result)==0){
	print('Adding Netbanx info.<br>');
	mysql_query("INSERT INTO payprovider (payProvID,payProvName,payProvShow,payProvEnabled,payProvAvailable,payProvDemo,payProvData1,payProvData2,payProvOrder) VALUES (15,'Netbanx','Credit Card',0,1,0,'','',15)") or print_sql_error();
}

print('Checking for Linkpoint upgrade...<br>');
$columnexists=TRUE;
$result = mysql_query("SELECT payProvID FROM payprovider WHERE payProvID=16") or print_sql_error();
if(mysql_num_rows($result)==0){
	print('Adding Netbanx info.<br>');
	mysql_query("INSERT INTO payprovider (payProvID,payProvName,payProvShow,payProvEnabled,payProvAvailable,payProvDemo,payProvData1,payProvData2,payProvOrder) VALUES (16,'Linkpoint','Credit Card',0,1,0,'','',16)") or print_sql_error();
}

print('Checking for options weight difference upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT optWeightDiff FROM options") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding options weight difference column.<br>');
	mysql_query("ALTER TABLE options ADD COLUMN optWeightDiff DOUBLE DEFAULT 0") or print_sql_error();
	mysql_query("UPDATE options SET optWeightDiff=0") or print_sql_error();
}

print('Checking for options wholesale price difference upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT optWholesalePriceDiff FROM options") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding options wholesale price difference column.<br>');
	mysql_query("ALTER TABLE options ADD COLUMN optWholesalePriceDiff DOUBLE DEFAULT 0") or print_sql_error();
	mysql_query("UPDATE options SET optWholesalePriceDiff=optPriceDiff") or print_sql_error();
}

print('Checking for stock options upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT optStock FROM options") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding stock options column.<br>');
	mysql_query("ALTER TABLE options ADD COLUMN optStock INT DEFAULT 0") or print_sql_error();
	mysql_query("UPDATE options SET optStock=0") or print_sql_error();
}

print('Checking for cartoptions weight difference upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT coWeightDiff FROM cartoptions") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding cartoptions weight difference column.<br>');
	mysql_query("ALTER TABLE cartoptions ADD COLUMN coWeightDiff DOUBLE DEFAULT 0") or print_sql_error();
	mysql_query("UPDATE cartoptions SET coWeightDiff=0") or print_sql_error();
}
flush();

// These are additions for template versions v2.0.2

print('Updating countries table data<br>');
mysql_query("UPDATE countries SET countryLCID='it_IT' WHERE countryID=93") or print_sql_error();
mysql_query("UPDATE countries SET countryLCID='fr_FR' WHERE countryID=65") or print_sql_error();
mysql_query("UPDATE countries SET countryLCID='de_DE' WHERE countryID=71") or print_sql_error();
mysql_query("UPDATE countries SET countryLCID='es_ES' WHERE countryID=175") or print_sql_error();
mysql_query("UPDATE countries SET countryLCID='da_DK' WHERE countryID=50") or print_sql_error();
mysql_query("UPDATE countries SET countryLCID='su_FI' WHERE countryID=64") or print_sql_error();
mysql_query("UPDATE countries SET countryLCID='el_GR' WHERE countryID=74") or print_sql_error();
mysql_query("UPDATE countries SET countryLCID='jp_JP' WHERE countryID=95") or print_sql_error();
mysql_query("UPDATE countries SET countryLCID='nl_NL' WHERE countryID=133") or print_sql_error();
mysql_query("UPDATE countries SET countryLCID='no_NO' WHERE countryID=143") or print_sql_error();
mysql_query("UPDATE countries SET countryLCID='pt_PT' WHERE countryID=153") or print_sql_error();
mysql_query("UPDATE countries SET countryLCID='en_ZA' WHERE countryID=174") or print_sql_error();
mysql_query("UPDATE countries SET countryLCID='sv_SE' WHERE countryID=182") or print_sql_error();

print('Checking for affilites upgrade...<br>');

$columnexists=TRUE;
mysql_query("SELECT affilID FROM affiliates") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding affiliates table<br>');
	$sSQL = "CREATE TABLE affiliates (affilID VARCHAR(32) NOT NULL PRIMARY KEY,";
	$sSQL .= "affilPW VARCHAR(32),";
	$sSQL .= "affilEmail VARCHAR(128),";
	$sSQL .= "affilName VARCHAR(255),";
	$sSQL .= "affilAddress VARCHAR(255),";
	$sSQL .= "affilCity VARCHAR(255),";
	$sSQL .= "affilState VARCHAR(255),";
	$sSQL .= "affilZip VARCHAR(255),";
	$sSQL .= "affilCountry VARCHAR(255),";
	$sSQL .= "affilInform TINYINT DEFAULT 0,";
	$sSQL .= "INDEX (affilID), UNIQUE (affilID))";

	mysql_query($sSQL) or print_sql_error();
}

print('Checking for Affiliate Commission Column...<br>');
$columnexists=TRUE;
mysql_query("SELECT affilCommision FROM affiliates") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding Affiliate Commission Column.<br>');
	mysql_query("ALTER TABLE affiliates ADD COLUMN affilCommision DOUBLE DEFAULT 0") or print_sql_error();
	mysql_query("UPDATE affiliates SET affilCommision=0") or print_sql_error();
}

print('Checking for Affiliate order upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT ordAffiliate FROM orders") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding ordAffiliate column.<br>');
	mysql_query("ALTER TABLE orders ADD COLUMN ordAffiliate VARCHAR(50)") or print_sql_error();
}

$columnexists=FALSE;
$result = mysql_query("SHOW COLUMNS FROM products") or print_sql_error();
while($rs = mysql_fetch_array($result)){
	if($rs[0]=="pDescription" && strtolower(substr($rs[1], 0, 7))=="varchar")
		$columnexists=TRUE;
}
if($columnexists){
	print('Updating product description column<br>');
	mysql_query("ALTER TABLE products MODIFY pDescription TEXT NULL") or print_sql_error();
}

flush();

print('Checking for IP address upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT ordIP FROM orders") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding IP Address column.<br>');
	mysql_query("ALTER TABLE orders ADD COLUMN ordIP VARCHAR(50)") or print_sql_error();
}

print('Checking for Multiple Shipping Method upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT pzMultiShipping FROM postalzones") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding pzMultiShipping column.<br>');
	mysql_query("ALTER TABLE postalzones ADD COLUMN pzMultiShipping TINYINT DEFAULT 0") or print_sql_error();
	mysql_query("UPDATE postalzones SET pzMultiShipping=0") or print_sql_error();
}
// A previous version did not set a default value for pzMultiShipping
mysql_query("ALTER TABLE postalzones MODIFY pzMultiShipping TINYINT DEFAULT 0") or print_sql_error();
mysql_query("UPDATE postalzones SET pzMultiShipping=0 WHERE pzMultiShipping IS NULL") or print_sql_error();

print('Checking for Extra Shipping Methods upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT pzMethodName1 FROM postalzones") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding Extra Shipping Methods columns.<br>');
	mysql_query("ALTER TABLE postalzones ADD COLUMN pzMethodName1 VARCHAR(255) NULL") or print_sql_error();
	mysql_query("ALTER TABLE postalzones ADD COLUMN pzMethodName2 VARCHAR(255) NULL") or print_sql_error();
	mysql_query("ALTER TABLE postalzones ADD COLUMN pzMethodName3 VARCHAR(255) NULL") or print_sql_error();
	mysql_query("ALTER TABLE postalzones ADD COLUMN pzMethodName4 VARCHAR(255) NULL") or print_sql_error();
	mysql_query("ALTER TABLE postalzones ADD COLUMN pzMethodName5 VARCHAR(255) NULL") or print_sql_error();
	mysql_query("UPDATE postalzones SET pzMethodName1='".@$standardship."'") or print_sql_error();
	mysql_query("UPDATE postalzones SET pzMethodName2='".@$expressship."'") or print_sql_error();
	mysql_query("ALTER TABLE zonecharges ADD COLUMN zcRate3 DOUBLE DEFAULT 0") or print_sql_error();
	mysql_query("ALTER TABLE zonecharges ADD COLUMN zcRate4 DOUBLE DEFAULT 0") or print_sql_error();
	mysql_query("ALTER TABLE zonecharges ADD COLUMN zcRate5 DOUBLE DEFAULT 0") or print_sql_error();
	mysql_query("UPDATE zonecharges SET zcRate3=0,zcRate4=0,zcRate5=0") or print_sql_error();
}

print('Checking for Multiple Shipping Method Charges upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT zcRate2 FROM zonecharges") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding zcRate2 column.<br>');
	mysql_query("ALTER TABLE zonecharges ADD COLUMN zcRate2 DOUBLE") or print_sql_error();
	mysql_query("UPDATE zonecharges SET zonecharges.zcRate2=zonecharges.zcRate") or print_sql_error();
}

$columnexists=FALSE;
$result = mysql_query("SHOW COLUMNS FROM sections") or print_sql_error();
while($rs = mysql_fetch_array($result)){
	if($rs[0]=="sectionDescription" && strtolower(substr($rs[1], 0, 7))=="varchar")
		$columnexists=TRUE;
}
if($columnexists){
	print('Updating section description column<br>');
	mysql_query("ALTER TABLE sections MODIFY sectionDescription TEXT NULL") or print_sql_error();
}

print('Checking for Unlimited Top Categories upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT rootSection FROM sections") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding Unlimited Top Categories column...<br>');
	mysql_query("ALTER TABLE sections ADD COLUMN sectionWorkingName VARCHAR(255) NULL") or print_sql_error();
	mysql_query("ALTER TABLE sections ADD COLUMN rootSection TINYINT DEFAULT 0") or print_sql_error();
	mysql_query("UPDATE sections SET rootSection=1") or print_sql_error();
	$result = mysql_query("SELECT adminSubCats FROM admin") or print_sql_error();
	$rs = mysql_fetch_array($result);
	$subCats=((int)($rs["adminSubCats"])==1);
	if($subCats){
		$addcomma = "";
		$tslist="";
		$result = mysql_query("SELECT DISTINCT topSection FROM sections") or print_sql_error();
		while($rs = mysql_fetch_assoc($result)){
			$tslist = $rs["topSection"] . $addcomma . $tslist;
			$addcomma = ",";
		}
		if($tslist!=""){
			$result = mysql_query("SELECT tsID,tsName,tsImage,tsOrder,tsDescription FROM topsections WHERE tsID IN (" . $tslist . ")") or print_sql_error();
			while($rs = mysql_fetch_assoc($result)){
				mysql_query("INSERT INTO sections (sectionName,sectionImage,sectionOrder,sectionDescription,rootSection,topSection) VALUES ('".mysql_escape_string($rs["tsName"])."','".$rs["tsImage"]."',".$rs["tsOrder"].",'".mysql_escape_string($rs["tsDescription"])."',0,0)") or print_sql_error();
				$iID = mysql_insert_id();
				mysql_query("UPDATE sections SET rootSection=2,topSection=" . $iID . " WHERE topSection=" . $rs["tsID"] . " AND rootSection<>2") or print_sql_error();
				mysql_query("UPDATE cpnassign SET cpaType=1,cpaAssignment='" . $iID . "' WHERE cpaAssignment='" . $rs["tsID"] . "' AND cpaType=0") or print_sql_error();
			}
			mysql_query("UPDATE sections SET rootSection=1 WHERE rootSection=2") or print_sql_error();
		}
	}
	mysql_query("DELETE FROM cpnassign WHERE cpaType=0") or print_sql_error();
	mysql_query("DROP TABLE topsections") or print_sql_error();
	mysql_query("UPDATE sections SET sectionWorkingName=sectionName") or print_sql_error();
	mysql_query("ALTER TABLE admin DROP COLUMN adminSubCats") or print_sql_error();
}

// All updates for version v4.7.0 and above below here

print('Checking for VeriSign PayFlow Pro upgrade...<br>');
$columnexists=TRUE;
$result = mysql_query("SELECT payProvID FROM payprovider WHERE payProvID=7") or print_sql_error();
if(mysql_num_rows($result)==0){
	print('Adding VeriSign PayFlow Pro info.<br>');
	mysql_query("INSERT INTO payprovider (payProvID,payProvName,payProvShow,payProvEnabled,payProvAvailable,payProvDemo,payProvData1,payProvData2,payProvOrder) VALUES (7,'Payflow Pro','Credit Card',0,1,1,'','',7)") or print_sql_error();
}

print('Checking for Price Break upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT * FROM pricebreaks") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding Price Break table...<br>');
	mysql_query("CREATE TABLE pricebreaks (pbQuantity INT NOT NULL,pbProdID VARCHAR(255) NOT NULL,pPrice DOUBLE DEFAULT 0,pWholesalePrice DOUBLE DEFAULT 0,PRIMARY KEY(pbProdID,pbQuantity))");
}

print('Checking for product dimensions upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT pDims FROM products") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding product dimensions column.<br>');
	mysql_query("ALTER TABLE products ADD COLUMN pDims VARCHAR(255) NULL") or print_sql_error();
}

print('Checking for Canada Post Methods upgrade...<br>');
$columnexists=TRUE;
$result = mysql_query("SELECT uspsID FROM uspsmethods WHERE uspsID=201") or print_sql_error();
if(mysql_num_rows($result)==0){
	print('Adding Canada Post Methods info.<br>');
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal,uspsFSA) VALUES (201,'1010','Regular',1,1,1)") or print_sql_error();
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (202,'1020','Expedited',1,1)") or print_sql_error();
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (203,'1030','Xpresspost',1,1)") or print_sql_error();
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (204,'1040','Priority Courier',1,1)") or print_sql_error();
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (205,'1120','Expedited Evening',1,1)") or print_sql_error();
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (206,'1130','XpressPost Evening',1,1)") or print_sql_error();
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (207,'1220','Expedited Saturday',1,1)") or print_sql_error();
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (208,'1230','XpressPost Saturday',1,1)") or print_sql_error();
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (210,'2005','Small Packets Surface',1,0)") or print_sql_error();
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (211,'2010','Surface USA',1,0)") or print_sql_error();
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (212,'2015','Small Packets Air USA',1,0)") or print_sql_error();
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (213,'2020','Air USA',1,0)") or print_sql_error();
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (214,'2025','Expedited USA Commercial',1,0)") or print_sql_error();
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (215,'2030','XPressPost USA',1,0)") or print_sql_error();
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (216,'2040','Purolator USA',1,0)") or print_sql_error();
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (217,'2050','PuroPak USA',1,0)") or print_sql_error();
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (218,'3005','Small Packets Surface International',1,0)") or print_sql_error();
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (221,'3010','Parcel Surface International',1,0)") or print_sql_error();
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (222,'3015','Small Packets Air International',1,0)") or print_sql_error();
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (223,'3020','Air International',1,0)") or print_sql_error();
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (224,'3025','XPressPost International',1,0)") or print_sql_error();
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (225,'3040','Purolator International',1,0)") or print_sql_error();
	mysql_query("INSERT INTO uspsmethods (uspsID,uspsMethod,uspsShowAs,uspsUseMethod,uspsLocal) VALUES (226,'3050','PuroPak International',1,0)") or print_sql_error();
}

print('Checking for Email 2 upgrade...<br>');
$columnexists=TRUE;
$result = mysql_query("SELECT payProvID FROM payprovider WHERE payProvID=17") or print_sql_error();
if(mysql_num_rows($result)==0){
	print('Adding Email 2 info.<br>');
	mysql_query("INSERT INTO payprovider (payProvID,payProvName,payProvShow,payProvEnabled,payProvAvailable,payProvDemo,payProvData1,payProvData2,payProvOrder) VALUES (17,'Email 2','Email 2',0,1,0,'','',17)") or print_sql_error();
}

print('Checking for IP Deny upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT * FROM multibuyblock") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding IP Deny table...<br>');
	mysql_query("CREATE TABLE multibuyblock (ssdenyid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,ssdenyip VARCHAR(255) NOT NULL,sstimesaccess INT DEFAULT 0,lastaccess DATETIME, INDEX (ssdenyid), UNIQUE (ssdenyid), INDEX (ssdenyip), UNIQUE (ssdenyip))") or print_sql_error();
	mysql_query("CREATE TABLE ipblocking (dcid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,dcip1 INT DEFAULT 0,dcip2 INT DEFAULT 0, INDEX (dcid), UNIQUE (dcid))") or print_sql_error();
	mysql_query("UPDATE sections SET sectionDisabled=127 WHERE sectionDisabled=1")  or print_sql_error();
}

print('Checking for multi language upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT adminlanguages FROM admin") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding multi language columns.<br>');
	mysql_query("ALTER TABLE admin ADD COLUMN adminlanguages INT DEFAULT 0") or print_sql_error();
	mysql_query("ALTER TABLE admin ADD COLUMN adminlangsettings INT DEFAULT 0") or print_sql_error();
	mysql_query("ALTER TABLE countries ADD COLUMN countryName2 VARCHAR(255) NULL") or print_sql_error();
	mysql_query("ALTER TABLE countries ADD COLUMN countryName3 VARCHAR(255) NULL") or print_sql_error();
	mysql_query("ALTER TABLE optiongroup ADD COLUMN optGrpName2 VARCHAR(255) NULL") or print_sql_error();
	mysql_query("ALTER TABLE optiongroup ADD COLUMN optGrpName3 VARCHAR(255) NULL") or print_sql_error();
	mysql_query("ALTER TABLE options ADD COLUMN optName2 VARCHAR(255) NULL") or print_sql_error();
	mysql_query("ALTER TABLE options ADD COLUMN optName3 VARCHAR(255) NULL") or print_sql_error();
	mysql_query("ALTER TABLE orderstatus ADD COLUMN statPublic2 VARCHAR(255) NULL") or print_sql_error();
	mysql_query("ALTER TABLE orderstatus ADD COLUMN statPublic3 VARCHAR(255) NULL") or print_sql_error();
	mysql_query("ALTER TABLE payprovider ADD COLUMN payProvShow2 VARCHAR(255) NULL") or print_sql_error();
	mysql_query("ALTER TABLE payprovider ADD COLUMN payProvShow3 VARCHAR(255) NULL") or print_sql_error();
	mysql_query("ALTER TABLE products ADD COLUMN pName2 VARCHAR(255) NULL") or print_sql_error();
	mysql_query("ALTER TABLE products ADD COLUMN pName3 VARCHAR(255) NULL") or print_sql_error();
	mysql_query("ALTER TABLE products ADD COLUMN pDescription2 TEXT NULL") or print_sql_error();
	mysql_query("ALTER TABLE products ADD COLUMN pDescription3 TEXT NULL") or print_sql_error();
	mysql_query("ALTER TABLE products ADD COLUMN pLongDescription2 TEXT NULL") or print_sql_error();
	mysql_query("ALTER TABLE products ADD COLUMN pLongDescription3 TEXT NULL") or print_sql_error();
	mysql_query("ALTER TABLE products ADD COLUMN pTax DOUBLE NULL") or print_sql_error();
	mysql_query("ALTER TABLE sections ADD COLUMN sectionName2 VARCHAR(255) NULL") or print_sql_error();
	mysql_query("ALTER TABLE sections ADD COLUMN sectionName3 VARCHAR(255) NULL") or print_sql_error();
	mysql_query("ALTER TABLE sections ADD COLUMN sectionDescription2 TEXT NULL") or print_sql_error();
	mysql_query("ALTER TABLE sections ADD COLUMN sectionDescription3 TEXT NULL") or print_sql_error();
	mysql_query("UPDATE admin SET adminlanguages=0,adminlangsettings=0") or print_sql_error();
	mysql_query("UPDATE countries SET countryName2=countryName,countryName3=countryName") or print_sql_error();
	mysql_query("UPDATE orderstatus SET statPublic2=statPublic,statPublic3=statPublic") or print_sql_error();
	mysql_query("UPDATE payprovider SET payProvShow2=payProvShow,payProvShow3=payProvShow") or print_sql_error();
	mysql_query("UPDATE countries SET countryOrder=1 WHERE countryOrder=2") or print_sql_error();
	mysql_query("UPDATE countries SET countryOrder=2 WHERE countryName='" . mysql_escape_string($xxCntryTxt) . "' OR countryName='" . mysql_escape_string($xxCntryTxt2) . "'") or print_sql_error();
	mysql_query("ALTER TABLE coupons ADD COLUMN cpnName VARCHAR(255) NULL") or print_sql_error();
	mysql_query("ALTER TABLE coupons ADD COLUMN cpnName2 VARCHAR(255) NULL") or print_sql_error();
	mysql_query("ALTER TABLE coupons ADD COLUMN cpnName3 VARCHAR(255) NULL") or print_sql_error();
	mysql_query("UPDATE coupons SET cpnName=cpnWorkingName") or print_sql_error();

	$sSQL = "SELECT adminShipping FROM admin WHERE adminID=1";
	$result = mysql_query($sSQL) or print(mysql_error());
	$rs = mysql_fetch_assoc($result);
	$shipType = (int)$rs["adminShipping"];
	if($shipType==3){
		// Convert lbs + Oz to lbs.Oz
		$sSQL = "SELECT pID,pWeight FROM products";
		$result = mysql_query($sSQL) or print(mysql_error());
		while($rs = mysql_fetch_assoc($result)){
			$pWeight = $rs["pWeight"];
			$pWeight = (int)$pWeight + (($pWeight - (int)$pWeight) / 0.16);
			mysql_query("UPDATE products SET pWeight=" . $pWeight . " WHERE pID='" . mysql_escape_string($rs["pID"]) . "'") or print_sql_error();
		}
		$sSQL = "SELECT optID,optWeightDiff FROM options INNER JOIN optiongroup ON options.optGroup=optiongroup.optGrpID WHERE (optType=2 OR optType=-2) AND optFlags<2";
		$result = mysql_query($sSQL) or print(mysql_error());
		while($rs = mysql_fetch_assoc($result)){
			$iPounds=(int)$rs["optWeightDiff"];
			$iOunces = $iPounds*16+round(((double)$rs["optWeightDiff"]-(double)$iPounds)*100,2);
			mysql_query("UPDATE options SET optWeightDiff=".($iOunces/16.0)." WHERE optID=" . $rs["optID"]);
		}
	}
}

print('Checking for dropshipper upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT * FROM dropshipper") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding dropshipper table...<br>');
	mysql_query("CREATE TABLE dropshipper (dsID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,dsName VARCHAR(255) NULL,dsEmail VARCHAR(255) NULL,dsAddress VARCHAR(255) NULL,dsCity VARCHAR(255) NULL,dsState VARCHAR(255) NULL,dsZip VARCHAR(255) NULL,dsCountry VARCHAR(255) NULL,dsAction INT DEFAULT 0)");
	mysql_query("ALTER TABLE products ADD COLUMN pDropship INT DEFAULT 0") or print_sql_error();
	mysql_query("UPDATE products SET pDropship=0");
}

print('Checking for Trans ID upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT ordTransID FROM orders WHERE ordID=1") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding Trans ID column...<br>');
	mysql_query("ALTER TABLE orders DROP COLUMN ordDemoMode") or print_sql_error();
	mysql_query("ALTER TABLE orders ADD COLUMN ordTransID VARCHAR(255) NULL") or print_sql_error();
}

mysql_query("DELETE FROM admin WHERE adminID<>1") or print_sql_error();

print('Checking for discount repeat upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT cpnThresholdRepeat FROM coupons") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding discount repeat columns.<br>');
	mysql_query("ALTER TABLE coupons ADD COLUMN cpnThresholdRepeat DOUBLE DEFAULT 0") or print_sql_error();
	mysql_query("ALTER TABLE coupons ADD COLUMN cpnQuantityRepeat INT DEFAULT 0") or print_sql_error();
	mysql_query("UPDATE coupons SET cpnThresholdRepeat=cpnThreshold,cpnQuantityRepeat=cpnQuantity") or print_sql_error();
}

print('Checking for category URL upgrade...<br>');
$columnexists=TRUE;
mysql_query("SELECT sectionurl FROM sections") or $columnexists=FALSE;
if($columnexists==FALSE){
	print('Adding category URL column.<br>');
	mysql_query("ALTER TABLE sections ADD COLUMN sectionurl VARCHAR(255) NULL") or print_sql_error();
}
flush();

if($haserrors){
	print('<font color="#FF0000"><b>Completed, but with errors !</b></font><br>');
}else{
	print("Updating version number to 'Ecommerce Plus " . $sVersion . "'...<br>");
	mysql_query("UPDATE admin SET adminVersion='Ecommerce Plus " . $sVersion . "'") or print_sql_error();
	print('<font color="#FF0000"><b>Everything updated succesfully !</b></font><br>');
	print '<meta http-equiv="Refresh" content="2; URL=updatestore.php?posted=2">';
}

mysql_close($dbh);

}elseif(@$_GET["posted"]=="2"){
?>
<table width="100%">
<tr><td align="center" width="100%">
<table width="80%">
<tr><td align="center" width="100%">
<p>&nbsp;</p>
<p>The database upgrade script has completed.</p>
<p>&nbsp;</p>
<p>After updating, please check our updater checklist / troubleshooting section on this page.</p>
<p><a href="http://www.ecommercetemplates.com/updater_info.asp#checklist" target="_blank">http://www.ecommercetemplates.com/updater_info.asp#checklist</a></p>
<p><strong>Please bookmark the above page so you can refer to it if you encounter any problems.</strong></p>
<p>&nbsp;</p>
<p><font color="#FF0000"><b>Please note that database script does not copy the updated scripts to your web. This must be done separately as detailed in the instructions</b></font></p>
<p>&nbsp;</p>
<p>Please now delete this file from your web.</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
</td></tr>
</table>
</td></tr>
</table>
<?php
}else{
?>
<form action="updatestore.php" method="POST">
<input type="hidden" name="posted" value="1">
<table width="100%">
<tr><td align="center" width="100%">
<table width="80%">
<tr><td align="center" width="100%">
<p>&nbsp;</p>
<p>Please click below to start your update.</p>
<p>&nbsp;</p>
<p><font color="#FF0000"><b>Please note that clicking the button below will update your database to the current version. However it will not copy the updated scripts to your web. This must be done separately as detailed in the instructions</b></font></p>
<p>&nbsp;</p>
<p>After performing the update, please delete this file from your web.</p>
<p>&nbsp;</p>
<input type="submit" value="Update Ecommerce Plus to version <?php print $sVersion; ?>">
<p>&nbsp;</p>
<p>&nbsp;</p>
</td></tr>
</table>
</td></tr>
</table>
</form>
<?php
}
?>
</body>
</html>