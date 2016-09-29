<?php

$conf['dformat'] = 'd F Y';
$conf['tformat'] = 'h:ia';

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