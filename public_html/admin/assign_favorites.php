<?php
require(__DIR__ . "/../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}

if (isset($_POST["users"]) && isset($_POST["teams"])) {
    $user_ids = $_POST["users"];
    $team_ids = $_POST["teams"];

    if (empty($user_ids) || empty($team_ids)) {
        flash("Both users and teams need to be selected", "warning");
    } else {
        $db = getDB();
        foreach ($user_ids as $uid) {
            foreach ($team_ids as $tid) {
                try {
                    $stmt = $db->prepare("SELECT id FROM favorite_teams WHERE user_id = :uid AND team_id = :tid");
                    $stmt->execute([":uid" => $uid, ":tid" => $tid]);
                    $favorite = $stmt->fetch();
                    if ($favorite) {
                        $stmt = $db->prepare("DELETE FROM favorite_teams WHERE id = :id");
                        $stmt->execute([":id" => $favorite["id"]]);
                    } else {
                        $stmt = $db->prepare("INSERT INTO favorite_teams (user_id, team_id) VALUES (:uid, :tid)");
                        $stmt->execute([":uid" => $uid, ":tid" => $tid]);
                    }
                } catch (PDOException $e) {
                    error_log("Error updating favorites: " . var_export($e, true));
                    flash("There was an error updating favorites", "danger");
                }
            }
        }
        flash("Updated favorites", "success");
    }
}

$users = [];
$username = "";
if (isset($_POST["username"])) {
    $username = se($_POST, "username", "", false);
    if (!empty($username)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, username FROM Users WHERE username LIKE :username LIMIT 25");
        try {
            $stmt->execute([":username" => "%$username%"]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($results) {
                $users = $results;
            }
        } catch (PDOException $e) {
            flash(var_export($e->errorInfo, true), "danger");
        }
    } else {
        flash("Username must not be empty", "warning");
    }
}

$teams = [];
$team_name = "";
if (isset($_POST["team_name"])) {
    $team_name = se($_POST, "team_name", "", false);
    if (!empty($team_name)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name FROM teams WHERE name LIKE :team_name LIMIT 25");
        try {
            $stmt->execute([":team_name" => "%$team_name%"]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($results) {
                $teams = $results;
            }
        } catch (PDOException $e) {
            flash(var_export($e->errorInfo, true), "danger");
        }
    } else {
        flash("Team name must not be empty", "warning");
    }
}
?>
<div class="container-fluid">
    <h1>Assign Favorite Teams</h1>
    
    <form method="POST" onsubmit="return validate(this)">
        <div class="row">
            <div class="col">
                <?php render_input(["type" => "search", "name" => "username", "placeholder" => "Search for username", "value" => $username]); ?>
            </div>
            <div class="col">
                <?php render_input(["type" => "search", "name" => "team_name", "placeholder" => "Search for team", "value" => $team_name]); ?>
            </div>
            <div class="col">
                <?php render_button(["text" => "Search", "type" => "submit"]); ?>
            </div>
        </div>
    </form>

    <form method="POST">
        <?php if (isset($username) && !empty($username)) : ?>
            <input type="hidden" name="username" value="<?php se($username, false); ?>" />
        <?php endif; ?>
        
        <table class="table">
            <thead>
                <th>Users</th>
                <th>Teams to Assign</th>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <table class="table">
                            <?php foreach ($users as $user) : ?>
                                <tr>
                                    <td>
                                    <?php render_input([
                                        "type" => "checkbox", 
                                        "id" => "user_" . se($user, 'id', "", false), 
                                        "name" => "users[]", 
                                        "label" => se($user, "username", "", false), 
                                        "value" => se($user, 'id', "", false)
                                    ]); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </td>
                    <td>
                        <?php foreach ($teams as $team) : ?>
                            <div>
                            <?php render_input([
                                "type" => "checkbox", 
                                "id" => "team_" . se($team, 'id', "", false), 
                                "name" => "teams[]",
                                "label" => se($team, "name", "", false), 
                                "value" => se($team, 'id', "", false)
                            ]); ?>
                            </div>
                        <?php endforeach; ?>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php render_button(["text" => "Toggle Favorites", "type" => "submit", "color" => "secondary"]); ?>
    </form>
</div>
<script>
    function validate(form) {
        let isValid = true;
        if (empty(form.username.value)) {
            isValid = false;
            flash("Username must not be empty", "danger");
        }
        if (empty(form.team_name.value)) {
            isValid = false;
            flash("Teams must not be empty", "danger");
        }
        return isValid;
    }
</script>
<?php
require_once(__DIR__ . "/../../partials/flash.php");
?>
