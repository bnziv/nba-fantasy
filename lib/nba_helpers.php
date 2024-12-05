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

function card($data = array()) {
    include(__DIR__. "/../partials/card.php");
}

?>