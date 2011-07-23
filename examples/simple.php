<?php

// setup Bugsense with the following two lines
require dirname(__FILE__)."/../bugsense.php";
Bugsense::setup("YOUR-API-KEY");

// control which errors are caught with error_reporting
error_reporting(E_ALL);

// start testing
$math = 1 / 0;

?>