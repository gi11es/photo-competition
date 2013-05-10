<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/
        
require_once(dirname(__FILE__).'/../constants.php');
?>

<fb:js-string var="loading_gif">  
<img src="http://72.249.78.217/~daruma/photocompetition/images/ajax-loader.gif">
</fb:js-string>

<script><!--
function votePicture(picture_id, topic_id, value) {  
        document.getElementById('stars').setInnerFBML(loading_gif);
        var ajax = new Ajax();
        ajax.responseType = Ajax.FBML;
        ajax.ondone = function(data) { 
                document.getElementById('stars').setInnerFBML(data);
        }
        ajax.requireLogin = true;
        ajax.post('<?=$AJAX_PATH?>picturevote.php?picture_id=' + picture_id + '&topic_id=' + topic_id + '&value=' + value);
}
//--></script>