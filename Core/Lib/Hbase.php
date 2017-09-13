<?php

namespace Swoole\Core\Lib;

class Hbase{
    public $hbase_host;
    public $hbase_port;
    public $socket;
    public $transport;
    public $protocol;
    public $hbase;

    public function __construct($host,$port){

        $GLOBALS['THRIFT_ROOT'] = SWOOLE_ROOT_DIR . "Core/Lib/Hbase/";
        include_once $GLOBALS['THRIFT_ROOT'] . 'packages/Hbase/Hbase.php';
        include_once $GLOBALS['THRIFT_ROOT'] . 'transport/TSocket.php';
        include_once $GLOBALS['THRIFT_ROOT'] . 'protocol/TBinaryProtocol.php';

        $this->hbase_host = $host;
        $this->hbase_port = $port;
        $this->socket = new \TSocket($this->hbase_host, $this->hbase_port);
        $this->socket->setSendTimeout(30000);
        $this->socket->setRecvTimeout(30000);
        $this->transport = new \TBufferedTransport($this->socket);
        $this->protocol = new \TBinaryProtocol($this->transport);
        $this->hbase = new \HbaseClient($this->protocol);
    }


    public function get_table_names(){
        try{
            $this->transport->open();
            $table_names_array = $this->hbase->getTableNames();
            $this->transport->close();
            return $table_names_array;
        }catch (\Exception $e){
            echo 'Caught exception: '.  $e->getMessage(). "\n";
        }
    }

    public function get_table_descriptors($table_name){
        try{
            $this->transport->open();
            $descriptors = $this->hbase->getColumnDescriptors($table_name);
            $this->transport->close();
            return $descriptors;
        }catch (\Exception $e){
            echo 'Caught exception: '.  $e->getMessage(). "\n";
        }
    }

    public function get_table_regions($table_name){
        try{
            $this->transport->open();
            $regions = $this->hbase->getTableRegions($table_name);
            $this->transport->close();
            return $regions;
        }catch (\Exception $e){
            echo 'Caught exception: '.  $e->getMessage(). "\n";
        }
    }

    public function get_table_columns($table_name){
        try{
            $this->transport->open();
            $descriptors = $this->hbase->getColumnDescriptors($table_name);
            $columns="";
            foreach($descriptors as $key=>$value)
            {
                $columns.=str_replace(":","",$key).",";
            }
            $columns=rtrim($columns,",");
            return $columns;
        }catch (\Exception $e){
            echo 'Caught exception: '.  $e->getMessage(). "\n";
        }
    }

    public function get_table_records($table_name,$count){
        try{
            $this->transport->open();
            $scan = new \TScan();
            $scan->caching=200;
            $scanner = $this->hbase->scannerOpenWithScan($table_name,$scan);
            $get_arr = $this->hbase->scannerGetList($scanner,$count);
            if($get_arr==null){
                return "null";
            }else{
                return $get_arr;
            }
            $this->transport->close();
        }catch(\Exception $e){
            echo 'Caught exception: '.  $e->getMessage(). "\n";
        }
    }

    public function search_table($table_name,$startrow='',$stoprow='',$timestamp='',$column='',$count=0){
        try{
            $this->transport->open();
            $result="";

            if(!empty($startrow) && empty($stoprow) && empty($timestamp) && empty($column)){

                $arr=$this->hbase->getRow($table_name,$startrow);
                $result = $arr[0];

            }elseif(!empty($startrow) && !empty($stoprow) && empty($timestamp) && empty($column)){

                $allcolumns=$this->get_table_columns($table_name);
                $columns=explode(",",$allcolumns);
                $record=$this->hbase->scannerOpenWithStop($table_name,$startrow,$stoprow,$columns);
                $result=$this->hbase->scannerGetList($record,$count);

            }elseif(!empty($startrow) && empty($stoprow) && empty($column) && !empty($timestamp)){

                $result=$this->hbase->getRowTs($table_name,$startrow,$timestamp);

            }elseif(!empty($startrow) && empty($stoprow) && empty($timestamp) && !empty($column)){

                //$column  array
                $arr=$this->hbase->getRowWithColumns($table_name,$startrow,$column);
                $result = $arr[0];

            }elseif(!empty($startrow) && empty($stoprow) && !empty($timestamp) && !empty($column)){

                $columns=explode(",",$column);
                $result=$this->hbase->getRowWithColumnsTs($table_name,$startrow,$columns,$timestamp);

            }elseif(!empty($startrow) && !empty($stoprow) && empty($timestamp) && !empty($column)){

                $columns=explode(",",$column);
                $record=$this->hbase->scannerOpenWithStop($table_name,$startrow,$stoprow,$columns);
                $result=$this->hbase->scannerGetList($record,$count);

            }elseif(!empty($startrow) && !empty($stoprow) && !empty($timestamp) && !empty($column)){

                $columns=explode(",",$column);
                $record=$this->hbase->scannerOpenWithStopTs($table_name,$startrow,$stoprow,$columns,$timestamp);
                $result=$this->hbase->scannerGetList($record,$count);

            }

            $this->transport->close();
            return $result;
        }catch(\Exception $e){
            echo 'Caught exception: '.  $e->getMessage(). "\n";
        }
    }


    public function enable_table($table_name){
        try{
            $this->transport->open();
            $this->hbase->enableTable($table_name);
            $this->transport->close();
        }catch (\Exception $e){
            echo 'Caught exception: '.  $e->getMessage(). "\n";
        }
    }

    public function disable_table($table_name){
        try{
            $this->transport->open();
            $this->hbase->disableTable($table_name);
            $this->transport->close();
        }catch (\Exception $e){
            echo 'Caught exception: '.  $e->getMessage(). "\n";
        }
    }

    public function is_table_enabled($table_name){
        try{
            $this->transport->open();
            $bool = $this->hbase->isTableEnabled($table_name);
            $this->transport->close();
            return $bool;
        }catch (\Exception $e){
            echo 'Caught exception: '.  $e->getMessage(). "\n";
        }
    }

    public function create_table($table_name, $column_families){
        try{
            $this->transport->open();
            $bool = $this->hbase->createTable($table_name, $column_families);
            $this->transport->close();
            return "success";
        }catch (\Exception $e){
            echo 'Caught exception: '.  $e->getMessage(). "\n";
        }
    }

    public function mutate_rowts($table_name, $row, $mutations, $timestamp){
        try{
            $this->transport->open();
            $bool = $this->hbase->mutateRowTs($table_name,$row,$mutations,$timestamp);
            $this->transport->close();
            return "success";
        }catch (\Exception $e){
            return 'Caught exception: '.  $e->getMessage(). "\n";
        }
    }



    public function delete_table($table_name){
        try{
            $this->transport->open();
            $bool = $this->hbase->deleteTable($table_name);
            $this->transport->close();
            return $bool;
        }catch (\Exception $e){
            echo 'Caught exception: '.  $e->getMessage(). "\n";
        }
    }

    public function truncate_table($table_name){
        try{
            $this->transport->open();
            $descriptors=$this->get_table_descriptors($table_name);
            if($this->is_table_enabled($table_name)){
                $this->disable_table($table_name);
                $this->delete_table($table_name);
            }else{
                $this->delete_table($table_name);
            }
            $result=$this->create_table($table_name,$descriptors);
            return $result;
        }catch (\Exception $e){
            echo 'Caught exception: '.  $e->getMessage(). "\n";
        }
    }
}