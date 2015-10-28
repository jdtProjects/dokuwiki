<?php

/**
 * Plugin facebookwall: Displays status messages on a facebook wall.
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @version    1.2
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

// Safeguard against multiple usage of the facebook class
if (!class_exists('Facebook'))
{ 
    include_once(DOKU_INC.'lib/plugins/facebookwall/facebook.php');
}

// Syntax parameters
define( "FB_WALL_APPLICATION_ID", "appid" ); // Facebook application id
define( "FB_WALL_SECRET", "secret" ); // Facebook secret
define( "FB_WALL_FAN_PAGE_ID", "fanpageid" ); // Facebook page id
define( "FB_WALL_SHOW_AS", "showAs" ); // Allow alternate displaying of the status messages
define( "FB_WALL_FROM_DATE", "from" ); // date from which to include the status message
define( "FB_WALL_TO_DATE", "to" ); // date to which to include status messages 
define( "FB_WALL_NR_ENTRIES", "numberOfEntries" ); // maximum number of entries
define( "FB_WALL_SORT", "sort" ); // Sort by create date ASC or DESC
define( "FB_WALL_LIMIT", "limit" ); // Limit size of the description by number of chars

// Configuration parameters
define( "FB_WALL_DATE_FORMAT", "dformat" ); // Date format
define( "FB_WALL_TIME_FORMAT", "tformat" ); // Time format
define( "FB_WALL_TEMPLATE", "template" ); // Template
 
/**
 * This plugin retrieves facebook status messages and displays them in HTML.
 *
 * Usage: {{facebookwall#appid=1234&secret=12345&fanpageid=12345&showAs=default}}
 * 
 */
class syntax_plugin_facebookwall extends DokuWiki_Syntax_Plugin
{
    function getInfo() {
      return array(
        'author' => 'J. Drost-Tenfelde',
        'email'  => 'info@drost-tenfelde.de',
        'date'   => '2012-02-09',
        'name'   => 'facebookwall',
        'desc'   => 'Displays status messages on a facebook wall',
        'url'    => 'http://www.drost-tenfelde.de/?id=dokuwiki:plugins:facebookwall',
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
        $this->Lexer->addSpecialPattern('\{\{facebookwall.*?\}\}',$mode,'plugin_facebookwall');
    }
    
	/**
	 * parse parameters from the {{facebookwall#...}} tag.
	 * @return an array that will be passed to the renderer function
	 */
	function handle($match, $state, $pos, &$handler) {
        $match = substr($match, 15, -2);
		parse_str($match, $params);
		
		// Make sure the necessary data is set
		if ( !$params[FB_WALL_APPLICATION_ID] ) {
		  $this->error = $this->getLang('error_appid_not_set');
        }
        if ( !$params[FB_WALL_SECRET] ) {
		  $this->error = $this->getLang('error_secret_not_set');
        }
        if ( !$params[FB_WALL_FAN_PAGE_ID] ) {
		  $this->error = $this->getLang('error_fanpageid_not_set');
        }
        if ( !$params[FB_WALL_SHOW_AS] ) {
            $params[FB_WALL_SHOW_AS] = 'default';
        }
        if ( !$params[FB_WALL_LIMIT] ) {
            $params[FB_WALL_LIMIT] = 0;        
        }
        
        // Get the appropriate display template
        $template = $this->getConf( $params[FB_WALL_SHOW_AS] );
        if ( !isset($template ) || $template == '' ) {
			$template = $this->getConf('default');
		}
        $params[FB_WALL_TEMPLATE] = $template;   
		
		// Get the FROM_DATE parameter
		if ($params[FB_WALL_FROM_DATE] == 'today') {
			$from = time();
		}
        else if (preg_match('#(\d\d)/(\d\d)/(\d\d\d\d)#', $params[FB_WALL_FROM_DATE], $fromDate)) {
			// must be MM/dd/yyyy
			$from = mktime(0, 0, 0, $fromDate[1], $fromDate[2], $fromDate[3]);
		}
        else if (preg_match('/\d+/', $params[FB_WALL_FROM_DATE])) {
			$from = $params[FB_WALL_FROM_DATE]; 
		}
		$params[FB_WALL_FROM_DATE] = $from;
		
        // Get the to parameter
		if ($params[FB_WALL_TO_DATE] == 'today') {
			$to = mktime(24, 0, 0, date("m") , date("d"), date("Y"));

		}
        else if (preg_match('#(\d\d)/(\d\d)/(\d\d\d\d)#', $params[FB_WALL_TO_DATE], $toDate)) {
			// must be MM/dd/yyyy
			$to = mktime(0, 0, 0, $toDate[1], $toDate[2], $toDate[3]);
		}
        else if (preg_match('/\d+/', $params[FB_WALL_TO_DATE])) {
			$to = $params[FB_WALL_TO_DATE]; 
		}
		$params[FB_WALL_TO_DATE] = $to;
		
		// Sorting
		if ( !$params[FB_WALL_SORT ] ) {
            $params[FB_WALL_SORT ] = 'DESC';
        }
        else {
            if ( $params[FB_WALL_SORT] != 'ASC') {
                $params[FB_WALL_SORT ] = 'DESC';
            }
        }
      	
		return $params;
	}
    
	/**
	 * Retrieves the facebook events and parses them to HTML.
	 */
	function render($mode, &$renderer, $data) {
        global $conf;
        
        $info = $this->getInfo();
	
        $content = '';
        
		if ($mode == 'xhtml') {
            // Catch errors
            if ($this->error) {
                $renderer->doc .= 'Error in Plugin '.$info['name'].': '.$this->error;
                return;
            }
            
            // Make a query to get the events for a fanpageid
            
            $fql = 'SELECT post_id, message, created_time, attachment from stream where source_id = '.$data['fanpageid'];
            $fql .= ' ORDER BY created_time '.$data[FB_WALL_SORT];
           			     
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
            $date_format = $this->getConf(FB_WALL_DATE_FORMAT);
            $time_format = $this->getConf(FB_WALL_TIME_FORMAT);
            $datetime_format = $date_format.' '.$time_format;

            // Get the time offset
            //$offset = $this->get_timezone_offset( "America/Los_Angeles" );
            $offset = 0;
            
            // Execute the query
            $fql_results = $facebook->api($param);
            
            $displayed_entries = 0;           
                       
            // Loop through the results  
            foreach( $fql_results as $keys => $values ) {
                $entry = $data[FB_WALL_TEMPLATE];
                
                // Process the date
                $values['created_time'] = $values['created_time'] - $offset;
                
                // If no message was provided, skip to the next
                if ( !$values['message'] ) {
                    continue;
                }
                
                // If the date is lower than the from date, skip to the next
                if ( isset($data[FB_WALL_FROM_DATE]) && ($values['created_time'] < $data[FB_WALL_FROM_DATE] ) )  {
                    continue;
                }
                // If the date is higher than the to data, skip to the next
                if ( $data[FB_WALL_TO_DATE] && ($values['created_time'] > $data[FB_WALL_TO_DATE] ))  {
                    continue;
                }

                // Limit?
                if ( isset( $data[FB_WALL_LIMIT]) && ($data[FB_WALL_LIMIT] > 0 ) ) {  
                    if ( strlen( $values['message'] ) > $data[FB_WALL_LIMIT] ) {    
                        $values['message'] = substr( $values['message'], 0, $data[FB_WALL_LIMIT] ).'...';
                    }
                }                 
                // Process the message                               
                $values['message'] = str_replace("\r\n", '<html><br /></html>', $values['message'] );
                $values['message'] = str_replace("\n", '<html><br /></html>', $values['message'] );
                
                $entry = str_replace('{message}', $values['message'], $entry );
                              
                // Replace tags in template
                $entry = str_replace('{date}', strftime( $date_format, $values['created_time']), $entry );
                $entry = str_replace('{time}', strftime( $time_format, $values['created_time']), $entry );
                $entry = str_replace('{datetime}', strftime( $datetime_format, $values['created_time']), $entry );
                
                // Url
                $post_id = $values['post_id'];
                $post_values = explode( "_", $post_id);
                $post_url = "http://www.facebook.com/".$post_values[0]."/posts/".$post_values[1];
                $entry = str_replace('{url}', $post_url, $entry );
                $entry = str_replace('{more}', '[['.$post_url.'|'.$this->getLang('read_more').']]', $entry );               
                
                // Attachments
                $attachment_content = '';
                
                $attachment = $values['attachment'];
                                
                if ( is_array( $attachment ) ) {
                    // $content .= print_r( $attachment, true );
                    // Get the attachment information
                    $attachment_media = $attachment['media'][0];
                    $attachment_type = $attachment_media['type'];
                    $attachment_name = $attachment['name'];
                    $attachment_caption = $attachment['caption'];
                    $attachment_description = $attachment['description'];

                    $href = $attachment_media['href'];
                    $alt = $attachment_media['alt'];
                    $src = $attachment_media['src'];
                                          
                    // Process links                  
                    if ( $attachment_type == 'link') {
                        
                        // Add the picture
                        if ( isset($src) && ($src != '' )) {
                            $attachment_content .= '<html><a href="'.$href.'" alt="'.$alt.'" target="'.$conf['target']['extern'].'">';
                            $attachment_content .= '<img src="'.$src.'" align="left"/>';
                            $attachment_content .= '</a></html>';
                        }
                        // Add the link
                        $attachment_content .= '[['.$href.' | '.$attachment_name.']]'."<html><br /></html>";
                        // Add the caption
                        if ( isset( $attachment_caption) && ($attachment_caption != '' )) { 
                            $attachment_content .= '<sup> [['.$href.'|'.$attachment_caption.']] </sup>'."<html><br /></html>";
                        }
                        // Add the description
                        if ( isset( $attachment_description) && ($attachment_description != '')) {
                            $attachment_content .= $attachment_description;
                        }
                    }
                    // Process photos
                    else if ( $attachment_type == 'photo') {                   
                        $attachment_content .= '[['.$href.'|{{'.$src.'}}]]';                       
                    }
                    // Process videos
                    else if ( $attachment_type == 'video') {
                        $attachment_content = '';
                        
                        // Add linked picture
                        if ( isset($src) && ($src != '' )) {
                            $attachment_content .= '<html><a href="'.$href.'" alt="'.$alt.'" target="'.$conf['target']['extern'].'">';
                            $attachment_content .= '<img src="'.$src.'" align="left"/>';
                            $attachment_content .= '</a></html>';                           
                        }
                        // Add the link
                        $attachment_content .= '[['.$href.' | '.$attachment_name.']]'."<html><br /></html>";
                        // Add the caption
                        if ( isset( $attachment_caption) && ($attachment_caption != '' )) { 
                            $attachment_content .= '<sup> [['.$href.'|'.$attachment_caption.']] </sup>'."<html><br /></html>";
                        }
                        // Add the description
                        if ( isset( $attachment_description) && ($attachment_description != '')) {
                            $attachment_content .= $attachment_description;
                            $attachment_content .= '<html><br /></html>';
                        }
                    }
                    $attachment_content .= '<html><br /><br /></html>';                    
                }
                // Replace the attachment tag with the attachment
                $entry = str_replace('{attachment}', $attachment_content, $entry );
                
                // Add the entry to the content
                $content .= $entry;
                
                // Only display a maximum number of entries
                $displayed_entries++;
                if ( isset($data[FB_WALL_NR_ENTRIES]) && $displayed_entries >= $data[FB_WALL_NR_ENTRIES] )
                {
                    break;
                } 
            }
		
			//$renderer->doc .= $ret;
			$html = p_render($mode, p_get_instructions( $content ), $info );
			$renderer->doc .= $html;
			
			return true;
		}
		return false;
	}
	
	function get_timezone_offset($remote_tz, $origin_tz = null) {
        if($origin_tz === null) {
            if(!is_string($origin_tz = date_default_timezone_get())) {
                return false; // A UTC timestamp was returned -- bail out!
            }
        }
        $origin_dtz = new DateTimeZone($origin_tz);
        $remote_dtz = new DateTimeZone($remote_tz);
        $origin_dt = new DateTime("now", $origin_dtz);
        $remote_dt = new DateTime("now", $remote_dtz);
        $offset = $origin_dtz->getOffset($origin_dt) - $remote_dtz->getOffset($remote_dt);
        return $offset;
    }
}

?>