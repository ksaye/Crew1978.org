<?php
require_once 'vendor\autoload.php';
use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Common\ServiceException;
use WindowsAzure\Table\Models\Entity;
use WindowsAzure\Table\Models\EdmType;

$backupStorageAcct  = ServicesBuilder::getInstance()->createTableService($_SERVER["CUSTOMCONNSTR_StorageAccount_backup"]);
$tableRestProxy     = ServicesBuilder::getInstance()->createTableService($_SERVER["CUSTOMCONNSTR_StorageAccount"]);
$tableRestProxyBack = ServicesBuilder::getInstance()->createTableService($backupStorageAcct);
$tableName          = "crew1978";
$today              = new DateTime();
$today              = $today->format("mdY");
$backuptablename    = $tableName . "backup" . $today;

createTable();
backupTable();

function backupTable() {
    global $tableName, $tableRestProxy, $tableRestProxyBack, $backuptablename;
    $filter = "";

    echo "Backing up table $tableName<br>";

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
            $tableRestProxyBack->insertEntity($backuptablename, $entity);
            echo ".";
        }
}

function createTable() {
    global $backuptablename, $tableRestProxyBack;

    echo "Creating table $backuptablename<br>";

    try {
        $tableRestProxyBack->createTable($backuptablename);
    }
        catch(ServiceException $e){
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code.": ".$error_message."<br />";
    }
}

?>


