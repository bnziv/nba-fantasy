<?php
require_once(__DIR__ . "/../../../partials/nav.php");

$id = (int)se($_GET, "id", -1, false);
$guide = [];
if ($id > 0) {
    $details = "SELECT t.name, t.code, t.conference, t.division, t.logo_url, s.wins, s.losses, s.win_percentage, s.division_rank, s.conference_rank, s.home_record, s.away_record, s.streak, s.last_10 FROM teams t
    JOIN standings s ON s.team_api_id = t.api_id WHERE t.id = :id LIMIT 100";
    try {
        $db = getDB();
        $stmt = $db->prepare($details);
        $stmt->execute([":id" => $id]);
        $r = $stmt->fetch();
        if ($r) {
            $details = $r;
        }
    } catch (PDOException $e) {
        error_log("Error fetching guide: " . var_export($e, true));
        flash("Error fetching details", "danger");
    }
    #Get last 5 played games and 5 upcoming games
    $games = "SELECT * FROM ((SELECT g.date, g.home_score, g.away_score, g.arena, g.status, ht.name AS home, at.name AS away FROM games g 
            JOIN teams ht ON g.home_team_api_id = ht.api_id 
            JOIN teams at on g.away_team_api_id = at.api_id
            JOIN teams t ON t.id = :id
            WHERE (t.api_id = home_team_api_id OR away_team_api_id = t.api_id) AND status = 'Finished' ORDER BY date DESC LIMIT 5)
            UNION
            (SELECT g.date, g.home_score, g.away_score, g.arena, g.status, ht.name AS home, at.name AS away FROM games g 
            JOIN teams ht ON g.home_team_api_id = ht.api_id 
            JOIN teams at on g.away_team_api_id = at.api_id
            JOIN teams t ON t.id = :id
            WHERE (t.api_id = home_team_api_id OR away_team_api_id = t.api_id) AND status = 'Scheduled' ORDER BY date ASC LIMIT 5)) AS selected ORDER by date";
    try {
        $db = getDB();
        $stmt = $db->prepare($games);
        $stmt->execute([":id" => $id]);
        $r = $stmt->fetchAll();
        if ($r) {
            $games = $r;
        }
    } catch (PDOException $e) {
        error_log("Error fetching guide: " . var_export($e, true));
        flash("Error fetching games", "danger");
    }

    //Get abbreviation (EST/EDT) 
    $tz = new DateTime("now", new DateTimeZone("America/New_York"));
    $transitions = $tz->getTimezone()->getTransitions();
    $current = end($transitions);
    $abbr = $current["abbr"];

    $data = array_map(function ($game) use ($abbr) {
        //Format time in EST
        $date = new DateTime($game["date"], new DateTimeZone("UTC"));
        $date = $date->setTimezone(new DateTimeZone("America/New_York"));
        $game["date"] = $date->format("M d h:i A");

        //Display win/loss
        if ($game["home_score"] != $game["away_score"]) {
            if ($game["home_score"] > $game["away_score"]) {
                $game["home"] = $game["home"] . " (W)";
                $game["away"] = $game["away"] . " (L)";
            } else {
                $game["home"] = $game["home"] . " (L)";
                $game["away"] = $game["away"] . " (W)";
            }
        }
        return [
            "Start Time ($abbr)" => $game["date"],
            "Home" => $game["home"],
            "Away" => $game["away"],
            "Home Score" => $game["home_score"],
            "Away Score" => $game["away_score"],
            "Arena" => $game["arena"]];
    }, $games);

    $table = ["data" => $data, "title" => "Next/Last 5 Games"];
} else {
    flash("Invalid guide", "danger");
}
?>

<div class="container-fluid">
    <div class="row">
    <div class="col-md-2 offset-md-1">
    <?php if($details):?>
        <?php card($details);?>
    <?php endif;?>
    </div>
    <div class="col-md-8">
    <?php if($games):?>
        <?php render_table($table);?>
    <?php endif;?>
    </div>
    </div>
</div>
<?php 
require_once(__DIR__ . "/../../../partials/flash.php");