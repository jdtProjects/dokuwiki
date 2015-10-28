<?php

/**
 * A Mindmap plugin using Graphviz. 
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Jannes Drost-Tenfelde <info@drost-tenfelde.de>
 */
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/cliopts.php');

/**
 * Mindmap plugin.
 * 
 */   
class syntax_plugin_mindmap extends DokuWiki_Syntax_Plugin {

    /**
     * Returns plugin information.
     * 
     * @return array with plugin information.          
     */
    function getInfo() {
        return array(
                'author' => 'Jannes Drost-Tenfelde',
                'email'  => 'info@drost-tenfelde.de',
                'date'   => '2011-10-11',
                'name'   => 'mindmap',
                'desc'   => 'This plugin allows you to make mindmaps of your website.',
                'url'    => 'http://www.drost-tenfelde.de/?id=dokuwiki:plugins:mindmap',
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
	 * Connects the syntax pattern to the lexer.
	 */
	function connectTo($mode) {
		$this->Lexer->addSpecialPattern('\{\{mindmap>[^}]*\}\}', $mode, 'plugin_mindmap');
	}

	/**
	 * Handles the matched pattern.
	 * 
	 */
	function handle($match, $state, $pos, &$handler){
		global $ID;
		
		$info = $this->getInfo();
		
        //strip markup from start and end
		$match = substr($match, 10, -2);
        
        // Assemble data		
		$data = array();

        // Get namespaces and parameters
        list($data['namespaces'], $parameters_string) = explode('#', $match);
        $data['namespaces'] = str_replace('&', ',', $data['namespaces']);
        
		parse_str($parameters_string, $params);

		// Add the default values
        $data['height'] = 0;
        $data['width'] = 0;
        $data['align'] = '';
        $data['format'] = 'dot';
        $data['depth'] = 3;
        $data['include_media'] = 'none';
        $data['use_cached_pages'] = 1;
		
		// Get the parameters (key=value), seperated by &
		$pairs = explode('&', $parameters_string);
		// Turn the pairs into key=>value
        foreach ($pairs as $pair) {
			list($key, $value) = explode('=', $pair, 2);
			$data[trim($key)] = trim($value);
        }
		// Turn all keys to lower case
        $data = array_change_key_case($data, CASE_LOWER); 
		return $data;
	}
	
	/**
	 * Renders the output.
	 * 
	 */         	
    function render($mode, &$renderer, $data) {
        global $conf;
		global $lang;
        		
		if ($mode == 'xhtml') {
		    // Get the path
		    $plugin_path = DOKU_BASE.'lib/plugins/mindmap/';
            // Was a different location for the plugin set?
            if ( $this->getConf('use_plugin_path') == 1) {
                $conf_plugin_path = $this->getConf('plugin_path');
                
                // Make sure the plugin path is set
                if ( $conf_plugin_path != '' ) {
                    // Make sure there is a trailing /
                    if ( $conf_plugin_path[strlen($conf_plugin_path) - 1] != '/' ) {
                        $conf_plugin_path .= '/';
                    }
                    $plugin_path = $conf_plugin_path; 
                }
            }
            
            if ( $data['format'] == 'gexf') {
                // Add a link to the GEXF XML file
                $xml = $plugin_path.'xml.php?'.buildURLparams($data); 
                $renderer->doc .= '<p><a href="'.$xml.'" target="_blank">'.$this->getLang('gexf_mindmap').'</a></p>';
            }
            else {
                // Force dot format
                $data['format'] = 'dot';
                
                $img = $plugin_path.'img.php?'.buildURLparams($data);
            
                if ( ($data['height'] != 0) || ($data['width'] != 0) ) {
                    // Add a link
                    $renderer->doc .= '<a href="'.$img.'" target="_blank" border="0">'; 
                }
                
                // Add the image
                $renderer->doc .= '<img src="'.$img.'" class="media'.$data['align'].'" alt=""';            
                if($data['width'])  $renderer->doc .= ' width="'.$data['width'].'"';
                if($data['height']) $renderer->doc .= ' height="'.$data['height'].'"';
                if($data['align'] == 'right') $renderer->doc .= ' align="right"';
                if($data['align'] == 'left')  $renderer->doc .= ' align="left"';
                $renderer->doc .= '/>';
                
                if ( ($data['height'] != 0) || ($data['width'] != 0) ) {
                    // Close the link
                    $renderer->doc .= '</a>'; 
                }
            }
            return true;
        }        
		return false;
	}
	
	/**
	 * Wrapper which retruns the appropriate gathered data based on parameters.
	 * 
	 * @param data plugin data
	 * @return gathered pages and media.
	 */                    	
	function get_gathered_data( $data ) {
        // Use cached pages?
        $use_cached_pages = true;
        if ( $data['use_cached_pages'] == 0 )
        {
            // Safeguard that cache is used if no namespaces were given
            if ( ($data['namespaces'] == '') || ($data['namespaces'] == ':') ) {
                $use_cached_pages = true;
            }
            else {
                $use_cached_pages = false;
            }
        }
        
        // Use first page header?
        $use_first_header = false;
        if ( $data['use_first_header'] == 1 ) {
            $use_first_header = true;
        }

        //Make a namespace array
        $namespaces = explode(',', $data['namespaces']);
        
        // Gather page/media data
        $gathered_data = $this->gather_data(
            $namespaces, $data['depth'],
            $data['include_media'],
            $use_cached_pages,
            $use_first_header
        );
        return $gathered_data;
    }
    
    /**
     * Returns the GEXF xml file.
     * 
     * @param data parameters.
     *           
     * @return XML.
     */                    
    function get_gexf_xml( $data ) {
        global $conf;
        
        $image = null;
        
        $gathered_data = $this->get_gathered_data( $data );
        
        $xml = $this->get_gexf( $gathered_data );
        
        return $xml;
    }
    
    /**
     * Returns the content of a graphviz image.
     * 
     * @param data parameters.
     *           
     * @return PNG image.
     */                    
    function get_graphviz_image( $data ) {
        global $conf;
        
        $image = null;
        
        $gathered_data = $this->get_gathered_data( $data );       
        $dot_input = $this->get_dot( $gathered_data );      
        
        // See if a manual path was given for graphviz
               
        if ( $this->getConf('graphviz_path') ) {
            // Local build
            $cmd  = $this->getConf('path');
            $cmd .= ' -Tpng';
            $cmd .= ' -K'.$data['layout'];
            $cmd .= ' -o'.escapeshellarg($image); //output
            $cmd .= ' '.escapeshellarg($dot_input); //input

            exec($cmd, $image, $error);

            if ($error != 0){
                if($conf['debug']) {
                    dbglog(join("\n",$image),'mindmap command failed: '.$cmd);
                }
                return false;
            }
        }
        else {
            // Remote via google chart tools           
            $http = new DokuHTTPClient();
            $http->timeout=30;

            $pass = array();
            $pass['cht'] = 'gv:'.$data['format'];
            $pass['chl'] = $dot_input;

            $image = $http->post('http://chart.apis.google.com/chart',$pass,'&');
            if(!$image) return false;
        }
        return $image;
    }
    
    /**
     * Searches a namespace for media files and adds them to the media array.
     * 
     * @param media pre-initialised media array.
     * @param ns namespace in which to search for media files
     * @param depth Depth of the search.    
     */ 
    function get_media( &$media, $ns, $depth=0 )
    {
        global $conf;
        
        $search_results = array();
        // Search all media files within the namespace
        search($search_results,
            $conf['mediadir'],
            'search_universal',
            array (
                'depth' => $depth,
                'listfiles' => true,
                'listdirs'  => false,
                'pagesonly' => false,
                'skipacl'   => true,
                'keeptxt'   => true,
                'meta'      => true,
            ),
            // Only search within the namespace
            str_replace(':', '/', $ns)
        );
        
        // Loop through the results
        while( $item = array_shift($search_results) ) {
            // Make a new media[id]=>array(title,size,ns,time) for the item
            $media[$item['id']] = array(
                'title' => noNS($item['id']),
                'size'  => $item['size'],
                'ns'    => getNS($item['id']),
                'time'  => $item['mtime'],
            );
        }
    }
    
    /**
     * Adds all pages of a specific namespace to the pages array.
     * 
     * @param pages pre-initialised pages array.
     * @param ns Namespace in which to look for pages.
     * @param depth Search depth.
     * @param use_first_header (optional) Includes the first header as page title. 
     */     
    function get_pages( &$pages, $ns, $depth=0, $use_first_header=false )
    {
        global $conf;
        
        // find pages
        $search_results = array();
        search($search_results,
            $conf['datadir'],
            'search_universal',
            array(
                'depth' => $depth,
                'listfiles' => true,
                'listdirs'  => false,
                'pagesonly' => true,
                'skipacl'   => true,
                'firsthead' => true,
                'meta'      => true,
            ),
            str_replace(':','/',$ns)
        );
       
        // Start page of the namespace
        if ($ns && page_exists($ns)) {
            // Add to the search results
            $search_results[] = array(
                'id'    => $ns,
                'ns'    => getNS($ns),
                'title' => p_get_first_heading($ns, false),
                'size'  => filesize(wikiFN($ns)),
                'mtime' => filemtime(wikiFN($ns)),
                'perm'  => 16,
                'type'  => 'f',
                'level' => 0,
                'open'  => 1,
            );
        }
        
        // loop through the pages
        while($item = array_shift($search_results)) {
            // Check that the user is allowed to read the page        
            if ( (auth_quickaclcheck($item['id']) > AUTH_READ) ) {
                    continue;
            }            
            // Check that the user is allowed to read the page        
            if ( (auth_quickaclcheck($item['ns']) > AUTH_READ) ) {
                    continue;
            }
        
            // Get the create time
            $time = (int) p_get_metadata($item['id'], 'date created', false);
            if(!$time) $time = $item['mtime'];
            
            // Get specific language part
            $lang = ($transplugin)?$transplugin->getLangPart($item['id']):'';
            if($lang) {
                $item['ns'] = preg_replace('/^'.$lang.'(:|$)/','',$item['ns']);
            }
            
            if ( $use_first_header ) {
                $title = $item['title'];
            }
            else {
                // Use the last part of the id for the name
                $title = ucwords( substr(strrchr(strtr($item['id'],'_',' '), ':'), 1 ) );
            }
            // Add the page to the page list             
            $pages[$item['id']] = array (
                'title' => $title,
                'ns'    => $item['ns'],
                'size'  => $item['size'],
                'time'  => $time,
                'links' => array(),
                'media' => array(),
                'lang'  => $lang
            );           
        }
    }
    
    /**
     * Gathers all page and media data for given namespaces.
     * 
     * @namespaces array() of namespaces
     * @depth Search depth
     * @include_media Determines if media should be regarded, Values: 'ns','all','none'.
     * @use_cached_pages Determines if only cached pages should be used. If this option is turned off, the operation will cache all non-cached pages within the namespace.
     * @use_first_header Determines if the first header is used for title of the pages.
     * 
     * @return array with pages and media: array('pages'=>pages, 'media'=>media).   
     */
    function gather_data($namespaces, $depth=0, $include_media='none', $use_cached_pages=true, $use_first_header=false) {
        global $conf;
    
        $transplugin = plugin_load('helper','translation');
    
        $pages = array();
        $media = array();
        
        // Loop through the namespaces
        foreach ($namespaces as $ns) {   
            // Get the media of the namespace
            if( $include_media == 'ns' ) {
                $this->get_media( $media, $ns, $depth );
            }
            // Get the pages of the namespace
            $this->get_pages( $pages, $ns, $depth, $use_first_header );
        }
        
        // Loop through the pages to get links and media
    
        foreach($pages as $pid => $item){
        
            // get instructions
            $ins = p_cached_instructions(wikiFN($pid), $use_cached_pages, $pid);
            
            // find links and media usage
            foreach ($ins as $i) {
                $mid = null;
    
                // Internal link?
                if ($i[0] == 'internallink') {
                    $id     = $i[1][0];
                    $exists = true;
                    resolve_pageid($item['ns'],$id,$exists);
                    list($id) = explode('#',$id,2);
                    if($id == $pid) continue; // skip self references
                                   
                    if($exists && isset($pages[$id])){
                        $pages[$pid]['links'][] = $id;
                    }
                    if(is_array($i[1][1]) && $i[1][1]['type'] == 'internalmedia'){
                        $mid = $i[1][1]['src']; // image link
                    }else{
                        continue; // we're done here
                    }
                }
    
                if($i[0] == 'internalmedia') {
                    $mid = $i[1][0];
                }
    
                if(is_null($mid)) continue;
                if($include_media == 'none') continue; // no media wanted
    
                $exists = true;
                resolve_mediaid($item['ns'],$mid,$exists);
                list($mid) = explode('#',$mid,2);
                $mid = cleanID($mid);
    
                if($exists){
                    if($include_media == 'all'){
                        if (!isset($media[$mid])) { //add node
                            $media[$mid] = array(
                                                'size'  => filesize(mediaFN($mid)),
                                                'time'  => filemtime(mediaFN($mid)),
                                                'ns'    => getNS($mid),
                                                'title' => noNS($mid),
                                           );
                        }
                        $pages[$pid]['media'][] = $mid;
                    } elseif(isset($media[$mid])){
                        $pages[$pid]['media'][] = $mid;
                    }
                }
            }
    
            // clean up duplicates
            $pages[$pid]['links'] = array_unique($pages[$pid]['links']);
            $pages[$pid]['media'] = array_unique($pages[$pid]['media']);
        }
    
        return array('pages'=>$pages, 'media'=>$media);
    }
    
    /**
     * Create a Graphviz dot representation
     */
    function get_dot(&$data ) {
        $pages =& $data['pages'];
        $media =& $data['media'];
    
        $output = "digraph G {\n";
        
        // create all nodes first
        foreach($pages as $id => $page) {
            $output .= "    \"page-$id\" [shape=note, label=\"{$page['title']}\\n{$id}\", color=lightblue, fontname=Helvetica];\n";
        }
        foreach($media as $id => $item) {
            $output .= "    \"media-$id\" [shape=box, label=\"$id\", color=sandybrown, fontname=Helvetica];\n";
        }
        // now create all the links
        foreach($pages as $id => $page){
            foreach($page['links'] as $link){
                $output .= "    \"page-$id\" -> \"page-$link\" [color=navy];\n";
            }
            foreach($page['media'] as $link){
                $output .= "    \"page-$id\" -> \"media-$link\" [color=firebrick];\n";
            }
        }
        $output .= "}\n";
    
        return $output;
    }
    
    /**
     * Create a GEXF representation
     */
    function get_gexf(&$data){
        $pages =& $data['pages'];
        $media =& $data['media'];
    
        $output = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $output .= "<gexf xmlns=\"http://www.gexf.net/1.1draft\" version=\"1.1\"
                       xmlns:viz=\"http://www.gexf.net/1.1draft/viz\">\n";
        $output .= "    <meta lastmodifieddate=\"".date('Y-m-d H:i:s')."\">\n";
        $output .= "        <creator>DokuWiki</creator>\n";
        $output .= "    </meta>\n";
        $output .= "    <graph mode=\"dynamic\" defaultedgetype=\"directed\">\n";
    
        // define attributes
        $output .= "        <attributes class=\"node\">\n";
        $output .= "            <attribute id=\"title\" title=\"Title\" type=\"string\" />\n";
        $output .= "            <attribute id=\"lang\" title=\"Language\" type=\"string\" />\n";
        $output .= "            <attribute id=\"ns\" title=\"Namespace\" type=\"string\" />\n";
        $output .= "            <attribute id=\"type\" title=\"Type\" type=\"liststring\">\n";
        $output .= "                <default>page|media</default>\n";
        $output .= "            </attribute>\n";
        $output .= "            <attribute id=\"time\" title=\"Created\" type=\"long\" />\n";
        $output .= "            <attribute id=\"size\" title=\"File Size\" type=\"long\" />\n";
        $output .= "        </attributes>\n";
    
        // create all nodes first
        $output .= "        <nodes>\n";
        foreach($pages as $id => $item){
            $title = htmlspecialchars($item['title']);
            $lang  = htmlspecialchars($item['lang']);
            $output .= "            <node id=\"page-$id\" label=\"$id\" start=\"{$item['time']}\">\n";
            $output .= "               <attvalues>\n";
            $output .= "                   <attvalue for=\"type\" value=\"page\" />\n";
            $output .= "                   <attvalue for=\"title\" value=\"$title\" />\n";
            $output .= "                   <attvalue for=\"lang\" value=\"$lang\" />\n";
            $output .= "                   <attvalue for=\"ns\" value=\"{$item['ns']}\" />\n";
            $output .= "                   <attvalue for=\"time\" value=\"{$item['time']}\" />\n";
            $output .= "                   <attvalue for=\"size\" value=\"{$item['size']}\" />\n";
            $output .= "               </attvalues>\n";
            $output .= "               <viz:shape value=\"square\" />\n";
            $output .= "               <viz:color r=\"173\" g=\"216\" b=\"230\" />\n";
            $output .= "            </node>\n";
        }
        foreach($media as $id => $item){
            $title = htmlspecialchars($item['title']);
            $lang  = htmlspecialchars($item['lang']);
            $output .= "            <node id=\"media-$id\" label=\"$id\" start=\"{$item['time']}\">\n";
            $output .= "               <attvalues>\n";
            $output .= "                   <attvalue for=\"type\" value=\"media\" />\n";
            $output .= "                   <attvalue for=\"title\" value=\"$title\" />\n";
            $output .= "                   <attvalue for=\"lang\" value=\"$lang\" />\n";
            $output .= "                   <attvalue for=\"ns\" value=\"{$item['ns']}\" />\n";
            $output .= "                   <attvalue for=\"time\" value=\"{$item['time']}\" />\n";
            $output .= "                   <attvalue for=\"size\" value=\"{$item['size']}\" />\n";
            $output .= "               </attvalues>\n";
            $output .= "               <viz:shape value=\"disc\" />\n";
            $output .= "               <viz:color r=\"244\" g=\"164\" b=\"96\" />\n";
            $output .= "            </node>\n";
        }
        $output .= "        </nodes>\n";
    
        // now create all the edges
        $output .= "        <edges>\n";
        $cnt = 0;
        foreach($pages as $id => $page){
            foreach($page['links'] as $link){
                $cnt++;
                $output .= "            <edge id=\"$cnt\" source=\"page-$id\" target=\"page-$link\" />\n";
            }
            foreach($page['media'] as $link){
                $cnt++;
                $output .= "            <edge id=\"$cnt\" source=\"page-$id\" target=\"media-$link\" />\n";
            }
        }
        $output .= "        </edges>\n";
    
        $output .= "    </graph>\n";
        $output .= "</gexf>\n";
        return $output;
    }
}


