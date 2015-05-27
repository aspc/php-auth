<?php

session_name("ASPC_PHP_SESSION");
session_set_cookie_params(0, "/", ".pomona.edu");
session_start();

echo var_dump($_SESSION);

die();

?>