<?php

# Default configuration for import_Facebook_events Dokuwiki Plugin

# Date format that is used to display the from and to values
# If you leave this empty '', then the default dformat from /conf/dokuwiki.php will be used.
$conf['dformat'] = 'd F Y';
$conf['tformat'] = 'h:ia';

// showAs=default
$conf['default'] = '
{datetime} **{title}**\\\\
{description}\\\\
{location}\\\\
{more}';
$conf['table'] = $conf['default'];
$conf['short'] = $conf['default'];

$conf['wallposts_default'] = '> {{{wp_userImage}?nolink |}} **{wp_userName}** \\ //[[{wp_permalink}|{wp_datetime}]]//
> [[{wp_mediaSource}|{{ {wp_mediaImage}?nolink|}}]]
{wp_content}

';
$conf['wallposts_alternate'] = '';
