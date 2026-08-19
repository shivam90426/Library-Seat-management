<?php
require_once __DIR__ . "/security.php";
library_system_bootstrap();
require_once __DIR__ . "/../config/db.php";

require_login($required_role ?? null);
?>
