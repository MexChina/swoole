<?php

namespace Swoole\Core\Helper;

class File {

    /**
     * 创建多级文件夹 参数为带有文件名的路径
     * @param string $path 路径名称
     */
    public static function creat_dir_with_filepath($path, $mode = 0777) {
        return self::creat_dir(dirname($path), $mode);
    }

    /**
     * 创建多级文件夹
     * @param string $path 路径名称
     */
    public static function creat_dir($path, $mode = 0777) {
        if (!is_dir($path)) {
            if (self::creat_dir(dirname($path))) {
                return mkdir($path, $mode);
            }
        } else {
            return true;
        }
    }

    /**
     * 递归复制文件
     * 
     * @param $source 源文件或目录名
     * @param $destination 目的文件或目录名
     * @param $child 是不是包含的子目录
     * @param $justnew 只拷贝新更改过的文件
     * */
    public static function all_copy($source, $destination, $justnew = false) {
        if (!is_dir($source)) {
            if (!file_exists(dirname($destination))) {
                File::creat_dir(dirname($destination));
            }
            if (!$justnew || !file_exists($destination) || filemtime($destination) < filemtime($source)) {
                @copy($source, $destination);
            }
        } else {
            self::creat_dir($destination);
            $handle = dir($source);
            while ($entry = $handle->read()) {
                if (strpos($entry, '.') !== 0) {
                    self::all_copy($source . "/" . $entry, $destination . "/" . $entry, $justnew);
                }
            }
        }
    }

    /**
     *
     * 清空文件夹
     * @param $dirName
     * @param $oldtime 小于的时间
     * @param $newtime 大于的时间
     */
    public static function clear_dir($dirName) {
        if (is_dir($dirName)) {//如果传入的参数不是目录，则为文件，应将其删除
            //如果传入的参数是目录
            $handle = @opendir($dirName);
            while (($file = @readdir($handle)) !== false) {
                if ($file != '.' && $file != '..') {
                    $dir = $dirName . '/' . $file; //当前文件$dir为文件目录+文件
                    self::remove_dir($dir);
                }
            }
        }
    }

    /**
     * 判断文件夹是否为空
     * 
     * @param string $path
     * @return boolean
     */
    public static function is_empty_dir($path) {
        $dh = opendir($path);
        while (false !== ($f = readdir($dh))) {
            if ($f != "." && $f != "..") {
                return false;
            }
        }
        return true;
    }

    /**
     * 删除文件<br/>
     * 如果此文件的上级文件夹为空则递归删除
     * 
     * @param string $filepath
     */
    public static function remove_file_with_parentdir($filepath) {
        $parentdir = dirname($filepath);
        @unlink($filepath);
        if (self::is_empty_dir($parentdir)) {
            self::remove_file_with_parentdir($parentdir);
        }
        return true;
    }

    /**
     *
     * 清空并删除文件夹
     * @param $dirName
     * @param $oldtime 小于的时间
     * @param $newtime 大于的时间
     */
    public static function remove_dir($dirName, $oldtime = null, $newtime = null) {
        if (!is_dir($dirName)) {//如果传入的参数不是目录，则为文件，应将其删除
            $mtime = filectime($dirName);
            if ($oldtime === null && $newtime === null) {
                @unlink($dirName);
            } else {
                if (isset($oldtime)) {
                    if ($mtime < $oldtime) {
                        @unlink($dirName);
                    }
                }
                if (isset($newtime)) {
                    if ($mtime > $newtime) {
                        @unlink($dirName);
                    }
                }
            }
            return false;
        }
        //如果传入的参数是目录
        $handle = @opendir($dirName);
        while (($file = @readdir($handle)) !== false) {
            if ($file != '.' && $file != '..') {
                $dir = $dirName . '/' . $file; //当前文件$dir为文件目录+文件
                self::remove_dir($dir, $oldtime, $newtime);
            }
        }
        closedir($handle);
        return @rmdir($dirName);
    }

    /**
     * 递归的文件夹大小 返回文本形式
     * @param string $dir
     */
    public static function dir_size($dir) {
        return self::get_real_size(self::get_dir_size($dir));
    }

    /**
     * 文件大小 返回文本形式
     * @param string $path
     */
    public static function file_size($path) {
        return self::get_real_size(filesize($path));
    }

    /**
     * 获得文件夹大小的调用文件
     * @param $dir
     */
    public static function get_dir_size($dir) {
        $sizeResult = 0;
        $handle = opendir($dir);
        while (false !== ($FolderOrFile = readdir($handle))) {
            if ($FolderOrFile != "." && $FolderOrFile != "..") {
                if (is_dir("$dir/$FolderOrFile")) {
                    $sizeResult += self::get_dir_size("$dir/$FolderOrFile");
                } else {
                    $sizeResult += filesize("$dir/$FolderOrFile");
                }
            }
        }
        closedir($handle);
        return $sizeResult;
    }

    /**
     * 文件大小的文本描述转换
     * @param integer $size
     */
    public static function get_real_size($size) {
        $size = intval($size);
        $kb = 1024;          // Kilobyte
        $mb = 1024 * $kb;    // Megabyte
        $gb = 1024 * $mb;    // Gigabyte
        $tb = 1024 * $gb;    // Terabyte
        if ($size < $kb) {
            return $size . " B";
        } else if ($size < $mb) {
            return round($size / $kb, 2) . " KB";
        } else if ($size < $gb) {
            return round($size / $mb, 2) . " MB";
        } else if ($size < $tb) {
            return round($size / $gb, 2) . " GB";
        } else {
            return round($size / $tb, 2) . " TB";
        }
    }

    /**
     * scandir目录列举
     * 
     * @param string $dir
     * @param integer $sort 1:名称 2:名称倒序 3时间  4时间倒序
     * @param bool $iterat 是否递归遍历所有目录
     * @return array 文件列表
     */
    public static function scandir($dir, $sort = 0, $iterat = TRUE) {
        $files = array();
        if (!file_exists($dir)) {
            return $files;
        }
        if (function_exists('scandir')) {
            $files = scandir($dir);
        } else {
            $dh = opendir($dir);
            while (false !== ($filename = readdir($dh))) {
                $files[] = $filename;
            }
        }
        $resf = array();
        if ($sort < 3) {
            foreach ($files as $fn) {
                if (strpos($fn, '.') !== 0) {
                    if ($iterat && is_dir($dir . "/" . $fn)) {
                        $myfiles = self::scandir($dir . "/" . $fn);
                        $resf[$fn] = $myfiles;
                    } else {
                        $resf[] = $fn;
                    }
                }
            }
        } else {
            foreach ($files as $fn) {
                if (strpos($fn, '.') !== 0) {
                    $resf[$fn] = filemtime($dir . '/' . $fn);
                }
            }
        }

        if ($sort == 1) {
            sort($resf);
        } else if ($sort == 2) {
            rsort($resf);
        }if ($sort == 3) {
            asort($resf);
            $resf = array_keys($resf);
        } else if ($sort == 4) {
            arsort($resf);
            $resf = array_keys($resf);
        }


        return $resf;
    }

    /**
     * 获得文件扩展名
     *
     * @param  string  $path
     * @return string
     */
    public static function extension($path) {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * 获得文件名
     *
     * @param  string  $path
     * @return string
     */
    public static function name_juest($path) {
        $filename = end(explode("/", $path));
        return substr($filename, 0, strlen($filename) - strlen(self::extension($filename)) - 1);
    }

    /**
     * 读取文件内容.
     *
     * @param  string  $path
     * @param  mixed   $default 默认值
     * @return string
     */
    public static function get($path, $default = null) {
        return (file_exists($path)) ? file_get_contents($path) : YYUC::value($default);
    }

    /**
     * 写入信息
     *
     * @param  string  $path
     * @param  string  $data
     * @return int
     */
    public static function put($path, $data) {
        self::creat_dir_with_filepath($path);
        return file_put_contents($path, $data, LOCK_EX);
    }

    /**
     * 文件中继续写入信息
     *
     * @param  string  $path
     * @param  string  $data
     * @return int
     */
    public static function append($path, $data) {
        return file_put_contents($path, $data, LOCK_EX | FILE_APPEND);
    }

    /**
     * 获得文件的Mime类型
     *
     * @param  string  $path
     * @return int
     */
    public static function mime($path, $data) {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
    }

    /**
     * 把文件压缩成zip
     * 
     * @param string $path
     * @param string $zip
     * @param string $ziproot zip文件夹内相对路径
     */
    public static function add_file_to_zip($path, $zip, $ziproot = '') {
        $zpath = null;
        if (is_string($zip)) {
            $zpath = $zip;
            $zip = new ZipArchive();
            if (!($zip->open($zpath, ZipArchive::OVERWRITE) === TRUE)) {
                die('压缩文件创建失败');
            }
        }
        $handler = opendir($path); //打开当前文件夹由$path指定。
        while (($filename = readdir($handler)) !== false) {
            if ($filename != "." && $filename != "..") {
                if (is_dir($path . "/" . $filename)) {// 如果读取的某个对象是文件夹，则递归
                    self::add_file_to_zip($path . "/" . $filename, $zip, $ziproot . "/" . $filename);
                } else { //将文件加入zip对象
                    $zip->addFile($path . "/" . $filename, $ziproot . "/" . $filename);
                }
            }
        }
        @closedir($path);
        if ($zpath != null) {
            $zip->close();
        }
    }

    /**
     * 把zip解压缩
     *
     * @param string $path
     * @param string $zip
     */
    public static function unzip_to_file($path, $zippath) {
        $zip = new ZipArchive();
        if ($zip->open($zippath) === TRUE) {
            $zip->extractTo($path);
            $zip->close();
        }
    }

    /*
      @function  		创建目录

      @var:$filename  目录名

      @return:   		true
     */

    static public function mk_dir($dir) {
        $dir = rtrim($dir, '/') . '/';
        if (!is_dir($dir)) {
            if (mkdir($dir, 0700) == false) {
                return false;
            }
            return true;
        }
        return true;
    }

    /*
      @function  		读取文件内容

      @var:$filename  文件名

      @return:   		文件内容
     */

    static public function read_file($filename) {
        $content = '';
        if (function_exists('file_get_contents')) {
            @$content = file_get_contents($filename);
        } else {
            if (@$fp = fopen($filename, 'r')) {
                @$content = fread($fp, filesize($filename));
                @fclose($fp);
            }
        }
        return $content;
    }

    /*
      @function   写文件

      @var:$filename  文件名

      @var:$writetext 文件内容

      @var:$openmod 	打开方式

      @return:   		成功=true
     */

    static function write_file($filename, $writetext, $openmod = 'w') {
        $dir = dirname($filename);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        if (@$fp = fopen($filename, $openmod)) {
            flock($fp, 2);
            fwrite($fp, $writetext);
            fclose($fp);
            return true;
        } else {
            return false;
        }
    }

    /*
      @function  		删除目录

      @var:$dirName  	原目录

      @return:   		成功=true
     */

    static function del_dir($dirName) {
        if (!file_exists($dirName)) {
            return false;
        }

        $dir = opendir($dirName);
        while ($fileName = readdir($dir)) {
            $file = $dirName . '/' . $fileName;
            if ($fileName != '.' && $fileName != '..') {
                if (is_dir($file)) {
                    self::del_dir($file);
                } else {
                    unlink($file);
                }
            }
        }
        closedir($dir);
        return rmdir($dirName);
    }

    /*
      @function  		复制目录

      @var:$surDir  	原目录

      @var:$toDir  	目标目录

      @return:   		true
     */

    static function copy_dir($surDir, $toDir) {
        $surDir = rtrim($surDir, '/') . '/';
        $toDir = rtrim($toDir, '/') . '/';
        if (!file_exists($surDir)) {
            return false;
        }

        if (!file_exists($toDir)) {
            self::mk_dir($toDir);
        }
        $file = opendir($surDir);
        while ($fileName = readdir($file)) {
            $file1 = $surDir . '/' . $fileName;
            $file2 = $toDir . '/' . $fileName;
            if ($fileName != '.' && $fileName != '..') {
                if (is_dir($file1)) {
                    self::copy_dir($file1, $file2);
                } else {
                    copy($file1, $file2);
                }
            }
        }
        closedir($file);
        return true;
    }

    /*
      @function  列出目录

      @var:$dir  目录名

      @return:   目录数组

      列出文件夹下内容，返回数组 $dirArray['dir']:存文件夹；$dirArray['file']：存文件
     */

    static function get_dirs($dir) {
        $dir = rtrim($dir, '/') . '/';
        $dirArray [][] = NULL;
        if (false != ($handle = opendir($dir))) {
            $i = 0;
            $j = 0;
            while (false !== ($file = readdir($handle))) {
                if (is_dir($dir . $file)) { //判断是否文件夹
                    if (substr($file, 0, 1) != ".") {
                        $dirArray ['dir'] [$i] = $file;
                        $i++;
                    }
                } else {
                    $dirArray ['file'] [$j] = $file;
                    $j++;
                }
            }
            closedir($handle);
        }
        return $dirArray;
    }

    /*
      @function  统计文件夹大小

      @var:$dir  目录名

      @return:   文件夹大小(单位 B)
     */

    static function get_size($dir) {
        $dirlist = opendir($dir);
        $dirsize = 0;
        while (false !== ($folderorfile = readdir($dirlist))) {
            if ($folderorfile != "." && $folderorfile != "..") {
                if (is_dir("$dir/$folderorfile")) {
                    $dirsize += self::get_size("$dir/$folderorfile");
                } else {
                    $dirsize += filesize("$dir/$folderorfile");
                }
            }
        }
        closedir($dirlist);
        return $dirsize;
    }

    /*
      @function  检测是否为空文件夹

      @var:$dir  目录名

      @return:   存在则返回true
     */

    static function empty_dir($dir) {
        return (($files = @scandir($dir)) && count($files) <= 2);
    }

    /*
      @function  文件缓存与文件读取

      @var:$name  文件名

      @var:$value  文件内容,为空则获取缓存

      @var:$path   文件所在目录,默认是当前应用的DATA目录

      @var:$cached  是否缓存结果,默认缓存

      @return:   返回缓存内容
     */

    function cache($name, $value = '', $path = DATA_PATH, $cached = true) {
        static $_cache = array();
        $filename = $path . $name . '.php';
        if ('' !== $value) {
            if (is_null($value)) {
                // 删除缓存
                return false !== strpos($name, '*') ? array_map("unlink", glob($filename)) : unlink($filename);
            } else {
                // 缓存数据
                $dir = dirname($filename);
                // 目录不存在则创建
                if (!is_dir($dir))
                    mkdir($dir, 0755, true);
                $_cache[$name] = $value;
                return file_put_contents($filename, strip_whitespace("<?php\treturn " . var_export($value, true) . ";?>"));
            }
        }
        if (isset($_cache[$name]) && $cached == true)
            return $_cache[$name];
        // 获取缓存数据
        if (is_file($filename)) {
            $value = include $filename;
            $_cache[$name] = $value;
        } else {
            $value = false;
        }
        return $value;
    }

    static function getMD5($dir) {
        $dir = realpath($dir);
        if (!$dir) {
            return fasle;
        }
        $return = array();
        if (!is_dir($dir)) {
            $return[$dir] = md5_file($dir);
        } else {
            $files = File::scandir($dir);
        }
    }

    static function getfilelist($dir, &$return = array(), $mypath = "") {
        if (is_string($dir)) {
            $files = self::scandir($dir);
        } elseif (is_array($dir)) {
            $files = $dir;
        }
        foreach ($files as $path => $myfiles) {
            if (is_array($myfiles)) {
                $mypath = $mypath ? $mypath . "/" . $path : $path;
                self::getfilelist($dir, $return, $mypath);
            } else {
                if ($path) {
                    $return[$path] = $myfiles;
                } else {
                    $return[] = $myfiles;
                }
            }
        }
        return $return;
    }

}
