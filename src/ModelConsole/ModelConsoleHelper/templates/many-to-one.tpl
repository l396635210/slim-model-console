

    /**
    * many-to-one | ${how}
    * @var ${model}
    */
    private $${field};

    /**
    * @return ${model}|object
    */
    public function ${getter}(){
        if(!$this->${field}){
            $finder = ModelManager::getInstance()->getFinder(${model}::class);
            $this->${field} = $finder->findOne($this->${selfField});
        }
        return $this->${field};
    }

    /**
    * @param ${model} $${field}
    * @return $this
    */
    public function ${setter}(${model} $${field}){
        $this->${field} = $${field};
        $this->${selfField} = $${field}->${toGetter}();
        return $this;
    }


