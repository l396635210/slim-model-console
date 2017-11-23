<?php
/**
 * Created by PhpStorm.
 * User: lz
 * Date: 11/19/17
 * Time: 10:11 AM
 */

namespace Liz\Console;


use Liz\Console\ModelConsoleHelper\DatabaseHelper;
use Liz\Console\ModelConsoleHelper\ModelHelper;
use Liz\Console\ModelConsoleHelper\ModelHelperInterface;
use Liz\Console\ModelConsoleHelper\TableHelper;

class ModelConsole
{

    /**
     * @var array
     */
    protected $db;

    /**
     * @var array
     */
    protected $relations;

    /**
     * @var array
     */
    protected $mapping;

    /**
     * @var string
     */
    protected $appsPath;


    protected $helpers = [];

    /**
     * @return mixed
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * @return mixed
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * @return array
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * @return string
     */
    public function getAppsPath()
    {
        return $this->appsPath;
    }


    /**
     * @return array
     */
    public function getHelpers()
    {
        return $this->helpers;
    }

    /**
     * @param $key
     * @return ModelHelperInterface
     */
    public function getHelper($key){
        return $this->helpers[$key];
    }


    /**
     * @param $key
     * @param ModelHelperInterface $helper
     * @return $this
     */
    public function addHelper($key, ModelHelperInterface $helper)
    {
        $this->helpers[$key] = $helper;
        return $this;
    }


    /**
     * ModelConsole constructor.
     * @param $orm array
     */
    public function __construct($orm)
    {
        $db = $orm['db'];
        $relations = $orm['relations'];
        $mapping = isset($orm['mapping'])&&$orm['mapping'] ? $orm['mapping'] : [];

        if (PHP_SAPI != 'cli') {
            echo "This script is only run in php-cli";
            return false;
        }

        if(!$db['charset']){
            $db['charset'] = 'utf8';
        }
        if(!$db['collate']){
            $db['collate'] = 'utf8_unicode_ci';
        }

        $this->db = $db;
        $this->relations = $relations;
        $this->appsPath = $orm['apps_path'];
        $this->mapping = $mapping;


    }


    public function run(){

        $header = "\ncommand start:>>>\n------------------Model Console Messages: ------------------------\n ";

        $command = explode(":",$_SERVER['argv'][1]);
        if($command[0]!='orm'){
            return;
        }

        try{
            $helper = $command[1];
            $method = $command[2];

            $this->addHelper('database', new DatabaseHelper($this));

            if($helper!='database'){
                $this->addHelper('table', new TableHelper($this))
                    ->addHelper('model', new ModelHelper($this))
                ;
            }

            $this->getHelper($helper)->$method();
        }catch (\Exception $exception){
            echo "\n------------------Model Console Fatal Error: ------------------------\n ";
            echo $exception->getMessage();
            echo $exception->getTraceAsString();die;
        }


        $footer = "-------------------Model Console Messages end!-------------------\n<<<command end!\n\n ";

        echo $header.$this->getHelper($helper)->getMessages().$footer;
    }
}