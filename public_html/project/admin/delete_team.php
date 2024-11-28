<?php
require(__DIR__ . "/../../../lib/functions.php");
session_start();
if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}
$id = se($_GET, "id", -1, false);

if ($id > 0) {
    $db = getDB();
    try {
        $stmt = $db->prepare("DELETE FROM teams where id = :id");
        $stmt->execute([":id" => $id]);
        flash("Delete successful", "success");
    } catch (PDOException $e) {
        error_log("Error deleting: " . var_export($e, true));
        flash("There was an error deleting the record", "danger");
    }
} else {
    flash("Invalid id passed", "danger");
}
unset($_GET["id"]);
$loc = get_url("admin/list_teams.php")."?" . http_build_query($_GET);
error_log("Location: $loc");
die(header("Location: $loc"));