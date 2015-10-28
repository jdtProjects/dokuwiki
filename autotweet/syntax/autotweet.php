<?php

if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'syntax.php');

/**
 * Plugin autotweet: Automatically posts tweets to your Twitter app when pages are changed.
 *   
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @version    1.0
 * @date       October 2011
 * @author     J. Drost-Tenfelde <info@drost-tenfelde.de>
 *
 */
class syntax_plugin_autotweet_autotweet extends DokuWiki_Syntax_Plugin {
    /**
     * Return the syntax plugin type.
     * 
     * @return Plugin type.
     */                   
    public function getType() {
        return 'substition';
    }

    /**
     * Return the paragraph type.
     * 
     * @return Paragrahp type.
     */                   
	public function getPType() { return 'block'; }

    /**
     * Return the sort type.
     * 
     * @return Sort type.
     */                   
	public function getSort() { return 305; }
   
   /**
    * Connects the plugin to the lexer.
    * 
    * @param mode Rendering mode.
    */               
	public function connectTo($mode) {
		$this->Lexer->addSpecialPattern('~~AUTOTWEET:.*?~~', $mode, 'plugin_autotweet_autotweet');
	}

    /**
     * Handles matched patterns and assembles data for the plugin.
     * 
     * @param match Matched pattern.
     * @param state State.
     * @param handler handler.
     * 
     * @return array with plugin data.          
     */                             
	public function handle($match, $state, $pos, &$handler){
        $data = array();
        
        # Get the tags from the syntax
        $match = trim( substr($match, 12, -2) );       
        // Get the parameters (key=value), seperated by &
		$pairs = explode('&', $match);
		// Turn the pairs into key=>value
        foreach ($pairs as $pair) {
			list($key, $value) = explode('=', $pair, 2);
			$data[trim($key)] = trim($value);
        }
		// Turn all keys to lower case
        $data = array_change_key_case($data, CASE_LOWER);
        
        // Get all essential (non-set) parameters from the configuration
        if ( !isset( $data['consumer_key'] ) ) {
            $data['consumer_key'] = $this->getConf('consumer_key');
        }
        if ( !isset( $data['consumer_secret'] ) ) {
            $data['consumer_secret'] = $this->getConf('consumer_secret');
        }
        if ( !isset( $data['access_token'] ) ) {
            $data['access_token'] = $this->getConf('access_token');
        }
        if ( !isset( $data['access_token_secret'] ) ) {
            $data['access_token_secret'] = $this->getConf('access_token_secret');
        }
      
        return $data;
	}

    /**
     * Renders the syntax and passes on configuration to the action plugin.
     * 
     * @param mode rendering mode.
     * @param renderer Renderer.
     * @param data Data returned by the handle function.
     * 
     */                                  
	public function render($mode, &$renderer, $data) {
	   global $ID;
		if ( $data === false) {
            return false;
        }

        // Pass on the configuration to the action plugin		
        if ( $mode == 'metadata') {
            $renderer->meta['autotweet']['consumer_key'] = $data['consumer_key'];
            $renderer->meta['autotweet']['consumer_secret'] = $data['consumer_secret'];
            $renderer->meta['autotweet']['access_token'] = $data['access_token'];
            $renderer->meta['autotweet']['access_token_secret'] = $data['access_token_secret'];
            return true;
        }
        else if ( $mode == 'xhtml')
        {
            // Do not render anything special
            return true;
        }
        return false;
	}
}
