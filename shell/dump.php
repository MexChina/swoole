<?php
/**
 * 数据库备份脚本
 */
 //配置信息
 $cfg_dbhost = '192.168.1.201';
 $cfg_dbname = 'api';
 $cfg_dbuser = 'devuser';
 $cfg_dbpwd = 'devuser';
 $cfg_db_language = 'utf8';
 $to_file_name = $cfg_dbname.".log";

 //链接数据库
$link = new mysqli($cfg_dbhost,$cfg_dbuser,$cfg_dbpwd,$cfg_dbname); 
$link->set_charset($cfg_db_language); 

 //数据库中有哪些表
 $tables = $link->query('SHOW TABLES');//执行查询语句
 //将这些表记录到一个数组
 
 echo "star running...\n";
 $info = "-- ---------------------------------------------------\n";
 $info .= "-- ".date("Y-m-d H:i:s",time())."\n";
 error_log($info,3,$to_file_name);
 while($row = $tables->fetch_assoc()){
  	$table = $row["Tables_in_{$cfg_dbname}"];
  	echo "show $table ";
  	$table_desc = $link->query("show create table $table");
  	$field = $table_desc->fetch_assoc();
  	if(empty($field)) continue;
	$info = "-- ---------------------------------------------------\n";
	$info .= "-- Table structure for `".$table."`\n";
	$info .= "-- ---------------------------------------------------\n";
	$info .= "DROP TABLE IF EXISTS `".$table."`;\n";
	$sqlStr = $info.$field['Create Table'].";\n";
 	error_log($sqlStr,3,$to_file_name);

	echo "...ok\nselect $table ";
  	
  	$result = $link->query("select * from $table",MYSQLI_USE_RESULT);
  	if(empty($result)) continue;
  	$info = "-- ---------------------------------------------------\n";
	$info .= "-- Records for `".$table."`\n";
	$info .= "-- ---------------------------------------------------\n";
	error_log($info,3,$to_file_name);
	echo "... ok\nsave $table ";
  	$i=0;
  	$info = "INSERT INTO `$table` VALUES ";
  	while($res = $result->fetch_array(MYSQLI_ASSOC)){
  		
  		$value_str ="(";
  		foreach($res as $value){
			$value_str .= "'".addslashes($value)."',";
		}
		$value_str = rtrim($value_str,',') . "),";
		
		if($i == 100){
			$info = rtrim($info,',').";\n";
			echo ".";
			error_log($info,3,$to_file_name);
			$info = "INSERT INTO `$table` VALUES ";
			$i=1;
		}else{
			$info .= $value_str;
			$i++;
		}
  	}
  	$result->free();
  	echo "ok\n";
 }

 $link->close();
 echo "complete !\n";