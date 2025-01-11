<?php require_once(__DIR__ . "/../../lib/functions.php");

try {
    $db = getDB();
    $team = se($_GET, "team", "", false);
    $position = se($_GET, "position", "", false);

    $query = "SELECT CONCAT(first_name, ' ' , last_name) AS name, position, id FROM players WHERE 1=1";
    $params = [];

    if ($team) {
        $query .= " AND team_id = :team";
        $params[":team"] = $team;
    }
    if ($position) {
        $query .= " AND position LIKE :position";
        $params[":position"] = $position;
    }

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($players);
} catch (PDOException $e) {
    error_log("Database Error: " . var_export($e, true));
    echo json_encode(["error" => "Failed to fetch players"]);
    exit();
}
?>