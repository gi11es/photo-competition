<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/
        
require_once(dirname(__FILE__).'/../constants.php');
?>

<script><!--

function starEventHandler(evt) {
	var off_image = '<?php echo $APP_REAL_PATH. 'images/star_big_off.gif'; ?>';
	var on_image = '<?php echo $APP_REAL_PATH. 'images/star_big_on.gif'; ?>';
	var starvalue = document.getElementById('star_value').getValue();
	
	if (evt.type == 'mouseout') {
		if (starvalue == 0) {
			document.getElementById('star1').setSrc(off_image);
			document.getElementById('star2').setSrc(off_image);
			document.getElementById('star3').setSrc(off_image);
			document.getElementById('star4').setSrc(off_image);
			document.getElementById('star5').setSrc(off_image);
		} else if (starvalue == 1) { 
			document.getElementById('star1').setSrc(on_image);
			document.getElementById('star2').setSrc(off_image);
			document.getElementById('star3').setSrc(off_image);
			document.getElementById('star4').setSrc(off_image);
			document.getElementById('star5').setSrc(off_image);
		} else if (starvalue == 2) {
			document.getElementById('star1').setSrc(on_image);
			document.getElementById('star2').setSrc(on_image);
			document.getElementById('star3').setSrc(off_image);
			document.getElementById('star4').setSrc(off_image);
			document.getElementById('star5').setSrc(off_image);
		} else if (starvalue == 3) {
			document.getElementById('star1').setSrc(on_image);
			document.getElementById('star2').setSrc(on_image);
			document.getElementById('star3').setSrc(on_image);
			document.getElementById('star4').setSrc(off_image);
			document.getElementById('star5').setSrc(off_image);
		} else if (starvalue == 4) {
			document.getElementById('star1').setSrc(on_image);
			document.getElementById('star2').setSrc(on_image);
			document.getElementById('star3').setSrc(on_image);
			document.getElementById('star4').setSrc(on_image);
			document.getElementById('star5').setSrc(off_image);
		} else if (starvalue == 5) {
			document.getElementById('star1').setSrc(on_image);
			document.getElementById('star2').setSrc(on_image);
			document.getElementById('star3').setSrc(on_image);
			document.getElementById('star4').setSrc(on_image);
			document.getElementById('star5').setSrc(on_image);
		}
	} else {
		eventFiredBy_ObjectId = evt.target.getId();
		
		if (eventFiredBy_ObjectId == 'star1') { 
			document.getElementById('star1').setSrc(on_image);
			document.getElementById('star2').setSrc(off_image);
			document.getElementById('star3').setSrc(off_image);
			document.getElementById('star4').setSrc(off_image);
			document.getElementById('star5').setSrc(off_image);
		} else if (eventFiredBy_ObjectId == 'star2') {
			document.getElementById('star1').setSrc(on_image);
			document.getElementById('star2').setSrc(on_image);
			document.getElementById('star3').setSrc(off_image);
			document.getElementById('star4').setSrc(off_image);
			document.getElementById('star5').setSrc(off_image);
		} else if (eventFiredBy_ObjectId == 'star3') {
			document.getElementById('star1').setSrc(on_image);
			document.getElementById('star2').setSrc(on_image);
			document.getElementById('star3').setSrc(on_image);
			document.getElementById('star4').setSrc(off_image);
			document.getElementById('star5').setSrc(off_image);
		} else if (eventFiredBy_ObjectId == 'star4') {
			document.getElementById('star1').setSrc(on_image);
			document.getElementById('star2').setSrc(on_image);
			document.getElementById('star3').setSrc(on_image);
			document.getElementById('star4').setSrc(on_image);
			document.getElementById('star5').setSrc(off_image);
		} else if (eventFiredBy_ObjectId == 'star5') {
			document.getElementById('star1').setSrc(on_image);
			document.getElementById('star2').setSrc(on_image);
			document.getElementById('star3').setSrc(on_image);
			document.getElementById('star4').setSrc(on_image);
			document.getElementById('star5').setSrc(on_image);
		}
	}
}

document.getElementById('star1').addEventListener('mouseover',starEventHandler);
document.getElementById('star2').addEventListener('mouseover',starEventHandler);
document.getElementById('star3').addEventListener('mouseover',starEventHandler);
document.getElementById('star4').addEventListener('mouseover',starEventHandler);
document.getElementById('star5').addEventListener('mouseover',starEventHandler);

document.getElementById('star1').addEventListener('mouseout',starEventHandler);
document.getElementById('star2').addEventListener('mouseout',starEventHandler);
document.getElementById('star3').addEventListener('mouseout',starEventHandler);
document.getElementById('star4').addEventListener('mouseout',starEventHandler);
document.getElementById('star5').addEventListener('mouseout',starEventHandler);

//--></script>