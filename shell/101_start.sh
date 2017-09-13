#!/bin/sh
ps -ef | grep gearman | grep icdc | grep -v grep | awk '{print $2}' | xargs kill -9
su --session-command="cd /home/mex/www/icdc; php icdc.php -c config/development/gearman.php -d > /opt/log/hup_icdc.log 2>&1 &"
ps -ef | grep gearman | grep icdc | grep -v grep





#gearman
#===================================================================================
#boot  http://www.boost.org
#wget https://nchc.dl.sourceforge.net/project/boost/boost/1.63.0/boost_1_63_0.tar.gz
#tar zxvf boost_1_63_0.tar.gz
#cd boost_1_63_0
#./bootstrap.sh --prefix=/usr/local/boost
#./bjam install
#ln -s /usr/local/boost/include/boost/ /usr/local/include/boost  
#ln -s /usr/local/boost/lib/libboost_program_options.so  /usr/lib/libboost_program_options.so
#===============================================================================================
#e2fsprogs
#wget https://nchc.dl.sourceforge.net/project/e2fsprogs/e2fsprogs/v1.43.4/e2fsprogs-1.43.4.tar.gz
#tar zxvf e2fsprogs-1.43.4.tar.gz
#cd e2fsprogs-1.43.4
#./configure --prefix=/usr/local/e2fsprogs --enable-elf-shlibs
#make
#make install
#cp -r lib/uuid/ /usr/include/
#cp -rf lib/libuuid.so* /usr/lib
#
#yum install gperf
#
#./configure --prefix=/usr/local/gearmand --with-sqlite3=/usr/local/sqlite3 --with-boost=/usr/local/boost --with-boost-libdir=/usr/local/boost/lib --with-memcached=[PATH]
#
#cd pecl-gearman
#phpize
#./configure --with-gearman=/usr/local/gearmand
#make
#make install
#
#
#vi /etc/ld.so.conf.d/gearman.conf
#/usr/local/boost/lib/  
#/usr/local/gearman/lib/
#ldconfig 
#
#
#
#/usr/local/gearmand/sbin/gearmand -u nobody -d -R -l /opt/log/gearmand.log -j 1 -t 4 -w 2 -p 4730 --verbose NOTICE --queue-type=MySQL --mysql-host=localhost --mysql-user=root --mysql-password=dongqings --mysql-db=gearman --mysql-table=dev_101
