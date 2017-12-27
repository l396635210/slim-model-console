

    /**
    * one-to-many | ${how}
    * @var array
    */
    private $${field} = [];

    /**
    * @return array
    */
    public function ${getter}(){
        if(!$this->${field}){
            $this->${field} = ModelManager::getInstance()->getFinder(${model}::class)->findBy([
                '${toField}'=>$this->${selfField},
            ],['id'=>'DESC'],20);
        }
        return $this->${field};
    }

    /**
    * @param ${model} $${param}
    * @return $this
    */
    public function ${adder}(${model} $${param}){
        $${param}->${toSetter}($this->${selfField});
        $this->${field}[] = $${param};
        return $this;
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
        return true;
    }

