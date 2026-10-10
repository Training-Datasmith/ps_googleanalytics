<?php

namespace Tests\Support {

class Database
{
    /** @var \PDO */
    private static $pdo;

    public static function connectFromEnv()
    {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $name = getenv('DB_NAME') ?: 'ganalytics';
        $user = getenv('DB_USER') ?: 'ganalytics';
        $pass = getenv('DB_PASSWORD') ?: 'ganalytics';

        self::$pdo = new \PDO(
            "mysql:host={$host};dbname={$name};charset=utf8",
            $user,
            $pass,
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]
        );
        $db = new \Db(self::$pdo);
        \Db::setInstance($db);
    }

    public static function pdo()
    {
        return self::$pdo;
    }

    public static function resetModuleTables()
    {
        $prefix = _DB_PREFIX_;
        self::$pdo->exec("DROP TABLE IF EXISTS `{$prefix}ganalytics_data`");
        self::$pdo->exec("DROP TABLE IF EXISTS `{$prefix}ganalytics`");
    }

    public static function truncateModuleTables()
    {
        $prefix = _DB_PREFIX_;
        foreach (['ganalytics_data', 'ganalytics'] as $table) {
            $like = $prefix . $table;
            $exists = self::$pdo->query('SHOW TABLES LIKE ' . self::$pdo->quote($like))->fetch();
            if ($exists) {
                self::$pdo->exec("TRUNCATE TABLE `{$like}`");
            }
        }
    }
}

}

namespace {

class Db
{
    const INSERT = 1;
    const INSERT_IGNORE = 4;

    /** @var Db */
    private static $instance;

    /** @var \PDO */
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public static function setInstance(Db $db)
    {
        self::$instance = $db;
    }

    public static function getInstance()
    {
        return self::$instance;
    }

    public function _escape($str)
    {
        if ($str === null) {
            return '';
        }

        return substr($this->pdo->quote((string) $str), 1, -1);
    }

    public function escape($string, $html_ok = false, $bq_sql = false)
    {
        if (is_numeric($string)) {
            return $string;
        }
        $string = $this->_escape($string);
        if (!$html_ok) {
            $string = strip_tags(nl2br($string));
        }
        if ($bq_sql === true) {
            $string = str_replace('`', '\`', $string);
        }

        return $string;
    }

    public function execute($sql)
    {
        return $this->pdo->exec($sql) !== false;
    }

    public function executeS($sql)
    {
        $stmt = $this->pdo->query($sql);
        if ($stmt === false) {
            return false;
        }

        return $stmt->fetchAll();
    }

    public function getValue($sql)
    {
        $stmt = $this->pdo->query($sql);
        if ($stmt === false) {
            return false;
        }
        $row = $stmt->fetch(\PDO::FETCH_NUM);

        return $row ? $row[0] : false;
    }

    public function insert($table, $data, $null_values = false, $use_cache = true, $type = self::INSERT)
    {
        $prefix = _DB_PREFIX_;
        $cols = [];
        $vals = [];
        foreach ($data as $k => $v) {
            $cols[] = '`' . $k . '`';
            if (is_array($v) && isset($v['type']) && $v['type'] === 'sql') {
                $vals[] = $v['value'];
            } else {
                $vals[] = $this->pdo->quote($v);
            }
        }
        $ignore = ($type === self::INSERT_IGNORE) ? 'IGNORE ' : '';
        $sql = 'INSERT ' . $ignore . 'INTO `' . $prefix . $table . '` (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ')';

        return $this->execute($sql);
    }

    public function update($table, $data, $where, $limit = 0)
    {
        $prefix = _DB_PREFIX_;
        $sets = [];
        foreach ($data as $k => $v) {
            if (is_array($v) && isset($v['type']) && $v['type'] === 'sql') {
                $sets[] = '`' . $k . '` = ' . $v['value'];
            } else {
                $sets[] = '`' . $k . '` = ' . $this->pdo->quote($v);
            }
        }
        $sql = 'UPDATE `' . $prefix . $table . '` SET ' . implode(', ', $sets) . ' WHERE ' . $where;
        if ($limit) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        return $this->execute($sql);
    }

    public function delete($table, $where)
    {
        $prefix = _DB_PREFIX_;
        $sql = 'DELETE FROM `' . $prefix . $table . '` WHERE ' . $where;

        return $this->execute($sql);
    }
}

function pSQL($string, $htmlOK = false)
{
    return Db::getInstance()->escape($string, $htmlOK);
}

}
