<?php

include(dirname(__DIR__) . '/test_stubs/before.php');

/**
 * send_email
 */
$p = new Petition_model();
$p->sendEmail('to-test', 'subject-test', 'message-test');

include(dirname(__DIR__) . '/test_stubs/after.php');

?>
