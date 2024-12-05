<?php

function get_teams() {
    $teams = [];
    $query = "SELECT t.api_id, t.name, COUNT(p.id) as player_count FROM teams t 
    LEFT JOIN players p on t.api_id = p.team_api_id GROUP BY t.api_id, t.name";
    try {
        $db = getDB();
        $stmt = $db->prepare($query);
        $stmt->execute();
        $r = $stmt->fetchAll();
        if ($r) {
            $teams = $r;
        }
    } catch (PDOException $e) {
        error_log("Error fetching guide: " . var_export($e, true));
        flash("Error fetching teams", "danger");
    }
    return $teams;
}

function card($data = array()) {
    include(__DIR__. "/../partials/card.php");
}

?>