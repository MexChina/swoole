<?php

namespace Swoole\Core\Lib\Thrift;

require_once __DIR__ . "/Thrift/ClassLoader/ThriftClassLoader.php";

use Thrift\ClassLoader\ThriftClassLoader;
use ThriftClient\ThriftClient;
use ThriftClient\AddressManager;
use Services\Hbase\ColumnDescriptor;
use Services\Hbase\Mutation;
use Services\Hbase\BatchMutation;
use Services\Hbase\IOError;

$loader = new ThriftClassLoader();
$loader->registerNamespace('Thrift', __DIR__);
$loader->registerNamespace('Services', __DIR__);
$loader->registerDefinition('Services', __DIR__);
$loader->registerNamespace('ThriftClient', __DIR__);
$loader->registerDefinition('ThriftClient', __DIR__);
$loader->register();

/* Dependencies. In the proper order. */

//require_once LIB . '/Thrift/Transport/TTransport.php';
//require_once LIB . '/Thrift/Transport/TSocket.php';
//require_once LIB . '/Thrift/Protocol/TProtocol.php';
//require_once LIB . '/Thrift/Protocol/TBinaryProtocol.php';
//require_once LIB . '/Thrift/Protocol/TCompactProtocol.php';
//require_once LIB . '/Thrift/Transport/TBufferedTransport.php';
//require_once LIB . '/Thrift/Type/TMessageType.php';
//require_once LIB . '/Thrift/Factory/TStringFuncFactory.php';
//require_once LIB . '/Thrift/StringFunc/TStringFunc.php';
//require_once LIB . '/Thrift/StringFunc/Core.php';
//require_once LIB . '/Thrift/Type/TType.php';
//require_once LIB . '/Thrift/Exception/TException.php';
//require_once LIB . '/Thrift/Exception/TTransportException.php';
//require_once LIB . '/Thrift/Exception/TProtocolException.php';
//require_once LIB . '/Clients/ThriftClient.php';
//require_once LIB . '/Clients/AddressManager.php';
//require_once LIB . '/Services/Hbase/Types.php';
//require_once LIB . '/Services/Hbase/Hbase.php';


class HbaseConn {

    private static $RETRY_TIMES = 3;
    private $table;
    private $buffer;
    private $client;
    private $batch_mutations = array();

    function __construct($tablename, $config = array(), $buffer = 1) {
        $addresses = array(
            '221.228.230.200:9090',
//            '192.168.8.72:9090',
//            '192.168.8.73:9090',
//            '192.168.8.74:9090',
//            '192.168.8.75:9090',
        );
        if (!empty($config) && !empty($config['addresses'])) {
            $addresses = $config['addresses'];
        }
        ThriftClient::config(
                array(
                    'Hbase' => array(
                        'addresses' => $addresses,
                        'thrift_protocol' => 'TBinaryProtocol',
                        'thrift_transport' => 'TBufferedTransport',
                    ),
                )
        );
        $this->table = $tablename;
        $this->buffer = $buffer;
        try {
            $this->client = ThriftClient::instance('Hbase');
        } catch (Exception $e) {
            throw new Exception('Hbase 服务不可用', 1); //错误码为1时,会发送报警邮件
        }
    }

    function __destruct() {
        $this->clean_buffer();
    }

    function __call($name, $arguments) {
        $params = [];
        if (!in_array($name, array("scannerGetList", "scannerClose"))) {
            $params[] = $this->table;
        }
        foreach ($arguments as $value) {
            $params[] = $value;
        }
        try {
            $r = call_user_func_array(array($this->client, $name), $params);
        } catch (Error $exc) {
            $r = $exc->getMessage();
        }
        return $r;
    }

    function clean_buffer() {
        if ($this->client != NULL && $this->batch_mutations != NULL && count($this->batch_mutations) != 0) {
            $retry = 0;
            while ($retry < self::$RETRY_TIMES) {
                try {
                    $this->client->mutateRows($this->table, $this->batch_mutations, NULL);
                    $this->batch_mutations = array();
                    break;
                } catch (Exception $e) {
                    $retry++;
                    $this->client->reset();
                    echo "HbaseConn Operation [mutateRows] retry " . $retry . "times\n";
                    if ($retry == self::$RETRY_TIMES) {
                        throw $e;
                    }
                }
            }
        }
        $this->batch_mutations = array();
    }

    function isTableExist() {
        if ($this->client == NULL) {
            throw new Exception('hbase连接丢失');
        }
        $table = array();
        $retry = 0;
        while ($retry < self::$RETRY_TIMES) {
            try {
                $table = $this->client->getTableNames();
                break;
            } catch (Exception $e) {
                $retry++;
                $this->client->reset();
                echo "HbaseConn Operation [getTableNames] retry " . $retry . "times\n";
                if ($retry == self::$RETRY_TIMES) {
                    throw $e;
                }
            }
        }

        return in_array($this->table, $table);
    }

    function createTable() {
        if ($this->client == NULL) {
            throw new Exception('hbase连接丢失');
        }
        if ($this->isTableExist()) {
            return TRUE;
        }
        $columns = array(new ColumnDescriptor(array('name' => 'cf:', 'inMemory' => 'true', 'bloomFilterType' => 'ROWCOL')));
        $retry = 0;
        while ($retry < self::$RETRY_TIMES) {
            try {
                $this->client->createTable($this->table, $columns);
                break;
            } catch (Exception $e) {
                $retry++;
                $this->client->reset();
                echo "HbaseConn Operation [createTable] retry " . $retry . "times\n";
                if ($retry == self::$RETRY_TIMES) {
                    throw $e;
                }
            }
        }
        if ($this->isTableExist()) {
            return TRUE;
        }

        return FALSE;
    }

    function isTableEnabled($table) {
        $ret = FALSE;
        $retry = 0;
        while ($retry < self::$RETRY_TIMES) {
            try {
                $ret = $this->client->isTableEnabled($table);
                break;
            } catch (Exception $e) {
                $retry++;
                $this->client->reset();
                echo "HbaseConn Operation [isTableEnabled] retry " . $retry . "times\n";
                if ($retry == self::$RETRY_TIMES) {
                    throw $e;
                }
            }
        }

        return $ret;
    }

    function disableTable($table) {
        $ret = FALSE;
        $retry = 0;
        while ($retry < self::$RETRY_TIMES) {
            try {
                $ret = $this->client->disableTable($table);
                break;
            } catch (Exception $e) {
                $retry++;
                $this->client->reset();
                echo "HbaseConn Operation [disableTable] retry " . $retry . "times\n";
                if ($retry == self::$RETRY_TIMES) {
                    throw $e;
                }
            }
        }

        return $ret;
    }

    function cleanTable() {
        if ($this->deleteTable()) {
            $this->createTable();
        }
    }

    function deleteTable() {
        if ($this->client == NULL) {
            throw new Exception('hbase连接丢失');
        }
        if (!$this->isTableExist()) {
            return TRUE;
        }
        if ($this->isTableEnabled($this->table)) {
            $this->disableTable($this->table);
        }
        sleep(1);
        $ret = FALSE;
        $retry = 0;
        while ($retry < self::$RETRY_TIMES) {
            try {
                $ret = $this->client->deleteTable($this->table);
                break;
            } catch (Exception $e) {
                $retry++;
                $this->client->reset();
                echo "HbaseConn Operation [deleteTable] retry " . $retry . "times\n";
                if ($retry == self::$RETRY_TIMES) {
                    throw $e;
                }
            }
        }
        sleep(1);
        if ($this->isTableExist()) {
            return FALSE;
        }

        return TRUE;
    }

    function put($key, $value) {
        if ($this->client == NULL) {
            throw new Exception('hbase连接丢失');
        }
        $mutations = array(
            new Mutation(array('column' => 'cf:fulltext', 'value' => gzcompress($value, 2))),
            new Mutation(array('column' => 'cf:md5', 'value' => md5($value))),
            new Mutation(array('column' => 'cf:length', 'value' => strlen($value)))
        );
        array_push($this->batch_mutations, new BatchMutation(array('row' => $key, 'mutations' => $mutations)));
        if (count($this->batch_mutations) >= $this->buffer) {
            $retry = 0;
            while ($retry < self::$RETRY_TIMES) {
                try {
                    $this->client->mutateRows($this->table, $this->batch_mutations, NULL);
                    $this->batch_mutations = array();
                    break;
                } catch (\Exception $e) {
                    $retry++;
                    $this->client->reset();
                    echo "HbaseConn Operation [mutateRows] retry : " . $retry . " times\n";
                    if ($retry == self::$RETRY_TIMES) {
                        throw $e;
                    }
                    //if ($this->isTableExist()) {
                    //    $this->createTable();
                    //}
                    //throw $e;
                }
            }
        }
    }

    function putMulti(array $values) {
        if ($this->client == NULL) {
            throw new Exception('hbase连接丢失');
        }
        foreach ($values as $m_value) {
            foreach ($m_value as $row_key => $row_value) {
                $mutations = [];
                foreach ($row_value as $vkey => $value) {
                    $mutations[] = new Mutation(array('column' => $vkey, 'value' => $value));
                }
                array_push($this->batch_mutations, new BatchMutation(array('row' => $row_key, 'mutations' => $mutations)));
            }
        }
        $retry = 0;
        while ($retry < self::$RETRY_TIMES) {
            try {
                $r = $this->client->mutateRows($this->table, $this->batch_mutations, NULL);
                $this->batch_mutations = array();
                break;
            } catch (\Exception $e) {
                $retry++;
                $this->client->reset();
                echo "HbaseConn Operation [mutateRows] retry : " . $retry . " times\n";
                if ($retry == self::$RETRY_TIMES) {
                    throw $e;
                }
            }
        }
        return $r;
    }

    function get($key) {
        if ($this->client == NULL) {
            throw new Exception('hbase连接丢失');
        }
        $arr = array();
        $retry = 0;
        while ($retry < self::$RETRY_TIMES) {
            try {
                $arr = $this->client->getRow($this->table, $key, NULL);
                break;
            } catch (\Exception $e) {
                $retry++;
                $this->client->reset();
                echo "HbaseConn Operation [getRow] retry : " . $retry . " times\n";
                if ($retry == self::$RETRY_TIMES) {
                    throw $e;
                }
            }
        }
        if (count($arr) == 1) {
            return gzuncompress($arr[0]->columns['cf:fulltext']->value);
        }

        return NULL;
    }

}

?>
