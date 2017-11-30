<?php
/**
 * Created by PhpStorm.
 * User: lz
 * Date: 11/19/17
 * Time: 3:33 PM
 */

namespace Liz\Console\ModelConsoleHelper;


use Liz\Console\ModelConsole;
use Liz\Console\ModelConsoleUtil\MappingUtil;

class ModelHelper extends AbstractHelper
{

    use CommonTrait;

    protected $modelTemplate;

    protected $fieldTemplate;

    protected $finderTemplate;

    protected $manyToOneTemplate;


    public function __construct(ModelConsole $console)
    {
        $this->modelConsole = $console;
        $this->modelTemplate = file_get_contents(__DIR__.'/templates/model.tpl');
        $this->fieldTemplate = file_get_contents(__DIR__.'/templates/field.tpl');
        $this->finderTemplate = file_get_contents(__DIR__.'/templates/finder.tpl');
        $this->manyToOneTemplate = file_get_contents(__DIR__.'/templates/many-to-one.tpl');

    }

    /**
     * @command orm:model:generate app
     */
    public function generate(){
        if(!isset($_SERVER['argv'][2])){
            $this->addError(self::$errorMissParam, $this->getMissParamMessage());
        }
        if(!$this->isParamValid()){
            $this->addError(self::$errorParam, $this->getErrorParamMessage());
        }

        if($this->isValid()){
            $mapping = $this->modelConsole->getMapping();
            $relations = $this->specialRelations ? $this->specialRelations : $this->modelConsole->getRelations();
            MappingUtil::addColumnFieldTypeRelations($mapping);

            foreach ($relations[$this->bundle] as $model => $relation){
                $modelFileContent = $this->makeModelContent($model,$relation);
                $finderFileContent = $this->makeFinderContent($model);
                $this->addFieldsToModelContent($modelFileContent, $relation);
                $this->addMappingsToModelContent($modelFileContent, $relation);

                $modelFileContent .= "\n\n}";
                $this->dumpModel($model, $modelFileContent);
                $this->dumpFinder($model, $finderFileContent);
            }
            #var_dump($this->relations[$this->bundle]);
            $this->addFinishMessage();
        }

    }

    protected function makeModelContent($model, $relation){
        $bundle = ucfirst($this->bundle);
        $model = ucfirst($model);
        $use = '';
        if(isset($relation['mapping'])){
            $use = "use Liz\ModelManager\ModelManager;\n";
        }
        $modelFIleContent = strtr($this->modelTemplate,[
            '${app}' => $bundle,
            '${Model}' => $model,
            '${use}' => $use,
        ]);

        return $modelFIleContent;
    }

    protected function makeFinderContent($model){
        $bundle = ucfirst($this->bundle);
        $model = ucfirst($model);
        $modelFIleContent = strtr($this->finderTemplate,[
            '${app}' => $bundle,
            '${Model}' => $model,
        ]);

        return $modelFIleContent;
    }

    protected function addFieldsToModelContent(&$modelContent, $relation){
        $fields = $relation['fields'];
        $modelContent .= strtr($this->fieldTemplate,[
            '${type}' => 'int',
            '${field}' => 'id',
            '${getter}' => 'getID',
            '${setter}' => 'setID',
        ]);
        foreach ($fields as $field=>$desc){

            $type = MappingUtil::getFieldTypeFieldsDesc($desc);
            $fieldFileContent = strtr($this->fieldTemplate,[
                '${type}' => $type,
                '${field}' => $field,
                '${getter}' => 'get'.MappingUtil::_2hump($field),
                '${setter}' => 'set'.MappingUtil::_2hump($field),
            ]);
            $modelContent .= $fieldFileContent;
        }

    }

    protected function addMappingsToModelContent(&$modelContent, $relation){
        if(isset($relation['mapping'])){
            $mapping = $relation['mapping'];

            foreach ($mapping as $field=>$info){
                list($bundle, $model) = explode('.', $info['which']);
                if(strtolower($bundle)==$this->bundle){
                    $model = ucfirst($model);
                }else{
                    $model = ucfirst($bundle).'\Model\\'.ucfirst($model);
                }
                list($selfField, $toField) = explode('->',$info['how']);
                $manyToOneContent = strtr($this->manyToOneTemplate,[
                    '${model}' => $model,
                    '${how}' => $info['how'],
                    '${field}' => $field,
                    '${getter}' => 'get'.MappingUtil::_2hump($field),
                    '${setter}' => 'set'.MappingUtil::_2hump($field),
                    '${selfField}' => $selfField,
                    '${toGetter}' => 'get'.MappingUtil::_2hump($toField),
                ]);
                $modelContent .= $manyToOneContent;

            }
        }
    }

    protected function dumpModel($model, $content){

        $bundle = ucfirst($this->bundle);
        $model = ucfirst($model);

        $appsPath = $this->modelConsole->getAppsPath();
        $modelPath = $bundle."/Model/";
        $path = $appsPath."/".$modelPath.$model.".php";
        if(file_exists($path)){
            copy($path, $path.".bkp");
        }
        if(file_put_contents($path, $content)){
            $this->addMessage("put file {$path} done! create model {$model} success!");
        }
    }

    protected function dumpFinder($model, $content){
        $bundle = ucfirst($this->bundle);
        $model = ucfirst($model);

        $appsPath = $this->modelConsole->getAppsPath();
        $finderPath = $bundle."/Finder/";
        $path = $appsPath."/".$finderPath.$model."Finder.php";

        if(!file_exists($path)){
            file_put_contents($path, $content);
            $this->addMessage("put file {$path} done! create model {$model} success!");
        }
    }
}