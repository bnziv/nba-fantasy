<?php
require(__DIR__ . "/../lib/functions.php");
session_start();
if (!is_logged_in()) {
    flash("You are not logged in", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}
$user = se($_GET, "user", get_user_id(), false);
$team = se($_GET, "team", -1, false);
$player = se($_GET, "player", -1, false);
$favorite_id = se($_GET, "id", -1, false);
if ($team > 0) {
    $db = getDB();
    try {
        $stmt = $db->prepare("SELECT id FROM favorite_teams WHERE user_id = :user_id AND team_id = :team_id");
        $stmt->execute([":user_id" => $user, ":team_id" => $team]);
        $favorite = $stmt->fetch();
        if ($favorite) {
            $stmt = $db->prepare("DELETE FROM favorite_teams WHERE id = :id");
            $stmt->execute([":id" => $favorite["id"]]);
            flash("Removed from favorites", "success");
        } else {
            $stmt = $db->prepare("INSERT INTO favorite_teams (user_id, team_id) VALUES (:user_id, :team_id)");
            $stmt->execute([":user_id" => $user, ":team_id" => $team]);
            flash("Added to favorites", "success");
        }
    } catch (PDOException $e) {
        error_log("Error updating favorites: " . var_export($e, true));
        flash("There was an error updating favorites", "danger");
    }
} else if ($player > 0) {
    $db = getDB();
    try {
        $stmt = $db->prepare("SELECT id FROM favorite_players WHERE user_id = :user_id AND player_id = :player_id");
        $stmt->execute([":user_id" => $user, ":player_id" => $player]);
        $favorite = $stmt->fetch();
        if ($favorite) {
            $stmt = $db->prepare("DELETE FROM favorite_players WHERE id = :id");
            $stmt->execute([":id" => $favorite["id"]]);
            flash("Removed from favorites", "success");
        } else {
            $stmt = $db->prepare("INSERT INTO favorite_players (user_id, player_id) VALUES (:user_id, :player_id)");
            $stmt->execute([":user_id" => $user, ":player_id" => $player]);
            flash("Added to favorites", "success");
        }
    } catch (PDOException $e) {
        error_log("Error updating favorites: " . var_export($e, true));
        flash("There was an error updating favorites", "danger");
    }
} else if ($favorite_id > 0) {
    if (has_role("Admin")) {
        $db = getDB();
        try {
            $stmt = $db->prepare("DELETE FROM favorite_teams WHERE id = :id");
            $stmt->execute([":id" => $favorite_id]);
            flash("Removed from favorites", "success");
        } catch (PDOException $e) {
            error_log("Error updating favorites: " . var_export($e, true));
            flash("There was an error updating favorites", "danger");
        }
    } else {
        flash("You do not have permission to do that", "warning");
    }
} else {
    flash("Invalid id", "danger");
}

unset($_GET["id"]);
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : get_url("home.php");
error_log("Location: $referer");
die(header("Location: $referer"));