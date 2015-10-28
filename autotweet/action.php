<?php

if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';

/**
 * Plugin autotweet: Automatically posts tweets to your Twitter app when pages are changed.
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @version    1.0
 * @date       October 2011
 * @author     J. Drost-Tenfelde <info@drost-tenfelde.de>
 *
 */ 
class action_plugin_autotweet extends DokuWiki_Action_Plugin {
    /**
     * Return plugin information.
     * 
     * @return Plugin information.
     */                   
    function getInfo() {
      return array(
        'author' => 'J. Drost-Tenfelde',
        'email'  => 'info@drost-tenfelde.de',
        'date'   => '2011-09-28',
        'name'   => 'autotweet',
        'desc'   => 'Automatically posts tweets to your Twitter app when pages are changed',
        'url'    => 'http://www.drost-tenfelde.de/?id=dokuwiki:plugins:autotweet',
      );
    }

    /**
     * Register the hooks.
     * 
     * @param controller Doku_Event_Handler.
     */                   
    public function register(Doku_Event_Handler &$controller) {
        // Catch after the page has been written to
        $controller->register_hook('IO_WIKIPAGE_WRITE', 'AFTER', $this, 'handle_wikipage_write' );
    }
    
    /**
     * Handles the event IO_WIKIPAGE_WRITE.
     * 
     * @param event Doku_Event IO_WIKIPAGE_WRITE.
     *
     * @param param Parameters.      
     */                        
    public function handle_wikipage_write( Doku_Event &$event, $param ) {
        global $ID;
        $data = $event->data;
        
        // Make sure the event data is set properly
        if ( is_array( $data ) ) 
        {
            $page_contents = $data[0][1];
                       
            // Check that the tag ~~AUTOTWEET: is inside the page contents
            if ( strpos($page_contents,'~~AUTOTWEET:') === FALSE ) {
                return;
            }
            
            // Get the configuration that was retrieved by the syntax plugin
            $autotweet = p_get_metadata( $ID, 'autotweet', true );

            // Get the plugin parameters           
            $consumer_key = $autotweet['consumer_key'];
            $consumer_secret = $autotweet['consumer_secret'];
            $access_token = $autotweet['access_token'];
            $access_token_secret = $autotweet['access_token_secret'];
            // Get configuration parameters
            $message_template = $this->getConf('message_template');
            $date_format = $this->getConf('date_format');        
            
            // Get the last change information of the current page
            $last_change = p_get_metadata( $ID, 'last_change', false );
            
            // Get the change type, [C,E,D,R] and make it readable
            $change_type = $last_change['type'];
            if ( $change_type == 'C') {
                $change_type = $this->getLang('creat');
            }
            else if ( $change_type == 'E') {
                $change_type = $this->getLang('edit');
            }
            else if ( $change_type == 'e') {
                $change_type = $this->getLang('minor_edit');
            }
            else if ( $change_type == 'D') {
                $change_type = $this->getLang('delet');
            }
            else if ( $change_type == 'R') {
                $change_type = $this->getLang('revert');
            }
            
            // Assemble the tweet
            $message = $message_template;
            $message = str_replace('{date}', strftime( $date_format ), $message );
            $message = str_replace('{type}', $change_type, $message );
            $message = str_replace('{user}', $last_change['user'], $message );
            $message = str_replace('{summary}', $_POST['summary'], $message );
            $message = str_replace('{page}', $last_change['id'], $message );
            $message = str_replace('{extra}', $last_change['extra'], $message );
                       
            // Make sure that CURL is installed
            if ( function_exists('curl_init') )  {
                include_once DOKU_PLUGIN.'autotweet/twitteroauth.php';
                // Access Twitter via OAuth  
                $tweet = new TwitterOAuth($consumer_key, $consumer_secret, $access_token, $access_token_secret);  
                //Send the tweet  
                $result = $tweet->post('statuses/update', array('status' => $message));
            }                                  
        }
    }
}
