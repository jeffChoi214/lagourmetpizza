<SCRIPT LANGUAGE="php">
session_cache_limiter('none');
session_start();
</SCRIPT><html dir="ltr">
<head>
<title>Products</title>
<link href="../text.css" rel="stylesheet" type="text/css">

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<SCRIPT LANGUAGE="JavaScript" SRC="dist/js/bootstrap.js"></SCRIPT>
	<link rel="stylesheet"  href="../dist/css/bootstrap.css"/>
	<link rel="sthlesheet"  href="../dist/css/boostrap-theme.css"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href='http://fonts.googleapis.com/css?family=Playfair+Display+SC:400,400italic,700,700italic' rel='stylesheet' type='text/css'>
	<link href='http://fonts.googleapis.com/css?family=Dancing+Script' rel='stylesheet' type='text/css'>
	
</head>

  <div class="navbar navbar-default navbar-fixed-top center-block" id="stylenav">

<div class="container">
<div class="navbar-header">
    <button class="btn navbar-toggle" data-toggle="collapse" data-target="#bar">
        <span class="icon-bar"></span>
         <span class="icon-bar"></span>
         <span class="icon-bar"></span>
    </button>
<a href="../index.html" class="navbar-brand">La Gourmet Pizza</a>
</div><!--navbar-header-->

<div class="navbar-collapse collapse" id="bar">
    <ul class= "nav navbar-nav navbar-center" id="navstyle">
            <li><a href='../index.html#menu1'>Menu</a></li>
             <li><a href='../index.html#about1'>About</a></li>
            <li><a href='../index.html#location1'>Location</a></li>
            <li><a href='../index.html#contact1'>Contact</a></li>
           
            <li><a href='categories.php'>Order Online</a></li>
    </ul>
</div><!--collapsed-->

</div><!--container-->

    </div><!--navbar-->
      
	
<div class="col-lg-12" >
<div class="textfancy">Categories</div>
<SCRIPT LANGUAGE="php">
include "vsadmin/db_conn_open.php";
  include "vsadmin/includes.php";
  include "vsadmin/inc/languagefile.php";
include "vsadmin/inc/incfunctions.php";
// include "includes/menu.htm";
          </SCRIPT>
          <div style="margin-top:75px">
   <SCRIPT LANGUAGE="php">
include "vsadmin/inc/incproducts.php";
    </SCRIPT>   
  </div>
    
     <tr align="center"> 
    <td colspan="6"><SCRIPT LANGUAGE="php">
include "includes/footer.htm";
    </SCRIPT></td>
  </div>

</body>
</html>