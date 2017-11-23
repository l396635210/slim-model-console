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
        'bigint'    => 'int',
        'char'      => 'string',
        'varchar'   => 'string',
        'text'      => 'string',
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

}