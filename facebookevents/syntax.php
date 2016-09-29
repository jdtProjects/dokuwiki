<?php

/**
 * Plugin facebookevents: Displays facebook events.
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @version    1.2
 * @date       September 2016
 * @author     J. Drost-Tenfelde <info@drost-tenfelde.de>
 *
 * This plugin uses Facebook's Graph API v2.7.
 *
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();
 
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

// Syntax parameters
define( "FB_EVENTS_APPLICATION_ID", "appid" );
define( "FB_EVENTS_SECRET", "secret" );
define( "FB_EVENTS_FAN_PAGE_ID", "fanpageid" );
define( "FB_EVENTS_SHOW_AS", "showAs" );
define( "FB_EVENTS_FROM_DATE", "from" );
define( "FB_EVENTS_TO_DATE", "to" );
define( "FB_EVENTS_NR_ENTRIES", "numberOfEntries" );
define( "FB_EVENTS_SHOW_END_TIMES", "showEndTimes" );
define( "FB_EVENTS_LIMIT", "limit" );

// Configuration parameters
define( "FB_EVENTS_DATE_FORMAT", "dformat" );
define( "FB_EVENTS_TIME_FORMAT", "tformat" );
define( "FB_EVENTS_TEMPLATE", "template" );

/**
 * This plugin retrieves facebook events and displays them in HTML.
 *
 * Usage: {{facebookevents#appid=1234&secret=12345&fanpageid=12345&showAs=default}}
 * 
 */
class syntax_plugin_facebookevents extends DokuWiki_Syntax_Plugin
{ 
    function getInfo() {
      return array(
        'author' => 'J. Drost-Tenfelde',
        'email'  => 'info@drost-tenfelde.de',
        'date'   => '20162-09-29',
        'name'   => 'facebookevents',
        'desc'   => 'Displays facebook events as HTML',
        'url'    => 'https://www.dokuwiki.org/plugin:facebookevents',
      );
    }
 
    // implement necessary Dokuwiki_Syntax_Plugin methods
    function getType() {
        return 'substition';
    }
    
    function getSort() {
        return 42;
    }
    
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{facebookevents.*?\}\}',$mode,'plugin_facebookevents');
    }
    
	function getData($url) {
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
	
	/**
	 * parse parameters from the {{facebookevents#...}} tag.
	 * @return an array that will be passed to the renderer function
	 */
	function handle($match, $state, $pos, &$handler) {
        $match = substr($match, 17, -2);
		parse_str($match, $params);
		
		// Make sure the necessary data is set
		if ( !$params[FB_EVENTS_APPLICATION_ID] ) {
		  $this->error = $this->getLang('error_appid_not_set');
        }
        if ( !$params[FB_EVENTS_SECRET] ) {
		  $this->error = $this->getLang('error_secret_not_set');
        }
        if ( !$params[FB_EVENTS_FAN_PAGE_ID] ) {
		  $this->error = $this->getLang('error_fanpageid_not_set');
        }
        if ( !$params[FB_EVENTS_SHOW_AS] ) {
            $params[FB_EVENTS_SHOW_AS] = 'default';
        }
        if ( !$params[FB_EVENTS_LIMIT] ) {
            $params[FB_EVENTS_LIMIT] = 0;        
        }
        
        // Get the appropriate display template
        $template = $this->getConf( $params[FB_EVENTS_SHOW_AS] );
        if ( !isset($template ) || $template == '' ) {
			$template = $this->getConf('default');
		}
        $params[FB_EVENTS_TEMPLATE] = $template;   
		
		// From
		if ($params[FB_EVENTS_FROM_DATE] == 'today') {
			$from = time();
		} else if (preg_match('#(\d\d)/(\d\d)/(\d\d\d\d)#', $params[FB_EVENTS_FROM_DATE], $fromDate)) {
			// must be MM/dd/yyyy
			$from = mktime(0, 0, 0, $fromDate[2], $fromDate[1], $fromDate[3]);
		} else if (preg_match('/\d+/', $params[FB_EVENTS_FROM_DATE])) {
			$from = $params[FB_EVENTS_FROM_DATE]; 
		}
		$params[FB_EVENTS_FROM_DATE] = $from;
		
        // Get the to parameter
		if ($params[FB_EVENTS_TO_DATE] == 'today') {
			$to = mktime(24, 0, 0, date("m") , date("d"), date("Y"));

		} else if (preg_match('#(\d\d)/(\d\d)/(\d\d\d\d)#', $params[FB_EVENTS_TO_DATE], $toDate)) {
			// must be MM/dd/yyyy
			$to = mktime(0, 0, 0, $toDate[2], $toDate[1], $toDate[3]);
		} else if (preg_match('/\d+/', $params[FB_EVENTS_TO_DATE])) {
			$to = $params[FB_EVENTS_TO_DATE]; 
		}
		$params[FB_EVENTS_TO_DATE] = $to;
		
		// Sorting
		if ( !$params[FB_EVENTS_SORT ] ) {
            $params[FB_EVENTS_SORT ] = 'ASC';
        }
        else {
            if ( $params[FB_EVENTS_SORT] != 'DESC') {
                $params[FB_EVENTS_SORT ] = 'ASC';
            }
        }
      	
		return $params;
	}
    
	/**
	 * Retrieves the facebook events and parses them to HTML.
	 */
	function render($mode, &$renderer, $data) {
        $info = $this->getInfo();
	
        $content = '';
       
		if ($mode == 'xhtml') {
            // Catch errors
            if ($this->error) {
                $renderer->doc .= 'Error in Plugin '.$info['name'].': '.$this->error;
                return;
            }
			
			// Get the date format
            $date_format = $this->getConf(FB_EVENTS_DATE_FORMAT);
            $time_format = $this->getConf(FB_EVENTS_TIME_FORMAT);
			
			// Get the facebook information
			$fb_app_id = $data[FB_EVENTS_APPLICATION_ID];
			$fb_secret = $data[FB_EVENTS_SECRET];
			$fb_page_id = $data[FB_EVENTS_FAN_PAGE_ID];
			
			// Get the access token using app-id and secret
			$token_url ="https://graph.facebook.com/oauth/access_token?client_id={$fb_app_id}&client_secret={$fb_secret}&grant_type=client_credentials";
			$token_data = $this->getData( $token_url );
			
			$elements = split("=", $token_data );
			if ( count($elements) < 2) {
				$renderer->doc .= 'Access token could not be retrieved for Plugin '.$info['name'].': '.$this->error;
				return;
			}
			$fb_access_token = $elements[1];
			
			// Get the events
			$since_date = $data[FB_EVENTS_FROM_DATE];
			$until_date = $data[FB_EVENTS_TO_DATE];
			
			$fb_fields="id,name,description,place,timezone,start_time,end_time,cover";
			
			$json_link = "https://graph.facebook.com/v2.7/{$fb_page_id}/events/attending/?fields={$fb_fields}&access_token={$fb_access_token}&since={$since_date}&until={$until_date}";
			$json = $this->getData( $json_link);			
			
			//$objects = json_decode($json, true, 512, JSON_BIGINT_AS_STRING);
			$objects = json_decode($json, true);
			
			
			// count the number of events
			$event_count = count($objects['data']);
			$displayed_entries = 0;
			// Loop through the events
			for ($index = 0; $index < $event_count; $index++){			
				$event = $objects['data'][$index];
				
				date_default_timezone_set($event['timezone']);
				
				$start_date = date( $date_format, strtotime($event['start_time']));			
				$start_time = date( $time_format, strtotime($event['start_time']));
				
				if ( !isset($event['end_time'])) {
					$event['end_time'] = $event['start_time'];
				}			
				$end_date = date( $date_format, strtotime($event['end_time']));
				$end_time = date( $time_format, strtotime($event['end_time']));
				
				$eid = $event['id'];
				$name = $event['name'];
				
				$description = isset($event['description']) ? $event['description'] : "";
				// Limit?
				if ( isset( $data[FB_EVENTS_LIMIT]) && ($data[FB_EVENTS_LIMIT] > 0 ) ) {  
					if ( strlen( $description ) > $data[FB_EVENTS_LIMIT] ) {    
						$description = substr( $description, 0, $data[FB_EVENTS_LIMIT] );
						// Find the first occurance of a space
						$index = strrpos ( $description, ' ' );
						$description = substr( $description, 0, $index ).'...';
					}
				}
				$description = str_replace("\r\n", '<html><br /></html>', $description );
				$description = str_replace("\n", '<html><br /></html>', $description );

				
				$pic = isset($event['cover']['source']) ? $event['cover']['source'] : "https://graph.facebook.com/v2.7/{$fb_page_id}/picture";
				$pic_large = isset($event['cover']['source']) ? $event['cover']['source'] : "https://graph.facebook.com/v2.7/{$fb_page_id}/picture?type=large";
				$pic_small = isset($event['cover']['source']) ? $event['cover']['source'] : "https://graph.facebook.com/v2.7/{$fb_page_id}/picture?type=small";

				// place
				$place_name = isset($event['place']['name']) ? $event['place']['name'] : "";
				$street = isset($event['place']['location']['street']) ? $event['place']['location']['street'] : "";
				$city = isset($event['place']['location']['city']) ? $event['place']['location']['city'] : "";
				$country = isset($event['place']['location']['country']) ? $event['place']['location']['country'] : "";
				$zip = isset($event['place']['location']['zip']) ? $event['place']['location']['zip'] : "";
 
				$location="";
 
				if ( $place_name && $street & $city && $country && $zip){
					$location = "{$place_name}, {$street}, {$zip} {$city}, {$country}";
				}
				else{
					$location = "Location not set or event data is too old.";
				}
				
				// Build the entry
				$entry = $data['template'];
				
				// Replace the values
				$entry = str_replace('{title}', $name, $entry );
				$entry = str_replace('{description}', $description, $entry );
				$entry = str_replace('{location}', $location, $entry );
				$entry = str_replace('{place}', $place_name, $entry );
				$entry = str_replace('{city}', $city, $entry );
				$entry = str_replace('{country}', $country, $entry );
				$entry = str_replace('{zip}', $zip, $entry );
				$entry = str_replace('{image}', $pic, $entry);
				$entry = str_replace('{image_large}', $pic_large, $entry);
				$entry = str_replace('{image_small}', $pic_small, $entry);
				$entry = str_replace('{image_square}', $pic, $entry); // Backward compatibility
				
				// DateTime
				if ( (!isset( $data[FB_EVENTS_SHOW_END_TIMES])) || $data[FB_EVENTS_SHOW_END_TIMES] == '1' ) {

					// Are they the same date?
					$compare_start_date = date( "Ymd", strtotime($event['start_time']));
					$compare_end_date = date( "Ymd", strtotime($event['end_time']));
					
					if ( $compare_start_date == $compare_end_date ) {
						$datetime_string = $start_date;
						//if ( isset($event['is_date_only']) && (!$event['is_date_only'])) {
							$datetime_string = $datetime_string.' '.$start_time.' - '.$end_time;
							
						//}
						$entry = str_replace('{date}', $date_string, $entry );
						$entry = str_replace('{datetime}', $datetime_string, $entry );
											}
					else {
						$date_string = $start_date.' - '.$end_date;
						$datetime_string = $date_string;
						//if ( isset($event['is_date_only']) && (!$event['is_date_only'])) {
							$datetime_string =  $start_date.' '.$start_time.' - '.$end_date.' '.$end_time;
						//}
						$entry = str_replace('{date}', $date_string, $entry );
						$entry = str_replace('{datetime}', $datetime_string, $entry );                    
					}
				}
				else {                                                
					$entry = str_replace('{date}', $start_date, $entry );
					$entry = str_replace('{datetime}', $start_date.' '.$start_time);
				}

				
				// [[ url | read more ]
                $event_url = "http://www.facebook.com/events/".$eid;
                $entry = str_replace('{url}', $event_url, $entry );
                $entry = str_replace('{more}', '[['.$event_url.'|'.$this->getLang('read_more').']]', $entry );
				
				// Add the entry to the content                                
				$content .= $entry;

				// Only display a maximum number of entries (if set)
				$displayed_entries++;
				if ( isset( $data[FB_EVENTS_NR_ENTRIES] ) && $displayed_entries >= $data[FB_EVENTS_NR_ENTRIES] ) {
					break;
				}
			}
		
			//$renderer->doc .= $ret;
			$html = p_render($mode, p_get_instructions( $content ), $info );
			$renderer->doc .= $html;
			
			// Set the timezone back to the original
			//date_default_timezone_set($origin_timezone);
			
			return true;
		}
		return false;
	}
}

?>