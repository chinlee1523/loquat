#!/bin/bash
#set -xv
BASEDIR=`dirname $0`
BASEDIR=`(cd "$BASEDIR"; pwd)`

INI="$1"

if [ ! -n "$INI" ];then
    INI="$BASEDIR/install.ini"
fi
if [ ! -f "$INI" ];then
    echo "there no exist $INI"
    exit
fi

function ini(){
    ARGU="$1"
    RES=`sed '/^'"$ARGU"'=/!d;s/.*=//' "$INI"`
    echo $RES
}
PHPBIN=`ini PHPBIN`
PHPINI=`ini PHPINI`
SCRIPT=`ini SCRIPT`

function install(){
#    set -xv
    SHELL="$2"
    RUNTIMES="$1"
    GREP=`echo "$RUNTIMES $SHELL" | sed  's/\\\\/\\\\\\\\/g' | sed 's/\\*/\\\\\\*/g'`
    CRONTAB="$RUNTIMES $SHELL > /dev/null 2>&1 &"
    ORI_CRONTAB=`crontab -l`
    COUNT=`crontab -l | grep  "^$GREP" |wc -l`
    if [ $COUNT -lt 1 ]; then
        echo -e "${ORI_CRONTAB}\n${CRONTAB}" | crontab -
    fi
}
RUNTIMES="* * * * *"
PERMINUTE="cd $BASEDIR && ./exec.sh $PHPBIN $PHPINI $SCRIPT PerMinute"
install "$RUNTIMES" "$PERMINUTE"
PERSECOND="cd $BASEDIR && for i in {0..59}; do ./exec.sh $PHPBIN $PHPINI $SCRIPT PerSecond & sleep 1;done"
install "$RUNTIMES" "$PERSECOND"
RUNTIMES="1 * * * *"
PERHOUR="cd $BASEDIR && ./exec.sh $PHPBIN $PHPINI $SCRIPT PerHour"
install "$RUNTIMES" "$PERHOUR"
