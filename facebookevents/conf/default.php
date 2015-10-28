<?php

# Default configuration for iCalendar Dokuwiki Plugin

# Date format that is used to display the from and to values
# If you leave this empty '', then the default dformat from /conf/dokuwiki.php will be used.
$conf['dformat'] = '%d %B %Y';
$conf['tformat'] = '%H:%M';

// showAs=default
$conf['default'] = '
===== {title} =====
| [[{image_large}|{{{image_square}?nolink}}]] | ** {datetime} **\\\\ {location}\\\\ \\\\ {description}\\\\ \\\\ {more}  |';

$conf['table'] = '
^ [[{image_large}|{{  {image_square}?nolink}}]] | {title}  | ** {datetime} **  | {location}  | {description} \\\\ {more}  |';

$conf['short'] = '
^ [[{image_large}|{{  {image_square}?nolink}}]] | {title}  | ** {datetime} **  | {location}  | {description} \\\\ {more}  |';


?>