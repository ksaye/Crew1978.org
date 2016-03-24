<?php

include_once "/includes/common.php";
include_once "/includes/header.php";

?>
<body>
<table class="maintable" align="center">
    
<tr><td><?php getPageHeader(); ?></td></tr>
<tr><td></td></tr>
<tr><td><?php getAbout(); ?></td></tr>
<tr><td></td></tr>
<tr align="center" valign="center"><td><?php getButtonBar(); ?></td></tr>
<tr><td></td></tr>
<tr><td><table border="0"><tr>

<?php
    if ($isMobile) {
        echo "<td>";
        openTable("Next Scheduled Events:");
        getScheduledEvents();
        getCalendar();
        addChangeEvent();
        closeTable();
        echo "</td><tr><td>";
        openTable("Crew Forms:");
        getCrewForms();
        closeTable();
        echo "</td><tr><td>";
        openTable("Current News:");
        getCurrentNews();
        addChangeNews();
        closeTable();
        echo "</td>";
    } else {
        echo "<td width=\"50\">";
        openTable("Next Scheduled Events:");
        getScheduledEvents();
        getCalendar();
        addChangeEvent();
        closeTable();
        echo "</td><td width=\"1px\"></td>";
        echo "<td width=\"50%\">";
        openTable("Crew Forms:");
        getCrewForms();
        closeTable();
        echo "<br width=\"1px\">";
        openTable("Current News:");
        getCurrentNews();
        addChangeNews();
        closeTable();
        echo "</td>";
    }
?>

</tr></table></td></tr>
<tr><td></td></tr>
<tr><td><?php  getFooter(); ?></td></tr>
</table>
</body>
</html>
