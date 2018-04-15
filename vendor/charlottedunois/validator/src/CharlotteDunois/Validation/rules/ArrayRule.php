<?php
/**
 * Validator
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Docs: https://laravel.com/docs/5.2/validation
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Validator/blob/master/LICENSE
**/

namespace CharlotteDunois\Validation\Rule;

class ArrayRule implements \CharlotteDunois\Validation\ValidationRule {
    function validate($value, $key, $fields, $options, $exists, \CharlotteDunois\Validation\Validator $validator) {
        if($exists === false) {
            return null;
        }
        
        if(!is_array($value)) {
            return 'formvalidator_make_array';
        }
        
        if(!empty($options)) {
            foreach($value as $val) {
                $type = gettype($val);
                if($type == 'double') {
                    $type = 'float';
                }
                
                if($type != $options) {
                    return array('formvalidator_make_array_subtype', array('{0}' => $options));
                }
            }
        }
        
        return true;
    }
}
