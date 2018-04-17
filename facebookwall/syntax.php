<?php

/**
 * Plugin facebookwall: Displays status messages on a facebook wall.
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @version    1.2
 * @date       September 2016
 * @author     J. Drost-Tenfelde <info@drost-tenfelde.de>
 *
 * This plugin uses Facebook Graph API v2.7.
 *
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();
 
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

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
define( "FB_WALL_PICTURE_MAX_WIDTH", "maxWidth" ); // Maximum allowed width for attachment picture
define( "FB_WALL_PICTURE_MAX_HEIGHT", "maxHeight" ); // Maximum allowed height for attachment picture
 
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
        
        // Max width
        $maxWidth = $this->getConf( FB_WALL_PICTURE_MAX_WIDTH );
        if ( !isset($maxWidth ) || $maxWidth == '' ) {
            $maxWidth = 999;
        }
        $params[FB_WALL_PICTURE_MAX_WIDTH] = $maxWidth;
        
        // Max height
        $maxHeight = $this->getConf( FB_WALL_PICTURE_MAX_HEIGHT );
        if ( !isset($maxHeight) || $maxHeight== '' ) {
            $maxHeight = 0;
        }
        $params[FB_WALL_PICTURE_MAX_HEIGHT] = $maxHeight;
        
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
            
			// Get the facebook information
			$fb_app_id = $data[FB_WALL_APPLICATION_ID];
			$fb_secret = $data[FB_WALL_SECRET];
			$fb_page_id = $data[FB_WALL_FAN_PAGE_ID];
			
			// Get the access token using app-id and secret
			$token_url ="https://graph.facebook.com/oauth/access_token?client_id={$fb_app_id}&client_secret={$fb_secret}&grant_type=client_credentials";		
					
			$token_data = file_get_contents($token_url);
			if ( !isset($token_data ) ) {
				$renderer->doc .= 'Access token could not be retrieved for Plugin '.$info['name'].': '.$this->error;
                return;
			}
			else {
				// Attempt to decode json
				try {
					$obj = json_decode($token_data);
					$fb_access_token = $obj->{'access_token'};
				}
				catch ( Exception $exception ) {
					$fb_access_token = split("=", $token_data )[1];
				}
			}
			
            // Get the date format
            $date_format = $this->getConf(FB_WALL_DATE_FORMAT);
            $time_format = $this->getConf(FB_WALL_TIME_FORMAT);
            $datetime_format = $date_format.' '.$time_format;

			$numberOfEntries = $data[FB_WALL_NR_ENTRIES];
			
            // Get the time offset
            //$offset = $this->get_timezone_offset( "America/Los_Angeles" );
            $offset = 0;

			$fields = "id,message,picture,link,name,description,type,icon,created_time,from,object_id";
			
			$limit = $numberOfEntries + 5;
			$json_link = "https://graph.facebook.com/{$fb_page_id}/feed?access_token={$fb_access_token}&fields={$fields}&limit={$limit}";
			$json = $this->getData( $json_link);

			//$objects = json_decode($json, true, 512, JSON_BIGINT_AS_STRING);
			$objects = json_decode($json, true);
			
			// count the number of wallposts
			$post_count = count($objects['data']);

			// Loop through the events
			for ($post_index = 0; $post_index < $post_count; $post_index++) {
				$post = $objects['data'][$post_index];
				
                $entry = $data[FB_WALL_TEMPLATE];
                
                // If no message was provided, skip to the next
                if ( !$post['message'] ) {
                    continue;
                }
                
                // If the date is lower than the from date, skip to the next
                if ( isset($data[FB_WALL_FROM_DATE]) && ($post['created_time'] < $data[FB_WALL_FROM_DATE] ) )  {
                    continue;
                }
                // If the date is higher than the to data, skip to the next
                if ( $data[FB_WALL_TO_DATE] && ($post['created_time'] > $data[FB_WALL_TO_DATE] ))  {
                    continue;
                }

                // Limit?
                if ( isset( $data[FB_WALL_LIMIT]) && ($data[FB_WALL_LIMIT] > 0 ) ) { 
                    if ( strlen( $post['message'] ) > $data[FB_WALL_LIMIT] ) {                   
                        $post['message_short'] = substr( $post['message'], 0, $data[FB_WALL_LIMIT] ).'...';
                        // Find the first occurance of a space
                        $index = strrpos ( $post['message_short'], ' ' );
						$post['message_short'] = substr( $post['message_short'], 0, $index ).'...';
                        $post['message'] = substr( $post['message_short'], 0, $index ).'...';
                    }
                }
				else {
					$post['message_short'] = substr( $post['message'], 0, 150 ).'...';
					$index = strrpos ( $post['message_short'], ' ' );
                    $post['message_short'] = substr( $post['message_short'], 0, $index ).'...';
				}
                // Process the message                               
                $post['message'] = str_replace("\r\n", '<html><br /></html>', $post['message'] );
                $post['message'] = str_replace("\n", '<html><br /></html>', $post['message'] );
				$post['message_short'] = str_replace("\r\n", '<html><br /></html>', $post['message_short'] );
                $post['message_short'] = str_replace("\n", '<html><br /></html>', $post['message_short'] );
                
                $entry = str_replace('{message}', $post['message'], $entry );
				$entry = str_replace('{message_short}', $post['message_short'], $entry );
                              
                // Replace tags in template
                $entry = str_replace('{date}', date( $date_format, strtotime($post['created_time'])), $entry );
                $entry = str_replace('{time}', date( $time_format, strtotime($post['created_time'])), $entry );
                $entry = str_replace('{datetime}', date( $datetime_format, strtotime($post['created_time'])), $entry );
                $entry = str_replace('{timestamp}', $post['created_time'], $entry );
                
				$pic = $post['picture'];
				// Add a fix for urls with get parameters
				if ( strpos($pic, '?') > 0 )
				{
					$pic .= '&.png';
				}
				$entry = str_replace('{image}', $pic, $entry );
				
                // Url
                $post_id = $post['id'];
                $post_values = explode( "_", $post_id);
                $post_url = "http://www.facebook.com/".$post_values[0]."/posts/".$post_values[1];
                $entry = str_replace('{url}', $post_url, $entry );
				
                $entry = str_replace('{more}', '[['.$post_url.'|'.$this->getLang('read_more').']]', $entry );               
                
                // Add the entry to the content
                $content .= $entry;
				
				$numberOfEntries--;
				if ( $numberOfEntries == 0 ) {
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
    
    /**
     * Makes a linked image.
     * 
     * @href link
     * @alt tooltip
     * @src location of the image
     * @data configuration parameters for the plugin.
     * @conf DokuWiki configuration.
     * 
     * @return HTML code with linked image.
     */                                                 
    function makeImage( $href, $alt, $src, $data, $conf) {
        $html = '<html><a href="'.$href.'" alt="'.$alt.'" target="'.$conf['target']['extern'].'">';
        $html .= '<img src="'.$src.'" align="left"';
        
        if ( $data[FB_WALL_PICTURE_MAX_WIDTH] > 0 || $data[FB_WALL_PICTURE_MAX_HEIGHT] > 0) {
            $html .= ' style="';
            if ( $data[FB_WALL_PICTURE_MAX_WIDTH] > 0 ) {
                $html .= 'max-width: '.$data[FB_WALL_PICTURE_MAX_WIDTH].'px !important;';
            }
            if ( $data[FB_WALL_PICTURE_MAX_HEIGHT] > 0 ) {
                $html .= 'max-height: '.$data[FB_WALL_PICTURE_MAX_HEIGHT].'px !important;';
            }
            $html .= '"';
        }
        $html .= '/>';
        $html .= '</a></html>';
        return $html;
    }
}

?>