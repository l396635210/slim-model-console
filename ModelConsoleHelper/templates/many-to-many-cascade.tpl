
    /**
    * many-to-many | ${how}
    * @var array|${model}[]
    */
    private $${field} = [];

    /**
    * @param Object|${model} $${param}
    * @return $this
    */
    public function ${adder}(${model} $${param}){
        $this->${field}[$${param}->${toGetter}()] = $${param};
        ModelManager::getInstance()->persist($${param});
        return $this;
    }

    /**
    * mapping
    * @return $this
    */
    public function ${mappinger}(){
        ModelManager::getInstance()->getMappingTable('${table}')
        ->mapping($this, $this->${field},'${modelID}', '${setID}');
    }

    /**
    * @param ${model} $${param}
    * @return bool
    */
    public function ${remover}(${model} $${param}){
        $key = array_search($${param}, $this->${field});
        if ($key === false) {
            return false;
        }
        unset($this->${field}[$key]);
        ModelManager::getInstance()->getMappingTable('${table}')->removeBy([
            '${modelID}' => $this->id,
            '${setID}'    => $${param}->getID(),
        ]);
        return true;
    }

    /**
    * @return array|${model}[]
    */
    public function ${getter}(){
        if(!$this->${field}){
            $this->${field} = ModelManager::getInstance()->getMappingTable('${table}')
            ->findSet($this, ${model}::class, '${setID}');
        }
        return $this->${field};
    }
