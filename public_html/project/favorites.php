<?php
require_once(__DIR__ . "/../../partials/nav.php");
if (!is_logged_in()) {
    flash("You are not logged in", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}
$name = se($_GET, "name", "", false);
$code = se($_GET, "code", "", false);
$conference = se($_GET, "conference", "", false);
$division = se($_GET, "division", "", false);
$team = se($_GET, "team", "", false);
$type = se($_GET, "type", "", false);
$limit = se($_GET, "limit", 10, false);

if (isset($_POST["unfavorite"]) && $_POST["unfavorite"] == "teams") {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM favorite_teams WHERE user_id = :user_id");
        $stmt->execute([":user_id" => get_user_id()]);
        flash("Removed from favorites", "success");
    } catch (PDOException $e) {
        error_log("Error updating favorites: " . var_export($e, true));
        flash("There was an error deleting favorites", "danger");
    }
} else if (isset($_POST["unfavorite"]) && $_POST["unfavorite"] == "players") {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM favorite_players WHERE user_id = :user_id");
        $stmt->execute([":user_id" => get_user_id()]);
        flash("Removed from favorites", "success");
    } catch (PDOException $e) {
        error_log("Error updating favorites: " . var_export($e, true));
        flash("There was an error deleting favorites", "danger");
    }
}

$teams_query = "SELECT t.id, t.name as Name, CONCAT(s.wins, \"-\", s.losses) AS Record, s.streak AS Streak,
t.conference as Conference, t.division as Division
FROM standings s JOIN teams t ON t.api_id = s.team_api_id
JOIN favorite_teams ft ON ft.team_id = t.id
WHERE ft.user_id = :user_id";
if ($type == "teams") {
    if (!empty($name)) {
        $teams_query .= " AND name LIKE :name";
        $teamParams[":name"] = "%$name%";
    }
    if (!empty($conference)) {
        $teams_query .= " AND conference LIKE :conference";
        $teamParams[":conference"] = "%$conference%";
    }
    if (!empty($division)) {
        $teams_query .= " AND division LIKE :division";
        $teamParams[":division"] = "%$division%";
    }
    $teams_query .= " ORDER BY name";

    $limit = 10;
    if (isset($_GET["limit"]) && !is_nan($_GET["limit"])) {
        $limit = $_GET["limit"];
        if ($limit < 0 || $limit > 100) {
            $limit = 10;
        }
    }
    $teams_query .= " LIMIT $limit";
}
$favorite_team_ids = get_favorites("team", get_user_id());
$favorite_players_ids = get_favorites("player", get_user_id());
$teamParams[":user_id"] = get_user_id();
try {
    $db = getDB();
    $stmt = $db->prepare($teams_query);
    $stmt->execute($teamParams);
    $r = $stmt->fetchAll();
    $favorite_teams = $r;
} catch (PDOException $e) {
    error_log("Error fetching teams: " . var_export($e, true));
    flash("Error fetching teams", "danger");
}
$teamTitle = "Favorite Teams (" . count($favorite_teams) . ")";
$teams_table = ["data" => $favorite_teams, "title" => $teamTitle, "empty_message" => "No teams favorited",
"ignored_columns" => ["id"], "view_url" => get_url("team_details.php"), "view_label" => "Details",
"favorite_url" => get_url("update_favorite.php"), "favorite_type" => "team", "favorites" => $favorite_team_ids];

$playersQuery = "SELECT p.id, CONCAT(p.first_name, \" \", p.last_name) AS Name, t.name, p.height as Height, p.weight as Weight, p.jersey_number as Jersey FROM players p 
JOIN favorite_players fp ON fp.player_id = p.id JOIN teams t ON t.id = p.team_id WHERE fp.user_id = :user_id";

if ($type == "players") {
    if (!empty($name)) {
        $playersQuery .= " AND CONCAT(p.first_name, \" \", p.last_name) LIKE :name";
        $playerParams[":name"] = "%$name%";
    }
    if (!empty($team)) {
        $playersQuery .= " AND p.team_id = :team";
        $playerParams[":team"] = $team;
    }
    $limit = 10;
    if (isset($_GET["limit"]) && !is_nan($_GET["limit"])) {
        $limit = $_GET["limit"];
        if ($limit < 0 || $limit > 100) {
            $limit = 10;
        }
    }
    $playersQuery .= " LIMIT $limit";
}
$playerParams[":user_id"] = get_user_id();
try {
    $db = getDB();
    $stmt = $db->prepare($playersQuery);
    $stmt->execute($playerParams);
    $r = $stmt->fetchAll();
    $favorite_players = $r;
} catch (PDOException $e) {
    error_log("Error fetching players: " . var_export($e, true));
    flash("Error fetching players", "danger");
}
$playerTitle = "Favorite Players (" . count($favorite_players) . ")";
$players_table = ["data" => $favorite_players, "title" => $playerTitle, "empty_message" => "No players favorited",
"ignored_columns" => ["id"], "favorite_url" => get_url("update_favorite.php"), "favorite_type" => "player", "favorites" => $favorite_players_ids];

$conferences = get_conferences();
$conferences = array_map(function ($v) {
    return [$v["conference"] => $v["conference"]];
}, $conferences);
$divisions = get_divisions();
$divisions = array_map(function ($v) {
    return [$v["division"] => $v["division"]];
}, $divisions);
array_push($conferences, ["" => "None"]);
array_push($divisions, ["" => "None"]);
$teams = get_teams();
$teams = array_map(function ($v) {
    return [$v["id"] => $v["name"]];
}, $teams);
array_push($teams, ["" => "None"]);
?>

<div class="container-fluid">
    <ul id="tabs" class="nav nav-pills justify-content-center">
        <li class="nav-item" style="margin-right: 20px">
            <a class="nav-link <?php echo $type == "teams" ? "active" : "" ?>" href="#" onclick="switchTab('teams')">Teams</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $type == "players" ? "active" : "" ?>" href="#" onclick="switchTab('players')">Players</a>
        </li>
    </ul>
    <div id="teams" class="tab-target">
        <div style="margin-top: 20px">
            <form>
                <div class="row">
                    <div class="col">
                        <?php render_input(["name" => "name", "type" => "text", "label" => "Name", "value" => $name]); ?>
                    </div>
                    <div class="col">
                        <?php render_input(["name" => "conference", "type" => "select", "label" => "Conference", "value" => $conference, "options" => $conferences]); ?>
                    </div>
                    <div class="col">
                        <?php render_input(["name" => "division", "type" => "select", "label" => "Division", "value" => $division, "options" => $divisions]); ?>
                    </div>
                    <div class="col">
                        <?php render_input(["name" => "limit", "type" => "number", "label" => "Limit", "value" => $limit]); ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <?php render_input(["type" => "hidden", "name" => "type", "value" => "teams"]); ?>
                        <?php render_button(["type" => "submit", "text" => "Search"]); ?>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-md-7" style="width:50%; margin: auto;">
            <?php render_table($teams_table); ?>
        </div>
        <form method="POST">
            <div class="row">
                <div class="col text-center">
                    <?php render_input(["type" => "hidden", "name" => "unfavorite", "value" => "teams"]); ?>
                    <?php render_button(["type" => "submit", "name" => "teams", "text" => "Unfavorite All", "color" => "danger"]) ?>
                </div>
            </div>
        </form>
    </div>
    <div id="players" style="display: none;" class="tab-target">
        <div style="margin-top: 20px">
            <form>
                <div class="row">
                    <div class="col">
                        <?php render_input(["name" => "name", "type" => "text", "label" => "Name", "value" => $name]); ?>
                    </div>
                    <div class="col">
                        <?php render_input(["name" => "team", "type" => "select", "label" => "Team", "value" => $team, "options" => $teams]); ?>
                    </div>
                    <div class="col">
                        <?php render_input(["name" => "limit", "type" => "number", "label" => "Limit", "value" => $limit]); ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <?php render_input(["type" => "hidden", "name" => "type", "value" => "players"]); ?>
                        <?php render_button(["type" => "submit", "text" => "Search"]); ?>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-md-7" style="width:50%; margin: auto;">
            <?php render_table($players_table); ?>
        </div>
        <form method="POST">
            <div class="row">
                <div class="col text-center">
                    <?php render_input(["type" => "hidden", "name" => "unfavorite", "value" => "players"]); ?>
                    <?php render_button(["type" => "submit", "text" => "Unfavorite All", "color" => "danger"]) ?>
                </div>
            </div>
        </form>
    </div>
</div>
<script>

    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        const type = urlParams.get('type');
        
        switchTab(type || 'teams');
    });

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

<?php require(__DIR__ . "/../../partials/flash.php"); ?>