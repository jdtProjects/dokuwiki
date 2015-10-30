<?php

// Date format
$conf['dformat'] = '%d %B %Y';

// Time format
$conf['tformat'] = '%H:%M';

// showAs=default
$conf['default'] = '===== {datetime} =====
{message}\\\\ \\\\ {more} \\\\ \\\\ ';

// showAs=table
$conf['table'] = '
^ {datetime}  |
| {message} \\\\ {more}  |';

// showAs=article
$conf['article'] = '[[{url}|{{ wiki:facebook.png?nolink}}]]** {datetime} ** \\\\
----
{message} \\\\
{more}
';

$conf['maxWidth'] = 300;
$conf['maxHeight'] = 0;

?>