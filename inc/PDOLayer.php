<?php

/*  Poweradmin, a friendly web-based admin tool for PowerDNS.
 *  See <https://www.poweradmin.org> for more details.
 *
 *  Copyright 2007-2010 Rejo Zenger <rejo@zenger.nl>
 *  Copyright 2010-2023 Poweradmin Development Team
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * PDO DB access layer
 *
 * @package     Poweradmin
 * @copyright   2007-2010 Rejo Zenger <rejo@zenger.nl>
 * @copyright   2010-2023 Poweradmin Development Team
 * @license     https://opensource.org/licenses/GPL-3.0 GPL
 */
require_once "PDOCommon.php";

/**
 * PDO access layer
 */
class PDOLayer extends PDOCommon {

    /**
     * Enables/disables debugging
     * @var boolean
     */
    private $debug = false;

    /**
     * Internal storage for queries
     * @var array
     */
    private $queries = array();

    /**
     * Quotes a string
     *
     * @param string $string
     * @param string $paramtype
     * @return string Returns quoted string
     */
    public function quote($string, $paramtype = NULL): string
    {
        if ($paramtype == 'integer') {
            $paramtype = PDO::PARAM_INT;
        } elseif ($paramtype == 'text') {
            $paramtype = PDO::PARAM_STR;
        }
        return parent::quote($string, $paramtype);
    }

    /**
     * Set execution options
     *
     * @param string $option Option name
     * @param int $value Option value
     */
    public function setOption($option, $value) {
        if ($option == 'debug' && $value == 1) {
            $this->debug = true;
        }
    }

    /**
     * Return executed queries
     *
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /**
     * Executes SQL query
     *
     * @param string $str SQL query
     * @param int|null $fetchMode
     * @param mixed ...$fetchModeArgs
     * @return PDOStatement
     */
    public function query($str, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement {
        if ($this->debug) {
            $this->queries[] = $str;
        }

        return parent::query($str);
    }

    /**
     * Dummy method
     */
    public function disconnect() {

    }

    public function executeMultiple($stmt, $params) {
        foreach ($params as $values) {
            $stmt->execute($values);
        }
    }

    /**
     * List all tables in the current database
     */
    public function listTables() {
        $tables = array();
        $db_type = $this->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($db_type == 'mysql') {
            $query = 'SHOW TABLES';
        } elseif ($db_type == 'pgsql') {
            $query = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'";
        } elseif ($db_type == 'sqlite') {
            $query = "SELECT name FROM sqlite_master WHERE type='table'";
        } else {
            die(_('Unknown database type.'));
        }

        $result = $this->query($query);
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        return $tables;
    }

    /**
     * Create a new table
     *
     * @param string $name Name of the table that should be created
     * @param mixed[] $fields Associative array that contains the definition of each field of the new table
     * @param mixed[] $options An associative array of table options
     */
    public function createTable($name, $fields, $options = array()) {
        $db_type = $this->getAttribute(PDO::ATTR_DRIVER_NAME);
        $query_fields = array();

        foreach ($fields as $key => $arr) {
            if ($arr['type'] == 'text' and isset($arr['length'])) {
                $arr['type'] = 'VARCHAR';
            }

            if ($db_type == 'pgsql' && isset($arr['autoincrement'])) {
                $line = $key . ' SERIAL';
            } elseif ($db_type == 'pgsql' && $arr['type'] == 'integer') {
                $line = $key . ' ' . $arr['type'];
            } else {
                $line = $key . ' ' . $arr['type'] . (isset($arr['length']) ? '(' . $arr['length'] . ')' : '');
            }

            if ($arr['notnull'] && $db_type != 'pgsql' && !isset($arr['autoincrement'])) {
                $line .= ' NOT NULL';
            }

            if ($db_type == 'mysql' && isset($arr['autoincrement'])) {
                $line .= ' AUTO_INCREMENT';
            }

            if ($arr['flags'] == 'primary_keynot_null') {
                $line .= ' PRIMARY KEY';
            }

            if (isset($arr['default']) && $arr['default'] != '0') {
                $line .= ' DEFAULT ' . $arr['default'];
            }

            $query_fields[] = $line;
        }

        $query = "CREATE TABLE $name (" . implode(', ', $query_fields) . ')';

        if ($db_type == 'mysql') {
            if (isset($options['type'])) {
                $query .= ' ENGINE=' . $options['type'];
            }

            if (isset($options['charset'])) {
                $query .= ' DEFAULT CHARSET=' . $options['charset'];
            }

            if (isset($options['collation'])) {
                $query .= ' COLLATE=' . $options['collation'];
            }
        }

        $this->exec($query);
    }

    /**
     * Drop an existing table
     *
     * @param string $name name of the table that should be dropped
     */
    public function dropTable($name) {
        $query = "DROP TABLE $name";
        $this->exec($query);
    }

}
