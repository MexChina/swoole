<?php

namespace Swoole\Core\Lib;

/**
 * SQL Formatter is a collection of utilities for debugging SQL queries.
 * It includes methods for formatting, syntax highlighting, removing comments, etc.
 *
 * @package    SqlFormatter
 * @author     Jeremy Dorn <jeremy@jeremydorn.com>
 * @author     Florin Patan <florinpatan@gmail.com>
 * @copyright  2013 Jeremy Dorn
 * @license    http://opensource.org/licenses/MIT
 * @link       http://github.com/jdorn/sql-formatter
 * @version    1.2.18
 */
class SqlFormatter {

    // Constants for token types
    const TOKEN_TYPE_WHITESPACE = 0;
    const TOKEN_TYPE_WORD = 1;
    const TOKEN_TYPE_QUOTE = 2;
    const TOKEN_TYPE_BACKTICK_QUOTE = 3;
    const TOKEN_TYPE_RESERVED = 4;
    const TOKEN_TYPE_RESERVED_TOPLEVEL = 5;
    const TOKEN_TYPE_RESERVED_NEWLINE = 6;
    const TOKEN_TYPE_BOUNDARY = 7;
    const TOKEN_TYPE_COMMENT = 8;
    const TOKEN_TYPE_BLOCK_COMMENT = 9;
    const TOKEN_TYPE_NUMBER = 10;
    const TOKEN_TYPE_ERROR = 11;
    const TOKEN_TYPE_VARIABLE = 12;
    // Constants for different components of a token
    const TOKEN_TYPE = 0;
    const TOKEN_VALUE = 1;
    const SPLIT_RESERVED = "SELECT";
    const CHILD_QUERY_NAME = "ChildQuery_";

    // Reserved words (for syntax highlighting)
    protected static $reserved = array(
        'ACCESSIBLE', 'ACTION', 'AGAINST', 'AGGREGATE', 'ALGORITHM', 'ALL', 'ALTER', 'ANALYSE', 'ANALYZE', 'AS', 'ASC',
        'AUTOCOMMIT', 'AUTO_INCREMENT', 'BACKUP', 'BEGIN', 'BETWEEN', 'BINLOG', 'BOTH', 'CASCADE', 'CASE', 'CHANGE', 'CHANGED', 'CHARACTER SET',
        'CHARSET', 'CHECK', 'CHECKSUM', 'COLLATE', 'COLLATION', 'COLUMN', 'COLUMNS', 'COMMENT', 'COMMIT', 'COMMITTED', 'COMPRESSED', 'CONCURRENT',
        'CONSTRAINT', 'CONTAINS', 'CONVERT', 'CREATE', 'CROSS', 'CURRENT_TIMESTAMP', 'DATABASE', 'DATABASES', 'DAY', 'DAY_HOUR', 'DAY_MINUTE',
        'DAY_SECOND', 'DEFAULT', 'DEFINER', 'DELAYED', 'DELETE', 'DESC', 'DESCRIBE', 'DETERMINISTIC', 'DISTINCT', 'DISTINCTROW', 'DIV',
        'DO', 'DUMPFILE', 'DUPLICATE', 'DYNAMIC', 'ELSE', 'ENCLOSED', 'END', 'ENGINE', 'ENGINE_TYPE', 'ENGINES', 'ESCAPE', 'ESCAPED', 'EVENTS', 'EXEC',
        'EXECUTE', 'EXISTS', 'EXPLAIN', 'EXTENDED', 'FAST', 'FIELDS', 'FILE', 'FIRST', 'FIXED', 'FLUSH', 'FOR', 'FORCE', 'FOREIGN', 'FULL', 'FULLTEXT',
        'FUNCTION', 'GLOBAL', 'GRANT', 'GRANTS', 'GROUP_CONCAT', 'HEAP', 'HIGH_PRIORITY', 'HOSTS', 'HOUR', 'HOUR_MINUTE',
        'HOUR_SECOND', 'IDENTIFIED', 'IF', 'IFNULL', 'IGNORE', 'IN', 'INDEX', 'INDEXES', 'INFILE', 'INSERT', 'INSERT_ID', 'INSERT_METHOD', 'INTERVAL',
        'INTO', 'INVOKER', 'IS', 'ISOLATION', 'KEY', 'KEYS', 'KILL', 'LAST_INSERT_ID', 'LEADING', 'LEVEL', 'LIKE', 'LINEAR',
        'LINES', 'LOAD', 'LOCAL', 'LOCK', 'LOCKS', 'LOGS', 'LOW_PRIORITY', 'MARIA', 'MASTER', 'MASTER_CONNECT_RETRY', 'MASTER_HOST', 'MASTER_LOG_FILE',
        'MATCH', 'MAX_CONNECTIONS_PER_HOUR', 'MAX_QUERIES_PER_HOUR', 'MAX_ROWS', 'MAX_UPDATES_PER_HOUR', 'MAX_USER_CONNECTIONS',
        'MEDIUM', 'MERGE', 'MINUTE', 'MINUTE_SECOND', 'MIN_ROWS', 'MODE', 'MODIFY',
        'MONTH', 'MRG_MYISAM', 'MYISAM', 'NAMES', 'NATURAL', 'NOT', 'NOW()', 'NULL', 'OFFSET', 'ON', 'OPEN', 'OPTIMIZE', 'OPTION', 'OPTIONALLY',
        'ON UPDATE', 'ON DELETE', 'OUTFILE', 'PACK_KEYS', 'PAGE', 'PARTIAL', 'PARTITION', 'PARTITIONS', 'PASSWORD', 'PRIMARY', 'PRIVILEGES', 'PROCEDURE',
        'PROCESS', 'PROCESSLIST', 'PURGE', 'QUICK', 'RANGE', 'RAID0', 'RAID_CHUNKS', 'RAID_CHUNKSIZE', 'RAID_TYPE', 'READ', 'READ_ONLY',
        'READ_WRITE', 'REFERENCES', 'REGEXP', 'RELOAD', 'RENAME', 'REPAIR', 'REPEATABLE', 'REPLACE', 'REPLICATION', 'RESET', 'RESTORE', 'RESTRICT',
        'RETURN', 'RETURNS', 'REVOKE', 'RLIKE', 'ROLLBACK', 'ROW', 'ROWS', 'ROW_FORMAT', 'SECOND', 'SECURITY', 'SEPARATOR',
        'SERIALIZABLE', 'SESSION', 'SHARE', 'SHOW', 'SHUTDOWN', 'SLAVE', 'SONAME', 'SOUNDS', 'SQL', 'SQL_AUTO_IS_NULL', 'SQL_BIG_RESULT',
        'SQL_BIG_SELECTS', 'SQL_BIG_TABLES', 'SQL_BUFFER_RESULT', 'SQL_CALC_FOUND_ROWS', 'SQL_LOG_BIN', 'SQL_LOG_OFF', 'SQL_LOG_UPDATE',
        'SQL_LOW_PRIORITY_UPDATES', 'SQL_MAX_JOIN_SIZE', 'SQL_QUOTE_SHOW_CREATE', 'SQL_SAFE_UPDATES', 'SQL_SELECT_LIMIT', 'SQL_SLAVE_SKIP_COUNTER',
        'SQL_SMALL_RESULT', 'SQL_WARNINGS', 'SQL_CACHE', 'SQL_NO_CACHE', 'START', 'STARTING', 'STATUS', 'STOP', 'STORAGE',
        'STRAIGHT_JOIN', 'STRING', 'STRIPED', 'SUPER', 'TABLE', 'TABLES', 'TEMPORARY', 'TERMINATED', 'THEN', 'TO', 'TRAILING', 'TRANSACTIONAL', 'TRUE',
        'TRUNCATE', 'TYPE', 'TYPES', 'UNCOMMITTED', 'UNIQUE', 'UNLOCK', 'UNSIGNED', 'USAGE', 'USE', 'USING', 'VARIABLES',
        'VIEW', 'WHEN', 'WITH', 'WORK', 'WRITE', 'YEAR_MONTH'
    );
    // For SQL formatting
    // These keywords will all be on their own line
    protected static $reserved_toplevel = array(
        'SELECT', 'FROM', 'WHERE', 'SET', 'ORDER BY', 'GROUP BY', 'LIMIT', 'DROP',
        'VALUES', 'UPDATE', 'HAVING', 'ADD', 'AFTER', 'ALTER TABLE', 'DELETE FROM', 'UNION ALL', 'UNION', 'EXCEPT', 'INTERSECT'
    );
    protected static $reserved_newline = array(
        'LEFT OUTER JOIN', 'RIGHT OUTER JOIN', 'LEFT JOIN', 'RIGHT JOIN', 'OUTER JOIN', 'INNER JOIN', 'JOIN', 'XOR', 'OR', 'AND'
    );
    protected static $functions = array(
        'ABS', 'ACOS', 'ADDDATE', 'ADDTIME', 'AES_DECRYPT', 'AES_ENCRYPT', 'AREA', 'ASBINARY', 'ASCII', 'ASIN', 'ASTEXT', 'ATAN', 'ATAN2',
        'AVG', 'BDMPOLYFROMTEXT', 'BDMPOLYFROMWKB', 'BDPOLYFROMTEXT', 'BDPOLYFROMWKB', 'BENCHMARK', 'BIN', 'BIT_AND', 'BIT_COUNT', 'BIT_LENGTH',
        'BIT_OR', 'BIT_XOR', 'BOUNDARY', 'BUFFER', 'CAST', 'CEIL', 'CEILING', 'CENTROID', 'CHAR', 'CHARACTER_LENGTH', 'CHARSET', 'CHAR_LENGTH',
        'COALESCE', 'COERCIBILITY', 'COLLATION', 'COMPRESS', 'CONCAT', 'CONCAT_WS', 'CONNECTION_ID', 'CONTAINS', 'CONV', 'CONVERT', 'CONVERT_TZ',
        'CONVEXHULL', 'COS', 'COT', 'COUNT', 'CRC32', 'CROSSES', 'CURDATE', 'CURRENT_DATE', 'CURRENT_TIME', 'CURRENT_TIMESTAMP', 'CURRENT_USER',
        'CURTIME', 'DATABASE', 'DATE', 'DATEDIFF', 'DATE_ADD', 'DATE_DIFF', 'DATE_FORMAT', 'DATE_SUB', 'DAY', 'DAYNAME', 'DAYOFMONTH', 'DAYOFWEEK',
        'DAYOFYEAR', 'DECODE', 'DEFAULT', 'DEGREES', 'DES_DECRYPT', 'DES_ENCRYPT', 'DIFFERENCE', 'DIMENSION', 'DISJOINT', 'DISTANCE', 'ELT', 'ENCODE',
        'ENCRYPT', 'ENDPOINT', 'ENVELOPE', 'EQUALS', 'EXP', 'EXPORT_SET', 'EXTERIORRING', 'EXTRACT', 'EXTRACTVALUE', 'FIELD', 'FIND_IN_SET', 'FLOOR',
        'FORMAT', 'FOUND_ROWS', 'FROM_DAYS', 'FROM_UNIXTIME', 'GEOMCOLLFROMTEXT', 'GEOMCOLLFROMWKB', 'GEOMETRYCOLLECTION', 'GEOMETRYCOLLECTIONFROMTEXT',
        'GEOMETRYCOLLECTIONFROMWKB', 'GEOMETRYFROMTEXT', 'GEOMETRYFROMWKB', 'GEOMETRYN', 'GEOMETRYTYPE', 'GEOMFROMTEXT', 'GEOMFROMWKB', 'GET_FORMAT',
        'GET_LOCK', 'GLENGTH', 'GREATEST', 'GROUP_CONCAT', 'GROUP_UNIQUE_USERS', 'HEX', 'HOUR', 'IF', 'IFNULL', 'INET_ATON', 'INET_NTOA', 'INSERT', 'INSTR',
        'INTERIORRINGN', 'INTERSECTION', 'INTERSECTS', 'INTERVAL', 'ISCLOSED', 'ISEMPTY', 'ISNULL', 'ISRING', 'ISSIMPLE', 'IS_FREE_LOCK', 'IS_USED_LOCK',
        'LAST_DAY', 'LAST_INSERT_ID', 'LCASE', 'LEAST', 'LEFT', 'LENGTH', 'LINEFROMTEXT', 'LINEFROMWKB', 'LINESTRING', 'LINESTRINGFROMTEXT', 'LINESTRINGFROMWKB',
        'LN', 'LOAD_FILE', 'LOCALTIME', 'LOCALTIMESTAMP', 'LOCATE', 'LOG', 'LOG10', 'LOG2', 'LOWER', 'LPAD', 'LTRIM', 'MAKEDATE', 'MAKETIME', 'MAKE_SET',
        'MASTER_POS_WAIT', 'MAX', 'MBRCONTAINS', 'MBRDISJOINT', 'MBREQUAL', 'MBRINTERSECTS', 'MBROVERLAPS', 'MBRTOUCHES', 'MBRWITHIN', 'MD5', 'MICROSECOND',
        'MID', 'MIN', 'MINUTE', 'MLINEFROMTEXT', 'MLINEFROMWKB', 'MOD', 'MONTH', 'MONTHNAME', 'MPOINTFROMTEXT', 'MPOINTFROMWKB', 'MPOLYFROMTEXT', 'MPOLYFROMWKB',
        'MULTILINESTRING', 'MULTILINESTRINGFROMTEXT', 'MULTILINESTRINGFROMWKB', 'MULTIPOINT', 'MULTIPOINTFROMTEXT', 'MULTIPOINTFROMWKB', 'MULTIPOLYGON',
        'MULTIPOLYGONFROMTEXT', 'MULTIPOLYGONFROMWKB', 'NAME_CONST', 'NULLIF', 'NUMGEOMETRIES', 'NUMINTERIORRINGS', 'NUMPOINTS', 'OCT', 'OCTET_LENGTH',
        'OLD_PASSWORD', 'ORD', 'OVERLAPS', 'PASSWORD', 'PERIOD_ADD', 'PERIOD_DIFF', 'PI', 'POINT', 'POINTFROMTEXT', 'POINTFROMWKB', 'POINTN', 'POINTONSURFACE',
        'POLYFROMTEXT', 'POLYFROMWKB', 'POLYGON', 'POLYGONFROMTEXT', 'POLYGONFROMWKB', 'POSITION', 'POW', 'POWER', 'QUARTER', 'QUOTE', 'RADIANS', 'RAND',
        'RELATED', 'RELEASE_LOCK', 'REPEAT', 'REPLACE', 'REVERSE', 'RIGHT', 'ROUND', 'ROW_COUNT', 'RPAD', 'RTRIM', 'SCHEMA', 'SECOND', 'SEC_TO_TIME',
        'SESSION_USER', 'SHA', 'SHA1', 'SIGN', 'SIN', 'SLEEP', 'SOUNDEX', 'SPACE', 'SQRT', 'SRID', 'STARTPOINT', 'STD', 'STDDEV', 'STDDEV_POP', 'STDDEV_SAMP',
        'STRCMP', 'STR_TO_DATE', 'SUBDATE', 'SUBSTR', 'SUBSTRING', 'SUBSTRING_INDEX', 'SUBTIME', 'SUM', 'SYMDIFFERENCE', 'SYSDATE', 'SYSTEM_USER', 'TAN',
        'TIME', 'TIMEDIFF', 'TIMESTAMP', 'TIMESTAMPADD', 'TIMESTAMPDIFF', 'TIME_FORMAT', 'TIME_TO_SEC', 'TOUCHES', 'TO_DAYS', 'TRIM', 'TRUNCATE', 'UCASE',
        'UNCOMPRESS', 'UNCOMPRESSED_LENGTH', 'UNHEX', 'UNIQUE_USERS', 'UNIX_TIMESTAMP', 'UPDATEXML', 'UPPER', 'USER', 'UTC_DATE', 'UTC_TIME', 'UTC_TIMESTAMP',
        'UUID', 'VARIANCE', 'VAR_POP', 'VAR_SAMP', 'VERSION', 'WEEK', 'WEEKDAY', 'WEEKOFYEAR', 'WITHIN', 'X', 'Y', 'YEAR', 'YEARWEEK'
    );
    // Punctuation that can be used as a boundary between other tokens
    protected static $boundaries = array(',', ';', ':', ')', '(', '.', '=', '<', '>', '+', '-', '*', '/', '!', '^', '%', '|', '&', '#');
    // For HTML syntax highlighting
    // Styles applied to different token types
    public static $quote_attributes = 'style="color: blue;"';
    public static $backtick_quote_attributes = 'style="color: purple;"';
    public static $reserved_attributes = 'style="font-weight:bold;"';
    public static $boundary_attributes = '';
    public static $number_attributes = 'style="color: green;"';
    public static $word_attributes = 'style="color: #333;"';
    public static $error_attributes = 'style="background-color: red;"';
    public static $comment_attributes = 'style="color: #aaa;"';
    public static $variable_attributes = 'style="color: orange;"';
    public static $pre_attributes = 'style="color: black; background-color: white;"';
    // Boolean - whether or not the current environment is the CLI
    // This affects the type of syntax highlighting
    // If not defined, it will be determined automatically
    public static $cli;
    // For CLI syntax highlighting
    public static $cli_quote = "\x1b[34;1m";
    public static $cli_backtick_quote = "\x1b[35;1m";
    public static $cli_reserved = "\x1b[37m";
    public static $cli_boundary = "";
    public static $cli_number = "\x1b[32;1m";
    public static $cli_word = "";
    public static $cli_error = "\x1b[31;1;7m";
    public static $cli_comment = "\x1b[30;1m";
    public static $cli_functions = "\x1b[37m";
    public static $cli_variable = "\x1b[36;1m";
    // The tab character to use when formatting SQL
    public static $tab = '  ';
    // This flag tells us if queries need to be enclosed in <pre> tags
    public static $use_pre = true;
    // This flag tells us if SqlFormatted has been initialized
    protected static $init;
    // Regular expressions for tokenizing
    protected static $regex_boundaries;
    protected static $regex_reserved;
    protected static $regex_reserved_newline;
    protected static $regex_reserved_toplevel;
    protected static $regex_function;
    // Cache variables
    // Only tokens shorter than this size will be cached.  Somewhere between 10 and 20 seems to work well for most cases.
    public static $max_cachekey_size = 15;
    protected static $token_cache = array();
    protected static $cache_hits = 0;
    protected static $cache_misses = 0;
    protected static $tokens = [];

    /**
     * Get stats about the token cache
     * @return Array An array containing the keys 'hits', 'misses', 'entries', and 'size' in bytes
     */
    public static function getCacheStats() {
        return array(
            'hits' => self::$cache_hits,
            'misses' => self::$cache_misses,
            'entries' => count(self::$token_cache),
            'size' => strlen(serialize(self::$token_cache))
        );
    }

    /**
     * Stuff that only needs to be done once.  Builds regular expressions and sorts the reserved words.
     */
    protected static function init() {
        if (self::$init)
            return;

        // Sort reserved word list from longest word to shortest, 3x faster than usort
        $reservedMap = array_combine(self::$reserved, array_map('strlen', self::$reserved));
        arsort($reservedMap);
        self::$reserved = array_keys($reservedMap);

        // Set up regular expressions
        self::$regex_boundaries = '(' . implode('|', array_map(array(__CLASS__, 'quote_regex'), self::$boundaries)) . ')';
        self::$regex_reserved = '(' . implode('|', array_map(array(__CLASS__, 'quote_regex'), self::$reserved)) . ')';
        self::$regex_reserved_toplevel = str_replace(' ', '\\s+', '(' . implode('|', array_map(array(__CLASS__, 'quote_regex'), self::$reserved_toplevel)) . ')');
        self::$regex_reserved_newline = str_replace(' ', '\\s+', '(' . implode('|', array_map(array(__CLASS__, 'quote_regex'), self::$reserved_newline)) . ')');

        self::$regex_function = '(' . implode('|', array_map(array(__CLASS__, 'quote_regex'), self::$functions)) . ')';

        self::$init = true;
    }

    /**
     * Return the next token and token type in a SQL string.
     * Quoted strings, comments, reserved words, whitespace, and punctuation are all their own tokens.
     *
     * @param String $string   The SQL string
     * @param array  $previous The result of the previous getNextToken() call
     *
     * @return Array An associative array containing the type and value of the token.
     */
    protected static function getNextToken($string, $previous = null) {
        // Whitespace
        if (preg_match('/^\s+/', $string, $matches)) {
            return array(
                self::TOKEN_VALUE => $matches[0],
                self::TOKEN_TYPE => self::TOKEN_TYPE_WHITESPACE
            );
        }

        // Comment
        if ($string[0] === '#' || (isset($string[1]) && ($string[0] === '-' && $string[1] === '-') || ($string[0] === '/' && $string[1] === '*'))) {
            // Comment until end of line
            if ($string[0] === '-' || $string[0] === '#') {
                $last = strpos($string, "\n");
                $type = self::TOKEN_TYPE_COMMENT;
            } else { // Comment until closing comment tag
                $last = strpos($string, "*/", 2) + 2;
                $type = self::TOKEN_TYPE_BLOCK_COMMENT;
            }

            if ($last === false) {
                $last = strlen($string);
            }

            return array(
                self::TOKEN_VALUE => substr($string, 0, $last),
                self::TOKEN_TYPE => $type
            );
        }

        // Quoted String
        if ($string[0] === '"' || $string[0] === '\'' || $string[0] === '`' || $string[0] === '[') {
            $return = array(
                self::TOKEN_TYPE => (($string[0] === '`' || $string[0] === '[') ? self::TOKEN_TYPE_BACKTICK_QUOTE : self::TOKEN_TYPE_QUOTE),
                self::TOKEN_VALUE => self::getQuotedString($string)
            );

            return $return;
        }

        // User-defined Variable
        if ($string[0] === '@' && isset($string[1])) {
            $ret = array(
                self::TOKEN_VALUE => null,
                self::TOKEN_TYPE => self::TOKEN_TYPE_VARIABLE
            );

            // If the variable name is quoted
            if ($string[1] === '"' || $string[1] === '\'' || $string[1] === '`') {
                $ret[self::TOKEN_VALUE] = '@' . self::getQuotedString(substr($string, 1));
            }
            // Non-quoted variable name
            else {
                preg_match('/^(@[a-zA-Z0-9\._\$]+)/', $string, $matches);
                if ($matches) {
                    $ret[self::TOKEN_VALUE] = $matches[1];
                }
            }

            if ($ret[self::TOKEN_VALUE] !== null)
                return $ret;
        }

        // Number (decimal, binary, or hex)
        if (preg_match('/^([0-9]+(\.[0-9]+)?|0x[0-9a-fA-F]+|0b[01]+)($|\s|"\'`|' . self::$regex_boundaries . ')/', $string, $matches)) {
            return array(
                self::TOKEN_VALUE => $matches[1],
                self::TOKEN_TYPE => self::TOKEN_TYPE_NUMBER
            );
        }

        // Boundary Character (punctuation and symbols)
        if (preg_match('/^(' . self::$regex_boundaries . ')/', $string, $matches)) {
            return array(
                self::TOKEN_VALUE => $matches[1],
                self::TOKEN_TYPE => self::TOKEN_TYPE_BOUNDARY
            );
        }

        // A reserved word cannot be preceded by a '.'
        // this makes it so in "mytable.from", "from" is not considered a reserved word
        if (!$previous || !isset($previous[self::TOKEN_VALUE]) || $previous[self::TOKEN_VALUE] !== '.') {
            $upper = strtoupper($string);
            // Top Level Reserved Word
            if (preg_match('/^(' . self::$regex_reserved_toplevel . ')($|\s|' . self::$regex_boundaries . ')/', $upper, $matches)) {
                return array(
                    self::TOKEN_TYPE => self::TOKEN_TYPE_RESERVED_TOPLEVEL,
                    self::TOKEN_VALUE => substr($string, 0, strlen($matches[1]))
                );
            }
            // Newline Reserved Word
            if (preg_match('/^(' . self::$regex_reserved_newline . ')($|\s|' . self::$regex_boundaries . ')/', $upper, $matches)) {
                return array(
                    self::TOKEN_TYPE => self::TOKEN_TYPE_RESERVED_NEWLINE,
                    self::TOKEN_VALUE => substr($string, 0, strlen($matches[1]))
                );
            }
            // Other Reserved Word
            if (preg_match('/^(' . self::$regex_reserved . ')($|\s|' . self::$regex_boundaries . ')/', $upper, $matches)) {
                return array(
                    self::TOKEN_TYPE => self::TOKEN_TYPE_RESERVED,
                    self::TOKEN_VALUE => substr($string, 0, strlen($matches[1]))
                );
            }
        }

        // A function must be suceeded by '('
        // this makes it so "count(" is considered a function, but "count" alone is not
        $upper = strtoupper($string);
        // function
        if (preg_match('/^(' . self::$regex_function . '[(]|\s|[)])/', $upper, $matches)) {
            return array(
                self::TOKEN_TYPE => self::TOKEN_TYPE_RESERVED,
                self::TOKEN_VALUE => substr($string, 0, strlen($matches[1]) - 1)
            );
        }

        // Non reserved word
        preg_match('/^(.*?)($|\s|["\'`]|' . self::$regex_boundaries . ')/', $string, $matches);

        return array(
            self::TOKEN_VALUE => $matches[1],
            self::TOKEN_TYPE => self::TOKEN_TYPE_WORD
        );
    }

    protected static function getQuotedString($string) {
        $ret = null;

        // This checks for the following patterns:
        // 1. backtick quoted string using `` to escape
        // 2. square bracket quoted string (SQL Server) using ]] to escape
        // 3. double quoted string using "" or \" to escape
        // 4. single quoted string using '' or \' to escape
        if (preg_match('/^(((`[^`]*($|`))+)|((\[[^\]]*($|\]))(\][^\]]*($|\]))*)|(("[^"\\\\]*(?:\\\\.[^"\\\\]*)*("|$))+)|((\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*(\'|$))+))/s', $string, $matches)) {
            $ret = $matches[1];
        }

        return $ret;
    }

    /**
     * Takes a SQL string and breaks it into tokens.
     * Each token is an associative array with type and value.
     *
     * @param String $string The SQL string
     *
     * @return Array An array of tokens.
     */
    protected static function tokenize($string) {
        self::init();

        $tokens = array();

        // Used for debugging if there is an error while tokenizing the string
        $original_length = strlen($string);

        // Used to make sure the string keeps shrinking on each iteration
        $old_string_len = strlen($string) + 1;

        $token = null;

        $current_length = strlen($string);

        // Keep processing the string until it is empty
        while ($current_length) {
            // If the string stopped shrinking, there was a problem
            if ($old_string_len <= $current_length) {
                $tokens[] = array(
                    self::TOKEN_VALUE => $string,
                    self::TOKEN_TYPE => self::TOKEN_TYPE_ERROR
                );

                return $tokens;
            }
            $old_string_len = $current_length;

            // Determine if we can use caching
            if ($current_length >= self::$max_cachekey_size) {
                $cacheKey = substr($string, 0, self::$max_cachekey_size);
            } else {
                $cacheKey = false;
            }

            // See if the token is already cached
            if ($cacheKey && isset(self::$token_cache[$cacheKey])) {
                // Retrieve from cache
                $token = self::$token_cache[$cacheKey];
                $token_length = strlen($token[self::TOKEN_VALUE]);
                self::$cache_hits++;
            } else {
                // Get the next token and the token type
                $token = self::getNextToken($string, $token);
                $token_length = strlen($token[self::TOKEN_VALUE]);
                self::$cache_misses++;

                // If the token is shorter than the max length, store it in cache
                if ($cacheKey && $token_length < self::$max_cachekey_size) {
                    self::$token_cache[$cacheKey] = $token;
                }
            }

            $tokens[] = $token;

            // Advance the string
            $string = substr($string, $token_length);

            $current_length -= $token_length;
        }

        return $tokens;
    }

    /**
     * Format the whitespace in a SQL string to make it easier to read.
     *
     * @param String  $string    The SQL string
     * @param boolean $highlight If true, syntax highlighting will also be performed
     *
     * @return String The SQL string with HTML styles and formatting wrapped in a <pre> tag
     */
    public static function format($string, $highlight = true) {
        // This variable will be populated with formatted html
        $return = '';

        // Use an actual tab while formatting and then switch out with self::$tab at the end
        $tab = "\t";

        $indent_level = 0;
        $newline = false;
        $inline_parentheses = false;
        $increase_special_indent = false;
        $increase_block_indent = false;
        $indent_types = array();
        $added_newline = false;
        $inline_count = 0;
        $inline_indented = false;
        $clause_limit = false;

        // Tokenize String
        $original_tokens = self::tokenize($string);

        // Remove existing whitespace
        $tokens = array();
        foreach ($original_tokens as $i => $token) {
            if ($token[self::TOKEN_TYPE] !== self::TOKEN_TYPE_WHITESPACE) {
                $token['i'] = $i;
                $tokens[] = $token;
            }
        }

        // Format token by token
        foreach ($tokens as $i => $token) {
            // Get highlighted token if doing syntax highlighting
            if ($highlight) {
                $highlighted = self::highlightToken($token);
            } else { // If returning raw text
                $highlighted = $token[self::TOKEN_VALUE];
            }

            // If we are increasing the special indent level now
            if ($increase_special_indent) {
                $indent_level++;
                $increase_special_indent = false;
                array_unshift($indent_types, 'special');
            }
            // If we are increasing the block indent level now
            if ($increase_block_indent) {
                $indent_level++;
                $increase_block_indent = false;
                array_unshift($indent_types, 'block');
            }

            // If we need a new line before the token
            if ($newline) {
                $return .= "\n" . str_repeat($tab, $indent_level);
                $newline = false;
                $added_newline = true;
            } else {
                $added_newline = false;
            }

            // Display comments directly where they appear in the source
            if ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_COMMENT || $token[self::TOKEN_TYPE] === self::TOKEN_TYPE_BLOCK_COMMENT) {
                if ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_BLOCK_COMMENT) {
                    $indent = str_repeat($tab, $indent_level);
                    $return .= "\n" . $indent;
                    $highlighted = str_replace("\n", "\n" . $indent, $highlighted);
                }

                $return .= $highlighted;
                $newline = true;
                continue;
            }

            if ($inline_parentheses) {
                // End of inline parentheses
                if ($token[self::TOKEN_VALUE] === ')') {
                    $return = rtrim($return, ' ');

                    if ($inline_indented) {
                        array_shift($indent_types);
                        $indent_level --;
                        $return .= "\n" . str_repeat($tab, $indent_level);
                    }

                    $inline_parentheses = false;

                    $return .= $highlighted . ' ';
                    continue;
                }

                if ($token[self::TOKEN_VALUE] === ',') {
                    if ($inline_count >= 30) {
                        $inline_count = 0;
                        $newline = true;
                    }
                }

                $inline_count += strlen($token[self::TOKEN_VALUE]);
            }

            // Opening parentheses increase the block indent level and start a new line
            if ($token[self::TOKEN_VALUE] === '(') {
                // First check if this should be an inline parentheses block
                // Examples are "NOW()", "COUNT(*)", "int(10)", key(`somecolumn`), DECIMAL(7,2)
                // Allow up to 3 non-whitespace tokens inside inline parentheses
                $length = 0;
                for ($j = 1; $j <= 250; $j++) {
                    // Reached end of string
                    if (!isset($tokens[$i + $j]))
                        break;

                    $next = $tokens[$i + $j];

                    // Reached closing parentheses, able to inline it
                    if ($next[self::TOKEN_VALUE] === ')') {
                        $inline_parentheses = true;
                        $inline_count = 0;
                        $inline_indented = false;
                        break;
                    }

                    // Reached an invalid token for inline parentheses
                    if ($next[self::TOKEN_VALUE] === ';' || $next[self::TOKEN_VALUE] === '(') {
                        break;
                    }

                    // Reached an invalid token type for inline parentheses
                    if ($next[self::TOKEN_TYPE] === self::TOKEN_TYPE_RESERVED_TOPLEVEL || $next[self::TOKEN_TYPE] === self::TOKEN_TYPE_RESERVED_NEWLINE || $next[self::TOKEN_TYPE] === self::TOKEN_TYPE_COMMENT || $next[self::TOKEN_TYPE] === self::TOKEN_TYPE_BLOCK_COMMENT) {
                        break;
                    }

                    $length += strlen($next[self::TOKEN_VALUE]);
                }

                if ($inline_parentheses && $length > 30) {
                    $increase_block_indent = true;
                    $inline_indented = true;
                    $newline = true;
                }

                // Take out the preceding space unless there was whitespace there in the original query
                if (isset($original_tokens[$token['i'] - 1]) && $original_tokens[$token['i'] - 1][self::TOKEN_TYPE] !== self::TOKEN_TYPE_WHITESPACE) {
                    $return = rtrim($return, ' ');
                }

                if (!$inline_parentheses) {
                    $increase_block_indent = true;
                    // Add a newline after the parentheses
                    $newline = true;
                }
            }

            // Closing parentheses decrease the block indent level
            elseif ($token[self::TOKEN_VALUE] === ')') {
                // Remove whitespace before the closing parentheses
                $return = rtrim($return, ' ');

                $indent_level--;

                // Reset indent level
                while ($j = array_shift($indent_types)) {
                    if ($j === 'special') {
                        $indent_level--;
                    } else {
                        break;
                    }
                }

                if ($indent_level < 0) {
                    // This is an error
                    $indent_level = 0;

                    if ($highlight) {
                        $return .= "\n" . self::highlightError($token[self::TOKEN_VALUE]);
                        continue;
                    }
                }

                // Add a newline before the closing parentheses (if not already added)
                if (!$added_newline) {
                    $return .= "\n" . str_repeat($tab, $indent_level);
                }
            }

            // Top level reserved words start a new line and increase the special indent level
            elseif ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_RESERVED_TOPLEVEL) {
                $increase_special_indent = true;

                // If the last indent type was 'special', decrease the special indent for this round
                reset($indent_types);
                if (current($indent_types) === 'special') {
                    $indent_level--;
                    array_shift($indent_types);
                }

                // Add a newline after the top level reserved word
                $newline = true;
                // Add a newline before the top level reserved word (if not already added)
                if (!$added_newline) {
                    $return .= "\n" . str_repeat($tab, $indent_level);
                }
                // If we already added a newline, redo the indentation since it may be different now
                else {
                    $return = rtrim($return, $tab) . str_repeat($tab, $indent_level);
                }

                // If the token may have extra whitespace
                if (strpos($token[self::TOKEN_VALUE], ' ') !== false || strpos($token[self::TOKEN_VALUE], "\n") !== false || strpos($token[self::TOKEN_VALUE], "\t") !== false) {
                    $highlighted = preg_replace('/\s+/', ' ', $highlighted);
                }
                //if SQL 'LIMIT' clause, start variable to reset newline
                if ($token[self::TOKEN_VALUE] === 'LIMIT' && !$inline_parentheses) {
                    $clause_limit = true;
                }
            }

            // Checks if we are out of the limit clause
            elseif ($clause_limit && $token[self::TOKEN_VALUE] !== "," && $token[self::TOKEN_TYPE] !== self::TOKEN_TYPE_NUMBER && $token[self::TOKEN_TYPE] !== self::TOKEN_TYPE_WHITESPACE) {
                $clause_limit = false;
            }

            // Commas start a new line (unless within inline parentheses or SQL 'LIMIT' clause)
            elseif ($token[self::TOKEN_VALUE] === ',' && !$inline_parentheses) {
                //If the previous TOKEN_VALUE is 'LIMIT', resets new line
                if ($clause_limit === true) {
                    $newline = false;
                    $clause_limit = false;
                }
                // All other cases of commas
                else {
                    $newline = true;
                }
            }

            // Newline reserved words start a new line
            elseif ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_RESERVED_NEWLINE) {
                // Add a newline before the reserved word (if not already added)
                if (!$added_newline) {
                    $return .= "\n" . str_repeat($tab, $indent_level);
                }

                // If the token may have extra whitespace
                if (strpos($token[self::TOKEN_VALUE], ' ') !== false || strpos($token[self::TOKEN_VALUE], "\n") !== false || strpos($token[self::TOKEN_VALUE], "\t") !== false) {
                    $highlighted = preg_replace('/\s+/', ' ', $highlighted);
                }
            }

            // Multiple boundary characters in a row should not have spaces between them (not including parentheses)
            elseif ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_BOUNDARY) {
                if (isset($tokens[$i - 1]) && $tokens[$i - 1][self::TOKEN_TYPE] === self::TOKEN_TYPE_BOUNDARY) {
                    if (isset($original_tokens[$token['i'] - 1]) && $original_tokens[$token['i'] - 1][self::TOKEN_TYPE] !== self::TOKEN_TYPE_WHITESPACE) {
                        $return = rtrim($return, ' ');
                    }
                }
            }

            // If the token shouldn't have a space before it
            if ($token[self::TOKEN_VALUE] === '.' || $token[self::TOKEN_VALUE] === ',' || $token[self::TOKEN_VALUE] === ';') {
                $return = rtrim($return, ' ');
            }

            $return .= $highlighted . ' ';

            // If the token shouldn't have a space after it
            if ($token[self::TOKEN_VALUE] === '(' || $token[self::TOKEN_VALUE] === '.') {
                $return = rtrim($return, ' ');
            }

            // If this is the "-" of a negative number, it shouldn't have a space after it
            if ($token[self::TOKEN_VALUE] === '-' && isset($tokens[$i + 1]) && $tokens[$i + 1][self::TOKEN_TYPE] === self::TOKEN_TYPE_NUMBER && isset($tokens[$i - 1])) {
                $prev = $tokens[$i - 1][self::TOKEN_TYPE];
                if ($prev !== self::TOKEN_TYPE_QUOTE && $prev !== self::TOKEN_TYPE_BACKTICK_QUOTE && $prev !== self::TOKEN_TYPE_WORD && $prev !== self::TOKEN_TYPE_NUMBER) {
                    $return = rtrim($return, ' ');
                }
            }
        }

        // If there are unmatched parentheses
        if ($highlight && array_search('block', $indent_types) !== false) {
            $return .= "\n" . self::highlightError("WARNING: unclosed parentheses or section");
        }

        // Replace tab characters with the configuration tab character
        $return = trim(str_replace("\t", self::$tab, $return));

        if ($highlight) {
            $return = self::output($return);
        }

        return $return;
    }

    /**
     * Add syntax highlighting to a SQL string
     *
     * @param String $string The SQL string
     *
     * @return String The SQL string with HTML styles applied
     */
    public static function highlight($string) {
        $tokens = self::tokenize($string);

        $return = '';

        foreach ($tokens as $token) {
            $return .= self::highlightToken($token);
        }

        return self::output($return);
    }

    /**
     * Split a SQL string into multiple queries.
     * Uses ";" as a query delimiter.
     *
     * @param String $string The SQL string
     *
     * @return Array An array of individual query strings without trailing semicolons
     */
    public static function splitQuery($string) {
        $queries = array();
        $current_query = '';
        $empty = true;

        $tokens = self::tokenize($string);

        foreach ($tokens as $token) {
            // If this is a query separator
            if ($token[self::TOKEN_VALUE] === ';') {
                if (!$empty) {
                    $queries[] = $current_query . ';';
                }
                $current_query = '';
                $empty = true;
                continue;
            }

            // If this is a non-empty character
            if ($token[self::TOKEN_TYPE] !== self::TOKEN_TYPE_WHITESPACE && $token[self::TOKEN_TYPE] !== self::TOKEN_TYPE_COMMENT && $token[self::TOKEN_TYPE] !== self::TOKEN_TYPE_BLOCK_COMMENT) {
                $empty = false;
            }

            $current_query .= $token[self::TOKEN_VALUE];
        }

        if (!$empty) {
            $queries[] = trim($current_query);
        }

        return $queries;
    }

    /**
     * Remove all comments from a SQL string
     *
     * @param String $string The SQL string
     *
     * @return String The SQL string without comments
     */
    public static function removeComments($string) {
        $result = '';

        $tokens = self::tokenize($string);

        foreach ($tokens as $token) {
            // Skip comment tokens
            if ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_COMMENT || $token[self::TOKEN_TYPE] === self::TOKEN_TYPE_BLOCK_COMMENT) {
                continue;
            }

            $result .= $token[self::TOKEN_VALUE];
        }
        $result = self::format($result, false);

        return $result;
    }

    /**
     * Compress a query by collapsing white space and removing comments
     *
     * @param String $string The SQL string
     *
     * @return String The SQL string without comments
     */
    public static function compress($string) {
        $result = '';

        self::$tokens = self::tokenize($string);

        $whitespace = true;
        foreach (self::$tokens as $token) {
            // Skip comment tokens
            if ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_COMMENT || $token[self::TOKEN_TYPE] === self::TOKEN_TYPE_BLOCK_COMMENT) {
                continue;
            }
            // Remove extra whitespace in reserved words (e.g "OUTER     JOIN" becomes "OUTER JOIN")
            elseif ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_RESERVED || $token[self::TOKEN_TYPE] === self::TOKEN_TYPE_RESERVED_NEWLINE || $token[self::TOKEN_TYPE] === self::TOKEN_TYPE_RESERVED_TOPLEVEL) {
                $token[self::TOKEN_VALUE] = strtoupper(preg_replace('/\s+/', ' ', $token[self::TOKEN_VALUE]));
            }

            if ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_WHITESPACE) {
                // If the last token was whitespace, don't add another one
                if ($whitespace) {
                    continue;
                } else {
                    $whitespace = true;
                    // Convert all whitespace to a single space
                    $token[self::TOKEN_VALUE] = ' ';
                }
            } else {
                $whitespace = false;
            }

            $result .= $token[self::TOKEN_VALUE];
        }

        return rtrim($result);
    }

    public static function parseSqlForSpider($string) {
        $result = '';
        self::$tokens = !empty(self::$tokens) ? self::$tokens : self::tokenize($string);
        $whitespace = true;
        $pre_token = [];
        $tmp_sqls = $split_tmp_sql = $split_sql = $bracket = [];
        $child_query_count = $right_bracket = $left_bracket = $parent_tmp_sql = 0;
        $list_child_query_coun = [];
        $split_tmp_sql[$child_query_count] = "";
        $last_keyword = "";
        foreach (self::$tokens as $token) {
            // Skip comment tokens
            if ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_COMMENT || $token[self::TOKEN_TYPE] === self::TOKEN_TYPE_BLOCK_COMMENT) {
                continue;
            }
            // Remove extra whitespace in reserved words (e.g "OUTER JOIN" becomes "OUTER JOIN")
            elseif ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_RESERVED || $token[self::TOKEN_TYPE] === self::TOKEN_TYPE_RESERVED_NEWLINE || $token[self::TOKEN_TYPE] === self::TOKEN_TYPE_RESERVED_TOPLEVEL) {
                $token[self::TOKEN_VALUE] = strtoupper(preg_replace('/\s+/', ' ', $token[self::TOKEN_VALUE]));
            }
            if (self::SPLIT_RESERVED === $token[self::TOKEN_VALUE] && strtolower($last_keyword) !== "union") {
                $child_query_count++;
                array_push($list_child_query_coun, $child_query_count);
                $parent_tmp_sql = $list_child_query_coun[count($list_child_query_coun) - 1];
                $split_tmp_sql[$parent_tmp_sql] = "";
                $bracket[$parent_tmp_sql] = 1;
            } elseif ($left_bracket || $right_bracket) {
                $split_tmp_sql[$parent_tmp_sql] .= $pre_token[self::TOKEN_VALUE];
            }
            if ($token[self::TOKEN_VALUE] === "(") {
                $left_bracket = 1;
                $right_bracket = 0;
                if (!empty($bracket[$parent_tmp_sql])) {
                    $bracket[$parent_tmp_sql] ++;
                }
            } elseif ($token[self::TOKEN_VALUE] === ")") {
                $right_bracket = 1;
                $left_bracket = 0;
                if (!empty($bracket[$parent_tmp_sql])) {
                    $bracket[$parent_tmp_sql] --;
                    if ($bracket[$parent_tmp_sql] == 0) {
                        unset($bracket[$parent_tmp_sql]);
                        $right_bracket = 0;
                        $child_sql_name = self::CHILD_QUERY_NAME . $parent_tmp_sql;
                        $split_sql[$parent_tmp_sql] = $split_tmp_sql[$parent_tmp_sql];
                        unset($split_tmp_sql[$parent_tmp_sql]);
                        array_pop($list_child_query_coun);
                        $parent_tmp_sql = $list_child_query_coun[count($list_child_query_coun) - 1];
                        $bracket[$parent_tmp_sql] --;
                        $split_tmp_sql[$parent_tmp_sql] .= $child_sql_name . " ";
                    }
                }
            } else {
                $left_bracket = $right_bracket = 0;
            }
            if ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_WHITESPACE) {
                // If the last token was whitespace, don't add another one
                if ($whitespace) {
                    continue;
                } else {
                    $whitespace = true;
                    // Convert all whitespace to a single space
                    $token[self::TOKEN_VALUE] = ' ';
                }
            } else {
                $pre_token = $token;
                $whitespace = false;
            }
            if ($token[self::TOKEN_VALUE] !== "(" && $token[self::TOKEN_VALUE] !== ")") {
                $split_tmp_sql[$parent_tmp_sql] .= $token[self::TOKEN_VALUE];
            }
            if ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_RESERVED_TOPLEVEL) {
                $last_keyword = $token[self::TOKEN_VALUE];
            }
        }
        //补全括号
        foreach ($split_tmp_sql as $key => $value) {
            if (!empty($value) && !empty($bracket[$key]) && $right_bracket) {
                $split_tmp_sql[$key] .= ")";
            }
        }
        $split_sql['0'] = $split_tmp_sql[0];
        $split_sql['1'] = $split_tmp_sql[1];
        ksort($split_sql);
        self::$tokens = [];
        return self::getMapReduceQuery($split_sql);
    }

    protected static function getMapReduceQuery(array $split_sql) {
        $reduce_sql = $pkeys = "";
        $map_sqls = $tmp_sqls = $unkeys = [];
        $save_sql = "";
        if (!empty($split_sql[0]) && $save_sql = $split_sql[0]) {
            unset($split_sql[0]);
        }
        $limit__patter = "/(select\s+?.+?)\s+?(from[\w\W]+)(limit[\w\W]+)/i";
        $order__patter = "/(select\s+?.+?)\s+?(from[\w\W]+)(order\s+?by[\w\W]+)/i";
        $group_patter = "/(select\s+?.+?)\s+?(from[\w\W]+)group by((?![^\,]+?(resume_id))[\w\W]+?)\s+?((order|limit|having)*.+)*/i";
        $tablename_patter = "/from\s+(?!`*(" . implode("|", $tables) . "|" . self::CHILD_QUERY_NAME . ")`*)(.+?)(\s|$)|join\s+(?!`*(" . implode("|", $tables) . "|" . self::CHILD_QUERY_NAME . ")`*)(.+?)(\s|$)/i";
        //$clean_table_patter = "/(from\s+?|join\s+?)[^\.\s\,\)\(]+?\.(" . implode("|", $tables) . ")/i";
        //处理特殊函数：count\sum\max\min\avg
        if (empty($split_sql[0])) {
            unset($split_sql[0]);
        }

        for ($ckey = count($split_sql); $ckey > 0; $ckey--) {
            //$child_sql = trim(preg_replace($clean_table_patter, "$1$2", trim($split_sql[$ckey])), ";") . " "; //加一个空格主要是为了正则表达是匹配是否有having|limit等
            $child_sql = trim($split_sql[$ckey]) . " ";
            if (count($split_sql) == 1) {
                $tmp_sqls[1] = $child_sql;
                goto sql_patter;
            }

            //if (($ckey == count($split_sql)) || (!empty($tmp_sqls) && ($patter_keys = array_diff(array_keys($tmp_sqls), $unkeys)))) {
            $patter = "/" . self::CHILD_QUERY_NAME . implode("|" . self::CHILD_QUERY_NAME, $patter_keys) . "/";
            if (!preg_match($patter, $child_sql)) {
                sql_patter:
                /* if (preg_match_all($tablename_patter, $child_sql, $matches)) {
                  if (strpos($child_sql, self::CHILD_QUERY_NAME) !== FALSE) {
                  $resule_map_sql = $tmp_sqls[$ckey + 1];
                  unset($tmp_sqls[$ckey + 1]);
                  $map_table_name = md5(trim($resule_map_sql));
                  $map_sqls[] = $resule_map_sql;
                  $tmp_sqls[$ckey] = preg_replace("/(" . self::CHILD_QUERY_NAME . "(\d+?)\s+)/", "`{$map_table_name}` ", $child_sql);
                  $unkeys[$ckey] = $ckey;
                  continue;
                  } else {
                  //等扩展
                  }
                  } else */
                if (preg_match($group_patter, $child_sql, $matches)) {
                    if (preg_match("/\s+?join\s+/i", $child_sql)) {
                        $reduse_map_sql = $child_sql;
                    } else {
                        //提取查询字段
                        if (preg_match("/[a-zA-z0-9]+?\.\*/", $matches[1], $field_all)) {
                            $fields[] = $field_all[0];
                        } else {
                            preg_match_all("/[a-zA-Z_][^\.,\s\(\)]+?\.[^\.,\s\(\)]+/", $matches[1] . $matches[3], $fields);
                            $fields = array_unique($fields[0]);
                            if (empty($fields)) {
                                $sql_fields = self::get_field_name($matches[1] . $matches[3]);
                                $fields = $sql_fields['fields'];
                            }
                        }
                        $reduse_map_sql = "SELECT " . implode(",", $fields) . " {$matches[2]} ";
                    }
                    $reduse_map_sql = trim(preg_replace_callback("/(" . self::CHILD_QUERY_NAME . "(\d+?)\s+)/", function($matche) use($tmp_sqls) {
                                return self::del_array_key($tmp_sqls, $matche[2]);
                            }, $reduse_map_sql));
                    $map_table_name = md5($reduse_map_sql);
                    $map_sqls[] = $reduse_map_sql;
                    $tmp_sqls[$ckey] = self::handle_reduce_sql($matches[1], "FROM `$map_table_name` GROUP BY", $matches[3] . $matches[5]);
//                        //获取需要添加索引的字段
//                        preg_match("/group\s+?by\s+?(.+)(having|order)*/i", $tmp_sqls[$ckey], $match);
//                        $pkeys = explode(",", trim($match[1]));
                    $unkeys[$ckey] = $ckey;
                    continue;
                } elseif (preg_match($order__patter, $child_sql, $matches)) {
                    $reduse_map_sql = preg_match("/limit/i", $child_sql) ? $child_sql : "{$matches[1]} {$matches[2]}";
                    $reduse_map_sql = trim(preg_replace_callback("/(" . self::CHILD_QUERY_NAME . "(\d+?)\s+)/", function($matche) use($tmp_sqls) {
                                return self::del_array_key($tmp_sqls, $matche[2]);
                            }, $reduse_map_sql));
                    $map_table_name = md5($reduse_map_sql);
                    $map_sqls[] = $reduse_map_sql;
                    $tmp_sqls[$ckey] = self::handle_reduce_sql($matches[1], "FROM `$map_table_name`", $matches[3]);
//                        //获取需要添加索引的字段
//                        preg_match("/order\s+?by\s+?(.+)(limit)*/i", $tmp_sqls[$ckey], $match);
//                        $pkeys = explode(",", trim($match[1]));
                    $unkeys[$ckey] = $ckey;
                    continue;
                } elseif (preg_match($limit__patter, $child_sql, $matches)) {
                    $reduse_map_sql = trim(preg_replace_callback("/(" . self::CHILD_QUERY_NAME . "(\d+?)\s+)/", function($matche) use($tmp_sqls) {
                                return self::del_array_key($tmp_sqls, $matche[2]);
                            }, $reduse_map_sql));
                    $map_table_name = md5($reduse_map_sql);
                    $map_sqls[] = $reduse_map_sql;
                    $tmp_sqls[$ckey] = self::handle_reduce_sql($matches[1], "FROM `$map_table_name`", $matches[3]);
                    $unkeys[$ckey] = $ckey;
                    continue;
                }
                //}
            }

            if (1 == $ckey) {
                if (empty($map_sqls)) {
                    $map_function_sql = "";
                    $function_map_reduce = [];
                    //处理 max/min/count/avg/sum 函数
                    if (preg_match("/(select[\w\W]*?(?=[\s,\(\/\*\-\+\%]+?(sum|avg|count|max|min)\()[\w\W]+?)from([\w\W]+)/i", $child_sql, $match)) {
                        $map_function_sql_from = $match[3];
                        if (preg_match_all("/[\s,\(\/\*\-\+\%]*?((sum|avg|count|max|min)\(([^\)]+?)\))(\s+?(as\s+?[^\s,]+?)[\s,]+?)*/i", $match[1], $matches)) {
                            foreach ($matches[2] as $k => $function) {
                                $function = strtolower($function);
                                $mk_count = substr_count($matches[3][$k], "(");
                                $field_name = $map_field = $matches[3][$k] . str_repeat(")", $mk_count);
                                $matches[1][$k] = $matches[1][$k] . str_repeat(")", $mk_count);
                                if (empty($pre_sql_str)) {
                                    $pre_sql_str = substr($split_sql[1], 0, strpos($split_sql[1], $matches[1][0]));
                                    if ($pre_sql_str[strlen($pre_sql_str) - 1] === "(") {
                                        $pre_sql_str = substr($pre_sql_str, 0, strrpos($pre_sql_str, ",")) . ",";
                                    }
                                    $reduce_pre_sql_str = $match[1];
                                }
                                $field_name = preg_replace("/[^\s,\.\d\(\)]+?\.`*([a-zA-Z0-9_]+)`*|`*([a-zA-Z0-9_]+)`*/", "$1", $field_name);
                                $tmp_field_name = "{$function}_{$field_name}";
                                switch ($function) {
                                    case "count":
                                        $map_function_sql .= ("{$matches[2][$k]}($map_field) AS `{$tmp_field_name}`,");
                                        $as_field = empty(trim($matches[5][$k])) ? " AS `$tmp_field_name`" : "";
                                        $reduce_pre_sql_str = str_replace($matches[1][$k], "SUM(`{$tmp_field_name}`) $as_field", $reduce_pre_sql_str);
                                        break;
                                    case "avg":
                                        $count_as_field_name = "{$field_name}_tmp_count";
                                        $map_function_sql .= ("{$matches[2][$k]}($map_field) AS `$tmp_field_name`,count(`$field_name`) AS `$count_as_field_name`,");
                                        $as_field = empty(trim($matches[5][$k])) ? " AS `$tmp_field_name`" : "";
                                        $reduce_pre_sql_str = str_replace($matches[1][$k], "(SUM(`$tmp_field_name`*`$count_as_field_name`)/SUM(`$count_as_field_name`)) $as_field", $reduce_pre_sql_str);
                                        break;
                                    default:
                                        $map_function_sql .= ("{$matches[2][$k]}($map_field) AS `$tmp_field_name`,");
                                        $as_field = empty(trim($matches[5][$k])) ? " AS `$tmp_field_name`" : "";
                                        $reduce_pre_sql_str = str_replace($matches[1][$k], str_replace($field_name, $tmp_field_name, $matches[1][$k]) . $as_field, $reduce_pre_sql_str);
                                        break;
                                }
                            }
                        }
                        $reduce_pre_sql_str = preg_replace("/([,\s\(]+?)[^\s,\.\d\(\)]+?\./", "$1", $reduce_pre_sql_str);
                        $map_function_sql = $pre_sql_str . trim($map_function_sql, ",") . " FROM" . $map_function_sql_from;
                        if (preg_match_all("/(" . self::CHILD_QUERY_NAME . "(\d+?)\s*?)/", $map_function_sql, $matches)) {
                            foreach ($matches[1] as $key => $value) {
                                $child_key = $matches[2][$key];
                                $tmp_sqls[$ckey] = str_replace($value, "({$tmp_sqls[$child_key]}) ", (!empty($tmp_sqls[$ckey]) ? $tmp_sqls[$ckey] : $map_function_sql));
                                unset($tmp_sqls[$child_key]);
                                if (!empty($unkeys[$child_key])) {
                                    $unkeys[$ckey] = $ckey;
                                    unset($unkeys[$child_key]);
                                }
                            }
                        } else {
                            $tmp_sqls[$ckey] = $map_function_sql;
                        }
                        $map_table_name = md5(trim($tmp_sqls[$ckey]));
                        $map_sqls[] = $tmp_sqls[$ckey];
                        $tmp_sqls[$ckey] = $reduce_pre_sql_str . " FROM `{$map_table_name}`";

                        unset($pre_sql_str);
                        unset($map_function_sql);
                        unset($fields);
                        continue;
                    }
                }
            }

            if (preg_match_all("/(" . self::CHILD_QUERY_NAME . "(\d+?)\s*?)/", $child_sql, $matches)) {
                foreach ($matches[1] as $key => $value) {
                    $child_key = $matches[2][$key];
                    $tmp_sqls[$ckey] = str_replace($value, "({$tmp_sqls[$child_key]}) ", (!empty($tmp_sqls[$ckey]) ? $tmp_sqls[$ckey] : $child_sql));
                    unset($tmp_sqls[$child_key]);
                    if (!empty($unkeys[$child_key])) {
                        $unkeys[$ckey] = $ckey;
                        unset($unkeys[$child_key]);
                    }
                }
            } else {
                $tmp_sqls[$ckey] = $child_sql;
            }
        }
        if (empty($map_sqls)) {
            if ($save_sql) {
                $map_sql = "$save_sql {$tmp_sqls[1]}";
            } else {
                $map_sql = $tmp_sqls[1];
            }
            $map_sqls[] = $map_sql;
        } else {
            $reduce_sql = $tmp_sqls[1];
            if ($save_sql) {
                $reduce_sql = "$save_sql $reduce_sql";
            }
        }
        return ["reduce_sql" => $reduce_sql, "map_sqls" => $map_sqls, "reduce_pkey" => $pkeys];
    }

    private static function handle_reduce_sql($select, $from, $other) {
        $reduce_sql = "";
        if (strpos($select, ".*") !== false) {
            $select = "SELECT * ";
        }
        $reduce_sql = "$select $from $other";
        $reduce_sql = preg_replace("/([,\s\(]+?)[a-zA-Z_]{1}[^,\s\(\)]+?\./", "$1", $reduce_sql);
        //$reduce_sql = preg_replace("/([,\s]+?)[^,\s\d\(\)]+?\s+?(?=as)as\s+/i", "$1", $reduce_sql);
        return $reduce_sql;
    }

    private static function del_array_key(&$tmp_sqls, $key) {
        $retrun = "(" . $tmp_sqls[$key] . ") ";
        unset($tmp_sqls[$key]);
        return $retrun;
    }

    /**
     * Highlights a token depending on its type.
     *
     * @param Array $token An associative array containing type and value.
     *
     * @return String HTML code of the highlighted token.
     */
    protected static function highlightToken($token) {
        $type = $token[self::TOKEN_TYPE];

        if (self::is_cli()) {
            $token = $token[self::TOKEN_VALUE];
        } else {
            if (defined('ENT_IGNORE')) {
                $token = htmlentities($token[self::TOKEN_VALUE], ENT_COMPAT | ENT_IGNORE, 'UTF-8');
            } else {
                $token = htmlentities($token[self::TOKEN_VALUE], ENT_COMPAT, 'UTF-8');
            }
        }

        if ($type === self::TOKEN_TYPE_BOUNDARY) {
            return self::highlightBoundary($token);
        } elseif ($type === self::TOKEN_TYPE_WORD) {
            return self::highlightWord($token);
        } elseif ($type === self::TOKEN_TYPE_BACKTICK_QUOTE) {
            return self::highlightBacktickQuote($token);
        } elseif ($type === self::TOKEN_TYPE_QUOTE) {
            return self::highlightQuote($token);
        } elseif ($type === self::TOKEN_TYPE_RESERVED) {
            return self::highlightReservedWord($token);
        } elseif ($type === self::TOKEN_TYPE_RESERVED_TOPLEVEL) {
            return self::highlightReservedWord($token);
        } elseif ($type === self::TOKEN_TYPE_RESERVED_NEWLINE) {
            return self::highlightReservedWord($token);
        } elseif ($type === self::TOKEN_TYPE_NUMBER) {
            return self::highlightNumber($token);
        } elseif ($type === self::TOKEN_TYPE_VARIABLE) {
            return self::highlightVariable($token);
        } elseif ($type === self::TOKEN_TYPE_COMMENT || $type === self::TOKEN_TYPE_BLOCK_COMMENT) {
            return self::highlightComment($token);
        }

        return $token;
    }

    /**
     * Highlights a quoted string
     *
     * @param String $value The token's value
     *
     * @return String HTML code of the highlighted token.
     */
    protected static function highlightQuote($value) {
        if (self::is_cli()) {
            return self::$cli_quote . $value . "\x1b[0m";
        } else {
            return '<span ' . self::$quote_attributes . '>' . $value . '</span>';
        }
    }

    /**
     * Highlights a backtick quoted string
     *
     * @param String $value The token's value
     *
     * @return String HTML code of the highlighted token.
     */
    protected static function highlightBacktickQuote($value) {
        if (self::is_cli()) {
            return self::$cli_backtick_quote . $value . "\x1b[0m";
        } else {
            return '<span ' . self::$backtick_quote_attributes . '>' . $value . '</span>';
        }
    }

    /**
     * Highlights a reserved word
     *
     * @param String $value The token's value
     *
     * @return String HTML code of the highlighted token.
     */
    protected static function highlightReservedWord($value) {
        if (self::is_cli()) {
            return self::$cli_reserved . $value . "\x1b[0m";
        } else {
            return '<span ' . self::$reserved_attributes . '>' . $value . '</span>';
        }
    }

    /**
     * Highlights a boundary token
     *
     * @param String $value The token's value
     *
     * @return String HTML code of the highlighted token.
     */
    protected static function highlightBoundary($value) {
        if ($value === '(' || $value === ')')
            return $value;

        if (self::is_cli()) {
            return self::$cli_boundary . $value . "\x1b[0m";
        } else {
            return '<span ' . self::$boundary_attributes . '>' . $value . '</span>';
        }
    }

    /**
     * Highlights a number
     *
     * @param String $value The token's value
     *
     * @return String HTML code of the highlighted token.
     */
    protected static function highlightNumber($value) {
        if (self::is_cli()) {
            return self::$cli_number . $value . "\x1b[0m";
        } else {
            return '<span ' . self::$number_attributes . '>' . $value . '</span>';
        }
    }

    /**
     * Highlights an error
     *
     * @param String $value The token's value
     *
     * @return String HTML code of the highlighted token.
     */
    protected static function highlightError($value) {
        if (self::is_cli()) {
            return self::$cli_error . $value . "\x1b[0m";
        } else {
            return '<span ' . self::$error_attributes . '>' . $value . '</span>';
        }
    }

    /**
     * Highlights a comment
     *
     * @param String $value The token's value
     *
     * @return String HTML code of the highlighted token.
     */
    protected static function highlightComment($value) {
        if (self::is_cli()) {
            return self::$cli_comment . $value . "\x1b[0m";
        } else {
            return '<span ' . self::$comment_attributes . '>' . $value . '</span>';
        }
    }

    /**
     * Highlights a word token
     *
     * @param String $value The token's value
     *
     * @return String HTML code of the highlighted token.
     */
    protected static function highlightWord($value) {
        if (self::is_cli()) {
            return self::$cli_word . $value . "\x1b[0m";
        } else {
            return '<span ' . self::$word_attributes . '>' . $value . '</span>';
        }
    }

    /**
     * Highlights a variable token
     *
     * @param String $value The token's value
     *
     * @return String HTML code of the highlighted token.
     */
    protected static function highlightVariable($value) {
        if (self::is_cli()) {
            return self::$cli_variable . $value . "\x1b[0m";
        } else {
            return '<span ' . self::$variable_attributes . '>' . $value . '</span>';
        }
    }

    /**
     * Helper function for building regular expressions for reserved words and boundary characters
     *
     * @param String $a The string to be quoted
     *
     * @return String The quoted string
     */
    private static function quote_regex($a) {
        return preg_quote($a, '/');
    }

    /**
     * Helper function for building string output
     *
     * @param String $string The string to be quoted
     *
     * @return String The quoted string
     */
    private static function output($string) {
        if (self::is_cli()) {
            return $string . "\n";
        } else {
            $string = trim($string);
            if (!self::$use_pre) {
                return $string;
            }

            return '<pre ' . self::$pre_attributes . '>' . $string . '</pre>';
        }
    }

    private static function is_cli() {
        if (isset(self::$cli))
            return self::$cli;
        else
            return php_sapi_name() === 'cli';
    }

    //获取sql里面要查询的所有的表对应的字段名
    public static function get_field_name($sql) {
        //首先找出表名和别名
        preg_match_all("/\s+?from\s+?(`*([^\s]+?)`*\.)*`*([^\s]+?)`*(\s+?as\s+?([^\s]+?))*\s+?|\s+?join\s+?(`*([^\s]+?)`*\.)*`*([^\s]+?)`*(\s+?as\s+?([^\s]+?))*\s+?/i", $sql, $matches);

        //return $matches;
        $dbs = array_filter($matches[2]) + array_filter($matches[7]);
        $tables = array_filter($matches[3]) + array_filter($matches[8]);
        $table_alias = array_filter($matches[5]) + array_filter($matches[10]);
        $fields = array();
        if (!empty($table_alias)) {
            foreach ($table_alias as $key => $alias) {
                preg_match_all("/{$alias}\.`*([^\s\,\=\.]+?)`*[\s\,\=\.]+?/", $sql, $matches);
                $fields[$key] = array_unique($matches[1]);
            }
        } else {
            //去掉AS及别名
            $sql = " " . preg_replace("/as\s+[^\s,\)]+?[\s,\(]+?/i", "", $sql);
            $partter = "/[^a-zA-Z_]" . $tables[0] . "(?![a-zA-Z_])|[^a-zA-Z_]" . implode("(?![a-zA-Z_])|[^a-zA-Z_]", self::$reserved)
                    . "(?![a-zA-Z_])|[^a-zA-Z_]" . implode("(?![a-zA-Z_])|[^a-zA-Z_]", self::$functions) . "(?![a-zA-Z_])|[^a-zA-Z_]"
                    . implode("(?![a-zA-Z_])|[^a-zA-Z_]", self::$reserved_newline) . "(?![a-zA-Z_])|[^a-zA-Z_]"
                    . implode("(?![a-zA-Z_])|[^a-zA-Z_]", self::$reserved_toplevel) . "|[^a-zA-Z_]\d+(?!a-zA-Z_)/i";
            $field_string = preg_replace($partter, "", $sql);
            $field_string = preg_replace("/`|\\" . implode("|\\", self::$boundaries) . "/", " ", $field_string);
            $fields[0] = preg_split("/\s+/", trim($field_string));
            $fields = array_unique($fields[0]);
        }

        return array("dbs" => $dbs, "tables" => $tables, "fields" => $fields);
    }

}
