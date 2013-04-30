#!/bin/bash

fcns=(
  'sendEmail'
  'sendNotification'
  'sendRejectedNotification'
  )

for fcn in "${fcns[@]}"
do
  echo "Testing $fcn"
  diff --strip-trailing-cr test_out/petition_$fcn.out <(php "test/petition_$fcn.php")
done
