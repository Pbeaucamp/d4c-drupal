#!/bin/bash

fileType=$1
output=$2
input=$3
shapeName=$4
projection=$5

ogr2ogr_command="ogr2ogr -t_srs $projection -f '$fileType' $output $input -nln $shapeName"
output=$(eval "$ogr2ogr_command")
echo "$output"
