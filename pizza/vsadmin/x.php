<?php

include "db_conn_open.php";

$sSQL = "SELECT adminUser, adminPassword FROM admin WHERE adminID=1";
$result = mysql_query($sSQL) or print(mysql_error());
$rs = mysql_fetch_assoc($result);
print_r($rs);

?>
