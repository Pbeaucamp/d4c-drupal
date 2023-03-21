#!/bin/bash


fileType=$1
output=$2
input=$3
projection=$4
shapeName=$5

if [ -z "$projection" ]
then
    projection="EPSG:4326"
else
    projection="$projection"
fi

if [ -z "$shapeName" ]
then
    echo "Shape name is empty"
    ogr2ogr_command="ogr2ogr -t_srs $projection -f '$fileType' $output $input"
    output=$(eval "$ogr2ogr_command")
    echo "$output"
else
    echo "Shape name is defined"
    ogr2ogr_command="ogr2ogr -t_srs $projection -f '$fileType' $output $input -nln $shapeName"
    output=$(eval "$ogr2ogr_command")
    echo "$output"
fi

