<?php        


$output = "";

  //Make the form
$output .= '<a name="form"></a>';
          
//Add the fields
$output .= '<form name="onlineordering_form" id="onlineordering_form" method="post" action="?'.$_SERVER['QUERY_STRING'].'#form">';
$output .= '<input type="hidden" name="onlineordering_stage" id="onlineordering_stage" value="'.$form['stage'].'">';
$output .= '<input type="hidden" name="onlineordering_title" id="onlineordering_title" value="'.$form['title'].'">';
$output .= '<input type="hidden" name="onlineordering_firstname" id="onlineordering_firstname" value="'.$form['firstname'].'">';
$output .= '<input type="hidden" name="onlineordering_lastname" id="onlineordering_lastname" value="'.$form['lastname'].'">';
$output .= '<input type="hidden" name="onlineordering_street" id="onlineordering_street" value="'.$form['street'].'">';
$output .= '<input type="hidden" name="onlineordering_postcode" id="onlineordering_postcode" value="'.$form['postcode'].'">';
$output .= '<input type="hidden" name="onlineordering_place" id="onlineordering_place" value="'.$form['place'].'">';
$output .= '<input type="hidden" name="onlineordering_country" id="onlineordering_country" value="'.$form['country'].'">';
$output .= '<input type="hidden" name="onlineordering_email" id="onlineordering_email" value="'.$form['email'].'">';
$output .= '<input type="hidden" name="onlineordering_tickets" id="onlineordering_tickets" value="'.$form['tickets'].'">';
$output .= '<input type="hidden" name="onlineordering_remarks" id="onlineordering_remarks" value="'.$form['remarks'].'">';

$output .= '<b>'.$this->getLang('confirm_values').'</b><br /><br />';

$output .= '<table>';
            
//Title
$form_title = $form['title'];
$output .= '<tr>';
$output .= 	'<th><label for="title">'.$this->getLang('title').'</label></th>';
$output .= 	'<td>'.$form['title'].'</td>';
$output .= '</tr>';


//Firstname
$output .= '<tr>';
$output .= 	'<th><label for="firstname">'.$this->getLang("firstname").'</label></th>';
$output .= 	'<td>'.$form['firstname'].'</td>';
$output .= '</tr>';

//Lastname
$output .= '<tr>';
$output .= 	'<th><label for="lastname">'.$this->getLang('lastname').'</label></th>';
$output .= 	'<td>'.$form['lastname'].'</td>';
$output .= '</tr>';

//Street
$output .= '<tr>';
$output .= 	'<th><label for="street">'.$this->getLang('street').'</label></th>';
$output .= 	'<td>'.$form['street'].'</td>';
$output .= '</tr>';

//Zipcode
$output .= '<tr>';
$output .= 	'<th><label for="postcode">'.$this->getLang('postcode').'</label></th>';
$output .= 	'<td>'.$form['postcode'].'</td>';
$output .= '</tr>';

//Place
$output .= '<tr>';
$output .= 	'<th><label for="place">'.$this->getLang('place').'</label></th>';
$output .= 	'<td>'.$form['place'].'</td>';
$output .= '</tr>';

//Country
$output .= '<tr>';
$output .= 	'<th><label for="country">'.$this->getLang('country').'</label></th>';
$output .= 	'<td>'.$form['country'].'</td>';
$output .= '</tr>';

//Email
$output .= '<tr>';
$output .= 	'<th><label for="email">'.$this->getLang('email').'</label></th>';
$output .= 	'<td>'.$form['email'].'</td>';
$output .= '</tr>';
           
//Tickets
$output .= '<tr>';
$output .= '<th><label for="price">'.$this->getLang('price').'</label></th>';

// Calculate the price

$porto = $this->_get_porto( $data, $form['country'] );

$price = $data['price'];

$output .= '<td>'.number_format(($price*$form['tickets']) + $porto,2,',','.').' '.$data['currency'];
$output .= ' ('.$form['tickets'].' x '.number_format($price,2,',','.').' '.$data['currency'].' + '.number_format($porto, 2, ",",":").' '.$data['currency'].' '.$this->getLang('porto').')</td>';

$output .= '</tr>';
  
//Remarks
$output .= '<tr>';
$output .= '<th><label for="remarks">'.$this->getLang('remarks').'</label></th>';
$output .= '<td>'.$form['remarks'].'</td>';
$output .= '</tr>';
 
$output .= '</table>';

//Submit
$output .= '<br /><input type="submit" name="Submit" value="'.$this->getLang('send').'" tabindex="10" />';
$output .= '</form>';
//Correct values
$output .= '<form name="onlineordering_form" id="onlineordering_form" method="post" action="?'.$_SERVER['QUERY_STRING'].'#form">';
$output .= '<input type="hidden" name="onlineordering_stage" id="onlineordering_stage" value="0">';
$output .= '<input type="hidden" name="onlineordering_title" id="onlineordering_title" value="'.$form['title'].'">';
$output .= '<input type="hidden" name="onlineordering_firstname" id="onlineordering_firstname" value="'.$form['firstname'].'">';
$output .= '<input type="hidden" name="onlineordering_lastname" id="onlineordering_lastname" value="'.$form['lastname'].'">';
$output .= '<input type="hidden" name="onlineordering_street" id="onlineordering_street" value="'.$form['street'].'">';
$output .= '<input type="hidden" name="onlineordering_postcode" id="onlineordering_postcode" value="'.$form['postcode'].'">';
$output .= '<input type="hidden" name="onlineordering_place" id="onlineordering_place" value="'.$form['place'].'">';
$output .= '<input type="hidden" name="onlineordering_country" id="onlineordering_country" value="'.$form['country'].'">';
$output .= '<input type="hidden" name="onlineordering_email" id="onlineordering_email" value="'.$form['email'].'">';
$output .= '<input type="hidden" name="onlineordering_tickets" id="onlineordering_tickets" value="'.$form['tickets'].'">';
$output .= '<input type="hidden" name="onlineordering_remarks" id="onlineordering_remarks" value="'.$form['remarks'].'">';
$output .= '<input type="submit" name="Submit" value="'.$this->getLang('correct').'" tabindex="10" />';
$output .= '</form>';







?>