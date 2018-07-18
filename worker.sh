#!/bin/sh
# filename: worker.sh

PROCESSORS=60;
x=0
# simple processor code for concurrent queue which is set to one according to our current requirements
while [ "$x" -lt "$PROCESSORS" ];
do
        PROCESS_COUNT=`pgrep -f process.php | wc -l`
        if [ $PROCESS_COUNT -ge $PROCESSORS ]; then
                exit 0
        fi
        x=`expr $x + 1`
        php -f /var/www/html/Queue/worker.php &
done
exit 0