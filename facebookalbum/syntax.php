<?php

/**
 * Plugin facebookalbum: Displays Facebook photo albums.
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @version    1.0
 * @date       April 2012
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
define( "FB_ALBUM_APPLICATION_ID", "appid" );
define( "FB_ALBUM_SECRET", "secret" );
define( "FB_ALBUM_FAN_PAGE_ID", "fanpageid" );
define( "FB_ALBUM_IGNORE", "ignore" );
define( "FB_ALBUM_ORDER", "order" );

define( "FB_ALBUM_TEMPLATE_ALBUM", "album_template" );
define( "FB_ALBUM_TEMPLATE_PICTURE", "picture_template" );

define( "FB_ALBUM_GET_PARAMETER_ACTION", "fbp_act" );
define( "FB_ALBUM_GET_PARAMETER_ALBUM_ID", "fbp_aid" );

// Template tags 
define( "FB_ALBUM_TAG_NAME", "{name}");
define( "FB_ALBUM_TAG_URL", "{url}");
define( "FB_ALBUM_TAG_IMAGE_URL", "{image_url}");
define( "FB_ALBUM_TAG_BACK", "{back}");

define( "FB_ALBUM_TAG_CAPTION", "{caption}");
define( "FB_ALBUM_TAG_ALBUM_NAME", "{album_name}");
define( "FB_ALBUM_TAG_IMAGE_SMALL_URL", "{image_small_url}");
define( "FB_ALBUM_TAG_IMAGE_BIG_URL", "{image_large_url}");
     
/**
 * This plugin retrieves facebook photos and photo albums displays them in HTML.
 *
 * Usage: {{facebookalbum#appid=1234&secret=12345&fanpageid=12345}}
 * 
 */
class syntax_plugin_facebookalbum extends DokuWiki_Syntax_Plugin
{
    function getInfo() {
      return array(
        'author' => 'J. Drost-Tenfelde',
        'email'  => 'info@drost-tenfelde.de',
        'date'   => '2012-04-13',
        'name'   => 'facebookalbum',
        'desc'   => 'Displays Facebook photo albums',
        'url'    => 'http://www.drost-tenfelde.de/?id=dokuwiki:plugins:facebookalbum',
      );
    }
 
    // implement necessary Dokuwiki_Syntax_Plugin methods
    function getType() { return 'substition'; }
    function getSort() { return 42; }
    function connectTo($mode) { $this->Lexer->addSpecialPattern('\{\{facebookalbum.*?\}\}',$mode,'plugin_facebookalbum'); }
    
	/**
	 * parse parameters from the {{facebookwall#...}} tag.
	 * @return an array that will be passed to the renderer function
	 */
	function handle($match, $state, $pos, &$handler) {
    // Get the syntax parameters
    $match = substr($match, 16, -2);
		parse_str($match, $params);
		
		// App-Id
    if ( !$params[FB_ALBUM_APPLICATION_ID] ) {
      // Get the application-id from the configuration instead
      $params[FB_ALBUM_APPLICATION_ID] = $this->getConf(FB_ALBUM_APPLICATION_ID);
      // Make sure it was set
      if ( !$params[FB_ALBUM_APPLICATION_ID] ) {
        $this->error = $this->getLang('error_appid_not_set');
      }
    }

    // Secret     
    if ( !$params[FB_ALBUM_SECRET] ) {
      // Get the secret from the configuration instead
      $params[FB_ALBUM_SECRET] = $this->getConf(FB_ALBUM_SECRET);
      // Make sure it was set
      if ( !$params[FB_ALBUM_SECRET] ) {
        $this->error = $this->getLang('error_secret_not_set');
      }
    }
    
    // Page-Id
    if ( !$params[FB_ALBUM_FAN_PAGE_ID] ) {
      // Get the page id from the configuration instead
      $params[FB_ALBUM_FAN_PAGE_ID] = $this->getConf(FB_ALBUM_FAN_PAGE_ID);
      // Make sure it was set
      if ( !$params[FB_ALBUM_FAN_PAGE_ID] ) {
        $this->error = $this->getLang('error_fanpageid_not_set');
      }
    }
    
    // Ordering
    if ( $params[FB_ALBUM_ORDER] != 'ASC' ) {
      // Get the ordering from the configuration instead
      $params[FB_ALBUM_ORDER] = $this->getConf(FB_ALBUM_ORDER);
      // If not set, default to descending
      if ( $params[FB_ALBUM_ORDER] != 'ASC' ) {      
        $params[FB_ALBUM_ORDER] = 'DESC';
      }
    }
        
    // Get the ignore names of albums that are to be ignored
    if ( !$params[FB_ALBUM_IGNORE] ) {
      // Get the ignore names from the configuration instead
      $params[FB_ALBUM_IGNORE] = $this->getConf(FB_ALBUM_IGNORE);
    }     
    $ignore_names = strtolower( $params[FB_ALBUM_IGNORE] );
    // Turn the names into an array
    $params[FB_ALBUM_IGNORE] = explode("|", $ignore_names );
    
		return $params;
  }
    
	/**
	 * Retrieves the facebook events and parses them to HTML.
	 */
	function render($mode, &$renderer, $data) {
    global $ID;
    $info = $this->getInfo();
                
    $content = '';
        
		if ($mode == 'xhtml') {            
      // Catch errors
      if ($this->error) {
        $renderer->doc .= 'Error in Plugin '.$info['name'].': '.$this->error;
        return;
      }
      
      //See if an action was provided
      isset( $_REQUEST[FB_ALBUM_GET_PARAMETER_ACTION] ) ? $action = $_REQUEST[FB_ALBUM_GET_PARAMETER_ACTION] : $action = "";
                  

      if (!class_exists('Facebook')) { 
        include_once('facebook.php');
      }
      // Initialise Facebook
      $facebook = new Facebook( array(
        'appId'  => $data['appid'],
        'secret' => $data['secret'],
        'cookie' => true, // enable optional cookie support
      ) );
            
      // See which action is to be taken
      if ( $action == '') {
        // Query for the albums
        $album_query = 'SELECT aid, cover_pid, name FROM album WHERE owner='.$data['fanpageid']. ' and photo_count > 0';
        $album_query .= 'ORDER BY name '.$data[FB_ALBUM_ORDER];
        
        // Query for the album covers                
        $cover_query = 'SELECT pid, src FROM photo where pid in (SELECT cover_pid from #album_query)';
        
        //Combine the queries for speed
        $queries = array( "album_query" => $album_query, "cover_query" => $cover_query );
        // Set the parameters for the API                
        $param = array(
          'method'    => 'fql.multiquery',
          'queries'     => $queries,
          'callback'  => ''
        );   

        // Execute the queries
        $results = $facebook->api($param);
        $album_results = $results[0]['fql_result_set'];
        $cover_results = $results[1]['fql_result_set'];
                
        // Turn the covers into an array for quick refference
        $covers = array();
        foreach( $cover_results as $cover ) {
          $covers[ $cover['pid'] ] = $cover['src'];    
        }
        
        // Get the template for the albums
        $album_template = $this->getConf(FB_ALBUM_TEMPLATE_ALBUM);
        
        // Loop through the albums                  
        foreach( $album_results as $keys => $values ) {
          // Make sure the album name is not to be ignored
          if ( in_array(strtolower($values['name']), $data[FB_ALBUM_IGNORE])) {
            continue;
          }

          // Get the cover image
          $album_cover = $covers[$values['cover_pid']];
          
          // Assemble the url
          $album_url = $_SERVER['REQUEST_URI']."&".FB_ALBUM_GET_PARAMETER_ACTION."=list_photos&".FB_ALBUM_GET_PARAMETER_ALBUM_ID."=".$values['aid'];
          
          // Assemble the content of the album
          $album_content = $album_template;
          // Name
          $album_content = str_replace(FB_ALBUM_TAG_NAME, $values['name'], $album_content);
          // URL
          $album_content = str_replace(FB_ALBUM_TAG_URL, $album_url, $album_content);
          // Cover image
          $album_content = str_replace(FB_ALBUM_TAG_IMAGE_URL, $album_cover, $album_content);

          $content .= $album_content;
        }
      }
      else if ( $action == 'list_photos' ) {
      
        // Get the template for the albums
        $photo_template = $this->getConf(FB_ALBUM_TEMPLATE_ALBUM);
        
        // Add a "Back" link
        $renderer->doc .= "<p>".html_wikilink($ID, $this->getLang('back_to_albums'))."</p>";
            
        // Get the album name
        $fql = "SELECT name FROM album WHERE aid='".$_REQUEST[FB_ALBUM_GET_PARAMETER_ALBUM_ID]."'";
        // Set the parameters
        $param = array(
          'method'    => 'fql.query',
          'query'     => $fql,
          'callback'  => ''
        );
        // Execute the query
        $fql_results = $facebook->api($param);
        foreach( $fql_results as $keys => $values ) {
          $album_name = $values['name'];
          break;
        }
        
        // Get pictures within the album
        $fql = "SELECT pid, src, src_small, src_big, caption FROM photo WHERE aid = '" . $_REQUEST[FB_ALBUM_GET_PARAMETER_ALBUM_ID] ."'  ORDER BY created DESC";
        $param  =   array(
          'method'    => 'fql.query',
          'query'     => $fql,
          'callback'  => ''
        );
        $fql_results = $facebook->api($param);

        // Get the display template
        $picture_template = $this->getConf(FB_ALBUM_TEMPLATE_PICTURE);
                        
        // Loop through the pictures
        foreach( $fql_results as $keys => $values ) {
          if( $values['caption'] == '' ){
            $caption = "";
          }
          else {
            $caption = $values['caption'];
          }
          
          $picture_content = $picture_template;
          $picture_content = str_replace( FB_ALBUM_TAG_CAPTION, $caption, $picture_content);
          $picture_content = str_replace( FB_ALBUM_TAG_ALBUM_NAME, album_name, $picture_content);
          $picture_content = str_replace( FB_ALBUM_TAG_IMAGE_URL, $values['src'], $picture_content);
          $picture_content = str_replace( FB_ALBUM_TAG_IMAGE_SMALL_URL, $values['src_small'], $picture_content);
          $picture_content = str_replace( FB_ALBUM_TAG_IMAGE_BIG_URL, $values['src_big'], $picture_content);
          
          $content .= $picture_content;
        }
      }
		      
      // Render the DokuWiki syntax
      $renderer->doc .= p_render($mode, p_get_instructions( $content ), $info );
      // Add a clearer
      $renderer->doc .= "<div class=\"clearer\"></div>";
			
			return true;
		}
		return false;
	}
}

?>