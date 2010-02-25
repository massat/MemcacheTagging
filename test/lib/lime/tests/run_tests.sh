#!/bin/sh

pear run-tests -r `dirname $0`
if [ -e "./run-tests.log" ]; then
  exit 1
fi
