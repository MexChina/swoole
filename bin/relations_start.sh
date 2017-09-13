#!/bin/sh
php /opt/source/bi_new/bin/swoole.php start GetRelations dev debug >/opt/source/bi_new/App/GetRelations/log.log &
sleep 2
php /opt/source/bi_new/bin/swoole.php start GearmanRelationsApi dev debug >/opt/source/bi_new/App/GearmanRelationsApi/log.log &