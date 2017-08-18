#!/bin/sh
memuse=$(free -m | awk '/Mem/ {print $4}')
if [ $memuse -le 1500 ]; then
    message="RAM in critical condition in this server"
    echo -e "$message" | sync; echo 3 > /proc/sys/vm/drop_caches
fi