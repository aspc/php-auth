<?php

session_start();
session_destroy();
header("Location: https://aspc.pomona.edu/accounts/logout/");
die();

?>