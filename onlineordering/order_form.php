<?php        


$output = "";

  //Make the form
$output .= '<a name="form"></a>';
          
//Add the fields
$output .= '<form name="onlineordering_form" id="onlineordering_form" method="post" action="?'.$_SERVER['QUERY_STRING'].'#form">';
$output .= '<input type="hidden" name="onlineordering_stage" id="onlineordering_stage" value="1">';

$output .= '<b>'.$this->getLang('enter_values').'</b><br />';
$output .= $this->getLang('mandatory_fields').'<br /><br />';

$output .= '<table>';
           
//Title
$form_title = $form['title'];
$output .= '<tr>';
$output .= 	'<th><label for="title">'.$this->getLang('title').'</label></th>';
$output .= 	'<td><select size="1" name="onlineordering_title" id="onlineordering_title" tabindex="0">';
//No-Title
$output .= '<option ';
if ($form_title == "") { $output .= 'selected'; }
$output .= ' value=""></option>';
$output .= '<option ';
//Ms
if ($form_title == $this->getLang('field_title_ms') ) { $output .= 'selected'; }
$output .= ' value="'.$this->getLang('field_title_ms').'">'.$this->getLang('field_title_ms').'</option>';
$output .= '<option ';
//Mr
if ($form_title == $this->getLang('field_title_mr') ) { $output .= 'selected'; }
$output .= ' value="'.$this->getLang('field_title_mr').'">'.$this->getLang('field_title_mr').'</option>';
$output .= '</select></td>';
$output .= '</tr>';

//Firstname
$output .= '<tr>';
$output .= 	'<th><label for="firstname">'.$this->getLang("firstname").'<i>*</i></label></th>';
$output .= 	'<td><input type="text" name="onlineordering_firstname" id="onlineordering_firstname" tabindex="1" size="20" value="'.$form['firstname'].'"/></td>';
$output .= '</tr>';

//Lastname
$output .= '<tr>';
$output .= 	'<th><label for="lastname">'.$this->getLang('lastname').'<i>*</i></label></th>';
$output .= 	'<td><input type="text" name="onlineordering_lastname" id="onlineordering_lastname" tabindex="2" size="20" value="'.$form['lastname'].'"/></td>';
$output .= '</tr>';

//Street
$output .= '<tr>';
$output .= 	'<th><label for="street">'.$this->getLang('street').'<i>*</i></label></th>';
$output .= 	'<td><input type="text" name="onlineordering_street" id="onlineordering_street" tabindex="3" size="40" value="'.$form['street'].'"/></td>';
$output .= '</tr>';

//Zipcode
$output .= '<tr>';
$output .= 	'<th><label for="postcode">'.$this->getLang('postcode').'<i>*</i></label></th>';
$output .= 	'<td><input type="text" name="onlineordering_postcode" id="onlineordering_postcode" tabindex="4" size="5" value="'.$form['postcode'].'"/></td>';
$output .= '</tr>';

//City
$output .= '<tr>';
$output .= 	'<th><label for="place">'.$this->getLang('place').'<i>*</i></label></th>';
$output .= 	'<td><input type="text" name="onlineordering_place" id="onlineordering_place" tabindex="5" size="20" value="'.$form['place'].'"/></td>';
$output .= '</tr>';

//Country
$output .= '<tr>';
$output .= 	'<th><label for="country">'.$this->getLang('country').'<i>*</i></label></th>';
$output .= 	'<td><select size="1" name="onlineordering_country" id="onlineordering_country" tabindex="6">';

// Get the countries
$countries = $data['countries'];
if ( !isset( $countries ) )
{
    // Use the configuration parameter
    $countries = $this->_get_countries();
}

if ( count( $countries ) == 1 )
{
    $output .= '<option selected value="'.$countries[0].'">'.$countries[0].'</option>';
}
else {
    // Loop through the countries
    for ( $index = 0; $index < count( $countries ); $index++ )
    {
        $country = $countries[ $index ];
             
        $output .= '<option ';
        if ( $country == $form['country'])
        {
            $output .= 'selected ';
        }            
        $output .= 'value="'.$country.'">'.$country.'</option>';
    }
}

$output .= '</select></td>';
$output .= '</tr>';

//Email
$output .= '<tr>';
$output .= 	'<th><label for="email">'.$this->getLang('email').'<i>*</i></label></th>';
$output .= 	'<td><input type="text" name="onlineordering_email" id="onlineordering_email" tabindex="7" size="20" value="'.$form['email'].'"/></td>';
$output .= '</tr>';
           
//Tickets
$output .= '<tr>';
$output .= '<th><label for="tickets">'.$this->getLang('nr_tickets').'<i>*</i></label></th>';
$output .= '<td><input type="text" name="onlineordering_tickets" id="onlineordering_tickes" tabindex="8" size="3" value="'.$form['tickets'].'"/>';

$porto = '';
if ( count($countries) == 1 )
{
    // Get the porto of the country
    $porto = number_format($this->_get_porto($data, $countries[0]), 2, ",", ":").' '.$data['currency']. ' ';
}
$output .= ' ('.$this->getLang("ppu").': '.number_format($data['price'],2,",",":").' '.$data['currency'].' + '.$porto.$this->getLang('porto').' )</td>';
$output .= '</tr>';
  
//Remarks
$output .= '<tr>';
$output .= '<th><label for="remarks">'.$this->getLang('remarks').'</label></th>';
$output .= '<td colspan="2"><textarea cols="40" rows="5" name="onlineordering_remarks" id="onlineordering_remarks" tabindex="9">'.$form['remarks'].'</textarea></td>';
$output .= '</tr>';

$output .= '</table>';

//Submit
$output .= '<br /><input type="submit" name="Submit" value="'.$this->getLang('confirm').'" tabindex="10" />';
$output .= '</form>';


?>