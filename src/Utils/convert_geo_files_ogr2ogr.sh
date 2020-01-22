#!/bin/bash

fileType=$1
output=$2
input=$3

ogr2ogr_command="ogr2ogr -f '$fileType' $output $input"
output=$(eval "$ogr2ogr_command")
echo "$output"
