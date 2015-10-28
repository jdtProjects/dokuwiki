<?php

/**
 * Add online ordering forms to dokuwiki.
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Jannes Drost-Tenfelde <info@drost-tenfelde.de>
 */
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * OnlineOrdering plugin.
 * 
 * Form based ordering.
 */   
class syntax_plugin_onlineordering extends DokuWiki_Syntax_Plugin {

    /**
     * Returns plugin information.
     * 
     * @return array with plugin information.          
     */
    function getInfo() {
        return array(
                'author' => 'Jannes Drost-Tenfelde',
                'email'  => 'info@drost-tenfelde.de',
                'date'   => '2011-09-27',
                'name'   => 'Online Ordering',
                'desc'   => 'Allows users to order items online using forms.',
                'url'    => 'http://www.drost-tenfelde.de/?id=dokuwiki:plugins:onlineordering',
        );
    }

    /**
	 * Returns the syntax type of the plugin.
	 *
	 * @return type.
	 */
	function getType(){
		return 'substition';
	}

	/**
	 * Returns the paragraph type.
	 *
	 * @return paragraph type.
	 */
	function getPType(){
		return 'block';
	}

	/**
	 * Where to sort in?
	 */
	function getSort(){
		return 301;
	}

  /**
	 * Connects the syntax pattern to the lexer
	 */
	function connectTo($mode) {
		$this->Lexer->addSpecialPattern('\{\{onlineordering>[^}]*\}\}', $mode, 'plugin_onlineordering');
	}

	/**
	 * Handle the match
	 */
	function handle($match, $state, $pos, &$handler){
		global $ID;
		
        //strip markup from start and end
		$match = substr($match, 17, -2); 

		// Get the parameters (key=value), seperated by &
		$pairs = explode('&', $match);
		
		// Turn the pairs into key=>value
        foreach ($pairs as $pair) {
			list($key, $value) = explode('=', $pair, 2);
			$data[trim($key)] = trim($value);
        }
		// Turn all keys to lower case
        $data = array_change_key_case($data, CASE_LOWER);
        
        // Adjust price
        $price =  $data['price'];
        if ( isset( $price ) )
        {
            $price = str_replace('.', '', $price);
            $price = str_replace(',', '.', $price);
            $data['price'] = floatval( $price );
        }
        
        // Adjust porto
        $porto_default = $data['porto_default'];
        if ( isset( $porto_default ) )
        {
            $porto_default = str_replace('.', '', $porto_default);
            $porto_default = str_replace(',', '.', $porto_default);
            $data['porto_default'] = floatval( $porto_default );
        }        
		return $data;
	}

	/**
	 * Evaluates POST parameters and renders the appropriate for the current onlineticket stage.
	 *
	 * @param mode Display mode.
	 *
	 * @param renderer Renderer to which the output is sent.
	 *
	 * @param data data provided within the onlineticket tag.
	 * 
	 */
	function render($mode, &$renderer, $data) {
        global $conf;
		global $lang;
        		
		if ($mode != 'xhtml') {
            return false;
        }
		
  		$content = '<div id="onlineordering_plugin">';
          $message = '';
                                   
  		// Make sure the parameters are set properly
  		if ( $this->_validate_configuration( $data, $message ) ) {
            // Get the form variables
            $form = $this->_get_form_variables();
            
            // Ensure the variables are valid
            $message = '';
            if ( !$this->_validate_form( $form, $message ) )
            {
                // Display the error
                $content .= $this->getLang('error').'<br />'.$message;
                // Go one stage back
                $form['stage'] = 1;
            }            
              
            // Get the querystring
            $querystring = $_SERVER['QUERY_STRING'];
             
            // Get the appropriate form
            switch ( $form['stage'] )
      		{
                default:
      			case 1:
                    @include('order_form.php');
                    $content .= $output;
      				break;
      			case 2:
                    @include('confirm_form.php');
                    $content .= $output;
      				break;
      			case 3:
      			   @include('send_form.php');
                    $content .= $output;
      				break;
      		}
      	}
      	else {
            $content .= $this->getLang('error').'<br />'.$message;
        }
        $content .= '</div>';
        $renderer->doc .= $content;
        
		return true;
	}
    
    /**
	 * Checks if the data necessary for the onlineticket system is valid.
	 * 
	 * @param data Array with data provided within the onlineticket tag.
	 * 	 
	 * @param message Validation message.
	 * 
	 * @return boolean
	 */	 
	function _validate_configuration( &$data, &$message )
	{
        global $conf;
        global $lang;

        $validation = true;
        
        /* Parameters */
        // Ensure that the item name is set
        if ( !isset( $data['item_name'] ) ) {
            $message .= $this->_get_error_message('error_item_name_not_set');
            $validation = false;        
        }
        // Ensure that the abbreviation is set
        if ( !isset( $data['abbreviation'] ) ) {
            $message .= $this->_get_error_message('error_abbreviation_not_set');
            $validation = false;
        }
        // Ensure that the currencty is set
        if ( !isset( $data['currency'] ) ) {
            $message .= $this->_get_error_message('error_currency_not_set');
            $validation = false;
        }
        // Ensure that the price is set
        if ( !isset( $data['price'] ) ) {
            $message .= $this->_get_error_message('error_price_not_set');
            $validation = false;
        }
               
        /* Configuration */
        
        // Ensure Bank details have been set
        $bank_account = $data['bank_account'];
        if ( !isset($bank_account)) {
            $bank_account = $this->getConf('bank_account'); 
        }
        if ( (!isset($bank_account)) || $bank_account == '' ) {
            $message .= $this->_get_error_message('error_conf_bank_account_not_set');
            $validation = false;
        }
        // Ensure the Sender E-mail is set
        $sender_email = $data['sender_email'];
        if ( !isset($sender_email)) {
            $sender_email = $this->getConf('sender_email'); 
        }
        if ( (!isset($sender_email)) || $sender_email == '' ) {
            $message .= $this->_get_error_message('error_conf_sender_email_not_set');
            $validation = false;
        }        
        // Ensure the Sender E-mail is set
        $countries = $data['countries'];
        if ( !isset($countries)) {
            $countries = $this->getConf('countries'); 
        }
        if ( (!isset($countries)) || $countries == '' ) {
            $message .= $this->_get_error_message('error_conf_countries_not_set');
            $validation = false;
        }
                
        return $validation;
    }
    
    function _validate_form( $form, &$message )
    {
        $valid = true;
        
        $stage = $form['stage'];
        $message = '';
        
        switch ( $stage )
        {
            // Empty form
            default:
            case 1:             
                break;
            // Confirmation form
            case 2:
            case 3:
                // First name
                if ( ( !isset($form['firstname']) ) || $form['firstname'] == '' )
                {
                    $message .= $this->_get_error_message('error_firstname_not_set');
                    $valid = false;
                }
                // Last name
                if ( ( !isset($form['lastname']) ) || $form['lastname'] == '' )
                {
                    $message .= $this->_get_error_message('error_lastname_not_set');
                    $valid = false;
                }
                // Street + nr
                if ( ( !isset($form['street']) ) || $form['street'] == '' )
                {
                    $message .= $this->_get_error_message('error_street_not_set');
                    $valid = false;
                }
                // Zipcode
                if ( ( !isset($form['postcode']) ) || $form['postcode'] == '' )
                {
                    $message .= $this->_get_error_message('error_postcode_not_set');
                    $valid = false;
                }
                // Place
                if ( ( !isset($form['place']) ) || $form['place'] == '' )
                {
                    $message .= $this->_get_error_message('error_place_not_set');
                    $valid = false;
                }
                // Country
                if ( ( !isset($form['country']) ) || $form['country'] == '' )
                {
                    $message .= $this->_get_error_message('error_country_not_set');
                    $valid = false;
                }
                // E-mail
                if ( ( !isset($form['email']) ) || $form['email'] == '' )
                {
                    $message .= $this->_get_error_message('error_email_not_set');
                    $valid = false;
                }
                else {
                    // Check that the email is of a valid format
                    if (!eregi( "^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-zÀ-Ýß?*-ý 0-9-]+(\.[a-zÀ-Ýß?*-ý 0-9-]+)*(\.[a-z]{2,5})$", $form['email'] ) ) {
                        $message .= $this->_get_error_message('error_email_invalid');
                        $valid = false;
                    }
                }
                // Tickets
                if ( ( !isset($form['tickets']) ) || $form['tickets'] == '' )
                {
                    $message .= $this->_get_error_message('error_tickets_not_set');
                    $valid = false;
                }
                else {
                    if ( eregi('[^0-9]', $form['tickets']) || $form['tickets'] <= 0 ) {
                        $message .= $this->_get_error_message('error_tickets_invalid');
                        $valid = false;
                    }
                }
                break;
            // Send order
            case 2:
                break;
        }
        return $valid;       
    }
    
    /**
     * Returns the appropriate error message.
     * 
     * @return formatted error message.
     */                   
    function _get_error_message( $id )
    {
        return '<div id="onlineordering_error">'.$this->getLang( $id ).'</div>';
    }
    
    /**
     * Returns the form variables.
     * 
     * @return form variables.
     */                   
    function _get_form_variables()
    {
        $form = array();
        
        $stage = $_POST['onlineordering_stage'];
        if ( !isset( $stage ) )
        {
            $stage = 1;
        }
        else {
            $stage = $stage + 1;
        }
        $form['stage'] = $stage;
        $form['title'] = $_POST['onlineordering_title'];
        if ( !isset( $form['title'] ) )
        {
            $form['title'] = '';
        }
        
        $form['firstname'] = $_POST['onlineordering_firstname'];
        $form['lastname'] = $_POST['onlineordering_lastname'];
        $form['street'] = $_POST['onlineordering_street'];
        $form['postcode'] = $_POST['onlineordering_postcode'];
        $form['place'] = $_POST['onlineordering_place'];
        $form['country'] = $_POST['onlineordering_country'];
        $form['email'] = $_POST['onlineordering_email'];
        $form['tickets'] = $_POST['onlineordering_tickets'];
        $form['remarks'] = $_POST['onlineordering_remarks'];

        return $form;
    }
    
    /**
     * Returns the countries for which the orders can be made.
     * 
     * @return Array() of countries.
     */                   
    function _get_countries()
    {
        // Get the configured countries
        $conf_countries = $this->getConf('countries');
        $countries = explode( ",", $conf_countries );
        
        return $countries;
    }
    
    /**
     * Returns the porto settings for a specific country.
     * 
     * @param data plugin parameters.
     * @param country Country name.
     * @return porto.
     */                             
    function _get_porto( $data, $country )
    {
        // Attempt to get the porto for the country       
        $porto = $data['porto_'.strtolower($country)];
        if ( isset( $porto ) )
        {
            $porto = str_replace('.', '', $porto);
            $porto = str_replace(',', '.', $porto);
            $porto = floatval( $porto );
        }
        else {
            // Get the default porto
            $porto = $data['porto_default'];
            
            if ( !isset( $porto ) ) {
                // No porto then
                $porto = 0;
            }        
        }
        
        return $porto;
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
