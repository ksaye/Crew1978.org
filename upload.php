<?php

include_once "/includes/common.php";
include_once "/includes/header.php";

use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Common\ServiceException;

$containerName = "downloads";

?>
<body>
<table class="maintable" align="center">
    
<tr><td><?php getPageHeader(); ?></td></tr>
<tr><td></td></tr>
<?php
    
checkLogin();

openTable("Upload files:");

$blobRestProxy = ServicesBuilder::getInstance()->createBlobService($_SERVER["CUSTOMCONNSTR_StorageAccount"]);

if ($_FILES == NULL) {
    echo "<table>";
    echo "<tr><td>Filename:</td><td>Size:</td><td>Date:</td><td>URL:</td><td></td></tr>";
    echo "<FORM action=upload.php method=\"post\" enctype=\"multipart/form-data\">";
    echo "<tr><td><input type=\"file\" name=\"file\" id=\"file\"></td>";
    echo "<td></td><td></td><td></td><td><input type=\"submit\" name=\"submit\" value=\"Add New\"></td></tr>";
    echo "</FORM>";

    if (isset($_GET["command"]) and $_GET["command"] == "delete") {
        try {
            $blobRestProxy->deleteBlob($containerName, $_GET["blobName"]);
        }
        catch(ServiceException $e){
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code.": ".$error_message."<br />";
        }
    }

    try {
        // List blobs.
        $blob_list = $blobRestProxy->listBlobs($containerName);
        $blobs = $blob_list->getBlobs();

        foreach($blobs as $blob)
        {
            $blobName = urlencode($blob->getName());
            $blobURL = $blob->getUrl();
            $blobSize = number_format($blob->getProperties()->getContentLength()/1048576,1) . " MB";
            $blobDate = $blob->getProperties()->getLastModified()->format("m/d/Y g:i A");
            echo "<tr><td>".$blob->getName()."</td><td>$blobSize</td><td>$blobDate</td><td><a href=\"$blobURL\">$blobURL</a></td>";
            echo "<td><a href=upload.php?command=delete&blobName=$blobName>Delete File</a></td></tr>";
        }
    }
    catch(ServiceException $e){
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message."<br />";
    }

} else {
    $content = fopen($_FILES["file"]["tmp_name"], "r");
    $blob_name = $_FILES["file"]["name"];

    try {
        //Upload blob
        $blobRestProxy->createBlockBlob($containerName, $blob_name, $content);
    }
    catch(ServiceException $e){
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message."<br />";
    }
    
    fclose($_FILES["file"]["tmp_name"]);
    unlink($_FILES["file"]["tmp_name"]);
    header("Location: /upload.php");
}
closeTable();

?>
</table>
</body>