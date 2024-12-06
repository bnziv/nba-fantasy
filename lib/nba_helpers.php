<?php

function get_teams() {
    $teams = [];
    $query = "SELECT t.id, t.name, COUNT(p.id) as player_count FROM teams t 
    LEFT JOIN players p on t.id = p.team_id GROUP BY t.name ORDER BY t.name";
    try {
        $db = getDB();
        $stmt = $db->prepare($query);
        $stmt->execute();
        $r = $stmt->fetchAll();
        if ($r) {
            $teams = $r;
        }
    } catch (PDOException $e) {
        error_log("Error fetching teams: " . var_export($e, true));
        flash("Error fetching teams", "danger");
    }
    return $teams;
}

/**
 * Convert a team's ID in database to their API ID
 */
function get_team_api_id($id) {
    $query = "SELECT api_id FROM teams WHERE id = :id";
    try {
        $db = getDB();
        $stmt = $db->prepare($query);
        $stmt->execute([":id" => $id]);
        $r = $stmt->fetch();
        if ($r) {
            return $r["api_id"];
        }
    } catch (PDOException $e) {
        error_log("Error fetching team: " . var_export($e, true));
        flash("Error fetching team", "danger");
    }
    return null;
}

/**
 * Convert a team's API ID to their ID in the database
 */
function get_team_db_id($api_id) {
    $query = "SELECT id FROM teams WHERE api_id = :api_id";
    try {
        $db = getDB();
        $stmt = $db->prepare($query);
        $stmt->execute([":api_id" => $api_id]);
        $r = $stmt->fetch();
        if ($r) {
            return $r["id"];
        }
    } catch (PDOException $e) {
        error_log("Error fetching team: " . var_export($e, true));
        flash("Error fetching team", "danger");
    }
    return "";
}

function get_conferences() {
    $query = "SELECT DISTINCT conference FROM teams ORDER BY conference";
    try {
        $db = getDB();
        $stmt = $db->prepare($query);
        $stmt->execute();
        $r = $stmt->fetchAll();
        if ($r) {
            return $r;
        }
    } catch (PDOException $e) {
        error_log("Error fetching conferences: " . var_export($e, true));
        flash("Error fetching conferences", "danger");
    }
    return [];
}

function get_divisions() {
    $query = "SELECT DISTINCT division FROM teams ORDER BY division";
    try {
        $db = getDB();
        $stmt = $db->prepare($query);
        $stmt->execute();
        $r = $stmt->fetchAll();
        if ($r) {
            return $r;
        }
    } catch (PDOException $e) {
        error_log("Error fetching divisions: " . var_export($e, true));
        flash("Error fetching divisions", "danger");
    }
    return [];
}

/**
 * Get all games for a given date
 * 
 * @param string $date The date in the format of Y-m-d as EST
 * 
 */
function get_games_for_date($date) {
    $query = "SELECT date, ht.name AS home, at.name AS away,
    home_score, away_score, arena, status FROM games 
    JOIN teams ht ON home_team_api_id = ht.api_id 
    JOIN teams at ON away_team_api_id = at.api_id 
    WHERE DATE(CONVERT_TZ(date, '+00:00', '-05:00')) = :date ORDER BY date";

    //Get today's date in EST
    if (!preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $date)) {
        return [];
    }
    $params = [":date" => $date];

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

        if ($game["status"] == "Finished") {
            if ($game["home_score"] > $game["away_score"]) {
                $game["home"] = $game["home"] . " (W)";
                $game["away"] = $game["away"] . " (L)";
            } else {
                $game["home"] = $game["home"] . " (L)";
                $game["away"] = $game["away"] . " (W)";
            }
        }

        return [
            "Start Time (EST)" => $game["date"],
            "Home" => $game["home"],
            "Away" => $game["away"],
            "Home Score" => $game["home_score"],
            "Away Score" => $game["away_score"],
            "Arena" => $game["arena"]];
    }, $games);
    return $games;
}

function card($data = array()) {
    include(__DIR__. "/../partials/card.php");
}

?>