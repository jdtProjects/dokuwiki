<?php

/**
 * Plugin facebookevents: Displays facebook events.
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @version    1.1
 * @date       March 2012
 * @author     J. Drost-Tenfelde <info@drost-tenfelde.de>
 *
 * This plugin uses the Facebook SDK.
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
 * This plugin retrieves facebook evetns and displays them in HTML.
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
        'date'   => '2012-02-09',
        'name'   => 'facebookevents',
        'desc'   => 'Displays facebook events',
        'url'    => 'http://www.drost-tenfelde.de/?id=dokuwiki:plugins:facebookevents',
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
            
            if (!class_exists('Facebook')) { 
              include_once('facebook.php');
            }
           
            // Make a query to get the events for a fanpageid
            $fql = "SELECT eid, name, pic, pic_small, pic_big, pic_square, start_time, end_time, location, description, is_date_only ". 
              " FROM event WHERE eid IN ( SELECT eid FROM event_member WHERE uid = ".$data['fanpageid']." ) ". 
              " ORDER BY start_time ".$data[FB_EVENTS_SORT];
           			     
            // Initialise Facebook
            $facebook = new Facebook( array(
                'appId'  => $data['appid'],
                'secret' => $data['secret'],
                'cookie' => true, // enable optional cookie support
            ) );
                       
            // Set the parameters
            $param = array(
                'method'    => 'fql.query',
                'query'     => $fql,
                'callback'  => ''
            );
            
            // Get the date format
            $date_format = $this->getConf(FB_EVENTS_DATE_FORMAT);
            $time_format = $this->getConf(FB_EVENTS_TIME_FORMAT);

            // Remember the "old" timezone"
            //$origin_timezone = date_default_timezone_get();
            
            // Set it to Facebook timezone
            date_default_timezone_set('Europe/Berlin');

            // Execute the query
            $fql_results = $facebook->api($param);
            
            $displayed_entries = 0;
            // Loop through the results  
            foreach( $fql_results as $keys => $values ) {
                $entry = $data['template'];
				
				// Convert the date time to old
				$values['start_time'] = strtotime($values['start_time']);
				if ( isset( $values['end_time']) ) {
					$values['end_time'] = strtotime($values['end_time']);
				}
				else {
					$values['end_time'] = $values['start_time'];
				}
				
                if ( !$values['description'] ) {
                    $values['description'] = $this->getLang('no_description');
                }
                
                if  ( !($values['end_time' ])) {
                    $values['end_time'] = $values['start_time'];
                }
                          
                // Is the start date lower than the current date?
                if ($data[FB_EVENTS_FROM_DATE]  && ($values['start_time'] < $data[FB_EVENTS_FROM_DATE] ) )  {
                    // Make sure the end-date lies after the current date
                    if ( (!isset($data[FB_EVENTS_TO_DATE])) || $values['end_time'] < $data[FB_EVENTS_TO_DATE] )  {
                      continue;
                    }
                }
                // If the date is higher than the to data, skip to the next
                if ( $data[FB_EVENTS_TO_DATE] && ($values['end_time'] < $data[FB_EVENTS_TO_DATE] ))  {
                    continue;
                }
                               
                // Limit?
                if ( isset( $data[FB_EVENTS_LIMIT]) && ($data[FB_EVENTS_LIMIT] > 0 ) ) {  
                    if ( strlen( $values['description'] ) > $data[FB_EVENTS_LIMIT] ) {    
                        $values['description'] = substr( $values['description'], 0, $data[FB_EVENTS_LIMIT] );
                        
                        // Find the first occurance of a space
                        $index = strrpos ( $values['description'], ' ' );
                       
                        $values['description'] = substr( $values['description'], 0, $index ).'...';
                    }
                }
                      
                // Process the description
                $values['description'] = str_replace("\r\n", '<html><br /></html>', $values['description'] );
                $values['description'] = str_replace("\n", '<html><br /></html>', $values['description'] );

                // Prepare the entry                
                $entry = str_replace('{title}', $values['name'], $entry );
                $entry = str_replace('{description}', $values['description'], $entry );
                $entry = str_replace('{location}', $values['location'], $entry );
                $entry = str_replace('{image}', $values['pic'], $entry);
                $entry = str_replace('{image_large}', $values['pic_big'], $entry);
                $entry = str_replace('{image_small}', $values['pic_small'], $entry);
                $entry = str_replace('{image_square}', $values['pic_square'], $entry);

                // Dates
                if ( (!isset( $data[FB_EVENTS_SHOW_END_TIMES])) || $data[FB_EVENTS_SHOW_END_TIMES] == '1' ) {
                    // Are they the same date?
                    $start_date = date( "Ymd", $values['start_time']);
                    $end_date = date( "Ymd", $values['end_time']);
					
                    if ( $start_date == $end_date ) {
                        $date_string = strftime( $date_format, $values['start_time']);
						$datetime_string = $date_string;
						if ( !$values['is_date_only']) {
							$datetime_string = $datetime_string.' '.strftime( $time_format, $values['start_time']).'-'.strftime( $time_format, $values['end_time']);
						}
                        $entry = str_replace('{date}', $date_string, $entry );
                        $entry = str_replace('{datetime}', $datetime_string, $entry );    
                    }
                    else {
                        $date_string = strftime( $date_format, $values['start_time']).' - '.strftime( $date_format, $values['end_time']);
						$datetime_string = $date_string;
						if ( !$values['is_date_only']) {
							$datetime_string =  strftime( $date_format, $values['start_time']).' '.strftime( $time_format, $values['start_time']).' - '.
												strftime( $date_format, $values['end_time']).' '.strftime( $time_format, $values['end_time']);                        
						}
                        $entry = str_replace('{date}', $date_string, $entry );
                        $entry = str_replace('{datetime}', $datetime_string, $entry );                    
                    }
                }
                else {                                                
                    $entry = str_replace('{date}', strftime( $date_format, $values['start_time']), $entry );
                    $entry = str_replace('{datetime}', strftime( $date_format, $values['start_time']).' '.strftime( $time_format, $values['start_time']) , $entry );
                }
                $entry = str_replace('{timestamp}', $values['start_time'], $entry);
                $entry = str_replace('{startdate}', strftime( $date_format, $values['start_time']), $entry);
                $entry = str_replace('{starttime}', strftime( $time_format, $values['start_time']), $entry);
                $entry = str_replace('{startdatetime}', strftime( $date_format, $values['start_time']).' '.strftime( $time_format, $values['start_time']), $entry);
                $entry = str_replace('{enddate}', strftime( $date_format, $values['end_time']), $entry);
                $entry = str_replace('{endtime}', strftime( $time_format, $values['end_time']), $entry);
                $entry = str_replace('{enddatetime}', strftime( $date_format, $values['end_time']).' '.strftime( $time_format, $values['end_time']), $entry);
                
                // [[ url | read more ]
                $event_url = "http://www.facebook.com/events/".$values['eid'];
                $entry = str_replace('{url}', $event_url, $entry );
                $entry = str_replace('{more}', '[['.$event_url.'|'.$this->getLang('read_more').']]', $entry );
                
                
                // Add the entry to the content                                
                $content .= $entry;

                // Only display a maximum number of entries
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