<?php

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/../utilities/cache.php');

print_r(Cache::getStats());

?>
