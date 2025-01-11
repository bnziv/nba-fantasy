<?php
//note we need to go up 1 more directory
require(__DIR__ . "/../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}
?>

<?php
if (isset($_POST["action"])) {
    $action = $_POST["action"];
    error_log(var_export($_POST, true));
    $rename = ["fname" => "first_name", "lname" => "last_name", "number" => "jersey_number", "team" => "team_id"];
    if ($action === "create") {
        foreach ($_POST as $k => $v) {
            if (!in_array($k, ["fname", "lname", "height", "weight", "number", "position", "team"])) {
                unset($_POST[$k]);
                continue;
            }
            //Formatting input
            if ($k === "height" && preg_match('/^([0-9]{1})-([0-9]{1,2})$/', $v, $matches)) {
                $v = $matches[1] . "'" . $matches[2] . '"';
            } else if ($k === "weight") {
                $v .= " lbs";
            } else if ($k === "position") {
                $v = strtoupper($v);
            }
            $new_key = $rename[$k] ?? $k;
            $players[$new_key] = $v;
        }
    } else {
        $action = get_team_api_id($action);
        if (empty($action)) {
            flash("Cannot fetch players from a user-created team", "warning");
            die(header("Location: $BASE_PATH" . "/admin/create_player.php"));
        } else {
            try {
                $players = fetch_players($action);
            } catch (Exception $e) {
                error_log("Error fetching team " . var_export($e, true));
                flash("Error fetching team", "danger");
            }
        }
    }
    if (!empty($players)) {
        try {
            $opts = ["update_duplicate" => true];
            $result = insert("players", $players, $opts);
            if (!$result) {
                flash("Unhandled error", "warning");
            } else {
                if ($action === "create") {
                    flash("Inserted player", "success");
                } else {
                    flash("Fetched players from team API ID $action", "success");
                }
            }
        } catch (InvalidArgumentException $e1) {
            error_log("Invalid arg" . var_export($e1, true));
            flash("Invalid data passed", "danger");
        } catch (PDOException $e2) {
            if ($e2->errorInfo[1] == 1062) {
                flash("Team exists or name/nickname/code in use", "warning");
            } else {
                error_log("Database error" . var_export($e2, true));
                flash("Database error", "danger");
            }
        } catch (Exception $e3) {
            error_log("Invalid data records" . var_export($e3, true));
            flash("Invalid data records", "danger");
        }
    } else {
        flash("Rate limit per minute exceed, please try again later", "warning");
    }
}
$teams = get_teams();
$team_names = array_map(function ($team) {
    if (isset($team["name"]) && isset($team["id"])) {
        return [$team["id"] => $team["name"]];
    }
    return [];
}, $teams);
$table = ["data" => $teams, "header_override" => ["Team", "Player Count"], "ignored_columns" => ["id"], "primary_key" => "id",
"post_self_form" => ["name" => "action", "classes" => "btn btn-primary", "label" => "Fetch"]];
?>

<div class="container-fluid">
    <h3>Create or Fetch Players</h3>
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link bg-success" href="#" onclick="switchTab('create')">Fetch</a>
        </li>
        <li class="nav-item">
            <a class="nav-link bg-success" href="#" onclick="switchTab('fetch')">Create</a>
        </li>
    </ul>
    <?php if ($teams): ?>
    <div id="fetch" class="tab-target">
        <?php render_table($table); ?>
    </div>
    <div id="create" style="display: none;" class="tab-target">
        <form method="POST" onsubmit="return validate(this)">
            <?php render_input(["type" => "text", "name" => "fname", "placeholder" => "First Name", "label" => "First Name", "rules" => ["required" => "required", "maxlength" => 20]]); ?>
            <?php render_input(["type" => "text", "name" => "lname", "placeholder" => "Last Name", "label" => "Last Name", "rules" => ["required" => "required", "maxlength" => 20]]); ?>
            <?php render_input(["type" => "text", "name" => "height", "placeholder" => "(ex. 6-3)", "label" => "Height", "rules" => ["required" => "required"]]); ?>
            <?php render_input(["type" => "number", "name" => "weight", "placeholder" => "in lbs", "label" => "Weight", "rules" => ["required" => "required"]]); ?>
            <?php render_input(["type" => "number", "name" => "number", "placeholder" => "Jersey Number", "label" => "Jersey Number", "rules" => ["required" => "required"]]); ?>
            <?php render_input(["type" => "text", "name" => "position", "placeholder" => "(ex. C, C-F, G)", "label" => "Position", "rules" => ["required" => "required"]]); ?>
            <?php render_input(["type" => "select", "name" => "team", "label" => "Team", "options" => $team_names, "rules" => ["required" => "required"]]); ?>

            <?php render_input(["type" => "hidden", "name" => "action", "value" => "create"]); ?>
            <?php render_button(["type" => "submit", "text" => "Create"]); ?>
        </form>
    </div>
    <?php else: ?>
    <div id="fetch" class="tab-target">
        <p>No teams found</p>
    </div>
    <div id="create" style="display: none;" class="tab-target">
        <p>No teams found</p>
    </div>
    <?php endif; ?>
</div>
<script>
    function switchTab(tab) {
        let target = document.getElementById(tab);
        if (target) {
            let eles = document.getElementsByClassName("tab-target");
            for (let ele of eles) {
                ele.style.display = (ele.id === tab) ? "none" : "block";
            }
        }
    }
    function validate(form) {
        document.getElementById("flash").innerHTML = "";
        let namePattern = /^[a-zA-Z0-9_-]{3,30}$/;
        let heightPattern = /^[0-9]{1}-[0-9]{1,2}$/;
        let positionPattern = /^[CFG](-[CFG])?$/;
        let isValid = true;
        if (!form.first_name.value) {
            flash("First name is required", "danger");
            isValid = false;
        }
        if (!form.last_name.value) {
            flash("Last name is required", "danger");
            isValid = false;
        }
        if (!form.height.value) {
            flash("Height is required", "danger");
            isValid = false;
        }
        if (!heightPattern.test(form.height.value)) {
            flash("Height must be in the correct format (ex. 6-3)", "danger");
            isValid = false;
        }
        if (!form.weight.value) {
            flash("Weight is required", "danger");
            isValid = false;
        }
        if (!form.number.value) {
            flash("Jersey number is required", "danger");
            isValid = false;
        }
        if (!form.position.value) {
            flash("Position is required", "danger");
            isValid = false;
        }
        if (!positionPattern.test(form.position.value)) {
            flash("Position must be in the correct format (ex. C, C-F, G)", "danger");
            isValid = false;
        }
        return isValid;
    }
</script>

<?php
require_once(__DIR__ . "/../../partials/flash.php");
?>