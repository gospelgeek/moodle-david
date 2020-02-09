#!/bin/bash

rm  build/plugin.zip 2> /dev/null
mkdir -p build/report_coursesstatus
cp -R src/* build/report_coursesstatus
cd build
zip -r plugin.zip report_coursesstatus/
rm -r report_coursesstatus 2> /dev/null
cp plugin.zip /var/www/html/moodle368/report
cd /var/www/html/moodle368/report
rm -R coursesstatus
unzip plugin.zip
rm plugin.zip
mv report_coursesstatus coursesstatus