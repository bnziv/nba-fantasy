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
        } else {
            $games = [];
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

/**
 * Get favorites for a user
 * 
 * @param string $type The type of favorite (team or player)
 * @param int $userId The ID of the user
 */
function get_favorites($type, $userId) {
    if ($type == "team") {
        $query = "SELECT team_id FROM favorite_teams WHERE user_id = :userId";
        $key = "team_id";
    } else if ($type == "player") {
        $query = "SELECT player_id FROM favorite_players WHERE user_id = :userId";
        $key = "player_id";
    } else {
        return [];
    }

    try {
        $db = getDB();
        $stmt = $db->prepare($query);
        $stmt->execute([":userId" => $userId]);
        $r = $stmt->fetchAll();
        if ($r) {
            return array_map(function ($v) use ($key) {
                return $v[$key];
            }, $r);
        }
    } catch (PDOException $e) {
        error_log("Error fetching favorites: " . var_export($e, true));
        flash("Error fetching favorites", "danger");
    }
    return [];
}

/**
 * Update the standings and games
 */
function update_stats() {
    $standings = fetch_standings();
    try {
        $opts = ["update_duplicate" => true];
        $result = insert("standings", $standings, $opts);
        if (!$result) {
            error_log("Unhandled error" . var_export($result, true));
        } else {
            error_log("Updated standings");
        }
    } catch (InvalidArgumentException $e1) {
        error_log("Invalid arg" . var_export($e1, true));
    } catch (PDOException $e2) {
        error_log("Database error" . var_export($e2, true));
    } catch (Exception $e3) {
        error_log("Invalid data records" . var_export($e3, true));
    }
    $games = fetch_games();
    try {
        $opts = ["update_duplicate" => true];
        $result = insert("games", $games, $opts);
        if (!$result) {
            error_log("Unhandled error" . var_export($result, true));
        } else {
            error_log("Updated all games");
        }
    } catch (InvalidArgumentException $e1) {
        error_log("Invalid arg" . var_export($e1, true));
    } catch (PDOException $e2) {
        error_log("Database error" . var_export($e2, true));
    } catch (Exception $e3) {
        error_log("Invalid data records" . var_export($e3, true));
    }
}

/**
 * Check if stats need to be updated (latest update is more than an hour ago)
 */
function check_update() {
    $query = "SELECT modified FROM games ORDER BY modified DESC LIMIT 1";
    try {
        $db = getDB();
        $stmt = $db->prepare($query);
        $stmt->execute();
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($r) {
            $last_modified = $r["modified"];
        }
    } catch (PDOException $e) {
        error_log("Error fetching last modified: " . var_export($e, true));
        flash("Error fetching last modified", "danger");
    }
    $last_modified = new DateTime($last_modified, new DateTimeZone("UTC"));
    $now = new DateTime("now", new DateTimeZone("UTC"));
    $gap = $now->format("U") - $last_modified->format("U");
    if ($gap > 3600) {
        update_stats();
    }
}

function card($data = array()) {
    include(__DIR__. "/../partials/card.php");
}

?>