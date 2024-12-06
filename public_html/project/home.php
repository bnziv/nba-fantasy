<?php
require(__DIR__ . "/../../partials/nav.php");
?>
<?php

if (is_logged_in(true)) {
    //comment this out if you don't want to see the session variables
    error_log("Session data: " . var_export($_SESSION, true));
}

$query = "SELECT date, ht.name AS home, at.name AS away,
home_score, away_score, arena FROM games 
JOIN teams ht ON home_team_api_id = ht.api_id 
JOIN teams at ON away_team_api_id = at.api_id 
WHERE DATE(CONVERT_TZ(date, '+00:00', '-05:00')) = :date ORDER BY date";

//Get today's date in EST
$date = new DateTime("now", new DateTimeZone("UTC"));
$date = $date->setTimezone(new DateTimeZone("America/New_York"));
$params = [":date" => $date->format("Y-m-d")];

try {
    $db = getDB();
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $r = $stmt->fetchAll();
    if ($r) {
        $games = $r;
    }
} catch (PDOException $e) {
    error_log("Error fetching games: " . var_export($e, true));
    flash("Error fetching games", "danger");
}

$games = array_map(function ($game) {
    //Format time in EST
    $date = new DateTime($game["date"], new DateTimeZone("UTC"));
    $date = $date->setTimezone(new DateTimeZone("America/New_York"));
    $game["date"] = $date->format("h:i A");
    return [
        "Start Time (EST)" => $game["date"],
        "Home" => $game["home"],
        "Away" => $game["away"],
        "Home Score" => $game["home_score"],
        "Away Score" => $game["away_score"],
        "Arena" => $game["arena"]];
}, $games);

$table = ["data" => $games, "extra_classes" => "table-striped"];

?>
<h1>Games for <?php echo $date->format("F j"); ?></h1>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-9" style="display: flex; justify-content: center; margin-top: 20px">
            <?php if ($games): ?>
                <?php render_table($table); ?>
            <?php else: ?>
                <p>No games today</p>
            <?php endif; ?>
        </div>
    </div>
<?php
require(__DIR__ . "/../../partials/flash.php");
?>