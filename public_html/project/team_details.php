<?php
require_once(__DIR__ . "/../../partials/nav.php");

$id = (int)se($_GET, "id", -1, false);
$details = [];
$games = [];
$players = [];
if ($id > 0) {
    if (get_team_api_id($id) === null) { //User generated team
        $detailsQuery = "SELECT t.name, t.code, t.conference, t.division, t.logo_url FROM teams t WHERE t.id = :id";
    } else { //API team
        $detailsQuery = "SELECT t.name, t.code, t.conference, t.division, t.logo_url, s.wins, s.losses, s.win_percentage, s.division_rank, s.conference_rank, s.home_record, s.away_record, s.streak, s.last_10 FROM teams t
        JOIN standings s ON s.team_api_id = t.api_id WHERE t.id = :id";
    }
    try {
        $db = getDB();
        $stmt = $db->prepare($detailsQuery);
        $stmt->execute([":id" => $id]);
        $r = $stmt->fetch();
        error_log(var_export($r, true));
        if ($r) {
            $details = $r;
        } else {
            flash("Team not found", "danger");
            die(header("Location: " . get_url("teams.php")));
        }
    } catch (PDOException $e) {
        error_log("Error fetching team details: " . var_export($e, true));
        flash("Error fetching details", "danger");
    }
    #Get last 5 played games and 5 upcoming games
    $gamesQuery = "SELECT * FROM ((SELECT g.date, g.home_score, g.away_score, g.arena, g.status, ht.name AS home, at.name AS away FROM games g 
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
        $stmt = $db->prepare($gamesQuery);
        $stmt->execute([":id" => $id]);
        $r = $stmt->fetchAll();
        if ($r) {
            $games = $r;
        }
    } catch (PDOException $e) {
        error_log("Error fetching games: " . var_export($e, true));
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

    $games_table = ["data" => $data, "title" => "Next/Last 5 Games", "empty_message" => "No games found"];

    $playersQuery = "SELECT CONCAT(p.first_name, \" \", p.last_name) AS name, p.height, p.weight, p.jersey_number FROM players p 
    JOIN teams t ON t.id = p.team_id WHERE t.id = :id ORDER BY p.last_name";
    try {
        $db = getDB();
        $stmt = $db->prepare($playersQuery);
        $stmt->execute([":id" => $id]);
        $r = $stmt->fetchAll();
        if ($r) {
            $players = $r;
        }
    } catch (PDOException $e) {
        error_log("Error fetching players: " . var_export($e, true));
        flash("Error fetching players", "danger");
    }
    $players = array_map(function ($player) {
        return [
            "Name" => $player["name"],
            "Height" => $player["height"] ?? "N/A",
            "Weight" => $player["weight"] ?? "N/A",
            "Jersey" => $player["jersey_number"] ?? "N/A"];
    }, $players);
    // $favorite_players = get_favorites("player", get_user_id());
    $favorite_players = [];
    $players_table = ["data" => $players, "title" => "Players", "empty_message" => "No players found",
        "favorite_url" => get_url("favorite.php"), "favorite_type" => "player", "favorites" => $favorite_players];
} else {
    flash("Invalid team", "danger");
    die(header("Location: " . get_url("teams.php")));
}
?>

<div class="container-fluid">
    <ul id="tabs" class="nav nav-pills justify-content-center">
        <li class="nav-item" style="margin-right: 20px">
            <a class="nav-link active" href="#" onclick="switchTab('games')">Games</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" onclick="switchTab('players')">Players</a>
        </li>
    </ul>

    <div class="row mt-2">
        <div class="col-md-2">
            <?php if ($details): ?>
                <?php card($details); ?>
            <?php endif; ?>
        </div>

        <div class="col-md-10">
            <div id="games" class="tab-target">
                <?php if ($games): ?>
                    <?php render_table($games_table); ?>
                <?php endif; ?>
            </div>

            <div id="players" class="tab-target" style="display: none;">
                <?php if ($players): ?>
                    <?php render_table($players_table); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <?php if ($id > 0 && has_role("Admin")): ?>
            <div class="col-md-2 text-center">
                <a href="<?php se(get_url("admin/edit_team.php")); ?>?id=<?php echo $id; ?>" class="btn btn-secondary">Edit Team</a>
                <a href="<?php se(get_url("admin/delete_team.php")); ?>?id=<?php echo $id; ?>" class="btn btn-danger">Delete Team</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function switchTab(tab) {
        let target = document.getElementById(tab);
        if (target) {
            let eles = document.getElementsByClassName("tab-target");
            for (let ele of eles) {
                ele.style.display = (ele.id === tab) ? "block" : "none";
            }

            let navLinks = document.querySelectorAll("#tabs .nav-link");
            navLinks.forEach(link => link.classList.remove("active"));
            document.querySelector(`[onclick="switchTab('${tab}')"]`).classList.add("active");
        }
    }
</script>

<?php 
require_once(__DIR__ . "/../../partials/flash.php");