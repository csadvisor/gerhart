#!/bin/bash

dif='diff --strip-trailing-cr'

diff --strip-trailing-cr test_out/petition_sendEmail.out <(php test/petition_sendEmail.php)
diff --strip-trailing-cr test_out/petition_sendNotification.out <(php test/petition_sendNotification.php)
