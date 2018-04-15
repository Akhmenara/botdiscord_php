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

class Confirmed implements \CharlotteDunois\Validation\ValidationRule {
    function validate($value, $key, $fields, $options, $exists, \CharlotteDunois\Validation\Validator $validator) {
        if($exists === false) {
            return null;
        }
        
        if(empty($options)) {
            $options = 'confirmation';
        }
        
        if(!isset($fields[$key.'_'.$options]) OR $fields[$key] !== $fields[$key.'_'.$options]) {
            return 'formvalidator_make_confirmed';
        }
        
        return true;
    }
}