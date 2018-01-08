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

    protected $oneToManyTemplate;

    protected $manyToManyTemplate;

    protected $manyToManyCascadeTemplate;

    protected $oneToOneTemplate;

    protected $fieldWithValueTemplate;
    protected $oneToOneCascadeTemplate;

    public function __construct(ModelConsole $console)
    {
        $this->modelConsole = $console;
        $this->modelTemplate = file_get_contents(__DIR__ . '/templates/model.tpl');
        $this->fieldTemplate = file_get_contents(__DIR__ . '/templates/field.tpl');
        $this->finderTemplate = file_get_contents(__DIR__ . '/templates/finder.tpl');
        $this->manyToOneTemplate = file_get_contents(__DIR__ . '/templates/many-to-one.tpl');
        $this->oneToManyTemplate = file_get_contents(__DIR__ . '/templates/one-to-many.tpl');
        $this->manyToManyTemplate = file_get_contents(__DIR__ . '/templates/many-to-many.tpl');
        $this->manyToManyCascadeTemplate = file_get_contents(
            __DIR__ . '/templates/many-to-many-cascade.tpl'
        );

        $this->oneToOneTemplate = file_get_contents(__DIR__.'/templates/one-to-one.tpl');
        $this->fieldWithValueTemplate = file_get_contents(__DIR__.'/templates/field-with-value.tpl');
        $this->oneToOneCascadeTemplate = file_get_contents(__DIR__.'/templates/one-to-one-cascade.tpl');
    }

    /**
     * @command orm:model:make app
     */
    public function make(){
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

                $model = MappingUtil::_2hump($model);
                $modelFileContent = $this->makeModelContent($model,$relation);
                $finderFileContent = $this->makeFinderContent($model);
                $this->addFieldsToModelContent($modelFileContent, $relation);
                $this->addMappingsToModelContent($modelFileContent, $relation);
                if(!strstr($modelFileContent, 'toArray')){
                    $modelFileContent .= file_get_contents(__DIR__."/templates/toArray.tpl");
                }
                $modelFileContent .= "\n\n}";
                $this->dumpModel($model, $modelFileContent);
                $this->dumpFinder($model, $finderFileContent);
            }
            #var_dump($this->relations[$this->bundle]);
            $this->addFinishMessage();
        }

    }

    protected function makeModelContent($model, $relation){
        $modelFIleContent = $this->getExistsModelContent($model);
        $use = '';
        if(isset($relation['mapping'])){
            $use = "use Liz\ModelManager\ModelManager;\n";
        }

        if(!$modelFIleContent){
            $bundle = MappingUtil::_2hump($this->bundle);

            $modelFIleContent = strtr($this->modelTemplate,[
                '${app}' => $bundle,
                '${Model}' => $model,
                '${use}' => $use,
            ]);
        }else{
            $contentExplode = explode("class {$model}", $modelFIleContent);
            if($use && !strstr($contentExplode[0], trim($use))){
                $contentExplode[0] .= $use;
            }
            $modelFIleContent = implode("class {$model}", $contentExplode);
            $modelFIleContent = trim(trim($modelFIleContent, "\t\n\r \v"),"}");
        }

        return $modelFIleContent;
    }

    protected function makeFinderContent($model){
        $bundle = MappingUtil::_2hump($this->bundle);
        $modelFIleContent = strtr($this->finderTemplate,[
            '${app}' => $bundle,
            '${Model}' => $model,
        ]);

        return $modelFIleContent;
    }

    protected function addFieldsToModelContent(&$modelContent, $relation){
        $fields = $relation['fields'];
        if(!strstr($modelContent, "private \$id")) {
            $modelContent .= strtr($this->fieldTemplate, [
                '${type}' => 'int',
                '${field}' => 'id',
                '${getter}' => 'getID',
                '${setter}' => 'setID',
            ]);
        }
        foreach ($fields as $field=>$desc){
            if(!strstr($modelContent, "private \$$field")){
                $fieldTemplate = $this->fieldTemplate;
                $value = "";
                if(strstr($desc,'DEFAULT') && !strstr($desc, 'CURRENT_TIMESTAMP')){
                    $arr = explode(" ", $desc );
                    $value = trim($arr[array_search("DEFAULT", $arr)+1], ',');
                    $fieldTemplate = $this->fieldWithValueTemplate;
                }
                $type = MappingUtil::getFieldTypeFieldsDesc($desc);
                $fieldFileContent = strtr($fieldTemplate,[
                    '${type}' => $type,
                    '${field}' => $field,
                    '${value}' => $value,
                    '${getter}' => 'get'.MappingUtil::_2hump($field),
                    '${setter}' => 'set'.MappingUtil::_2hump($field),
                ]);
                $modelContent .= $fieldFileContent;
            }
        }

    }

    protected function addMappingsToModelContent(&$modelContent, $relation){
        if(!isset($relation['mapping'])){
            return false;
        }
        $mapping = $relation['mapping'];

        foreach ($mapping as $field=>$info){
            if(!strstr($modelContent, "private \$$field;")
                && !strstr($modelContent, "private \$$field =")
                && !strstr($modelContent, "private \$$field=")
            ){
                switch ($info['what']){
                    case 'many-to-one':
                        $modelContent .= $this->dumpManyToOne($field, $info);
                        break;

                    case 'one-to-many':
                        $modelContent .= $this->dumpOneToMany($field, $info);
                        break;

                    case 'many-to-many':
                        $modelContent .= $this->dumpManyToMany($field, $info, $relation);
                        break;

                    case 'one-to-one':
                        $modelContent .= $this->dumpOneToOne($field, $info);
                        break;
                }
            }
        }
    }

    protected function prepareModelForManyToX($bundle, $model){
        if(strtolower($bundle)==$this->bundle){
            $model = MappingUtil::_2hump($model);
        }else{
            $model = MappingUtil::_2hump($bundle).'\Model\\'.MappingUtil::_2hump($model);
        }
        return $model;
    }

    protected function dumpManyToOne($field, $info){
        list($bundle, $model) = explode('.', $info['which']);
        $model = $this->prepareModelForManyToX($bundle, $model);

        list($selfField, $toField) = explode('->',$info['how']);
        $content = strtr($this->manyToOneTemplate,[
            '${model}' => $model,
            '${how}' => $info['how'],
            '${field}' => $field,
            '${getter}' => 'get'.MappingUtil::_2hump($field),
            '${setter}' => 'set'.MappingUtil::_2hump($field),
            '${selfField}' => $selfField,
            '${toGetter}' => 'get'.MappingUtil::_2hump($toField),
        ]);
        return $content;
    }

    protected function dumpOneToOne($field, $info){
        list($bundle, $model) = explode('.', $info['which']);
        $model = $this->prepareModelForManyToX($bundle, $model);

        list($selfField, $toField) = explode('->',$info['how']);
        $template = $this->oneToOneTemplate;
        if(isset($info['cascade'])&&$info['cascade']===true){
            $template = $this->oneToOneCascadeTemplate;
        }

        $content = strtr($template,[
            '${model}' => $model,
            '${how}' => $info['how'],
            '${field}' => $field,
            '${getter}' => 'get'.MappingUtil::_2hump($field),
            '${selfField}' => $selfField,
            '${toField}' => $toField,
            '${setter}' => 'set'.MappingUtil::_2hump($field),
            '${toGetter}' => 'get'.MappingUtil::_2hump($toField),
        ]);
        return $content;
    }

    protected function dumpOneToMany($field, $info){
        list($bundle, $_model) = explode('.', $info['which']);

        $model = $this->prepareModelForManyToX($bundle, $_model);

        list($selfField, $toField) = explode('->',$info['how']);
        $content = strtr($this->oneToManyTemplate,[
            '${model}' => $model,
            '${how}' => $info['how'],
            '${field}' => $field,
            '${param}' => substr($field,0, -1),
            '${getter}' => 'get'.MappingUtil::_2hump($field),
            '${adder}' => 'add'.MappingUtil::_2hump($_model),
            '${remover}' => 'remove'.MappingUtil::_2hump($_model),
            '${selfField}' => $selfField,
            '${toField}' => $toField,
            '${toSetter}' => 'set'.MappingUtil::_2hump($toField),
        ]);

        return $content;
    }


    protected function dumpManyToMany($field, $info, $relation){
        list($bundle, $_model) = explode('.', $info['which']);

        $model = $this->prepareModelForManyToX($bundle, $_model);

        list($selfField, $toField) = explode('->',$info['how']);
        $tableInfo = [
            $relation['table'],$_model
        ];
        asort($tableInfo);
        $template = $this->manyToManyTemplate;
        if(isset($info['cascade'])&&$info['cascade']===true){
            $template = $this->manyToManyCascadeTemplate;
        }
        $content = strtr($template,[
            '${model}' => $model,
            '${table}' => implode('_', $tableInfo),
            '${modelID}' => $relation['table']."_id",
            '${setID}' => $_model."_id",
            '${how}' => $info['how'],
            '${field}' => $field,
            '${param}'=> $_model,
            '${adder}' => 'add'.MappingUtil::_2hump($_model),
            '${mappinger}'=> 'mapping'.MappingUtil::_2hump($field),
            '${getter}' => 'get'.MappingUtil::_2hump($field),
            '${setter}' => 'set'.MappingUtil::_2hump($field),
            '${remover}' => 'remove'.MappingUtil::_2hump($_model),
            '${selfField}' => $selfField,
            '${toGetter}' => 'get'.MappingUtil::_2hump($toField),
        ]);
        return $content;
    }

    protected function getExistsModelContent($model){
        $appsPath = $this->modelConsole->getAppsPath();
        $path = $appsPath."Model/".$model.".php";
        if(file_exists($path)){
            return file_get_contents($path);
        }
        return "";
    }

    protected function dumpModel($model, $content){

        $appsPath = $this->modelConsole->getAppsPath();
        $path = $appsPath."Model/".$model.".php";
        if(file_exists($path)){
            copy($path, $path.".~");
        }
        if(file_put_contents($path, $content)){
            $this->addMessage("put file {$path} done! create model {$model} success!");
        }
    }

    protected function dumpFinder($model, $content){

        $appsPath = $this->modelConsole->getAppsPath();
        $path = $appsPath."Finder/".$model."Finder.php";

        if(!file_exists($path)){
            file_put_contents($path, $content);
            $this->addMessage("put file {$path} done! create model {$model} success!");
        }
    }
}