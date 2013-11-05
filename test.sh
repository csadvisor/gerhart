#!/bin/bash

fcns=(
  'sendEmail'
  'sendNotification'
  'sendApprovedNotification'
  'sendRejectedNotification'
  'sendCreatedNotification'
  )

for fcn in "${fcns[@]}"
do
  echo "Testing $fcn"
  diff --strip-trailing-cr test_out/petition_$fcn.out <(php "test/petition_$fcn.php")
done
