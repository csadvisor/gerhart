<?php

include(dirname(__DIR__) . '/test_stubs/before.php');

/**
 * send_email
 */
$to = array('admin', 'advisee', 'advisor');
$message = array(
  'Dear student,',
  '',
  'We have seen some conerning',
  'Stuff',
  'Yea you should figure it out',
  'Super long line this should get word wrapped cause its really really really really really long',
);

$p = new Petition_model();
$p->sendNotification($to, 'subject-test', $message);

include(dirname(__DIR__) . '/test_stubs/after.php');

?>
