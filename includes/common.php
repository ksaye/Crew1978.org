<?php
require_once 'vendor\autoload.php';
require('fpdf.php');
use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Common\ServiceException;
use WindowsAzure\Table\Models\Entity;
use WindowsAzure\Table\Models\EdmType;

try {
    //while (@ob_end_flush());
    }
    catch(ServiceException $e){
        $code = $e->getCode();
    }

// Including the new sendmail file
include './mail/sendmail.php';

$tableRestProxy     = ServicesBuilder::getInstance()->createTableService($_SERVER["CUSTOMCONNSTR_StorageAccount"]);
$tableName          = "crew1978";
$time               = date("Y-m-d h:i:s A");
$ip                 = $_SERVER['REMOTE_ADDR'];
$showcaldays        = 14;
$cookiedays         = 365;
$notificationemail  = "all@crew1978.org";
$administratoremail = "ksaye@saye.org";
$timezone           = "America/Chicago";
$pageTitle          = "Crew 1978, Garland Texas";
date_default_timezone_set($timezone);
$from               = "notifications@crew1978.org";
$emailNotices       = array(3,7);
$isMobile           = FALSE;
$newsCount          = 4;

$timeformat = "m/d/y g:i A";
if (isset($_COOKIE["user"]) ) {
    $username = $_COOKIE["user"];
} else {
    $username = "Anonymous";
}

checkMobile();

updateLastSeen();

checkemailnotice();

function showExistingReservations() {
    global $tableRestProxy, $tableName, $timeformat;

    $filter = "PartitionKey eq 'scheduledSCUBA'";
    
    try {
        $result = $tableRestProxy->queryEntities($tableName, $filter);
    }
    catch(ServiceException $e){
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message."<br />";
    }

    $entities = $result->getEntities();
    uasort($entities, 'startTimeSort');

    echo "<table>"; 
    echo "<tr><td>Time:</td><td>Name:</td><td>Email Address:</td><td>Phone Number:</td></tr>";
    foreach($entities as $entity){
        echo "<tr>";
        echo "<td>".$entity->getProperty("startTime")->getValue()->format($timeformat)."</td>";
        echo "<td>".$entity->getProperty("title")->getValue()."</td>";
        echo "<td>".$entity->getProperty("email")->getValue()."</td>";
        echo "<td>".$entity->getProperty("phone")->getValue()."</td>";
        echo "</tr>";
    }
    echo "</table>";        
}

function submitReservation() {
    global $pageTitle;
    $ServerName = $_SERVER['SERVER_NAME'];
    $encryptedEmail = md5(trim($_POST['email']));

    $reservationEmail = trim($_POST['email']);
    $reservationSession = urlencode(trim($_POST['session']));
    $reservationName = urlencode(trim($_POST['name']));
    $reservationPhone = urlencode(trim($_POST['phone']));
    $emailbody = "To complete the reservation, click the following link:  http://$ServerName/scuba.php?command=completeReservation&name=$reservationName&session=$reservationSession&phone=$reservationPhone&email=$reservationEmail&encrypted=$encryptedEmail \r\n\r\n";
    $emailbody = $emailbody . "If you need to cancel your reservation, click the following link:  http://$ServerName/scuba.php?command=cancelReservation&name=$reservationName&session=$reservationSession&phone=$reservationPhone&email=$reservationEmail&encrypted=$encryptedEmail \r\n\r\n";
    $emailbody = $emailbody . "You can always view up to date information, by clicking this link:  http://$ServerName/scuba \r\n\r\n";
    $emailbody = $emailbody . "Do not reply to this email as it is not a monitored.";
    sendmail($reservationEmail, "scuba@crew1978.org", "[$pageTitle]: Discover SCUBA Reservation", $emailbody);
}

function insertReservation() {
    global $tableRestProxy, $tableName, $timezone, $username, $administratoremail, $pageTitle;
    
    $ServerName = $_SERVER['SERVER_NAME'];
    $reservationEmail = trim($_GET['email']);
    $reservationSession = urldecode(trim($_GET['session']));
    $reservationName = urldecode(trim($_GET['name']));
    $reservationPhone = urldecode(trim($_GET['phone']));

    if ($_GET['encrypted'] == md5($_GET['email'])) {
        $entity = new Entity();
        $entity->setPartitionKey("scheduledSCUBA");
        $entity->setRowKey($reservationName . ' ' . str_replace("/","", $reservationSession));
        $entity->addProperty("title", EdmType::STRING, $reservationName);
        $entity->addProperty("email", EdmType::STRING, $reservationEmail);
        $entity->addProperty("phone", EdmType::STRING, $reservationPhone);
        $entity->addProperty("startTime", EdmType::DATETIME, new DateTime ($reservationSession, new DateTimeZone("UTC")));
       
        try{
            $tableRestProxy->insertEntity($tableName, $entity);
        }
        catch(ServiceException $e){
            // Handle exception based on error codes and messages.
            // Error codes and messages are here:
            // http://msdn.microsoft.com/library/azure/dd179438.aspx
            $code = $e->getCode();
            $error_message = $e->getMessage();
        }
        openTable("");
        echo "<br><b>Your reservation is complete</b><br>";
        closeTable();
        
        $emailbody = "Your reservation at $reservationSession has been confirmed. \r\n\r\n";
        $emailbody = $emailbody . "You can always view up to date information, by clicking this link:  http://$ServerName/scuba \r\n\r\n";
        $emailbody = $emailbody . "Do not reply to this email as it is not a monitored.";
        sendmail($reservationEmail, "scuba@crew1978.org", "[$pageTitle]: Discover SCUBA Reservation", $emailbody);
    } else {
        openTable("");
        echo "<br><b>Your reservation could not be complete.  Please email ".$administratoremail." with any problems.</b><br>";
        closeTable();
    }
}

function removeReservation() {
    global $tableRestProxy, $tableName, $timezone, $username, $administratoremail, $pageTitle;
    
    $ServerName = $_SERVER['SERVER_NAME'];
    $reservationEmail = trim($_GET['email']);
    $reservationSession = urldecode(trim($_GET['session']));
    $reservationName = urldecode(trim($_GET['name']));
    $reservationPhone = urldecode(trim($_GET['phone']));

    if ($_GET['encrypted'] == md5($_GET['email'])) {
        $tableRestProxy->deleteEntity($tableName, "scheduledSCUBA", $reservationName . ' ' . str_replace("/","", $reservationSession));
    
        openTable("");
        echo "<br><b>Your reservation has removed</b><br>";
        closeTable();

        $emailbody = "Your reservation at $reservationSession has been removed. \r\n\r\n";
        $emailbody = $emailbody . "You can always view up to date information, by clicking this link:  http://$ServerName/scuba \r\n\r\n";
        $emailbody = $emailbody . "Do not reply to this email as it is not a monitored.";
        sendmail($reservationEmail, "scuba@crew1978.org", "[$pageTitle]: Discover SCUBA Reservation", $emailbody);
    } else {
        openTable("");
        echo "<br><b>Your reservation could not be removed.  Please email ".$administratoremail." with any problems.</b><br>";
        closeTable();
    }
}

function getReservationCount ($reservationDate) {
    global $tableRestProxy, $tableName, $timeformat;
    $reservationDate = new DateTime($reservationDate->format("m/d/Y g:i A"));
    $reservationDate=$reservationDate->format("Y-m-d\TH:i:00");

    $filter = "PartitionKey eq 'scheduledSCUBA' and startTime eq datetime'$reservationDate'";
    $mycount = 0;

    try {
        $result = $tableRestProxy->queryEntities($tableName, $filter);
    }
    catch(ServiceException $e){
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message."<br />";
    }
    
    $entities = $result->getEntities();

    foreach($entities as $entity){
        $mycount = $mycount + 1;
    }
    return $mycount;
}

function showReservationTable() {
    global $tableRestProxy, $tableName, $timeformat;
    $maxStudents = 10;
    $optionList = "";
    
    # find all Calendar events titled "Fundraiser: Discover SCUBA"

    $startdate = new DateTime();
    $startdate = $startdate->format("Y-m-d");

    //get the next 180 days of scheduled events
    $enddate = new DateTime();
    date_add($enddate, date_interval_create_from_date_string('180 days'));
    $enddate = $enddate->format("Y-m-d");
                
    $filter = "PartitionKey eq 'scheduledEvents' and title eq 'Fundraiser: Discover SCUBA' and startTime ge datetime'$startdate' and startTime lt datetime'$enddate'";
    
    try {
        $result = $tableRestProxy->queryEntities($tableName, $filter);
    }
    catch(ServiceException $e){
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message."<br />";
    }

    $entities = $result->getEntities();
    uasort($entities, 'startTimeSort');
    
    foreach($entities as $entity){
        // for each entry, we need to populate the select list
        $localStartTime = $entity->getProperty("startTime")->getValue();

        // cycle through each hour interval scheduled        
        while($localStartTime < $entity->getProperty("endTime")->getValue()){

            if (getReservationCount($localStartTime) < $maxStudents) {
                $optionList = $optionList."<option value=\"".$localStartTime->format($timeformat)."\">".$localStartTime->format("l ".$timeformat)."</option>";
            }
            date_add($localStartTime, date_interval_create_from_date_string('1 hour'));
         }
    }
    
    echo "<b>Step 1: Select your time</b><br><br>";
    
    echo "<form action=\"\scuba.php?command=makeReservation\" method=\"post\">";
    echo "<table><tr><td>Session:</td><td>Name:</td><td>Email Address:</td><td>Phone Number:</td><td></td></tr>";
    echo "<td><select name=\"session\">".$optionList."</select></td>";
    echo "<td><INPUT type=\"text\" size=\"75\" name=\"name\"></td>";
    echo "<td><INPUT type=\"text\" size=\"75\" name=\"email\"></td>";
    echo "<td><INPUT type=\"text\" size=\"50\" name=\"phone\"></td>";
    echo "<td><input type=\"submit\" name=\"submit\" value=\"Register\"></td>";
    echo "<tr>";
    echo "</table></form>";

    echo "<br><b>Step 2: Confirm the email you receive from scuba@crew1978.org</b>  <br><i>Your time is not secured until you confirm your email.  You may need to check your SPAM filter, and the email normally takes 1 - 3 minutes.</i>";

}

function checkMobile() {
    global $isMobile;
    if (strstr($_SERVER['HTTP_USER_AGENT'], "Mobile") == FALSE) {
        $isMobile = FALSE;
    } else {
        $isMobile = TRUE;
    }
}

function checkemailnotice() {
    global $emailNotices, $tableRestProxy, $tableName, $notificationemail, $from, $pageTitle, $timeformat;
    foreach ($emailNotices as $emailNotice) {
        $startdate = new DateTime();
        date_add($startdate, date_interval_create_from_date_string(($emailNotice - 1). ' days'));
        $startdate = $startdate->format("Y-m-d");

        $enddate = new DateTime();
        date_add($enddate, date_interval_create_from_date_string($emailNotice . ' days'));
        $enddate = $enddate->format("Y-m-d");

        $today = new DateTime();
        $today = $today->format("Y-m-d");
              
        $filter = "PartitionKey eq 'scheduledEvents' and startTime ge datetime'$startdate' and startTime lt datetime'$enddate' and lastNotification ne datetime'$today'";

        try {
            $result = $tableRestProxy->queryEntities($tableName, $filter);
        }
        catch(ServiceException $e){
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code.": ".$error_message."<br />";
        }

        $entities = $result->getEntities();
    
        foreach($entities as $entity){
            $title      = $entity->getProperty("title")->getValue();
            $startTime  = $entity->getProperty("startTime")->getValue()->format($timeformat);
            $endTime    = $entity->getProperty("endTime")->getValue()->format($timeformat);
            $location   = $entity->getProperty("location")->getValue();
            $RowKey     = $entity->getRowKey();
            
            $emailbody = "Start Time: $startTime \r\n End Time: $endTime \r\n Location: $location";

            sendmail($notificationemail, $from, "Upcoming Event: $title", $emailbody);

            echo "Sent email notice about: $title on $startTime.";

            $result = $tableRestProxy->getEntity($tableName, "scheduledEvents", $RowKey);
            $entity = $result->getEntity();
            $entity->setPropertyValue("lastNotification", new DateTime ($today, new DateTimeZone("UTC")));
   
            try {
                $tableRestProxy->updateEntity($tableName, $entity);
            }
            catch(ServiceException $e){
                // Handle exception based on error codes and messages.
                // Error codes and messages are here:
                // http://msdn.microsoft.com/library/azure/dd179438.aspx
                $code = $e->getCode();
                $error_message = $e->getMessage();
                echo $code.": ".$error_message."<br />";
            } 
        }

        
    }

}

#printVars();

function printVars () {
    foreach($_SERVER as $key_name => $key_value) {
        print $key_name . " = " . $key_value . "<br>";
    }
}

function updateLastSeen() {
    global $username, $tableRestProxy, $tableName, $time, $ip, $username;
    if ($username <> "Anonymous") {
        try {
            $result = $tableRestProxy->getEntity($tableName, "users", $username);
            $entity = $result->getEntity();
            $entity->setPropertyValue("lastSeen", new DateTime ($time, new DateTimeZone("UTC")));
            $entity->setPropertyValue("IPAddress", $ip);
   
            $tableRestProxy->updateEntity($tableName, $entity);
        }
        catch(ServiceException $e){
            $code = $e->getCode();
            $error_message = $e->getMessage();
            #echo $code.": ".$error_message."<br />";
            try {
                $entity = new Entity();
                $entity->setPartitionKey("users");
                $entity->setRowKey($username);
                $entity->addProperty("lastSeen", EdmType::DATETIME, new DateTime ($time, new DateTimeZone("UTC")));
                $entity->addProperty("hash", EdmType::STRING,  md5($username));
                $entity->addProperty("enteredBy", EdmType::STRING, "Contact List");
                $tableRestProxy->insertEntity($tableName, $entity);

            } catch(ServiceException $e){
                $code = $e->getCode();
                $error_message = $e->getMessage();
                #echo $code.": ".$error_message."<br />";
            }
        
        }
    }

    $entity = new Entity();
    $entity->setPartitionKey("visitor");
    $entity->setRowKey($_SERVER['HTTP_X_ARR_LOG_ID'] . "-" . $_SERVER['REQUEST_TIME_FLOAT']);
    $entity->addProperty("HTTP_USER_AGENT", EdmType::STRING, $_SERVER['HTTP_USER_AGENT']);
    if (isset($_SERVER['HTTP_REFERER'])) {
        $entity->addProperty("HTTP_REFERER", EdmType::STRING, $_SERVER['HTTP_REFERER']);
    }
    $entity->addProperty("REMOTE_HOST", EdmType::STRING, $_SERVER['REMOTE_HOST']);
    $entity->addProperty("REQUEST_URI", EdmType::STRING, $_SERVER['REQUEST_URI']);
    $entity->addProperty("REQUEST_METHOD", EdmType::STRING, $_SERVER['REQUEST_METHOD']);
    $entity->addProperty("COMPUTERNAME", EdmType::STRING, $_SERVER['COMPUTERNAME']);
    $entity->addProperty("userName", EdmType::STRING, $username);
    $entity->addProperty("lastSeen", EdmType::DATETIME, new DateTime ($time, new DateTimeZone("UTC")));

    try{
        $tableRestProxy->insertEntity($tableName."visitors", $entity);
    }
    catch(ServiceException $e){
        // Handle exception based on error codes and messages.
        // Error codes and messages are here:
        // http://msdn.microsoft.com/library/azure/dd179438.aspx
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $error_message;
    }

}

function addNewUserScreen() {
    checkLogin();
    
    echo "Type in the email address of the user to invite.  They will receive an email with an activation link.";
    echo "<form action=\"\login.php?command=addNewUser\" method=\"post\">";
    echo "<tr>";
    echo "<td>Email:<INPUT type=\"text\" size=\"50\" name=\"email\">";
    echo "<input type=\"submit\" name=\"submit\" value=\"Send E-Mail\"></td>";
    echo "<tr>";
    echo "</form>";

}

function addNewUser() {
    global $tableRestProxy, $tableName,$username, $from, $pageTitle;
    $userToAdd = trim($_POST['email']);
    $encrypteduser = md5($userToAdd);
    $ServerName = $_SERVER['SERVER_NAME'];

    $entity = new Entity();
    $entity->setPartitionKey("users");
    $entity->setRowKey($userToAdd);
    $entity->addProperty("hash", EdmType::STRING, $encrypteduser);
    $entity->addProperty("enteredBy", EdmType::STRING, $username);

    try{
        $tableRestProxy->insertEntity($tableName, $entity);
    }
    catch(ServiceException $e){
        // Handle exception based on error codes and messages.
        // Error codes and messages are here:
        // http://msdn.microsoft.com/library/azure/dd179438.aspx
        $code = $e->getCode();
        $error_message = $e->getMessage();
    }

    $emailbody = "You have been invited and setup on the \"$pageTitle\" website.  Please click the following link to login to the website: http://$ServerName/login.php?command=login&user=$toemail&encryption=$encrypteduser";

    sendmail($userToAdd, $from, "[$pageTitle]: New User Email", $emailbody);

    header("Location: /");

}

function login() {
    global $cookiedays, $username, $tableRestProxy, $tableName, $ip;

    $encrypteduser = $_GET["encryption"];
    $user = $_GET["user"];

    # Source IP is only used for self user reset, not for invitation
    if (isset($_GET["source"])) {
        $source = $_GET["source"];
        if (md5($ip) == $source) {
            $sourceCheck = TRUE;
        } else {
            $sourceCheck = FALSE;
        }
    } else {
        $sourceCheck = TRUE;
    }

    if ((md5($user) == $encrypteduser) and ($sourceCheck)) {
        // the user and ip address match, we will check if in the database!
        $filter = "RowKey eq '$user' or email eq '$user'";
    
        try {
            $result = $tableRestProxy->queryEntities($tableName, $filter);
        }
        catch(ServiceException $e){
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code.": ".$error_message."<br />";
        }

        $entities = $result->getEntities();
    
        foreach($entities as $entity){
            $isvalid = TRUE;
        }

        if ($isvalid) {
            setcookie("user", $user, time() + (86400 * $cookiedays), "/"); // 86400 = 1 day
            $username = $user;
        }
    } 
    header("Location: /");
}

function logout() {
    setcookie ("user", "", time() - 3600);
    header("Location: /");
}

function loginScreen() {
    echo "This site does not user passwords.<br>";
    echo "Type in your email address to receive a login email.  It may take a <b>few minutes</b> for the email to arrive.";
    echo "<form action=\"\login.php\" method=\"post\">";
    echo "<tr>";
    echo "<td>Email:<INPUT type=\"text\" size=\"50\" name=\"email\">";
    echo "<input type=\"submit\" name=\"submit\" value=\"Get E-Mail\"></td>";
    echo "<tr>";
    echo "</form>";

}

function checkLogin() {
    global $username, $tableRestProxy, $tableName, $ip, $time, $timezone ;
    if ($username == "Anonymous") {
        header("Location: /login.php");
        die();
    }
    
        $filter = "RowKey eq '$username' or email eq '$username'";
    
    try {
        $result = $tableRestProxy->queryEntities($tableName, $filter);
    }
    catch(ServiceException $e){
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message."<br />";
    }

    $entities = $result->getEntities();
    
    foreach($entities as $entity){
        $isvalid = TRUE;
    }

    if ($isvalid == FALSE) {
        logout();
    } else {
        $result = $tableRestProxy->getEntity($tableName, "users", $username);
        $entity = $result->getEntity();
        $entity->setPropertyValue("lastSeen", new DateTime ($time, new DateTimeZone("UTC")));
        $entity->setPropertyValue("hash", md5($username));
        $entity->setPropertyValue("IPAddress", $ip);
   
        try {
            $tableRestProxy->updateEntity($tableName, $entity);
        }
        catch(ServiceException $e){
            // Handle exception based on error codes and messages.
            // Error codes and messages are here:
            // http://msdn.microsoft.com/library/azure/dd179438.aspx
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code.": ".$error_message."<br />";
        }    

    }
}

function sendemail() {
    global $from, $pageTitle, $administratoremail, $encryptionkey, $ip, $tableName, $tableRestProxy;
    $toemail = strtolower(trim($_POST['email']));
    $encrypteduser = md5($toemail);
    $source = md5($ip);
    $ServerName = $_SERVER['SERVER_NAME'];
    $isvalid = FALSE;

    $filter = "RowKey eq '$toemail' or email eq '$toemail'";
    
    try {
        $result = $tableRestProxy->queryEntities($tableName, $filter);
    }
    catch(ServiceException $e){
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message."<br />";
    }

    $entities = $result->getEntities();
    
    foreach($entities as $entity){
        $isvalid = TRUE;
    }

    if ($isvalid) {
        $emailbody = "Please click the following link to login to the website: http://$ServerName/login.php?command=login&user=$toemail&encryption=$encrypteduser&source=$source";
        sendmail($toemail, $from, "[$pageTitle]: Login Email", $emailbody);
        header("Location: /");
    } else {
        echo "Your email address \"$toemail\" was not found in the user database.  If you feel this is in error, please contact: <a href=\"mailto:$administratoremail?Subject=[$pageTitle]: Login Request&Body=My email address $toemail was not found in the database.  Can you help?\"/>$administratoremail</a>.";
        die();
    }

}

function openTable($title) {
    echo "<table class=\"contentTable\">";
    echo "<tr><td class=\"contentTableTitle\">" . $title . "</td></tr>";
    echo "<tr><td>";
    
}

function closeTable() {
    echo "</td></tr></table>";
}

function getScheduledEvents() {
    global $tableRestProxy, $tableName, $showcaldays, $timeformat;
    echo "Events in the next $showcaldays days:";
    echo "<ul>";
    
    # Because there is not order by with Azure tables, have to select each day
    for ($day = 1; $day <= $showcaldays; $day++) {

        $startdate = new DateTime();
        date_add($startdate, date_interval_create_from_date_string(($day - 1). ' days'));
        $startdate = $startdate->format("Y-m-d");

        $enddate = new DateTime();
        date_add($enddate, date_interval_create_from_date_string($day . ' days'));
        $enddate = $enddate->format("Y-m-d");
                
        $filter = "PartitionKey eq 'scheduledEvents' and startTime ge datetime'$startdate' and startTime lt datetime'$enddate'";
    
        try {
            $result = $tableRestProxy->queryEntities($tableName, $filter);
        }
        catch(ServiceException $e){
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code.": ".$error_message."<br />";
        }

        $entities = $result->getEntities();
        uasort($entities, 'startTimeSort');
    
        foreach($entities as $entity){
            echo "<li><b>".$entity->getProperty("title")->getValue()."</b> (".$entity->getProperty("startTime")->getValue()->format($timeformat)." - ".$entity->getProperty("endTime")->getValue()->format($timeformat).") at ".$entity->getProperty("location")->getValue()."</li>";
        }
    }
    echo "</ul>";
}

function updateEvent() {
    global $tableRestProxy, $tableName, $timezone, $username;
    
    $result = $tableRestProxy->getEntity($tableName, "scheduledEvents", $_POST['rowKey']);
    $entity = $result->getEntity();
    $entity->setPropertyValue("title", $_POST['title']);
    $entity->setPropertyValue("startTime", new DateTime ($_POST['startTime'], new DateTimeZone("UTC")));
    $entity->setPropertyValue("endTime", new DateTime ($_POST['endTime'], new DateTimeZone("UTC")));
    $entity->setPropertyValue("location", $_POST['location']);
    $entity->setPropertyValue("enteredBy", $username);
   
    try {
        $tableRestProxy->updateEntity($tableName, $entity);
    }
    catch(ServiceException $e){
        // Handle exception based on error codes and messages.
        // Error codes and messages are here:
        // http://msdn.microsoft.com/library/azure/dd179438.aspx
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message."<br />";
    }
    header("Location: /addEvent.php");
}

function addNewEvent() {
    global $tableRestProxy, $tableName, $timezone, $username;
    
    $entity = new Entity();
    $entity->setPartitionKey("scheduledEvents");
    $entity->setRowKey($_POST['title'] . ' ' . str_replace("/","", $_POST['startTime']));
    $entity->addProperty("title", EdmType::STRING, $_POST['title']);
    $entity->addProperty("startTime", EdmType::DATETIME, new DateTime ($_POST['startTime'], new DateTimeZone("UTC")));
    $entity->addProperty("endTime", EdmType::DATETIME, new DateTime ($_POST['endTime'], new DateTimeZone("UTC")));
    $entity->addProperty("location", EdmType::STRING, $_POST['location']);
    $entity->addProperty("lastNotification", EdmType::DATETIME, new DateTime ("1/1/1", new DateTimeZone("UTC")));
    $entity->addProperty("enteredBy", EdmType::STRING, $username);

    try{
        $tableRestProxy->insertEntity($tableName, $entity);
    }
    catch(ServiceException $e){
        // Handle exception based on error codes and messages.
        // Error codes and messages are here:
        // http://msdn.microsoft.com/library/azure/dd179438.aspx
        $code = $e->getCode();
        $error_message = $e->getMessage();
    }
    header("Location: /addEvent.php");
}

function deleteEvent() {
    global $tableRestProxy, $tableName;
    try {
        // Delete entity.
        $tableRestProxy->deleteEntity($tableName, "scheduledEvents", $_GET['rowKey']);
    }
    catch(ServiceException $e){
        // Handle exception based on error codes and messages.
        // Error codes and messages are here:
        // http://msdn.microsoft.com/library/azure/dd179438.aspx
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message."<br />";
    }
    header("Location: /addEvent.php");
}

function getAllScheduledEvents() {
    if (isset($_GET['command']) ) {
        if ($_GET['command'] == "update") {
            updateEvent();
        }
        if ($_GET['command'] == "delete") {
            deleteEvent();
        }
       if ($_GET['command'] == "addNew") {
            addNewEvent();
        }
    }

    global $tableRestProxy, $tableName, $showcaldays, $timeformat;
    echo "<table>";
    echo "<tr><td>Event</td><td>Start</td><td>End</td><td>Location:</td><td>Author:</td><td></td><td></td></tr>";
    echo "<form action=\"?command=addNew\" method=\"post\">";
    echo "<tr>";
    echo "<td><INPUT type=\"text\" size=\"50\" name=\"title\"></td>";
    echo "<td><INPUT type=\"text\" name=\"startTime\"></td>";
    echo "<td><INPUT type=\"text\" name=\"endTime\"></td>";
    echo "<td><INPUT type=\"text\" name=\"location\"></td>";
    echo "<td></td>";
    echo "<input type=\"hidden\" name=\"rowKey\" value=\"\">";
    echo "<td col span=\"2\"><input type=\"submit\" name=\"submit\" value=\"Add New\"></td>";
    echo "<tr>";
    echo "</form>";
    echo "<tr></tr>";
    
    # Because there is not order by with Azure tables, have to select each day
    for ($month = 1; $month <= 12; $month++) {

        $thisMonth = date("Y-m")."-01";

        $startdate = new DateTime($thisMonth);
        date_add($startdate, date_interval_create_from_date_string(($month - 1). ' months'));
        $startdate = $startdate->format("Y-m-d");

        $enddate = new DateTime($thisMonth);
        date_add($enddate, date_interval_create_from_date_string($month . ' months'));
        $enddate = $enddate->format("Y-m-d");
       
        $filter = "PartitionKey eq 'scheduledEvents' and startTime ge datetime'$startdate' and startTime lt datetime'$enddate'";
    
        try {
            $result = $tableRestProxy->queryEntities($tableName, $filter);
        }
        catch(ServiceException $e){
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code.": ".$error_message."<br />";
        }

        $entities = $result->getEntities();
        uasort($entities, 'startTimeSort');

        $monthYear = new DateTime();
        date_add($monthYear, date_interval_create_from_date_string(($month - 1). ' months'));
        $monthYear = $monthYear->format("F Y");

        echo "<tr><td colspan=7 class=\"contentTableTitle\">$monthYear<td></tr>";

        foreach($entities as $entity){
            echo "<form action=\"?command=update\" method=\"post\">";
            echo "<tr>";
            echo "<td><INPUT type=\"text\" size=\"50\" name=\"title\" value=\"".$entity->getProperty("title")->getValue()."\"></td>";
            echo "<td><INPUT type=\"text\" name=\"startTime\" value=\"".$entity->getProperty("startTime")->getValue()->format($timeformat)."\"></td>";
            echo "<td><INPUT type=\"text\" name=\"endTime\" value=\"".$entity->getProperty("endTime")->getValue()->format($timeformat)."\"></td>";
            echo "<td><INPUT type=\"text\" name=\"location\" value=\"".$entity->getProperty("location")->getValue()."\"></td>";
            echo "<td>".$entity->getProperty("enteredBy")->getValue()."</td>";
            echo "<input type=\"hidden\" name=\"rowKey\" value=\"".$entity->getRowKey()."\">";
            echo "<td><input type=\"submit\" name=\"submit\" value=\"Update\"></td>";
            echo "<td><a href=\"addEvent.php?command=delete&rowKey=" .$entity->getRowKey(). "\">delete</a></td>";
            echo "<tr>";
            echo "</form>";
        }
    }
    echo "</table>";
}

function updateContact() {

    deleteContact();
    addNewContact();
    header("Location: /contacts.php");    
    
}

function deleteContact() {
    global $tableRestProxy, $tableName;
    try {
        $tableRestProxy->deleteEntity($tableName, "contacts", $_GET['name']);
    }
    catch(ServiceException $e){
        // Handle exception based on error codes and messages.
        // Error codes and messages are here:
        // http://msdn.microsoft.com/library/azure/dd179438.aspx
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message."<br />";
    }
    header("Location: /contacts.php");
}

function addNewContact () {
    global $tableRestProxy, $tableName, $username;
    
    $entity = new Entity();
    $entity->setPartitionKey("contacts");
    $entity->setRowKey($_POST['name']);
    $entity->addProperty("email", EdmType::STRING, strtolower($_POST['email']));
    $entity->addProperty("title", EdmType::STRING, $_POST['title']);
    $entity->addProperty("phoneNumber", EdmType::STRING, $_POST['phoneNumber']);
    $entity->addProperty("enteredBy", EdmType::STRING, $username);
    if ($_POST['DOB'] ==! NULL) {
        $entity->addProperty("DOB", EdmType::DATETIME, new DateTime ($_POST['DOB'], new DateTimeZone("UTC")));
        $DOB = new DateTime ($_POST['DOB']);
        $DOBString = $DOB->format("m/d");
        $entity->addProperty("DOBString", EdmType::STRING, $DOBString);
    }
    $entity->addProperty("address", EdmType::STRING, $_POST['address']);
    $entity->addProperty("BSAID", EdmType::STRING, $_POST['BSAID']);
    if ($_POST['YPT'] ==! NULL) {
        $entity->addProperty("YPT", EdmType::DATETIME, new DateTime ($_POST['YPT'], new DateTimeZone("UTC")));
    }
    try{
        $tableRestProxy->insertEntity($tableName, $entity);
    }
    catch(ServiceException $e){
        // Handle exception based on error codes and messages.
        // Error codes and messages are here:
        // http://msdn.microsoft.com/library/azure/dd179438.aspx
        $code = $e->getCode();
        $error_message = $e->getMessage();
    }
    header("Location: /contacts.php");
}

function getPDF() {
    $timeformat = "m/d/Y";
    global $tableRestProxy, $tableName, $showcaldays, $timeformat;
    

    //Instanciation of inherited class
    $pdf=new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Times','',12);

    $filter = "PartitionKey eq 'contacts'";
    
    try {
        $result = $tableRestProxy->queryEntities($tableName, $filter);
    }
    catch(ServiceException $e){
        $code = $e->getCode();
    }

    $entities = $result->getEntities();
    #uasort($entities, 'startTimeSort');
    $oddEven = 1;

    foreach($entities as $entity){

        $pdf->Cell(0,10,"blah",0,1);
                
        if($oddEven&1) {
            $rowColor = "#E0E0E0";
        } else {
            $rowColor = "White";
        }

        $oddEven++;

        echo $entity->getRowKey();
        $entity->getProperty("title")->getValue();
    }
    $pdf->Output();
}

function getAllContacts() {
    $timeformat = "m/d/Y";
    if (isset($_GET['command']) ) {
        if ($_GET['command'] == "updateContact") {
            updateContact();
        }
        if ($_GET['command'] == "deleteContact") {
            deleteContact();
        }
       if ($_GET['command'] == "addNewContact") {
            addNewContact();
        }
        if ($_GET['command'] == "getPDF") {
            getPDF();
        }
    }

    global $tableRestProxy, $tableName, $showcaldays, $timeformat;
    echo "<table>";
    echo "<form action=\"contacts.php?command=addNewContact\" method=\"post\">";
    echo "<tr>";
    echo "<td>Name:<INPUT type=\"text\" size=\"30\" name=\"name\"></td>";
    echo "<td>Title:<INPUT type=\"text\" size=\"20\" name=\"title\"></td>";
    echo "<td>Email:<INPUT type=\"text\" size=\"30\" name=\"email\"></td>";
    echo "<td>Phone:<INPUT type=\"text\" size=\"14\" name=\"phoneNumber\"></td>";
    echo "<input type=\"hidden\" name=\"rowKey\" value=\"\">";
    echo "<td colspan=\"2\" rowspan=\"2\"><input type=\"submit\" name=\"submit\" value=\"Add New\"></td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>Address:<INPUT type=\"text\" size=\"30\" name=\"address\"></td>";
    echo "<td>DOB:<INPUT type=\"text\" size=\"10\" name=\"DOB\"></td>";
    echo "<td>BSAID:<INPUT type=\"text\" size=\"10\" name=\"BSAID\"></td>";
    echo "<td>YPT Date:<INPUT type=\"text\" size=\"10\" name=\"YPT\"></td>";
    echo "</tr>";
    echo "</form>";
    echo "<tr><td colspan=6>&nbsp;</td></tr>";
    echo "<tr><td colspan=6 align=right><a href=contacts.php?command=getPDF>Get Printer Friendly PDF</a></td></tr>";
      
    $filter = "PartitionKey eq 'contacts'";
    
    try {
        $result = $tableRestProxy->queryEntities($tableName, $filter);
    }
    catch(ServiceException $e){
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message."<br />";
    }

    $entities = $result->getEntities();
    #uasort($entities, 'startTimeSort');
    $oddEven = 1;

    foreach($entities as $entity){
        
        if($oddEven&1) {
            $rowColor = "#E0E0E0";
        } else {
            $rowColor = "White";
        }

        $oddEven++;

        echo "<form action=\"contacts.php?command=updateContact&name=" .$entity->getRowKey()."\" method=\"post\">";
        echo "<tr bgcolor=\"$rowColor\">";
        echo "<td><INPUT STYLE=\"background-color: $rowColor\" type=\"text\" size=\"40\" name=\"name\" value=\"".$entity->getRowKey()."\"></td>";
        if ($entity->getProperty("title") == !NULL) {
            echo "<td><INPUT STYLE=\"background-color: $rowColor\" type=\"text\" size=\"30\" name=\"title\" value=\"".$entity->getProperty("title")->getValue()."\"></td>";
        } else {
            echo "<td><INPUT STYLE=\"background-color: $rowColor\" type=\"text\" size=\"30\" name=\"title\"></td>";
        }       
        echo "<td><INPUT STYLE=\"background-color: $rowColor\" type=\"text\" size=\"30\" name=\"email\" value=\"".$entity->getProperty("email")->getValue()."\"></td>";
        echo "<td><INPUT STYLE=\"background-color: $rowColor\" type=\"text\" size=\"14\" name=\"phoneNumber\" value=\"".$entity->getProperty("phoneNumber")->getValue()."\"></td>";
        echo "<td rowspan=\"2\"><input STYLE=\"background-color: $rowColor\" type=\"submit\" name=\"submit\" value=\"Update\"></td>";
        echo "<td rowspan=\"2\"><a href=\"contacts.php?command=deleteContact&name=" .$entity->getRowKey(). "\">delete</a></td>";
        echo "</tr>";
        echo "<tr bgcolor=\"$rowColor\">";
        if ($entity->getProperty("address") == !NULL) {
            echo "<td><INPUT STYLE=\"background-color: $rowColor\" type=\"text\" size=\"40\" name=\"address\" value=\"".$entity->getProperty("address")->getValue()."\"></td>";
        } else {
            echo "<td><INPUT STYLE=\"background-color: $rowColor\" type=\"text\" size=\"40\" name=\"address\"></td>";
        } 
        if ($entity->getProperty("DOB") == !NULL) {
            echo "<td>DOB:<INPUT STYLE=\"background-color: $rowColor\" type=\"text\" size=\"10\" name=\"DOB\" value=\"".$entity->getProperty("DOB")->getValue()->format("m/d/Y")."\"></td>";
        } else {
            echo "<td>DOB:<INPUT STYLE=\"background-color: $rowColor\" type=\"text\" size=\"10\" name=\"DOB\"></td>";
        } 
        if ($entity->getProperty("BSAID") == !NULL) {
            echo "<td>BSA:<INPUT STYLE=\"background-color: $rowColor\" type=\"text\" size=\"10\" name=\"BSAID\" value=\"".$entity->getProperty("BSAID")->getValue()."\"></td>";
        } else {
            echo "<td>BSA:<INPUT STYLE=\"background-color: $rowColor\" type=\"text\" size=\"10\" name=\"BSAID\"></td>";
        } 

        if ($entity->getProperty("YPT") == !NULL) {
            echo "<td>YPT:<INPUT STYLE=\"background-color: $rowColor\" type=\"text\" size=\"10\" name=\"YPT\" value=\"".$entity->getProperty("YPT")->getValue()->format("m/d/Y")."\"></td>";
        } else {
            echo "<td>YPT:<INPUT STYLE=\"background-color: $rowColor\" type=\"text\" size=\"10\" name=\"YPT\"></td>";
        } 
        echo "</tr>";
        #echo "<tr><td col span=6>&nbsp;</td></tr>";
        echo "</form>";
    }
    echo "</table>";
}

function startTimeSort ($first, $second) {
    if ($first->getProperty("startTime")->getValue() == $second->getProperty("startTime")->getValue()) {
        return 0;
    }
    return ($first->getProperty("startTime")->getValue() < $second->getProperty("startTime")->getValue()) ? -1 : 1;  
}

function lastSeenSortDesc ($first, $second) {
    if ($first->getProperty("lastSeen")->getValue() == $second->getProperty("lastSeen")->getValue()) {
        return 0;
    }
    return ($first->getProperty("lastSeen")->getValue() < $second->getProperty("lastSeen")->getValue()) ? 1 : -1;  
}

function startTimeSortAsc ($first, $second) {
    if ($first->getProperty("startTime")->getValue() == $second->getProperty("startTime")->getValue()) {
        return 0;
    }
    return ($first->getProperty("startTime")->getValue() < $second->getProperty("startTime")->getValue()) ? -1 : 1;  
}

function updateNews() {
    global $tableRestProxy, $tableName, $timezone, $username;
    
    $result = $tableRestProxy->getEntity($tableName, "news", $_POST['rowKey']);
    $entity = $result->getEntity();
    $entity->setPropertyValue("title", $_POST['title']);
    $entity->setPropertyValue("story", $_POST['story']);
    $entity->setPropertyValue("enteredBy", $username);
    $entity->setPropertyValue("lastSeen", new DateTime ($time, new DateTimeZone("UTC")));
   
    try {
        $tableRestProxy->updateEntity($tableName, $entity);
    }
    catch(ServiceException $e){
        // Handle exception based on error codes and messages.
        // Error codes and messages are here:
        // http://msdn.microsoft.com/library/azure/dd179438.aspx
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message."<br />";
    }
    header("Location: /addNews.php");
}

function deleteNews() {
    global $tableRestProxy, $tableName;
    try {
        // Delete entity.
        $tableRestProxy->deleteEntity($tableName, "news", $_GET['rowKey']);
    }
    catch(ServiceException $e){
        // Handle exception based on error codes and messages.
        // Error codes and messages are here:
        // http://msdn.microsoft.com/library/azure/dd179438.aspx
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message."<br />";
    }
    header("Location: /addNews.php");
}

function addNewNews() {
    global $tableRestProxy, $tableName, $timezone, $username;
    
    $entity = new Entity();
    $entity->setPartitionKey("news");
    $entity->setRowKey($_POST['title'] . ' ' . time());
    $entity->addProperty("title", EdmType::STRING, $_POST['title']);
    $entity->addProperty("story", EdmType::STRING, $_POST['story']);
    $entity->addProperty("enteredBy", EdmType::STRING, $username);
    $entity->addProperty("lastSeen", EdmType::DATETIME, new DateTime ($time, new DateTimeZone("UTC")));

    try{
        $tableRestProxy->insertEntity($tableName, $entity);
    }
    catch(ServiceException $e){
        // Handle exception based on error codes and messages.
        // Error codes and messages are here:
        // http://msdn.microsoft.com/library/azure/dd179438.aspx
        $code = $e->getCode();
        $error_message = $e->getMessage();
    }
   header("Location: /addNews.php");
}

function getAllNews() {
    if (isset($_GET['command']) ) {
        if ($_GET['command'] == "update") {
            updateNews();
        }
        if ($_GET['command'] == "delete") {
            deleteNews();
        }
       if ($_GET['command'] == "addNewNews") {
            addNewNews();
        }
    }

    global $tableRestProxy, $tableName, $showcaldays, $timeformat;
    echo "<table>";
    echo "<tr><td>Title</td><td>Story</td><td></td><td></td></tr>";
    echo "<form action=\"?command=addNewNews\" method=\"post\">";
    echo "<tr>";
    echo "<td><INPUT type=\"text\" size=\"50\" name=\"title\"></td>";
    echo "<td><TextArea cols=40 rows=12 name=\"story\"></TextArea></td>";
    echo "<input type=\"hidden\" name=\"rowKey\" value=\"\">";
    echo "<td col span=\"2\"><input type=\"submit\" name=\"submit\" value=\"Add New\"></td>";
    echo "<tr>";
    echo "</form>";
    echo "<tr></tr>";
                
    $filter = "PartitionKey eq 'news'";
    
    try {
        $result = $tableRestProxy->queryEntities($tableName, $filter);
    }
    catch(ServiceException $e){
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message."<br />";
    }

    $entities = $result->getEntities();

    uasort($entities, 'lastSeenSortDesc');
    
    foreach($entities as $entity){
        echo "<form action=\"?command=update\" method=\"post\">";
        echo "<tr>";
        echo "<td><INPUT type=\"text\" size=\"50\" name=\"title\" value=\"".$entity->getProperty("title")->getValue()."\"></td>";
        echo "<td><TextArea cols=40 rows=12 name=\"story\">".$entity->getProperty("story")->getValue()."</TextARea></td>";
        echo "<input type=\"hidden\" name=\"rowKey\" value=\"".$entity->getRowKey()."\">";
        echo "<td><input type=\"submit\" name=\"submit\" value=\"Update\"></td>";
        echo "<td><a href=\"addNews.php?command=delete&rowKey=" .$entity->getRowKey(). "\">delete</a></td>";
        echo "<tr>";
        echo "</form>";
    }
    echo "</table>";
}

function addChangeEvent() {
    echo "<table><tr>";
    echo "<td align=\"left\"><a href=\"getCalendar.php\">Download to Phone / Computer</a></td>";
    echo "<td align=\"right\"><a href=\"addEvent.php\">Add/Change Event</a></td>";
    echo "</tr></table>";
}

function getCurrentNews() {
    global $tableRestProxy, $tableName, $newsCount;
    $currentNewsCount = 1;
    $filter = "PartitionKey eq 'news'";

    try {
        $result = $tableRestProxy->queryEntities($tableName, $filter);
    }
    catch(ServiceException $e){
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message."<br />";
    }

    $entities = $result->getEntities();

    uasort($entities, 'lastSeenSortDesc');

    echo "<ul>";
    foreach($entities as $entity){
        if ($currentNewsCount <= $newsCount) {
            echo "<li><b>".$entity->getProperty("title")->getValue()."</b> ".$entity->getProperty("story")->getValue()."</li>";
            $currentNewsCount++;
        }
    }
    echo "</ul>";

}

function addChangeNews() {
    echo "</td></tr><tr><td align=\"right\">";
    echo "<a href=\"addNews.php\">Add/Change</a>";
}

function getPageHeader() {
    global $username, $ip;
    echo "<table><tr>";
    echo "<td align=\"left\"><a href=\".\"><img height=\"125\" alt=\"Crew 1978\" src=\"images/Logo_Small.png\" border=\"0\"></td></a>";
    echo "<td align=\"right\" valign=\"bottom\">You are: $username<br>";
    if ($username == "Anonymous") {
        echo "IP Address: $ip<br>";
        echo "<br><a href=\"login.php\">Login</a></td>";

    } else {
        echo "IP Address: $ip<br>";
        echo "<br><a href=\"login.php?command=logout\">Logout</a>";
        echo "<br><a href=\"login.php?command=addNewUserScreen\">Invite New User</a></td>";
    }
    echo "</tr></table>";
}

function getFooter() {
    global $pageTitle;
    echo "<table><tr>";
    echo "<td class=\"footer\">Copyright: " . $pageTitle . " " . date('F j, Y, g:i a T') . "</td><td class=\"footer\"><a href=/visitors.php>Website Statistics</a></td>";
    echo "</tr></table>";
}

function getAbout() {
    openTable("About Crew 1978:");
    echo "Crew 1978 started in 2015 as a co-ed organization for older BoyScouts, GirlScouts and non Scouts in Garland and surounding cities. It is sponsored by Cornerstone United Methodist of Garland Texas.";
    closeTable();
}

function getButtonBar() {
    global $administratoremail, $username;
    echo "<table><tr align=\"center\" valign=\"center\">";
    echo "<td class=\"contentTableTitle\"><a href=\"contacts.php\"><font class=\"contentTableTitle\">Contact List</font></a></td>";
    echo "<td class=\"contentTableTitle\"><a href=\"mailto:$administratoremail?Subject=[Crew 1978] Add me to the Crew email list&Body=Please add me to the email list.\"><font class=\"contentTableTitle\">Join Email List</font></a></td>";
    echo "<td class=\"contentTableTitle\"><a href=\"http://www.shutterfly.com\"><font class=\"contentTableTitle\">Crew Pictures</font></a></td>";
    if ($username != "Anonymous") {
        echo "<td class=\"contentTableTitle\"><a href=upload.php><font class=\"contentTableTitle\">Upload Files</font></a></td>";
    }
    echo "<td class=\"contentTableTitle\"><a href=\"mailto:$administratoremail?Subject=[Crew 1978] Contact Request from website\"><font class=\"contentTableTitle\">Contact Us</font></a></td>";
    echo "</tr></table>";
}

function getJoinEmail() {
    openTable("Notifications:");
    echo "Get email notification of scheduled events before they happen.  <a href=\"mailto:ksaye@saye.org?Subject=[Crew 1978] Add me to the Crew email list&Body=Please add me to the email list.\">Click here</a> to get added to the news and calendar email or notification list.";
    closeTable();
}

function getCrewForms() {
    echo "<ul>";
    echo "<li><a href=\"https://crew1978.blob.core.windows.net/downloads/Reimbursement%20Request%20Form.pdf\">Reimbursement Request Form</a></li>";
    echo "<li><a href=\"http://crew1978.blob.core.windows.net/downloads/Crew Tax Exempt Form.pdf\">Tax Exempt Form</a></li>";
    echo "</ul>";
}

function getCalendar() {
    global $timezone;
    # reference: http://goatella.com/GitHub/PHPCalendar/
    //This gets today's date
    
    // In case we are moving forward or backwards in the calendar
    if (isset($_GET['direction'])) {
        $date = strtotime("1 " . $_GET['month'] ." " .$_GET['year']);
    } else {
        $date=time(); 
    }
    
    //This puts the day, month, and year in seperate variables
    $day = date('d', $date) ;
    $month = date('m', $date) ;
    $year = date('Y', $date) ;

    //Here we generate the first day of the month
    $first_day = mktime(0,0,0,$month, 1, $year) ;

    //This gets us the month name
    $title = date('F', $first_day) ; 
    //Here we find out what day of the week the first day of the month falls on 
    $day_of_week = date('D', $first_day) ; 

    //Once we know what day of the week it falls on, we know how many blank days occure before it. If the first day of the week is a Sunday then it would be zero 
    switch($day_of_week){ 
	    case "Sun": $blank = 0; break; 
	    case "Mon": $blank = 1; break; 
	    case "Tue": $blank = 2; break; 
	    case "Wed": $blank = 3; break;  
	    case "Thu": $blank = 4; break; 
	    case "Fri": $blank = 5; break; 
	    case "Sat": $blank = 6; break; 
    } 

    //We then determine how many days are in the current month 
    $days_in_month = cal_days_in_month(0, $month, $year) ;
    
    $nextMonthDT = new DateTime ($month . "/" . $day . "/" . $year);
    date_add($nextMonthDT, date_interval_create_from_date_string('1 month'));
    $lastMonthDT = new DateTime ($month . "/" . $day . "/" . $year);
    date_add($lastMonthDT, date_interval_create_from_date_string('-1 month'));

    $nextMonth  = $nextMonthDT->format("F");
    $nextYear   = $nextMonthDT->format("Y");
    $lastMonth  = $lastMonthDT->format("F");
    $lastYear   = $lastMonthDT->format("Y");

    //Here we start building the table heads 
    echo "<table border=0>"; 
    echo "<tr><td class=\"contentTableTitle\"><a href=\"?direction=move&month=$lastMonth&year=$lastYear\"><font class=\"contentTableTitle\">Prior</font></a></td><td class=\"contentTableTitle\" align=\"center\" colspan=5> $title $year </td><td class=\"contentTableTitle\"><a href=\"?direction=move&month=$nextMonth&year=$nextYear\"><font class=\"contentTableTitle\">Next</font></a></td></tr>"; 
    echo "<tr><td align=center>Sun</td><td align=center>Mon</td><td align=center>Tue</td><td align=center>Wed</td><td align=center>Thu</td><td align=center>Fri</td><td align=center>Sat</td></tr>"; 

    //This counts the days in the week, up to 7 
    $day_count = 1; 

    echo "<tr>"; 

    //first we take care of those blank days 
    while ( $blank > 0 ) { 
	    echo "<td></td>"; 
	    $blank = $blank-1; 
	    $day_count++; 
    }

    //sets the first day of the month to 1 
    $day_num = 1; 

    //count up the days, untill we've done all of them in the month 
    while ( $day_num <= $days_in_month ) { 

        $calendarEvent = getCalendarEvent($day_num, $month, $year);
        if (isset($calendarEvent)) {
            echo "<td class=calendarDay style=\"background-color:lightGrey\"><b> <center>$day_num</center>$calendarEvent</b></td>";
        } else {
            echo "<td class=calendarDay><center> $day_num </center></td>";
        }
        
	    $day_num++; 
	    $day_count++; 

	    //Make sure we start a new row every week 
	    if ($day_count > 7) { 
		    echo "</tr><tr>"; 
		    $day_count = 1; 
	    } 
    } 

    //Finaly we finish out the table with some blank details if needed 
    while ( $day_count >1 && $day_count <=7 ) { 
	    echo "<td> </td>"; 
	    $day_count++; 
    } 
    echo "</tr></table>"; 
}

function getCalendarEvent ($day, $month, $year) {
    global $tableRestProxy, $tableName;
    $toBeReturned = NULL;

    $calendarStartDay = new DateTime ($month . "/" . $day . "/" . $year);
    $calendarEndDay = new DateTime ($month . "/" . $day . "/" . $year);
    date_add($calendarEndDay, date_interval_create_from_date_string('1 days'));
    $calendarStartDay = $calendarStartDay->format("Y-m-d");
    $calendarEndDay = $calendarEndDay->format("Y-m-d");

    $filter = "PartitionKey eq 'scheduledEvents' and ((startTime ge datetime'$calendarStartDay' and startTime le datetime'$calendarEndDay') or (startTime le datetime'$calendarStartDay' and endTime ge datetime'$calendarStartDay'))";
    
    try {
        $result = $tableRestProxy->queryEntities($tableName, $filter);
    }
    catch(ServiceException $e){
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message."<br />";
    }

    $entities = $result->getEntities();
    
    foreach($entities as $entity){
        if ($entity->getProperty("title") == !NULL) {
            $title      = $entity->getProperty("title")->getValue();
            $startTime  = $entity->getProperty("startTime")->getValue()->format("g:i A");
            $startDate  = $entity->getProperty("startTime")->getValue()->format("Y-m-d");
            if ($startDate <> $calendarStartDay) {
                $toBeReturned = "$title";
            } else {
                $toBeReturned = "$title<br>@ $startTime";
            }
        } else {
            $toBeReturned = NULL;
        }
    }

    $DOB = new DateTime ($month . "/" . $day . "/" . $year);
    $DOBString = $DOB->format("m/d");

    $filter = "PartitionKey eq 'contacts' and DOBString eq '$DOBString'";
    
    try {
        $result = $tableRestProxy->queryEntities($tableName, $filter);
    }
    catch(ServiceException $e){
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message."<br />";
    }

    $entities = $result->getEntities();
    
    foreach($entities as $entity){
        $toBeReturned = "Birthday: ". $entity->getRowKey() . "<br>" . $toBeReturned;
    }

    return $toBeReturned;
}

class PDF extends FPDF
{
//Page header
function Header()
{
    //Logo
    $this->Image('images/Logo_Small.png',10,8,33);
    //Arial bold 15
    $this->SetFont('Arial','B',15);
    //Move to the right
    $this->Cell(80);
    //Title
    $this->Cell(30,10,'Troop Roster',1,0,'C');
    //Line break
    $this->Ln(20);
}
 
//Page footer
function Footer()
{
    //Position at 1.5 cm from bottom
    $this->SetY(-15);
    //Arial italic 8
    $this->SetFont('Arial','I',8);
    //Page number
    $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
}
}


?>

