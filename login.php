<?php

session_set_cookie_params(0, "/", ".pomona.edu");
session_start();
error_log("Started session.");

$DJANGO_SESSION_COOKIE_NAME = "sessionid";

# If already logged in, say so
if (isset($_SESSION["username"])) {
	echo "Welcome, " . $_SESSION["username"] . ". You are already logged in.";
	die();
}

# If there is a Django session cookie present, attempt to auth via Django
elseif (isset($_COOKIE[$DJANGO_SESSION_COOKIE_NAME])) {
	error_log("Django cookie set.");
	$django_session_id = $_COOKIE[$DJANGO_SESSION_COOKIE_NAME];

	# Checks if a Django session already exists, and returns the associated user data for the session if it does
	$command = "python /home/mattdahl/main/public/peninsula/django_session.py " . escapeshellarg($django_session_id);
	$result = json_decode(shell_exec($command));
	error_log("Result decoded.");

	# Evaluates result
	if ($result == "no_session") {
		error_log("no_session returned.");
		header("Location: https://aspc.pomona.edu/accounts/login/");
		die();
	}
	elseif ($result == "no_user") {
		error_log("no_user returned.");
		header("Location: https://aspc.pomona.edu/accounts/login/");
		die();
	}
	else {
		# Perform the normal login sequence (see the LDAP version in login.php)
		error_log($result["username"] . " logged in from Django.");
		$username_parts = explode("@", $result[0]); # Array of form [username, suffix]

		if (strpos($username_parts[1], "pomona.edu") == FALSE) {
			echo "Sorry, only Pomona students can access this app.";
			die();
		}
		else {
			# The username for users as PHP knows them is still just the Pomona username without the email suffix
			$_SESSION["username"] = $username_parts[0];
			$_SESSION["first"] = $result[1];
			$_SESSION["last"] = $result[2];

			# Redirect to desired location, or index.php
			if (isset($_GET["redirect"]) && !empty($_GET["redirect"])) {
				header("Location: " . $_GET["redirect"]);
			}
			else {
				header("Location: http://aspc.pomona.edu/");
			}
		}
	}
}

# Otherwise, redirect to Django auth to login there
else {
	header("Location: https://aspc.pomona.edu/accounts/login/");
	die();
}

?>