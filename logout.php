<?php

# This is a ONE-WAY logout script (hitting this solo will not fully log out the user of all session types)
# Logout requests should never hit this script directly; the logout flow should always be
#
# https://aspc.pomona.edu/accounts/logout
# ↓
# https://aspc.pomona.edu/logout.php?redirect=https%3A%2F%2Fcas1.campus.pomona.edu%2Fcas%2Flogout
# ↓
# https://cas1.campus.pomona.edu/cas/logout
#
# For BOTH Django apps and PHP apps (always start on the Django side first)

session_name("ASPC_PHP_SESSION");
session_set_cookie_params(0, "/", ".pomona.edu");
session_start();
session_destroy();

# Redirect to the CAS logout page, or the mainsite homepage
if (isset($_GET["redirect"]) && !empty($_GET["redirect"])) {
	header("Location: " . $_GET["redirect"]);
}
else {
	header("Location: https://aspc.pomona.edu/");
}

die();

?>