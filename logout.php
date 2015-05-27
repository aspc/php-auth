<?php

session_name("ASPC_PHP_SESSION");
session_set_cookie_params(0, "/", ".pomona.edu");
session_start();
session_destroy();
header("Location: https://aspc.pomona.edu/accounts/logout/");
die();

?>