<SCRIPT LANGUAGE="php">
session_cache_limiter('none');
session_start();
</SCRIPT><html>
<head>
<title>Products</title>
<LINK REL=STYLESHEET TYPE="text/css" HREF="style.css">
</head>

<body bgcolor="#EFECE2">
  <SCRIPT LANGUAGE="php">
include "includes/header.htm";
</SCRIPT>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr> 
    <td width="16"><img src="images/clearpixel.gif" width="16" height="1"></td>
    <td width="124" valign="top"><table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
          <td width="100%"><img src="images/menutop.gif" width="124" height="19"></td>
        </tr>
        <tr>
          <td valign="top" width="100%" height="190"><SCRIPT LANGUAGE="php">
include "vsadmin/db_conn_open.php";
include "vsadmin/includes.php";
include "vsadmin/inc/languagefile.php";
include "vsadmin/inc/incfunctions.php";
include "includes/menu.htm";
</SCRIPT></td>
        </tr>
        <tr>
          <td valign="top" width="100%"><img src="images/menubottom.gif" width="124" height="19"></td>
        </tr>
        <tr>
          <td width="100%"></td>
        </tr>
      </table></td>
    <td width=100% rowspan="2" valign="top">
<SCRIPT LANGUAGE="php">
include "vsadmin/inc/incaffiliate.php";
</SCRIPT>   <p><img border="0" src="images/clearpixel.gif" width="450" height="1">   
    </td>
    <td width="16" background="images/inbg.gif"><img src="images/clearpixel.gif" width="16" height="1"></td>
    <td width="118" bgcolor="#E2DED4" valign="top" align="center"><img src="images/newstop.gif" width="118" height="19"> 
     <SCRIPT LANGUAGE="php">
include "includes/news1.htm";
</SCRIPT>
      <p><img src="images/rightline.gif" width="100" height="1"></p>
       <SCRIPT LANGUAGE="php">
include "includes/rightgraphic.htm";
</SCRIPT>
      <p><img src="images/rightline.gif" width="100" height="1"></p>
      <SCRIPT LANGUAGE="php">
include "includes/news2.htm";
</SCRIPT>
      <p><img src="images/rightline.gif" width="100" height="1"></p>
      <p>&nbsp;</p>
    </td>
    <td width="16" background="images/outbg.gif"><img src="images/clearpixel.gif" width="16" height="1"></td>
  </tr>
  <tr> 
    <td width="16">&nbsp;</td>
    <td width="124">&nbsp;</td>
    <td width="16">&nbsp;</td>
    <td width="118" valign="top"><img src="images/bottomright.gif" width="118" height="18"></td>
    <td width="16" background="images/bottombg.gif">&nbsp;</td>
  </tr>
  <tr align="center"> 
    <td colspan="6"><br>
     <SCRIPT LANGUAGE="php">
include "includes/footer.htm";
</SCRIPT></td>
  </tr>
</table></body>
</html>
