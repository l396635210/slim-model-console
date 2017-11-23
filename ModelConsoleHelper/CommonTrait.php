<?php
/**
 * Created by PhpStorm.
 * User: 39663
 * Date: 2017/11/21
 * Time: 10:48
 */

namespace Liz\Console\ModelConsoleHelper;


use Liz\Console\ModelConsole;

trait CommonTrait
{

    /**
     * @var ModelConsole
     */
    protected $modelConsole;

    /**
     * @var string
     */
    protected $bundle;

    /**
     * @var array
     */
    protected $specialRelations = [];

    protected function getMissParamMessage(){
        $relations = $this->modelConsole->getRelations();
        $msg = "Please put app name,check your 'orm.yml'\n";
        $bundles = implode(",", array_keys($relations));
        $msg .= "i.e [{$bundles}]\n";
        return $msg;
    }

    protected function isParamValid(){
        $isValid = false;
        $relations = $this->modelConsole->getRelations();
        $this->bundle = $_SERVER['argv'][2];
        $bundles = array_keys($relations);

        if(in_array($this->bundle, $bundles)){
            $isValid = true;
            if(isset($_SERVER['argv'][3]) && $_SERVER['argv'][3]){
                $specials = explode(',', $_SERVER['argv'][3]);
                foreach ($specials as $special){
                    if(key_exists($special, $relations[$this->bundle])){
                        $this->specialRelations[$this->bundle][$special] = $relations[$this->bundle][$special];
                    }else{
                        $isValid = false;
                    }
                }
            }
        }

        return $isValid;
    }

    protected function getErrorParamMessage(){
        $relations = $this->modelConsole->getRelations();
        $msg = "app name is error,check your 'orm.yml'\n";
        $bundles = implode(",", array_keys($relations));
        $msg .= "i.e [{$bundles}]\n";
        return $msg;
    }

}