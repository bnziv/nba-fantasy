<?php
require(__DIR__."/../../partials/nav.php");
?>
<h1>Home</h1>
<?php
error_log("SESSION DATA: " . var_export($_SESSION, true));
if (is_logged_in()) {
  echo "Welcome, " . get_user_email();
} else {
  echo "Not logged in";
}
?>