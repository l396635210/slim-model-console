<?php
/**
 * Created by PhpStorm.
 * User: lz
 * Date: 11/19/17
 * Time: 2:52 PM
 */

namespace Liz\Console\ModelConsoleHelper;


use Liz\Console\ModelConsole;

class TableHelper extends AbstractHelper
{

    use CommonTrait;

    protected $db;

    protected $pdo;

    protected $tablesInSchema;

    public function __construct(ModelConsole $console)
    {
        $this->db = $console->getDb();
        $this->modelConsole = $console;
        $this->pdo = $this->installPDO();
    }

    public function installPDO(){
        $db = $this->db;
        $pdo = new \PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'].
            ";charset=". $db['charset'].";collate=". $db['collate'],
            $db['user'], $db['pass'], ['port'=>$db['port']]);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        return $pdo;
    }

    protected function isTableExists($table=null){
        if(!$this->tablesInSchema){
            $sql = "show tables;";
            $sth = $this->pdo->prepare($sql);
            $sth->execute();
            $res = $sth->fetchAll();
            $this->tablesInSchema = array_column($res, "Tables_in_".$this->db['dbname']);
        }
        return in_array($table, $this->tablesInSchema);
    }

    /**
     *
     */
    public function update(){
        if(!isset($_SERVER['argv'][2])){
            $this->addError(self::$errorMissParam, $this->getMissParamMessage());
        }
        if(!$this->isParamValid()){
            $this->addError(self::$errorParam, $this->getErrorParamMessage());
        }

        if($this->isValid()){
            $relations = $this->specialRelations ? $this->specialRelations : $this->modelConsole->getRelations();
            foreach ($relations[$this->bundle] as $relation){
                $table = $relation['table'];
                $fields = $relation['fields'];

                if($this->isTableExists($table)){
                    $this->updateTable($table, $fields);
                }else{
                    $this->createTable($table, $fields);
                }
                if(isset($relation['mapping']) && $relation['mapping']){
                    $this->mappingTable($relation);
                }
            }
            $this->addFinishMessage();
        }
    }

    protected function mappingTable($relation){
        foreach ($relation['mapping'] as $item){
            switch ($item['what']){
                case 'many-to-many':
                    $this->createManyToMany($item, $relation);
                    break;
            }

        }
    }

    protected function createManyToMany($mapping, $relation){
        list($bundle, $_model) = explode('.', $mapping['which']);
        $_table = $relation['table'];
        $tableInfo = [
            $_table, $_model,
        ];
        asort($tableInfo);
        $table = implode("_", $tableInfo);
        if(!$this->isTableExists($table)){
            $this->createTable($table,[
                $_table.'_id' => 'int not null',
                $_model.'_id' => 'int not null',
            ]);
        }
    }

    protected function createTable($table, array $fields){
        $sqlStart = "CREATE TABLE IF NOT EXISTS {$table} (\n";
        $sqlBody = "id int(11) unsigned NOT NULL AUTO_INCREMENT,\n";
        $sqlEnd = ") ENGINE=InnoDB DEFAULT CHARSET={$this->db['charset']} COLLATE={$this->db['collate']};\n";
        foreach ($fields as $field=>$desc){
            $desc = strpos($desc, ',') ? substr(trim($desc), 0, -1) : trim($desc);
            $sqlBody .= "{$field} $desc,\n";
        }
        $sqlBody .= "PRIMARY KEY (id)\n";
        $sql = $sqlStart.$sqlBody.$sqlEnd;
        $this->execSQLAndAddMessage($sql);
    }

    protected function updateTable($table, array $fields){
        $columnInfo = $this->fetchColumnInfoByTable($table);
        $addColumns = array_diff_key($fields, $columnInfo);
        if($addColumns){
            $this->addColumns($table, $addColumns);
        }
        $dropColumns = array_diff_key($columnInfo, $fields);
        if($dropColumns){
            $this->dropColumns($table, $dropColumns);
        }
        $modifyColumns = array_diff($fields, $columnInfo);
        if($modifyColumns){
            $this->modifyColumns($table, $modifyColumns);
        }
    }

    protected function fetchColumnInfoByTable($table){
        $info = [];
        $sql = "SELECT COLUMN_NAME AS name, COLUMN_TYPE AS type, IS_NULLABLE AS nullable, 
                  COLUMN_DEFAULT as default_value, COLUMN_COMMENT AS comment
                  FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = '{$this->db['dbname']}' AND TABLE_NAME = '{$table}' AND COLUMN_NAME<>'id';";
        $sth = $this->pdo->prepare($sql);
        $sth->execute();
        
        $fieldsInSchema = $sth->fetchAll();

        foreach ($fieldsInSchema as $item){
            $nullable = $item['nullable'] == 'NO' ? 'not null' : 'default null';
            $default = $item['default_value'] ? " default {$item['default_value']} " : ' ';
            $comment = trim($item['comment']) ? "comment '{$item['comment']}'" : " ";
            $item['type'] = strstr($item['type'], 'int') ? explode("(", $item['type'])[0] : $item['type'];
            $info[$item['name']] = "{$item['type']} {$nullable}{$default}{$comment}";
        }
        return $info;
    }

    protected function addColumns($table, $columns){
        foreach ($columns as $column=>$desc){
            $sql = "ALTER TABLE {$table} ADD {$column} {$desc}";
            $this->execSQLAndAddMessage($sql);
        }
    }

    protected function dropColumns($table, $columns){
        foreach ($columns as $column=>$desc){
            $sql = "ALTER TABLE {$table} DROP {$column}";
            $this->execSQLAndAddMessage($sql);
        }
    }

    protected function modifyColumns($table, $columns){
        foreach ($columns as $column=>$desc){
            $desc = strpos($desc, ',') ? substr(trim($desc), 0, -1) : trim($desc);
            $sql = "ALTER TABLE {$table} MODIFY {$column} {$desc}";
            $this->execSQLAndAddMessage($sql);
        }
    }

    /**
     * @param $sql
     * @return \PDOStatement
     */
    protected function execSQLAndAddMessage($sql){

        $sth = $this->pdo->prepare($sql);
        $sth->execute();
        $this->addMessage("EXEC SQL: {$sql}");
        return $sth;
    }
}