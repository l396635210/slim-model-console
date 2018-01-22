
    /**
    * many-to-many | ${how}
    * @var array|${model}[]
    */
    private $${field} = [];

    /**
    * @return array|${model}[]
    */
    public function ${getter}(){
        if(!$this->${field}){
            $this->${field} = ModelManager::getInstance()->getMappingTable('${table}')
            ->findSet($this, ${model}::class, ['${modelID}' => '${setID}']);
        }
        return $this->${field};
    }
