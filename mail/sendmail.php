<?php
/*
 * test_smtp.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/smtp/test_smtp.php,v 1.19 2011/02/03 08:08:24 mlemos Exp $
 *
 */

//sendmail("ksaye@saye.org", "notifications@troop1978.org", "mysubject is here", "mybody is here");

function sendmail($toAddress, $fromAddress, $subject, $body) {
	require("smtp.php");
	require("sasl.php");
	
	$from="$fromAddress";                           /* Change this to your address like "me@mydomain.com"; */ $sender_line=__LINE__;
	$to="$toAddress";                             /* Change this to your test recipient address */ $recipient_line=__LINE__;

	$smtp=new smtp_class;

	$smtp->host_name="smtp.sendgrid.net";       /* Change this variable to the address of the SMTP server to relay, like "smtp.myisp.com" */
	$smtp->host_port=587;                /* Change this variable to the port of the SMTP server to use, like 465 */
	$smtp->ssl=0;                       /* Change this variable if the SMTP server requires an secure connection using SSL */

	$smtp->start_tls=1;                 /* Change this variable if the SMTP server requires security by starting TLS during the connection */
	$smtp->localhost="www.crew1978.org";       /* Your computer address */
	$smtp->timeout=10;                  /* Set to the number of seconds wait for a successful connection to the SMTP server */
	$smtp->data_timeout=0;              /* Set to the number seconds wait for sending or retrieving data from the SMTP server.
	                                       Set to 0 to use the same defined in the timeout variable */
	
	$smtp->user="azure_4e1178006c056afc5454bd5d5952c917@azure.com";                     /* Set to the user name if the server requires authetication */
	$smtp->password="ng7o9PDTlRF3h2e";                 /* Set to the authetication password */

	/*
	 * If you need to use the direct delivery mode and this is running under
	 * Windows or any other platform that does not have enabled the MX
	 * resolution function GetMXRR() , you need to include code that emulates
	 * that function so the class knows which SMTP server it should connect
	 * to deliver the message directly to the recipient SMTP server.
	 */

	$smtp->SendMessage($from,array($to),array(
			"From: $from",
			"To: $to",
			"Subject: $subject",
			"Date: ".strftime("%a, %d %b %Y %H:%M:%S %Z")
		),
		"$body");

    }
?>