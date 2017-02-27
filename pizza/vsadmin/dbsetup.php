<?php

include "db_conn_open.php";

$pass = $_POST['password'];
if ($pass == "setup")
{
mysql_query("ALTER TABLE `optiongroup` ADD `checkbox` TINYINT(1) DEFAULT '0' NOT NULL")
or die("MySQL Error - Unable to add checkbox column to optiongroup table.");

print "Setup Successful";

}

else

{
?>
<form action="dbsetup.php" method="POST">
Password: <input name="password" type="password"><br>
<input type="Submit" value="Setup Database">
</form>
<?php
}
?>
