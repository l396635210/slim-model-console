<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 11/21/17
 * Time: 6:36 AM
 */

namespace Liz\Console\ModelConsoleUtil;


class MappingUtil
{

    protected static $columnFieldTypeRelations = [
        'int'       => 'int',
        'smallint'  => 'int',
        'mediumint' => 'int',
        'bigint'    => 'int',
        'char'      => 'string',
        'decimal'   => 'string',
        'varchar'   => 'string',
        'text'      => 'string',
        'longtext'  => 'string',
        'tinyint'   => 'boolean',
        'date'      => '\DateTime',
        'time'      => '\DateTime',
        'datetime'  => '\DateTime',
    ];

    /**
     * @param array $mapping
     */
    public static function addColumnFieldTypeRelations(array $mapping){
        foreach ($mapping as $column=>$field){
            self::$columnFieldTypeRelations[$column] = $field;
        }
    }

    public static function getFieldTypeFieldsDesc($desc){
        $typeDesc = explode(' ', $desc)[0];
        $columnType = explode('(', $typeDesc)[0];
        return self::$columnFieldTypeRelations[$columnType];
    }

    public static function _2hump($str, $ucFirst=true){
        $tmp = ucwords(strtr($str,['_'=>' ']));
        return $ucFirst ? strtr($tmp,[' '=>'']) : strtr(lcfirst($tmp), [' '=>'']);
    }
}