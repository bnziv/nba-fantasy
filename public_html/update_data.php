<?php
require_once(__DIR__ . "/../lib/functions.php");
check_update();
check_fantasy_update();
echo json_encode(array("status" => "success"));