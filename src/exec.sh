#!/bin/bash
#set -xv
BASEDIR=`dirname $0`
BASEDIR=`(cd "$BASEDIR"; pwd)`

if [ $# -lt "4" ]; then
    echo "argv count is smaller than 4"
    exit
fi
PHPBIN="$1"
PHPINI="$2"
SCRIPT="$3"

FREQ="$4"

function exec() {
#    set -xv
    SHELLES=`"$PHPBIN" -c "$PHPINI" "$SCRIPT" Crontab\\\\Exec "$FREQ"`

    SHCOUNT=`echo "$SHELLES" | wc -l | awk '{print $1;}'`
    for((j=1;j<=$SHCOUNT;j++))
    do
        sh=`echo "$SHELLES" | head -n $j | tail -n 1`
        "$PHPBIN" -c "$PHPINI" "$SCRIPT" $sh &
    done
}

exec
