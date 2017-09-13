Mysql5.7学习笔记
背景：为什么要学习mysql5.7


新特性了解
1、性能提升
   在OLTP只读模式下，MYSQL5.7有近100万QPS，是mysql5.6的3倍
   在OLTP读/写模式下，MYSQL5.7压缩到了近60万TPS，比MYSQL5.6的2倍
   参考官网统计表：https://www.mysql.com/why-mysql/benchmarks/

2、安全性提升
	1、默认开启了安全套接层(Secue Sockets Layer,SSL),在mysql5.7启动的时候，使用OpenSSL可以自动生成SSL和RSA证书和秘钥文件。安全套接层(SSL)及其继任者传输层安全(Transport Layer Security,TLS)是为网络通信提供安全及数据完整性的一种安全协议。TLS与SSL在传输层对网络连接进行加密，用以保障在Internet上数据传输安全，利用数据加密技术(Encryption)技术，可确保数据在网络传输过程中不会被截取及窃听。
	2、不在明文显示用户密码
	3、sql_mode模式改变

3、InnoDB存储引擎的提升
	1、更改索引名字时不锁表
	2、在线DDL修改varchar字段属性时不锁表，
	3、InnoDB/MyiSAM存储引擎支持中文全文索引
	4、InnoDB Buffer Pool预热改进
	5、在线调整innodb_Buffer_Pool_Size不用重启mysql进程
	6、回收undo log回滚日志物理文件空间
	7、InnoDB提供通用表空间
	8、创建InnoDB独立表空间可以指定存放路径
	9、迁移单独一张InnoDB表到远程服务器
	10、死锁可以打印到错误日志里
	11、支持InnoDB 只读事务
	12、支持InnoDB表空间数据碎片整理

4、JSON格式支持
5、支持虚拟列
6、功能提升
	1、支持杀死慢sql语句
	2、支持一张表可以有多个触发器
	3、企业版支持线程池技术 (https://www.mysql.com/products/enterprise/scalability.html)
	4、支持审计日志功能
	5、支持explain update
	6、在命令终端，按Ctrl+C组合键不会退出客户端
	7、支持将错误日志打印到系统日志文件中

7、优化器改进
	1、针对子查询select采用半连接优化
	2、优化派生子查询
	3、优化排序limit
	4、优化IN条件表达式
	5、优化union all
	6、支持索引下推优化
	7、支持Multi Range Read索引优化
	8、半同步复制改进
	9、gtid复制改进
	10、支持丛库崩溃安全恢复
	11、slave丛库多线程复制
	12、slave支持多源复制
	13、设置同步复制过滤不用重启mysql服务进程
 

数据可视化监控平台：http://192.168.1.66:8081

1、监控mysql
	无需Agent即可实现远程监控云中的数据库。
	再web上直观的管理和监控数据库
	实时对mysql的监控进行监视和报警
	实时对mysql的复制进行监视和报警
	实时对mysql的资源进行监视和分析
	实时对mysql的缓存等性能进行监控
	实时对InnoDB IO的性能进行监控
	MySQL表空间增长趋势分析
	可视化MySQL慢查询在线分析
	MySQL慢查询自动推送
	MySQL AWR在线性能分析

2、监控服务器
3、扩展各种数据可视化报表



php提升脚本性能的小技巧

1、 如果能将类的方法定义成static，就尽量定义成static，它的速度会提升将近4倍。

2、$row[’id’] 的速度是$row[id]的7倍。

3、echo 比 print快，并且使用echo的多重参数(译注：指用逗号而不是句点)代替字符串连接，比如echo $str1,$str2。


4、在执行for循环之前确定最大循环数，不要每循环一次都计算最大值，最好运用foreach代替。

5、注销那些不用的变量尤其是大数组，以便释放内存。

6、尽量避免使用__get，__set，__autoload。

7、require_once()代价昂贵。

8、include文件时尽量使用绝对路径，因为它避免了PHP去include_path里查找文件的速度，解析操作系统路径所需的时间会更少。

9、如果你想知道脚本开始执行(译注：即服务器端收到客户端请求)的时刻，使用$_SERVER[‘REQUEST_TIME’]要好于 time()。

10、函数代替正则表达式完成相同功能。

11、str_replace函数比preg_replace函数快，但strtr函数的效率是str_replace函数的四倍。

12、如果一个字符串替换函数，可接受数组或字符作为参数，并且参数长度不太长，那么可以考虑额外写一段替换代码，使得每次传递参数是一个字符，而不是只写一行代码接受数组作为查询和替换的参数。

13、使用选择分支语句(译注：即switch case)好于使用多个if，else if语句。

14、用@屏蔽错误消息的做法非常低效，极其低效。

15、数据库连接当使用完毕时应关掉，不要用长连接。

16、错误消息代价昂贵。

17、在方法中递增局部变量，速度是最快的。几乎与在函数中调用局部变量的速度相当。

18、递增一个全局变量要比递增一个局部变量慢2倍。

19、递增一个对象属性(如：$this->prop++)要比递增一个局部变量慢3倍。

20、递增一个未预定义的局部变量要比递增一个预定义的局部变量慢9至10倍。

21、仅定义一个局部变量而没在函数中调用它，同样会减慢速度(其程度相当于递增一个局部变量)。PHP大概会检查看是否存在全局变量。

22、方法调用看来与类中定义的方法的数量无关，因为我(在测试方法之前和之后都)添加了10个方法，但性能上没有变化。

23、调用带有一个参数的空函数，其花费的时间相当于执行7至8次的局部变量递增操作。类似的方法调用所花费的时间接近于15次的局部变量递增操作。

24、除非脚本可以缓存，否则每次调用时都会重新编译一次。引入一套PHP缓存机制通常可以提升25%至100%的性能，以免除编译开销。

25、尽量做缓存，可使用memcached。memcached是一款高性能的内存对象缓存系统，可用来加速动态Web应用程序，减轻数据库负载。对运算码 (OP code)的缓存很有用，使得脚本不必为每个请求做重新编译。

26、当操作字符串并需要检验其长度是否满足某种要求时，你想当然地会使用strlen()函数。此函数执行起来相当快，因为它不做任何计算，只返回在zval 结构(C的内置数据结构，用于存储PHP变量)中存储的已知字符串长度。但是，由于strlen()是函数，多多少少会有些慢，因为函数调用会经过诸多步骤，如字母小写化(译注：指函数名小写化，PHP不区分函数名大小写)、哈希查找，会跟随被调用的函数一起执行。在某些情况下，你可以使用isset() 技巧加速执行你的代码。
(举例如下)
if (strlen($foo) < 5) { echo “Foo is too short”$$ }
(与下面的技巧做比较)
if (!isset($foo{5})) { echo “Foo is too short”$$ }
调用isset()恰巧比strlen()快，因为与后者不同的是，isset()作为一种语言结构，意味着它的执行不需要函数查找和字母小写化。也就是说，实际上在检验字符串长度的顶层代码中你没有花太多开销。

27、当执行变量$i的递增或递减时，$i++会比++$i慢一些。这种差异是PHP特有的，并不适用于其他语言，所以请不要修改你的C或 Java代码并指望它们能立即变快，没用的。++$i更快是因为它只需要3条指令(opcodes)，$i++则需要4条指令。后置递增实际上会产生一个临时变量，这个临时变量随后被递增。而前置递增直接在原值上递增。这是最优化处理的一种，正如Zend的PHP优化器所作的那样。牢记这个优化处理不失为一个好主意，因为并不是所有的指令优化器都会做同样的优化处理，并且存在大量没有装配指令优化器的互联网服务提供商(ISPs)和服务器。

28、并不是事必面向对象(OOP)，面向对象往往开销很大，每个方法和对象调用都会消耗很多内存。

29、并非要用类实现所有的数据结构，数组也很有用。

30、尽量采用大量的PHP内置函数。

31、如果在代码中存在大量耗时的函数，你可以考虑用C扩展的方式实现它们。

32、评估检验(profile)你的代码。检验器会告诉你，代码的哪些部分消耗了多少时间。Xdebug调试器包含了检验程序，评估检验总体上可以显示出代码的瓶颈。

33、在可以用file_get_contents替代file、fopen、feof、fgets等系列方法的情况下，尽量用 file_get_contents，因为他的效率高得多!但是要注意file_get_contents在打开一个URL文件时候的PHP版本问题;

34、尽量的少进行文件操作，虽然PHP的文件操作效率也不低的;

35、多维数组尽量不要循环嵌套赋值;

36、在可以用PHP内部字符串操作函数的情况下，不要用正则表达式;

37、foreach效率更高，尽量用foreach代替while和for循环;

38、用单引号替代双引号引用字符串;

39、用i+=1代替i=i+1。符合c/c++的习惯，效率还高;

40、对global变量，应该用完就unset()掉。


mysql json示例：

mysql> show create table resumes_algorithms\G
*************************** 1. row ***************************
       Table: resumes_algorithms
Create Table: CREATE TABLE `resumes_algorithms` (
  `id` bigint(20) NOT NULL DEFAULT '0' COMMENT '简历id',
  `data` json DEFAULT NULL COMMENT '算法数据',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='简历算法信息'
1 row in set (0.00 sec)


insert into resumes_algorithms(`id`,`data`) VALUES(
	'1','{"cv_source":"array1","cv_trade":"1","cv_title":"","cv_tag":"","cv_entity":"","cv_education":"","cv_feature":"","skill_tag":"","personal_tag":"","diff":"","cv_quality":"","cv_language":"","cv_degree":""}'
),(
	'2','{"cv_source":"array2","cv_trade":"2","cv_title":"","cv_tag":"","cv_entity":"","cv_education":"","cv_feature":"","skill_tag":"","personal_tag":"","diff":"","cv_quality":"","cv_language":"","cv_degree":""}'
),(
	'3','{"cv_source":"array3","cv_trade":"3","cv_title":"","cv_tag":"","cv_entity":"","cv_education":"","cv_feature":"","skill_tag":"","personal_tag":"","diff":"","cv_quality":"","cv_language":"","cv_degree":""}'
)



mysql> select * from resumes_algorithms\G
*************************** 1. row ***************************
        id: 1
      data: {"diff": "", "cv_tag": "", "cv_title": "", "cv_trade": "1", "cv_degree": "", "cv_entity": "", "cv_source": "array1", "skill_tag": "", "cv_feature": "", "cv_quality": "", "cv_language": "", "cv_education": "", "personal_tag": ""}
created_at: 2017-03-07 10:36:00
updated_at: 2017-03-07 10:36:00
*************************** 2. row ***************************
        id: 2
      data: {"diff": "", "cv_tag": "", "cv_title": "", "cv_trade": "2", "cv_degree": "", "cv_entity": "", "cv_source": "array2", "skill_tag": "", "cv_feature": "", "cv_quality": "", "cv_language": "", "cv_education": "", "personal_tag": ""}
created_at: 2017-03-07 10:36:00
updated_at: 2017-03-07 10:36:00
*************************** 3. row ***************************
        id: 3
      data: {"diff": "", "cv_tag": "", "cv_title": "", "cv_trade": "3", "cv_degree": "", "cv_entity": "", "cv_source": "array3", "skill_tag": "", "cv_feature": "", "cv_quality": "", "cv_language": "", "cv_education": "", "personal_tag": ""}
created_at: 2017-03-07 10:36:00
updated_at: 2017-03-07 10:36:00
3 rows in set (0.00 sec)


mysql> select id,json_extract(data,'$.cv_trade') as cv_trade from resumes_algorithms;
+----+----------+
| id | cv_trade |
+----+----------+
|  1 | "1"      |
|  2 | "2"      |
|  3 | "3"      |
+----+----------+
3 rows in set (0.00 sec)


mysql> mysql> select id,json_extract(data,'$.cv_source') as cv_source from resumes_algorithms where json_extract(data,'$.cv_trade')="1";
+----+-----------+
| id | cv_source |
+----+-----------+
|  1 | "array1"  |
+----+-----------+
1 row in set (0.00 sec)

mysql> select id,json_extract(data,'$.cv_source') as cv_source from resumes_algorithms where json_extract(data,'$.cv_source') like "%array%";
+----+-----------+
| id | cv_source |
+----+-----------+
|  1 | "array1"  |
|  2 | "array2"  |
|  3 | "array3"  |
+----+-----------+
3 rows in set (0.02 sec)

mysql> update resumes_algorithms set data=json_set(data,'$.cv_degree','[1,2]') where id=2;
Query OK, 1 row affected (0.02 sec)
Rows matched: 1  Changed: 1  Warnings: 0