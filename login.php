<?php

include_once "/includes/common.php";

if (isset($_GET['command'])) {
    if ($_GET['command'] == "login") {
        login();
    } elseif ($_GET['command'] == "logout") {
        logout();
    } elseif ($_GET['command'] == "addNewUser") {
        addNewUser();
    } 
} elseif (isset($_POST['email'])) {
    sendemail();
    echo "mail sent";
    die();
}

include_once "/includes/header.php";

?>

<body>
<table class="maintable" align="center">
    
<tr><td><?php getPageHeader(); ?></td></tr>
<tr><td></td></tr>
<tr><td><?php 

if (isset($_GET['command'])) {
    if ($_GET['command'] == "addNewUserScreen") {
                
        openTable("Create new user and send email invitation:");
        addNewUserScreen();
        closeTable();

    }
} else {
    openTable("User Login:");
    loginScreen();
    closeTable();
}

?></td></tr>
<tr><td></td></tr>
<tr><td><?php  getFooter(); ?></td></tr>
</table>
</body>
</html>

