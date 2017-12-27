<?php
/**
 * Created by PhpStorm.
 * User: lz
 * Date: 11/19/17
 * Time: 10:57 AM
 */

namespace Liz\Console\ModelConsoleHelper;


use Liz\Console\ModelConsole;

class DatabaseHelper extends AbstractHelper
{

    protected $db;

    protected $pdo;

    public function __construct(ModelConsole $console)
    {
        $this->db = $console->getDb();
        $this->pdo= $this->installPDO();
    }

    public function installPDO(){
        $db = $this->db;
        $pdo = new \PDO("mysql:host=" . $db['host'] .
            ";charset=". $db['charset'].";collate=". $db['collate'],
            $db['user'], $db['pass'], ['port'=>$db['port']]);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        return $pdo;
    }

    public function create(){

        try{
            $sql = "create database {$this->db['dbname']} DEFAULT CHARSET {$this->db['charset']} COLLATE {$this->db['collate']};";

            $sth = $this->pdo->prepare($sql);
            $this->addMessage($this->dump($sql));
            $sth->execute();
        }catch (\Exception $exception){
            $this->addError($exception->getCode(), $exception->getMessage())
                ->addError(self::$errorException, $exception->getTraceAsString());

        }
    }

    public function drop(){
        try{
            $sql = "drop database {$this->db['dbname']};";

            $sth = $this->pdo->prepare($sql);
            $this->addMessage($this->dump($sql));
            $sth->execute();
        }catch (\Exception $exception){
            $this->addError($exception->getCode(), $exception->getMessage())
                ->addError(self::$errorException, $exception->getTraceAsString());

        }
    }


    protected function dump($sql){
        return "exec sql: {$sql} \n";
    }

}