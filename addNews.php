<?php

include_once "/includes/common.php";
include_once "/includes/header.php";

?>
<body>
<table class="maintable" align="center">
    
<tr><td><?php getPageHeader(); ?></td></tr>
<tr><td></td></tr>
<tr><td><?php 

checkLogin();

openTable("All News:");
getAllNews();
closeTable();
 
?></td></tr>
<tr><td></td></tr>
<tr><td><?php  getFooter(); ?></td></tr>
</table>
</body>
</html>
