<?php
require(__DIR__ . "/../../partials/nav.php");
?>
<?php

if (is_logged_in(true)) {
    //comment this out if you don't want to see the session variables
    error_log("Session data: " . var_export($_SESSION, true));
}

//Get today's date in EST
$date = new DateTime("now", new DateTimeZone("America/New_York"));
$todays_games = get_games_for_date($date->format("Y-m-d"), );
$today_title = "Games for Today - " . $date->format("F j");
$today_table = ["data" => $todays_games, "extra_classes" => "table-striped", "title" => $today_title];

//Get yesterday's date in EST
$yesterday = new DateTime("yesterday", new DateTimeZone("America/New_York"));
$yesterday_games = get_games_for_date($yesterday->format("Y-m-d"));
$yesterday_title = "Games from Yesterday - " . $yesterday->format("F j");
$yesterday_table = ["data" => $yesterday_games, "extra_classes" => "table-striped", "title" => $yesterday_title];

?>
<h1>Home</h1>
<div class="container-fluid">
    <div class="row" style="margin-top: 50px">
        <div class="col-md-9 offset-md-1">
            <?php if ($todays_games): ?>
                <?php render_table($today_table); ?>
            <?php else: ?>
                <p>No games today</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-9 offset-md-1">
            <?php if ($yesterday_games): ?>
                <?php render_table($yesterday_table); ?>
            <?php else: ?>
                <p>No games yesterday</p>
            <?php endif; ?>
        </div>
    </div>
<?php
require(__DIR__ . "/../../partials/flash.php");
?>