<?php

# Default configuration for iCalendar Dokuwiki Plugin

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

/*'
^ [[{image_large}|{{  {image}?100&nolink}}]] | {title}  | ** {datetime} **  | {location}  | {description} \\\\ {more}  |';

$conf['short'] = '
^ [[{image_large}|{{  {image}?100&nolink}}]] | {title}  | ** {datetime} **  | {location}  | {description} \\\\ {more}  |';
*/

?>