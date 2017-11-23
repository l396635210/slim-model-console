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


    public function __construct(ModelConsole $console)
    {
        $this->modelConsole = $console;
        $this->modelTemplate = file_get_contents(__DIR__.'/templates/model.tpl');
        $this->fieldTemplate = file_get_contents(__DIR__.'/templates/field.tpl');

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
                $modelFileContent = $this->makeModelContent($model);
                $this->addFieldsToModelContent($modelFileContent, $relation);
                $this->dumpModel($model, $modelFileContent);
            }
            #var_dump($this->relations[$this->bundle]);
            $this->addFinishMessage();
        }

    }

    protected function makeModelContent($model){
        $bundle = ucfirst($this->bundle);
        $model = ucfirst($model);
        $modelFIleContent = strtr($this->modelTemplate,[
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
        ]);
        foreach ($fields as $field=>$desc){

            $type = MappingUtil::getFieldTypeFieldsDesc($desc);
            $fieldFileContent = strtr($this->fieldTemplate,[
               '${type}' => $type,
                '${field}' => $field,
            ]);
            $modelContent .= $fieldFileContent;
        }
        $modelContent .= "\n\n}";

    }

    protected function dumpModel($model, $content){

        $bundle = ucfirst($this->bundle);
        $model = ucfirst($model);

        $appsPath = $this->modelConsole->getAppsPath();
        $modelPath = $bundle."/Model/";
        $path = $appsPath."/".$modelPath.$model.".php";
        if(file_exists($path)){
            copy($path, $path."~");
        }
        if(file_put_contents($path, $content)){
            $this->addMessage("put file {$path} done! create model {$model} success!");
        }
    }

}