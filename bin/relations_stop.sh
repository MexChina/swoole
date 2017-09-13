#!/bin/sh
php /opt/source/bi_new/bin/swoole.php kill GetRelations dev 
sleep 2
php /opt/source/bi_new/bin/swoole.php kill GearmanRelationsApi dev
