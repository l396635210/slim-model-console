<?php
/**
 * Created by PhpStorm.
 * User: lz
 * Date: 11/20/17
 * Time: 6:58 AM
 */

namespace Liz\Console\ModelConsoleHelper;


abstract class AbstractHelper implements ModelHelperInterface
{

    protected static $errorParam = 1;

    protected static $errorMissParam = 2;

    protected static $errorException = 3;

    protected $errors;

    protected $messages = [];

    public function getMessages()
    {
        $messages = implode(";\n", $this->messages);
        return $messages;
    }

    public function isValid()
    {
        return !$this->errors;
    }

    public function addError($err, $message){
        $this->errors[] = $err;
        $this->messages[] = $message;
        return $this;
    }


    public function addMessage($message){
        $this->messages[] = $message;
        return $this;
    }

    protected function addFinishMessage($message="orm execute finish, thank for use!\n"){
        $this->messages[] = $message;
    }

}