<?php
/*
 * Project         : b1gMail backend for Z-Push
 * File            : sqlite3.php
 * Description     : Wrapper to use PDO's SQLite driver in the same way as the SQLite3 extension.
 * Created         : 05.02.2017
 *
 * Copyright (C) 2017 Patrick Schlangen <ps@b1g.de>, 2022 b1gMail.eu
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

define('SQLITE3_ASSOC',			0);
define('SQLITE3_NUM',			1);
define('SQLITE3_BOTH',			2);

define('SQLITE3_INTEGER',		0);
define('SQLITE3_FLOAT',			1);
define('SQLITE3_TEXT',			2);
define('SQLITE3_BLOB',			3);
define('SQLITE3_NULL',			4);

/**
 * SQLite3Stmt wrapper for PDO.
 */
class SQLite3Stmt
{
    private $stmt = null;

    public function __construct($stmt)
    {
        $this->stmt = $stmt;
    }

    public function bindValue($variable, $value, $dataType = SQLITE3_TEXT)
    {
        $dataTypeMap = [
            SQLITE3_INTEGER => PDO::PARAM_INT,
            SQLITE3_FLOAT => PDO::PARAM_STR,
            SQLITE3_TEXT => PDO::PARAM_STR,
            SQLITE3_BLOB => PDO::PARAM_LOB,
            SQLITE3_NULL => PDO::PARAM_NULL,
        ];
        $this->stmt->bindValue($variable, $value, $dataTypeMap[$dataType]);
    }

    public function execute()
    {
        try {
            $this->stmt->execute();

            return new SQLite3Result($this->stmt);
        } catch (PDOException $ex) {
            return false;
        }
    }
}

/**
 * SQLite3Result wrapper for PDO.
 */
class SQLite3Result
{
    private $res = null;

    public function __construct($res)
    {
        $this->res = $res;
    }

    public function fetchArray($mode = SQLITE3_BOTH)
    {
        $fetchModeMap = [
            SQLITE3_ASSOC => PDO::FETCH_ASSOC,
            SQLITE3_NUM => PDO::FETCH_NUM,
            SQLITE3_BOTH => PDO::FETCH_BOTH,
        ];

        return $this->res->fetch($fetchModeMap[$mode]);
    }

    public function finalize()
    {
        $this->res->closeCursor();
    }
}

/**
 * SQLite3 wrapper for PDO.
 */
class SQLite3
{
    private $filename;
    private $pdo = null;

    public function __construct($filename)
    {
        $this->filename = $filename;

        $this->pdo = new PDO('sqlite:'.$filename);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function close()
    {
        $this->pdo = null;
    }

    public function busyTimeout($timeout)
    {
        $this->pdo->setAttribute(PDO::ATTR_TIMEOUT, $timeout / 1000);
    }

    public function query($q)
    {
        try {
            $res = $this->pdo->query($q);

            return new SQLite3Result($res);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function prepare($q)
    {
        try {
            $res = $this->pdo->prepare($q);

            return new SQLite3Stmt($res);
        } catch (PDOException $e) {
            return $e;
        }
    }

    public function escapeString($str)
    {
        return substr($this->pdo->quote($str), 1, -1);
    }

    public function lastInsertRowID()
    {
        return $this->pdo->lastInsertId();
    }
}
