<?php
/**
 * Open Labyrinth [ http://www.openlabyrinth.ca ]
 *
 * Open Labyrinth is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Open Labyrinth is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Open Labyrinth.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright Copyright 2012 Open Labyrinth. All Rights Reserved.
 *
 */
defined('SYSPATH') or die('No direct script access.');

class DatabaseBackup
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Backup the whole database or just some tables
     * Use '*' for the whole database or 'table1, table2, table3'
     * @param string $tables
     */
    public function backupTables($tables = '*')
    {
        set_time_limit(0);
        try {
            if ($tables == '*') {
                $tables = array();
                $result = $this->pdo->query('SHOW TABLES');
                while ($row = $result->fetch(PDO::FETCH_NUM)) {
                    $tables[] = $row[0];
                }
            } else {
                $tables = is_array($tables) ? $tables : explode(',', $tables);
            }

            $sql = 'SET FOREIGN_KEY_CHECKS = 0;' . "\n\n";

            foreach ($tables as $table) {
                $result = $this->pdo->query('SELECT * FROM `' . $table . '`');
    		if (!$result) {
        		// Handle the error
        		die('Error fetching data from table ' . $table);
    		}
                $numFields = $result->columnCount();

                $row2 = $this->pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch(PDO::FETCH_NUM);
    		if (!$row2) {
        		// Handle the error
        		die('Error fetching CREATE TABLE statement for table ' . $table);
    		}
                $sql .= "\n" . $row2[1] . ";\n";

                while ($row = $result->fetch(PDO::FETCH_NUM)) {
                    $sql .= 'INSERT INTO `' . $table . '` VALUES(';
                    for ($j = 0; $j < $numFields; $j++) {
                        $row[$j] = addslashes($row[$j]);
                        $row[$j] = str_replace("\n", "\\n", $row[$j]);
                        if (isset($row[$j])) {
                            $sql .= '"' . $row[$j] . '"';
                        } else {
                            $sql .= '""';
                        }

                        if ($j < ($numFields - 1)) {
                            $sql .= ',';
                        }
                    }

                    $sql .= ");\n";
                }
            }
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }

        return $sql;
    }

    /**
     * Save SQL to file
     * @param string $sql
     * @param string $outputDir
     */
    public function saveFile($sql, $outputDir = '.')
    {
        if (empty($sql)) return false;

        try {
            $fileName = $outputDir . '/db-backup-' . $this->pdo->query('SELECT DATABASE()')->fetchColumn() . '-' . date("Ymd-His", time()) . '.sql';
            return (file_put_contents($fileName, $sql) !== false);
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }
}
