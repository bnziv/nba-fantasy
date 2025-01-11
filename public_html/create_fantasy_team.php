<?php 
require(__DIR__ . "/../partials/nav.php"); 
?>

<?php
if (!is_logged_in()) {
    flash("You are not logged in", "warning");
    die(header("Location: $BASE_PATH/login.php"));
}
$user_id = get_user_id();
try {
    $db = getDB();
    $teams = $db->query("SELECT id, name from teams WHERE api_id IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    flash($e->getMessage(), "danger");
    die(header("Location: $BASE_PATH/home.php"));
}

$teams = array_map(function ($team) {
    return [$team["id"] => $team["name"]];
}, $teams);
$teams = array_merge([["" => "Select Team"]], $teams);

if (isset($_POST["g1"])) {
    $g1 = se($_POST, "g1", "", false);
    $g2 = se($_POST, "g2", "", false);
    $f1 = se($_POST, "f1", "", false);
    $f2 = se($_POST, "f2", "", false);
    $c = se($_POST, "c", "", false);
    $name = se($_POST, "name", "", false);

    if ($g1 && $g2 && $f1 && $f2 && $c && $name) {
        $db = getDB();
        try{
            $stmt = $db->prepare("INSERT INTO fantasy_teams (name, guard_1_id, guard_2_id, forward_1_id, forward_2_id, center_id, created_by) 
            VALUES (:name, :g1, :g2, :f1, :f2, :c, :user_id)");
            $stmt->execute([
                ":name" => $name,
                ":g1" => $g1,
                ":g2" => $g2,
                ":f1" => $f1,
                ":f2" => $f2,
                ":c" => $c,
                ":user_id" => $user_id
            ]);
            flash("Successfully created fantasy team!", "success");
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                flash("A fantasy team with this name already exists, please try another", "warning");
            } else {
                flash("There was an error creating your fantasy team", "danger");
            }
        }
    } else {
        flash("Please select a fantasy team name and player for each position", "danger");
    }
}

?>
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white text-center">
            <h3>Create Fantasy Team</h3>
        </div>
        <div class="card-body">
            <form method="POST" onsubmit="return validate(this)">
                <div class="mb-3 col-md-6 offset-md-3">
                    <input type="text" name="name" id="name" class="form-control" placeholder="Fantasy Team Name" required />
                </div>

                <div class="mb-4">
                    <?php render_input(["type" => "select", "label" => "Filter Players by Team", "id" => "team_filter", "options" => $teams, "value" => ""]); ?>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="guard_1" class="form-label">Guard 1</label>
                        <select name="g1" id="guard_1" class="form-control" disabled>
                            <option value="">Select Player</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="guard_2" class="form-label">Guard 2</label>
                        <select name="g2" id="guard_2" class="form-control" disabled>
                            <option value="">Select Player</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="forward_1" class="form-label">Forward 1</label>
                        <select name="f1" id="forward_1" class="form-control" disabled>
                            <option value="">Select Player</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="forward_2" class="form-label">Forward 2</label>
                        <select name="f2" id="forward_2" class="form-control" disabled>
                            <option value="">Select Player</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-4">
                        <label for="center" class="form-label">Center</label>
                        <select name="c" id="center" class="form-control" disabled>
                            <option value="">Select Player</option>
                        </select>
                    </div>
                </div>

                <div class="text-center">
                    <?php render_button(["type" => "submit", "text" => "Create Team", "class" => "btn btn-primary px-4"]); ?>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    let positions = ['guard_1', 'guard_2', 'forward_1', 'forward_2', 'center'];
    let selectedPlayers = {
        guard_1: { name: null, id: null },
        guard_2: { name: null, id: null },
        forward_1: { name: null, id: null },
        forward_2: { name: null, id: null },
        center: { name: null, id: null }
    };

    function storeCurrentSelections() {
        positions.forEach(position => {
            const dropdown = document.getElementById(position);
            selectedPlayers[position] = { name: dropdown.options[dropdown.selectedIndex].text, id: dropdown.value };
        });
    }

    document.getElementById('team_filter').addEventListener('change', function() {
        const team = this.value;
        if (team) {
            storeCurrentSelections();
            fetchPlayers(team);
        } else {
            resetDropdowns();
        }
    });

    function fetchPlayers(team) {
        fetch(`filter_players.php?team=${team}`)
            .then(response => response.json())
            .then(data => {
                updatePositionDropdowns(data);
            })
            .catch(error => {
                console.error('Error fetching players:', error);
            });
    }

    function updatePositionDropdowns(players) {
        positions.forEach(position => {
            const positionLetter = position[0].toUpperCase();
            const dropdown = document.getElementById(position);
            const currentSelection = selectedPlayers[position].name;
            const currentSelectionID = selectedPlayers[position].id;

            dropdown.innerHTML = '';
            
            players.forEach(player => {
                if (player.position && !player.position.includes(positionLetter)) {
                    return;
                }
                const option = document.createElement('option');
                option.value = player.id;
                option.textContent = player.name;
                dropdown.appendChild(option);
            });

            if (currentSelection) {
                const option = document.createElement('option');
                option.value = currentSelectionID;
                option.textContent = currentSelection;
                dropdown.insertBefore(option, dropdown.firstChild);
                dropdown.value = currentSelectionID;
            }

            dropdown.disabled = false;
        });
    }

    function resetDropdowns() {
        positions.forEach(position => {
            const dropdown = document.getElementById(position);
            dropdown.innerHTML = '<option value="">Select Player</option>';
            dropdown.disabled = true;
        });
    }

    function validate(form) {
        document.getElementById("flash").innerHTML = "";
        const teamName = form.name.value;
        if (!teamName) {
            flash("Please enter a team name", "danger");
            return false;
        }

        const positions = ['g1', 'g2', 'f1', 'f2', 'c'];
        const submittedPlayers = positions.map(positions => form[positions].value);

        if (submittedPlayers.includes("")) {
            flash("Please select a player for each position", "danger");
            return false;
        }
        const uniquePlayers = new Set(submittedPlayers);
        if (uniquePlayers.size < submittedPlayers.length) {
            flash("Please select unique players for each position", "danger");
            return false;
        }
        return true;
    }
</script>

<?php
require_once(__DIR__ . "/../partials/flash.php");
?>