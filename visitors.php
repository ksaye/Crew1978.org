<?php

include_once "/includes/common.php";
include_once "/includes/header.php";

?>
<body>
<table class="maintable" align="center">
    
<tr><td><?php getPageHeader(); ?></td></tr>
<tr><td>

<?php

checkLogin();
    
openTable("User View");

global $tableRestProxy, $tableName;
$visitorTable = $tableName . "visitors";
$visitors = array();
$IPs = array();
$agents = array();
               
$filter = "PartitionKey eq 'visitor'";
    
try {
    $result = $tableRestProxy->queryEntities($visitorTable, $filter);
}
catch(ServiceException $e){
    $code = $e->getCode();
    $error_message = $e->getMessage();
    echo $code.": ".$error_message."<br />";
}

$entities = $result->getEntities();
    
foreach($entities as $entity){

    if (($entity->getProperty("HTTP_USER_AGENT") != NULL) and isset($agents[$entity->getProperty("HTTP_USER_AGENT")->getValue()])) {
        $agentsCurrentCount = $agents[$entity->getProperty("HTTP_USER_AGENT")->getValue()];
    } else {
        $agentsCurrentCount = 0;
    }

    if (($entity->getProperty("REMOTE_HOST") != NULL) and isset($IPs[$entity->getProperty("REMOTE_HOST")->getValue()])) {
        $IPsCurrentCount = $IPs[$entity->getProperty("REMOTE_HOST")->getValue()];
    } else {
        $IPsCurrentCount = 0;
    }

    if (isset($visitors[$entity->getProperty("userName")->getValue()])) {
        $visitorsCurrentCount = $visitors[$entity->getProperty("userName")->getValue()];
    } else {
        $visitorsCurrentCount = 0;
    }
    
    $visitors[$entity->getProperty("userName")->getValue()] = $visitorsCurrentCount + 1;
    
    if ($entity->getProperty("REMOTE_HOST") != NULL) {
            $IPs[$entity->getProperty("REMOTE_HOST")->getValue()] = $IPsCurrentCount + 1;
        }
    if ($entity->getProperty("HTTP_USER_AGENT") != NULL) {
            $agents[$entity->getProperty("HTTP_USER_AGENT")->getValue()] = $agentsCurrentCount + 1;
        }
}

arsort($visitors);
arsort($IPs);
arsort($agents);

echo "Unique users and Count:";
echo "<ul>";
foreach  ($visitors as $key => $visitor) {
    if ($visitor > 5) {
        echo "<li> $key ($visitor) </li>";
    }
}
echo "</ul>";


echo "Unique IPs and Count:";
echo "<ul>";
foreach  ($IPs as $key => $IP) {
    if ($IP > 5) {
        echo "<li> $key ($IP) </li>";
    }
}
echo "</ul>";

echo "Unique Agents and Count:";
echo "<ul>";
foreach  ($agents as $key => $agent) {
    if ($agent > 5) {
        echo "<li> $key ($agent) </li>";
    }
}
echo "</ul>";

closeTable();


?>

</td></tr>
<tr><td><?php  getFooter(); ?></td></tr>
</table>
</body>
</html>