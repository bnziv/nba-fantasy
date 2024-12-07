<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}
?>

<?php
if (isset($_POST["action"])) {
    $action = $_POST["action"];
    if ($action === "teams") {
        $teams = fetch_all_teams();
        try {
            $opts = ["update_duplicate" => true];
            $result = insert("teams", $teams, $opts);
            if (!$result) {
                flash("Unhandled error", "warning");
            } else {
                flash("Inserted all teams", "success");
            }
        } catch (InvalidArgumentException $e1) {
            error_log("Invalid arg" . var_export($e1, true));
            flash("Invalid data passed", "danger");
        } catch (PDOException $e2) {
            error_log("Database error" . var_export($e2, true));
            flash("Database error", "danger");
        } catch (Exception $e3) {
            error_log("Invalid data records" . var_export($e3, true));
            flash("Invalid data records", "danger");
        }
    } else if ($action === "standings") {
        $standings = fetch_standings();
        try {
            $opts = ["update_duplicate" => true];
            $result = insert("standings", $standings, $opts);
            if (!$result) {
                flash("Unhandled error", "warning");
            } else {
                flash("Inserted all standings", "success");
            }
        } catch (InvalidArgumentException $e1) {
            error_log("Invalid arg" . var_export($e1, true));
            flash("Invalid data passed", "danger");
        } catch (PDOException $e2) {
            error_log("Database error" . var_export($e2, true));
            flash("Database error", "danger");
        } catch (Exception $e3) {
            error_log("Invalid data records" . var_export($e3, true));
            flash("Invalid data records", "danger");
        }
    } else if ($action === "games") {
        $games = fetch_games();
        try {
            $opts = ["update_duplicate" => true];
            $result = insert("games", $games, $opts);
            if (!$result) {
                flash("Unhandled error", "warning");
            } else {
                flash("Inserted all games", "success");
            }
        } catch (InvalidArgumentException $e1) {
            error_log("Invalid arg" . var_export($e1, true));
            flash("Invalid data passed", "danger");
        } catch (PDOException $e2) {
            error_log("Database error" . var_export($e2, true));
            flash("Database error", "danger");
        } catch (Exception $e3) {
            error_log("Invalid data records" . var_export($e3, true));
            flash("Invalid data records", "danger");
        }
    }
}

?>
<div class="container-fluid">
    <h3>Fetch Data</h3>
    <div id="fetch" class="tab-target">
        <form method="POST">
            <?php render_input(["type" => "hidden", "name" => "action", "value" => "teams"]); ?>
            <?php render_button(["text" => "Fetch Teams", "type" => "submit",]); ?>
        </form>
        <form method="POST">
            <?php render_input(["type" => "hidden", "name" => "action", "value" => "standings"]); ?>
            <?php render_button(["text" => "Fetch Standings", "type" => "submit",]); ?>
        </form>
        <form method="POST">
            <?php render_input(["type" => "hidden", "name" => "action", "value" => "games"]); ?>
            <?php render_button(["text" => "Fetch Games", "type" => "submit",]); ?>
        </form>
    </div>
</div>

<?php
require_once(__DIR__ . "/../../../partials/flash.php");
?>