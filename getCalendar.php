<?php
include_once "/includes/common.php";
require_once '/includes/iCalcreator.class.php';

if(date("I") == 0) {
    $timeoffset = 6;
} else {
    $timeoffset = 5;
};

$sitename="Crew 1978";
$v = new vcalendar();      
$v->setProperty( "X-WR-TIMEZONE", "Central Standard Time" );	        // set timezone
$v->setProperty( 'X-WR-CALNAME', $sitename . " Calendar" );     // set some X-properties, name, content

$filter = "PartitionKey eq 'scheduledEvents'";	

try {
    $result = $tableRestProxy->queryEntities($tableName, $filter);
}
catch(ServiceException $e){
    $code = $e->getCode();
    $error_message = $e->getMessage();
    echo $code.": ".$error_message."<br />";
}

$entities = $result->getEntities();
uasort($entities, 'startTimeSortAsc');

foreach($entities as $entity){
	$title              = $entity->getProperty("title")->getValue();
	#$id                 = $entity->getProperty("title")->getValue();
    $description        = "Crew Event: " . $title;
    $eventLocation      = $entity->getProperty("location")->getValue();
    $realStartDate      = $entity->getProperty("startTime")->getValue();
    $realEndDate        = $entity->getProperty("endTime")->getValue();

    // Because of Outlook compatability issues, we need to subtract the timeZone Difference
    date_add($realStartDate, date_interval_create_from_date_string($timeoffset . 'hours'));
    date_add($realEndDate, date_interval_create_from_date_string($timeoffset . 'hours'));

    $eventDate          = $realStartDate->format("Y-m-d");
    $endDate            = $realEndDate->format("Y-m-d");
    $startTime          = $realStartDate->format("H:i:s");
    $endTime            = $realEndDate->format("H:i:s");

	$eventStartArray	= explode("-", $eventDate);
	$eventEndArray		= explode("-", $endDate);
	$startTimeArray		= explode(":", $startTime);
	$endTimeArray		= explode(":", $endTime);
              
	$e = new vevent();                             				// initiate a new EVENT
	$e->setProperty( 'categories', $sitename );   				// categorize
	$e->setProperty( 'categories', $sitename );   				// categorize
	$e->setProperty( 'dtstart',  $eventStartArray[0], $eventStartArray[1], $eventStartArray[2], $startTimeArray[0], $startTimeArray[1], 00, "Z"); 
	$e->setProperty( 'dtend', $eventEndArray[0], $eventEndArray[1], $eventEndArray[2], $endTimeArray[0], $endTimeArray[1], 00, "Z");

    #$e->setProperty( 'dtstart',  2006, 12, 24, 19, 30, 00 );  // 24 dec 2006 19.30

	$e->setProperty( 'description', $description );  			// describe the event
	$e->setProperty( 'location', $eventLocation );         	    // locate the event
	$e->setProperty( 'summary', $title );             	   		// locate the event
	
	$a = new valarm();                             				// initiate ALARM
	$a->setProperty( 'action', 'DISPLAY' );                  	// set what to do
	$a->setProperty( 'description', $title  );       			// describe alarm
	$a->setProperty( 'trigger', array( 'day' => 3 ));        	// set trigger 3 days before
	$e->setComponent( $a );                        				// add alarm component to event component as subcomponent
	$v->addComponent( $e );                        				// add component to calendar

}

$v->returnCalendar();                       				// generate and redirect output to user browser

exit;

?>