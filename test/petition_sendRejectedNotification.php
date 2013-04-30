<?php

include(dirname(__DIR__) . '/test_stubs/before.php');

$p = new Petition_model();
$p->sendRejectedNotification();

include(dirname(__DIR__) . '/test_stubs/after.php');

?>
