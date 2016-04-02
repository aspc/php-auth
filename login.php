<?php

# This is a ONE-WAY login script (hitting this out-of-order will simply result in a redirect to the proper starting point)
# Login requests should never hit this script directly; the login flow should always be
#
# https://aspc.pomona.edu/accounts/login/?next=wherever
# ↓
# CAS handshake, ticket validation
# ↓
# https://aspc.pomona.edu/php-auth/login.php?redirect=wherever
#
# For BOTH Django apps and PHP apps (always start on the Django side first; it will take care of initiating the chain)

session_name("ASPC_PHP_SESSION");
session_set_cookie_params(0, "/", ".pomona.edu");
session_start();

$DJANGO_SESSION_COOKIE_NAME = "sessionid";

function mail_error($line_number, $message) {
	$message = (string)$message;

	if (isset($GLOBALS['django_session_id'])) {
		$message .= "\n\n" . "django_session_id: " . (string)$GLOBALS['django_session_id'];
	}

	mail("digitalmedia@aspc.pomona.edu", "[ASPC] PHP ERROR: login.php:" . (string)$line_number, (string)$message);
}

# If already logged in, redirect to the homepage
# This should never happen in the normal login flow; this will only be triggered if the user
# manually navigates to https://aspc.pomona.edu/php-auth/login.php himself after already logging in
if (isset($_SESSION["username"])) {
	header("refresh:5;url=https://aspc.pomona.edu/");
	echo "Whoops, " . $_SESSION["username"] . "! You are already logged in.";
	echo "Redirecting to <a href=\"https://aspc.pomona.edu/\">https://aspc.pomona.edu/</a>...";
	die();
}

# If there is a Django session cookie present, create the session using the Django session data
elseif (isset($_COOKIE[$DJANGO_SESSION_COOKIE_NAME])) {
	$GLOBALS['django_session_id'] = escapeshellarg($_COOKIE[$DJANGO_SESSION_COOKIE_NAME]);

	# Checks if a Django session already exists, and returns the associated user data for the session if it does
	# We run this as a Python script because we have to use Postgres and Python's pickle library
	# The session should exist in the database if the Django cookie has been set, so no error should happen here
	try {
		$command = "python " . getcwd() . "/django_session.py " . $GLOBALS['django_session_id'];
		$result = json_decode(shell_exec($command));
	}
	catch (Exception $e) {
		mail_error(__LINE__, $e->getMessage());
		die();
	}

	# Evaluates result
	if (is_object($result) && property_exists($result, "error")) {
		# Log the error and try logging in again through Django (might cause a redirect loop... lol)
		mail_error(__LINE__, (string)$result->stack_trace . "\n" . (string)$result->error);
		header("Location: https://aspc.pomona.edu/accounts/login/");
		die();
	}

	# Performs the PHP $_SESSION login sequence (see the LDAP version in the old login.php)
	elseif (is_array($result)) {
		$email_parts = explode("@", $result[3]); # Break the email into an array of form [username, suffix]

		# Only log in Pomona students on the PHP side
		# (5C students shouldn't have access to voting or course reviews, at least yet)
		if (strpos($email_parts[1], "pomona.edu") !== FALSE) {
			# The username for users as PHP knows them is still just the Pomona username without the email suffix
			try {
				$_SESSION["username"] = $email_parts[0];
				$_SESSION["first"] = $result[1];
				$_SESSION["last"] = $result[2];
				$_SESSION["is_faculty"] = $result[4];
			}
			catch (Exception $e) {
				mail_error(__LINE__, $e->getMessage());
				header("Location: https://aspc.pomona.edu/accounts/login/");
				die();
			}
		}

		# Redirect to desired location, or the mainsite homepage
		# Django will pass the redirect querystring parameter during the normal login flow
		if (isset($_GET["redirect"]) && !empty($_GET["redirect"])) {
			header("Location: " . $_GET["redirect"]);
			die();
		}
		else {
			header("Location: https://aspc.pomona.edu/");
			die();
		}
	}

	# Something unexpected happened... Don't know how to handle, so just try logging in again
	else {
		mail_error(__LINE__, var_dump($result));
		header("Location: https://aspc.pomona.edu/accounts/login/");
		die();
	}
}

# Otherwise, the user has not logged in at all yet, so redirect to Django to initiate the login flow there
# Django will redirect back here in turn to complete the PHP login, which will be handled in the main elseif block
else {
	header("Location: https://aspc.pomona.edu/accounts/login/");
	die();
}

?>