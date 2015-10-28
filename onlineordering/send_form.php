<?php

/**
 * Calculates a new random ticket number.
 * 
 * @param digits number of digits the ticket number should have.
 * 
 * @return ticket number.    
 */ 
function ticketNumber($digits) {
	$result = "";
	for ($index = 0; $index < $digits; $index++) {
		$random = (rand()%9);
		$result = $result.$random;
	}
	return $result;
}

/**
 * Prepares text for HTML display.
 * 
 * @param text Text to display.
 * @return HTML.
 */    
function prepareHTML( $text )
{
	$text = str_replace('\n', '<br/>', $text );
	//Replace special chars
	$text = str_replace('ä', '&auml;', $text );
	$text = str_replace('Ä', '&Auml;', $text );
	$text = str_replace('ö', '&ouml;', $text );
	$text = str_replace('Ö', '&Ouml;', $text );
	$text = str_replace('ß', '&szlig;', $text );
	$text = str_replace('ü', '&uuml;', $text );
	$text = str_replace('Ü', '&Uuml;', $text );
	$text = str_replace('\n', '<br />', $text );
	return $text;
}

/**
 * Prepares text for mail sending.
 * 
 * @param text text to send via mail.
 * @param prepared mail.
 */    
function prepareMail( $text )
{
	//Replace special chars
	$text = str_replace('ä', 'ae', $text );
	$text = str_replace('Ä', 'Ae', $text );
	$text = str_replace('ö', 'oe', $text );
	$text = str_replace('Ö', 'Oe', $text );
	$text = str_replace('ü', 'ue', $text );
	$text = str_replace('Ü', 'Ue', $text );
	$text = str_replace('\n', '<br />', $text );
	return $text;
}

// Calculate a new ticket number

$ticket = $data['abbreviation'].ticketNumber(5);

$template = @file_get_contents(DOKU_PLUGIN.'onlineordering/template_'.$conf['lang'].'.txt');

// Replace parameters
$template = str_replace('{title}', $form['title'], $template);
$template = str_replace('{firstname}', $form['firstname'], $template);
$template = str_replace('{lastname}', $form['lastname'], $template);
$template = str_replace('{postcode}', $form['postcode'], $template);
$template = str_replace('{street}', $form['street'], $template);
$template = str_replace('{place}', $form['place'], $template);
$template = str_replace('{country}', $form['country'], $template);
$template = str_replace('{remarks}', $form['remarks'], $template);

$template = str_replace('{ticket}', $ticket, $template);
$template = str_replace('{nr_tickets}', $form['tickets'], $template);

$template = str_replace('{item_name}', $data['item_name'], $template);
$template = str_replace('{currency}', $data['currency'], $template);
$template = str_replace('{price}', number_format($data['price'],2,',','.'), $template);

$bank_account = $data['bank_account'];
if ( !isset($bank_account) ) {
    $bank_account = $this->getConf('bank_account');
}
$template = str_replace('{bank_account}', $bank_account, $template);
                                                                    
$porto = $this->_get_porto( $data, $form['country'] );
$total_price = ($data['price'] * $form['tickets']) + $porto;


$template = str_replace('{porto}', number_format($porto,2,',','.'), $template);
$template = str_replace('{total_price}', number_format($total_price,2,',','.'), $template);

$date = date("d-m-Y");
$time = date("H:m");

$datetime = $date.' '.$time;

$template = str_replace('{date}', $date, $template);
$template = str_replace('{time}', $time, $template);
$template = str_replace('{datetime}', $datetime, $template);

$template = str_replace('{signature}', $this->getConf('signature'), $template);

$mail = prepareMail( $template );

// Prepare email
$subject = "Online ordering of ".$data['item_name'];

// Get the sender_email from the parameters
$sender_email = $data['sender_email'];
if ( !isset( $sender_email))
{
    // Get the sender_email from the configuration
    $sender_email = $this->getConf('sender_email');
}

// Make the from
$from = 'From: ';
// Get the sender name
$sender_name = $data['sender_name'];
if ( !isset( $sender_name ) ) {
    $sender_name = $this->getConf('sender_name'); 
}
if ( isset( $sender_name ) )
{
    $from .= '"'.$sender_name.'" '; 
}
// Add the email
$from .= '<'.$sender_email.'>\r\n';
// Make a header
$header  = 'MIME-Version: 1.0' . "\r\n";
$header .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

// Make the recipient address
$recipient = '"'.$form['firstname'].' '.$form['lastname'].'" <'.$form['email'].'>';

// Get the cc email from the parameters
$email_cc = $data['email_cc'];
if ( !isset( $email_cc ) ) {
    // Get the cc email from the configuration
    $email_cc = $this->getConf("email_cc");
}

if ( isset( $email_cc ) ) {
    $email_cc = str_replace(', ', ',', $email_cc );
    // Add blind copies
    $header .= "BCC: ".$email_cc."\r\n";
}

$result = @mail($recipient, $subject, $mail, $header.$from);
if ($result) {
    $output .= '<p>';
    $output .= '<b>'.$this->getLang('thank_you_for_ordering').'</b><br /><br />';
    
    $sent_notice = str_replace('{email}', $form['email'], $this->getLang('sent_notice'));
    $sent_notice = str_replace('{name}', $form['firstname'].' '.$form['lastname'], $sent_notice );
     
    $output .= $sent_notice.':<br /><br />';
    $output .= '<code>';
	$output .= prepareHTML( $template );
	$output .= '</code>';
	$output .= '</p>';
		
}
else {
    $form['stage'] = 0;
    @include('order_form.php');
    $output = '<div id="onlineordering_error">'.str_replace('{email}', $form['email'], $this->getLang('send_failure')).'</div>'.$output;    
}

?>