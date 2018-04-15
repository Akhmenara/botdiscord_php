<?php
/**
 * Validator
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Docs: https://laravel.com/docs/5.2/validation
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Validator/blob/master/LICENSE
**/

namespace CharlotteDunois\Validation\Rule;

class Lowercase implements \CharlotteDunois\Validation\ValidationRule {
    function validate($value, $key, $fields, $options, $exists, \CharlotteDunois\Validation\Validator $validator) {
        if($exists === false) {
            return null;
        }
        
        if(!is_string($value)) {
            return 'formvalidator_make_string';
        }
        
        if(mb_strtolower($value) !== $value) {
            return 'formvalidator_make_lowercase';
        }
        
        return true;
    }
}
