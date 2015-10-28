<?php

$conf['appid'] = '';
$conf['secret'] = '';
$conf['fanpageid'] = '';
$conf['ignore'] = 'cover photos';
$conf['order'] = 'DESC';

$conf['album_template'] = '
<html>
<div class="facebookalbum" style="float:left; padding: 5px; ">
<center>
  <a href="{url}" title="{name}">
    <img src="{image_url}" alt="{name}" height="100" width="130" /><br />
    <small>{name}</small><br />
    <br />
  </a>
</center>
</div>
</html>
';

$conf['picture_template'] = '
<html>
<a rel="facebook_album" href="{image_large_url}" title="{caption}" class="thickbox">
  <img align="left" src="{image_url}" alt="{caption}" vspace="5" hspace="5" height="100" Width="150" />
</a>
</html>
';