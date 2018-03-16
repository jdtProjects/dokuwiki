<?php

/**
 * Plugin importfacebookevents: Displays facebook events.
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @version    3.2
 * @date       March 2018
 * @author     G. Surrel <gregoire.surrel.org>, J. Drost-Tenfelde <info@drost-tenfelde.de>
 *
 * This plugin uses Facebook's Graph API v2.12.
 *
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

// Syntax parameters
define("FB_EVENTS_APPLICATION_ID", "fb_application_id");
define("FB_EVENTS_APPLICATION_SECRET", "fb_application_secret");
define("FB_EVENTS_FAN_PAGE_ID", "fanpageid");
define("FB_EVENTS_SHOW_AS", "showAs");
define("FB_EVENTS_WALLPOSTS_SHOW_AS", "showPostsAs");
define("FB_EVENTS_FROM_DATE", "from");
define("FB_EVENTS_TO_DATE", "to");
define("FB_EVENTS_SORT", "sort");
define("FB_EVENTS_NR_ENTRIES", "numberOfEntries");
define("FB_EVENTS_SHOW_END_TIMES", "showEndTimes");
define("FB_EVENTS_LIMIT", "limit");
define("FB_EVENTS_QUOTE_PREFIX", "quoteprefix");

// Configuration parameters
define("FB_EVENTS_DATE_FORMAT", "dformat");
define("FB_EVENTS_TIME_FORMAT", "tformat");
define("FB_EVENTS_TEMPLATE", "template");
define("FB_EVENTS_WALLPOSTS_TEMPLATE", "wallposts");

// Helper sorting functions
function compareEventStartDateAsc($a, $b) {
	return strtotime($a['start_time']) - strtotime($b['start_time']);
}
function compareEventStartDateDesc($b, $a) {
	return strtotime($a['start_time']) - strtotime($b['start_time']);
}


/**
 * This plugin retrieves facebook events and displays them in HTML.
 *
 * Usage (simple): {{facebookevents>fanpageid=12345}}
 * Usage (complex): {{facebookevents>fanpageid=12345&showAs=table&showPostsAs=wallposts_alternate&from=-2 weeks&to=today}}
 *
 */
class syntax_plugin_importfacebookevents extends DokuWiki_Syntax_Plugin
{
	function getInfo() {
	  return array(
		'author' => 'G. Surrel, J. Drost-Tenfelde',
		'email'  => '',
		'date'   => '2018-03-09',
		'name'   => 'import_facebook_events',
		'desc'   => 'Displays facebook events as HTML',
		'url'    => 'https://www.dokuwiki.org/plugin:import_facebook_events',
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
		$this->Lexer->addSpecialPattern('\{\{facebookevents.*?\}\}',$mode,'plugin_importfacebookevents');
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
	 * parse parameters from the {{facebookevents>...}} tag.
	 * @return an array that will be passed to the renderer function
	 */
	function handle($match, $state, $pos, &$handler) {
		$match = substr($match, 17, -2);
		parse_str($match, $params);

		// Make sure the necessary data is set
		if ($this->getConf(FB_EVENTS_APPLICATION_ID) == '') {
		  $this->error = /*$this->getLang*/('error_appid_not_set');
		}
		if ($this->getConf(FB_EVENTS_APPLICATION_SECRET) == '') {
		  $this->error = /*$this->getLang*/('error_appsecret_not_set');
		}
		if (!$params[FB_EVENTS_FAN_PAGE_ID]) {
		  $this->error = /*$this->getLang*/('error_fanpageid_not_set');
		}
		if (!$params[FB_EVENTS_SHOW_AS]) {
			$params[FB_EVENTS_SHOW_AS] = 'default';
		}
		if (!$params[FB_EVENTS_WALLPOSTS_SHOW_AS]) {
			$params[FB_EVENTS_WALLPOSTS_SHOW_AS] = 'wallposts_default';
		}
		if (!$params[FB_EVENTS_QUOTE_PREFIX]) {
			$params[FB_EVENTS_QUOTE_PREFIX] = '> ';
		}
		if (!$params[FB_EVENTS_LIMIT]) {
			$params[FB_EVENTS_LIMIT] = 0;
		}

		// Get the appropriate display template
		$template = $this->getConf($params[FB_EVENTS_SHOW_AS]);
		if (!isset($template) || $template == '') {
			$template = $this->getConf('default');
		}
		$params[FB_EVENTS_TEMPLATE] = $template;

		// Get the appropriate display template for comments
		$wallposts_template = $this->getConf($params[FB_EVENTS_WALLPOSTS_SHOW_AS]);
		if (!isset($wallposts_template) || $wallposts_template == '') {
			$wallposts_template = $this->getConf('wallposts_default');
		}
		$params[FB_EVENTS_WALLPOSTS_TEMPLATE] = $wallposts_template;

		// Sorting
		if (!$params[FB_EVENTS_SORT]) {
			$params[FB_EVENTS_SORT] = 'ASC';
		}
		elseif ($params[FB_EVENTS_SORT] != 'DESC') {
			$params[FB_EVENTS_SORT] = 'ASC';
		}

		return $params;
	}

	/**
	 * Retrieves the facebook events and parses them to HTML.
	 */
	function render($mode, &$renderer, $data) {
		$info = $this->getInfo();
		
		// Disable caching because the result depends on an external ressource
		//$renderer->info['cache'] = false;

		$content = '';

        dbglog(date(DATE_ATOM));

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
			$fb_app_id = $this->getConf(FB_EVENTS_APPLICATION_ID);
			$fb_secret = $this->getConf(FB_EVENTS_APPLICATION_SECRET);
			$fb_page_id = $data[FB_EVENTS_FAN_PAGE_ID];

			// Get the access token using app-id and secret
			$token_url ="https://graph.facebook.com/oauth/access_token?client_id={$fb_app_id}&client_secret={$fb_secret}&grant_type=client_credentials";
			$token_data = $this->getData($token_url);

            dbglog($token_url);
            
			$elements = explode('"',$token_data);
			if (count($elements) < 9) {
				$renderer->doc .= 'Access token could not be retrieved for Plugin '.$info['name'].': '.$this->error.' | '.$token_data;
				return;
			}

            dbglog($elements);
            
			$fb_access_token = $elements[3];

			// Get the events
			$since_date = strtotime("-2 month", $data[FB_EVENTS_FROM_DATE]); // Go back in time as recurrent events disappear
			$until_date = strtotime($data[FB_EVENTS_TO_DATE]);
			$limit = $data[FB_EVENTS_NR_ENTRIES];

			$fb_fields="id,name,place,updated_time,timezone,start_time,end_time,event_times,cover,photos{picture},picture{url},description,feed.limit(10){from{name,picture},created_time,type,message,link,permalink_url,source,picture}";

			$json_link = "https://graph.facebook.com/v2.12/{$fb_page_id}/events/?fields={$fb_fields}&access_token={$fb_access_token}&limit={$limit}&since={$since_date}&until={$until_date}";
			$json = $this->getData($json_link);
			
			dbglog($json_link);

			//$objects = json_decode($json, true, 512, JSON_BIGINT_AS_STRING);
			$objects = json_decode($json, true);
			$events = $objects['data'];

			// Save timezone setting
			$origin_timezone = date_default_timezone_get();

            // Handle recurring events
            foreach($events as $i => $event){
                if(isset($event['event_times'])) {
                    foreach($event['event_times'] as $event_time) {
                        if(strtotime($event_time['start_time']) < strtotime($data[FB_EVENTS_FROM_DATE])) continue;
                        $json_link = "https://graph.facebook.com/v2.12/".$event_time['id']."/?fields={$fb_fields}&access_token={$fb_access_token}";
                        array_push($events, json_decode($this->getData($json_link), true));
                    }
                    unset($events[$i]);
                }
            }

			// Sort array of events by start time
			if ($data[FB_EVENTS_SORT] === 'ASC') {
				usort($events, 'compareEventStartDateAsc');
			}
			else {
				usort($events, 'compareEventStartDateDesc');
			}
            
			// Iterate over events
			foreach($events as $event){
                
				date_default_timezone_set($event['timezone']);

				$start_date = date($date_format, strtotime($event['start_time']));
				$start_time = date($time_format, strtotime($event['start_time']));
                
                if(strtotime($event['start_time']) < strtotime($data[FB_EVENTS_FROM_DATE])) continue;
                
				if (!isset($event['end_time'])) {
					$event['end_time'] = $event['start_time'];
				}
				$end_date = date($date_format, strtotime($event['end_time']));
				$end_time = date($time_format, strtotime($event['end_time']));

				$eid = $event['id'];
				$name = $event['name'];

				$description = isset($event['description']) ? $event['description'] : "";
				// Limit?
				if (isset($data[FB_EVENTS_LIMIT]) && ($data[FB_EVENTS_LIMIT] > 0)) {
					if (strlen($description) > $data[FB_EVENTS_LIMIT]) {
						$description = substr($description, 0, $data[FB_EVENTS_LIMIT]);
						// Find the first occurance of a space
						$index = strrpos ($description, ' ');
						$description = substr($description, 0, $index).'…';
					}
				}
				$description = str_replace("\r", '', $description);
				$description = str_replace("\n", "\\\\\n", $description);

				$picFull = isset($event['cover']['source']) ? $event['cover']['source'] : "https://graph.facebook.com/v2.7/{$fb_page_id}/picture";
				if (strpos($picFull, '?') > 0) $picFull .= '&.jpg';
				$picSmall = isset($event['photos']['data'][0]['picture']) ? $event['photos']['data'][0]['picture'] : "https://graph.facebook.com/v2.7/{$fb_page_id}/picture";
				if (strpos($picSmall, '?') > 0) $picSmall .= '&.jpg';
				$picSquare = isset($event['picture']['data']['url']) ? $event['picture']['data']['url'] : "https://graph.facebook.com/v2.7/{$fb_page_id}/picture";
				if (strpos($picSquare, '?') > 0) $picSquare .= '&.jpg';

				// place
				$place_name = isset($event['place']['name']) ? $event['place']['name'] : "";
				$street = isset($event['place']['location']['street']) ? $event['place']['location']['street'] : "";
				$city = isset($event['place']['location']['city']) ? $event['place']['location']['city'] : "";
				$country = isset($event['place']['location']['country']) ? $event['place']['location']['country'] : "";
				$zip = isset($event['place']['location']['zip']) ? $event['place']['location']['zip'] : "";

				$location="";
				$location_address="";

				if ($place_name && $street & $city && $country && $zip){
					$location = "{$place_name}";
					$location_address = "{$street}, {$zip} {$city}, {$country}";
				}
				else{
					$location = '?';
					$location_address = '?';
				}

				// Build the entry
				$entry = $data[FB_EVENTS_TEMPLATE];

				// Date
				$dateStart = date($this->getConf(FB_EVENTS_DATE_FORMAT), strtotime($event['start_time']));
				$dateEnd = date($this->getConf(FB_EVENTS_DATE_FORMAT), strtotime($event['end_time']));
				$dateStart != $dateEnd ? $date = "{$dateStart} - {$dateEnd}" : $date = "{$dateStart}";
				// Time
				$timeStart = date($this->getConf(FB_EVENTS_TIME_FORMAT), strtotime($event['start_time']));
				$timeEnd = date($this->getConf(FB_EVENTS_TIME_FORMAT), strtotime($event['end_time']));
				$timeStart != $timeEnd ? $time = "{$timeStart} - {$timeEnd}" : $time = "{$timeStart}";
				// DateTime
				$dateTimeStart = date($this->getConf(FB_EVENTS_TIME_FORMAT).', '.$this->getConf(FB_EVENTS_DATE_FORMAT), strtotime($event['start_time']));
				$dateTimeEnd = date($this->getConf(FB_EVENTS_TIME_FORMAT).', '.$this->getConf(FB_EVENTS_DATE_FORMAT), strtotime($event['end_time']));
				if($dateStart != $dateEnd) {
					$dateTime = $timeStart.', '.$dateStart.' - '.$timeEnd.', '.$dateEnd;
				}
				else {
					$dateTime = $timeStart.' - '.$timeEnd.', '.$dateEnd;
				}

				// Metadata
				$microdata = '<html><script type="application/ld+json">{json_microdata}</script></html>';
                $json_microdata = array (
                    '@context' => 'http://schema.org',
                    '@type' => 'Event',
                    'name' => '{title}',
                    'startDate' => '{starttimestamp}',
                    'endDate' => '{endtimestamp}',
                    'url' => '{url}',
                    'location' => 
                    array (
                        '@type' => 'Place',
                        'name' => '{location}',
                        'address' => '{location_address}',
                    ),
                    'description' => $description,
                    'image' => '{image}',
                );
					
				$microdata = str_replace('{json_microdata}', json_encode($json_microdata), $microdata);
				$entry = str_replace('{microdata}',$microdata, $entry);

				// Replace the values
				$entry = str_replace('{title}', $name, $entry);
				$entry = str_replace('{description}', $description, $entry);
				$entry = str_replace('{location}', $location, $entry);
				$entry = str_replace('{location_address}', $location_address, $entry);
				$entry = str_replace('{place}', $place_name, $entry);
				$entry = str_replace('{city}', $city, $entry);
				$entry = str_replace('{country}', $country, $entry);
				$entry = str_replace('{zip}', $zip, $entry);
				$entry = str_replace('{image}', $picFull, $entry);
				$entry = str_replace('{image_large}', $picFull, $entry);
				$entry = str_replace('{image_small}', $picSmall, $entry);
				$entry = str_replace('{image_square}', $picSquare, $entry);
				// Date & time replacements
				$entry = str_replace('{date}', $dateStart, $entry);
				$entry = str_replace('{time}', $time, $entry);
				$entry = str_replace('{datetime}', $dateTime, $entry);
				$entry = str_replace('{startdatetime}', $dateTimeStart, $entry);
				$entry = str_replace('{startdate}', $dateStart, $entry);
				$entry = str_replace('{starttime}', $timeStart, $entry);
				$entry = str_replace('{enddatetime}', $dateTimeEnd, $entry);
				$entry = str_replace('{enddate}', $dateEnd, $entry);
				$entry = str_replace('{endtime}', $timeEnd, $entry);
				$entry = str_replace('{timestamp}', date('c', strtotime($event['start_time'])), $entry);
				$entry = str_replace('{starttimestamp}', date('c', strtotime($event['start_time'])), $entry);
				$entry = str_replace('{endtimestamp}', date('c', strtotime($event['end_time'])), $entry);
				// [[ url | read more ]
				$event_url = "http://www.facebook.com/events/".$eid;
				$entry = str_replace('{url}', $event_url, $entry);
				$entry = str_replace('{more}', '[['.$event_url.'|'.$this->getLang('read_more').']]', $entry);
				
				// Handle wall posts
				$wallposts = '';
				foreach($event['feed']['data'] as $post) {
					$wallpost = $data[FB_EVENTS_WALLPOSTS_TEMPLATE];
					$quoteprefix = $data[FB_EVENTS_QUOTE_PREFIX];

					$userImage = $post['from']['picture']['data']['url'].'&.jpg';
					$userName = $post['from']['name'];

					$postDateTime = date($this->getConf(FB_EVENTS_DATE_FORMAT), strtotime($post['created_time']));

					$postLink = $post['permalink_url'];

					$description = $quoteprefix.$post['message'];
					$description = str_replace("\r", '', $description);
					$description = str_replace("\n", "\n{$quoteprefix}", $description);

					if(isset($post['source'])) {
						$mediaSource = $post['source'].'&.jpg';
					}
					elseif(isset($post['link'])) {
						$mediaSource = $post['link'];
					}
					else {
						$mediaSource = '';
					}
					isset($post['picture']) ? $mediaImage = $post['picture'].'&.jpg' : $mediaImage = '';

					$wallpost = str_replace('{wp_userImage}', $userImage, $wallpost);
					$wallpost = str_replace('{wp_userName}', $userName, $wallpost);
					$wallpost = str_replace('{wp_datetime}', $postDateTime, $wallpost);
					$wallpost = str_replace('{wp_content}', $description, $wallpost);
					$wallpost = str_replace('{wp_mediaSource}', $mediaSource, $wallpost);
					$wallpost = str_replace('{wp_mediaImage}', $mediaImage, $wallpost);
					$wallpost = str_replace('{wp_permalink}', $postLink, $wallpost);

					$wallposts .= $wallpost;
				}
				$entry = str_replace('{wallposts}', $wallposts, $entry);

				// Add the entry to the content
				$content .= $entry;
			}

			$html = p_render($mode, p_get_instructions($content), $info);
			$renderer->doc .= $html;

			// Set the timezone back to the original
			date_default_timezone_set($origin_timezone);

			return true;
		}
		return false;
	}
}
