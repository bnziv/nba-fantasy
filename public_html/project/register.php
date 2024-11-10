<?php
require(__DIR__ . "/../../partials/nav.php");
?>
<form onsubmit="return validate(this)" method="POST">
    <div>
        <label for="email">Email</label>
        <input id="email" type="email" name="email" required />
    </div>
    <div>
        <label for="pw">Password</label>
        <input type="password" id="pw" name="password" required minlength="8" />
    </div>
    <div>
        <label for="confirm">Confirm</label>
        <input type="password" name="confirm" required minlength="8" />
    </div>
    <input type="submit" value="Register" />
</form>
<script>
    function validate(form) {
        //TODO 1: implement JavaScript validation
        //ensure it returns false for an error and true for success

        return true;
    }
</script>
<?php
//TODO 2: add PHP code
if (isset($_POST["email"]) && isset($_POST["password"]) && isset($_POST["confirm"])) {
    $email = se($_POST, "email", "", false); //$_POST["email"];
    $password = se($_POST, "password", "", false); //$_POST["password"];
    $confirm = se($_POST, "confirm", "", false); //$_POST["confirm"];
    //TODO 3: validate/use
    $hasError = false;
    if (empty($email)) {
        echo "Email must not be empty";
        $hasError = true;
    }
    $email = sanitize_email($email);
    if (!is_valid_email($email)) {
        echo "Please enter a valid email";
        $hasError = true;
    }
    if (empty($password)) {
        echo "Password must not be empty";
        $hasError = true;
    }
    if (empty($confirm)) {
        echo "Confirm password must not be empty";
        $hasError = true;
    }
    if (strlen($password) < 8) {
        echo "Password must be at least 8 characters";
        $hasError = true;
    }
    if ($password !== $confirm) {
        echo "Passwords must match";
        $hasError = true;
    }
    if (!$hasError) {
        //echo "Welcome, $email";
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO Users (email, password) VALUES (:email, :password)");
        try {
            $stmt->execute([":email" => $email, ":password" => $hash]);
            echo "Successfully registered!";
        } catch (Exception $e) {
            echo "There was a prolem registering";
            echo "<pre>" . var_export($e, true) . "</pre>";
        }
    }
}
?>