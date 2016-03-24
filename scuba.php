<?php

$pageTitle = "Crew 1978's SCUBA Fundraiser";

include_once "/includes/common.php";
include_once "/includes/header.php";

?>
<body>
<table class="maintable" align="center">
<tr><td><?php getPageHeader(); ?></td></tr>
<tr><td></td></tr>
<tr><td><?php getAbout(); ?></td></tr>
<tr><td></td></tr>
<tr><td>
<?php openTable("Thanks for your support!"); ?>
    This website is setup to provide more information and allow customers to make reservations.<br>
    <ul>
        <li><b>Cost:</b> Your $40 ticket entitles you to all the benefits inluding: Discovery SCUBA, $40 off a PADI Certification and 10% off SCUBA equipment purchased in the store (one time purchase).</li>
        <li><b>What to bring:</b> Adventure SCUBA has all the needed equipment and facilities
        including an 18′ heated indoor pool.  Simply bring: (1) your ticket, (2) swim suit, (3) towel and this (4) waiver <a href="http://www.padi.com/scuba-diving/documents/padi-courses/discover-scuba-statement">Discover SCUBA Health and Waiver</a>.  Dressing rooms are provided.</li>
        <li><b>Location:</b> Adventure SCUBA is located west of 75 between Park and Parker road.
        The physical address is: 2301 N. Central Expressway #140 Plano, TX 75075</li>
        <li><b>Age:</b> Discover SCUBA is avialable for anyone 10 years of age and older.</li>
        <li><b>Health:</b>Discover SCUBA is for anyone reasonabily healthy.  Before showing up
        please fill out this form <a href="http://www.padi.com/scuba-diving/documents/padi-courses/discover-scuba-statement">Discover SCUBA Health and Waiver</a>.
        If you have any questions if you are fit to SCUBA, consult your doctor.</li>
    </ul>

    <b>What will you learn and experience?</b><br>
    We will go over the basic skills and safety information needed to dive in our 18′ pool with a PADI Divemaster or Instructor.  Get ready to:<br>
    <ul>
<li>Go over the scuba equipment you use to dive and how easy it is to move around underwater with your gear.</li>
<li>Find out what it’s like to breathe underwater.</li>
<li>Learn key skills that you’ll use during every scuba dive.</li>
<li>Have fun swimming around and exploring.</li>
<li>Hear about becoming a certified diver through the PADI Open Water Diver course.</li>
</ul>

<?php closeTable(); ?>
<tr><td></td></tr>
<tr><td>
<?php 

if (isset($_GET['command'])) {
    if ($_GET['command'] == "makeReservation") {
            submitReservation();

            openTable("");
            echo "<br><b>Step 1: Your Reservation is now being processed.</b><br>";
            echo "<br><b>Step 2: Confirm the email to <i>" .trim($_POST['email']) . "</i> you receive from scuba@crew1978.org</b>  <br><i>Your time is not secured until you confirm your email.  You may need to check your SPAM filter, and the email normally takes 1 - 3 minutes.</i>";
            closeTable();
        } elseif ($_GET['command'] == "completeReservation") {
            insertReservation();    
        } elseif ($_GET['command'] == "cancelReservation") {
            removeReservation();
        }
    } else {
        openTable("Reserver your Discover SCUBA Session now");
        showReservationTable(); 
        closeTable();
    }

 ?>
</td></tr>

<?php
   
    if ($username <> "Anonymous") {
        echo "<tr><td></td></tr>";
        echo "<tr><td>";
        openTable("Registered Users <i>(only shown to logged in users)</i>");
        showExistingReservations();
        closeTable();
        echo "</td></tr>";
    }  
?>

</table>
</body>
</html>