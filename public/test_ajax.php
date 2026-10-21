<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// We need to bypass the session check to test it properly, 
// so let's directly call the database query logic here!
require '../app/Config/Database.php';
// Or even better, let's just grep the latest CI4 log again to see if anything showed up today.

