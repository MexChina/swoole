#!/bin/sh


WATCHDIR="/opt/wwwroot/ver_triger/"
VERPATH="/opt/wwwroot/check/"
NOHUP="/usr/bin/nohup"
PHP="/opt/app/php/bin/php"
USER="nobody"
BASH="/bin/bash"


restart_grab_proxy(){

    #KPID=`ps aux|grep nobody|grep php|grep gearman|grep '\-d'|grep Ss|awk '{print $2}'`
    KPID=`ps aux|grep php|grep icdc_basic|grep '\-d'|grep Ss|awk '{print $2}'`
 #   su --session-command="cd /opt/wwwroot/rd/icdc; ${NOHUP} ${PHP} icdc.php -c config/development/icdc_basic.php -d > /opt/log/hup_icdc.log 2>&1 &" --shell="${BASH}" ${USER}

    for vid in $KPID
    do
            kill -15 $vid
    done

}
restart_grab_proxy

