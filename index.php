<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/
        
error_reporting(E_ALL);

require_once(dirname(__FILE__).'/constants.php');
require_once(dirname(__FILE__).'/settings.php');

echo "<fb:redirect url=\"".$PAGE["COMPETITIONS2"]."\"/>";

?>

<script src="http://www.google-analytics.com/urchin.js" type="text/javascript">
</script>
<script type="text/javascript">
_uacct = "<?=$GOOGLE_ANALYTICS_CODE?>";
urchinTracker();
</script>
