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

class Image implements \CharlotteDunois\Validation\ValidationRule {
    function validate($value, $key, $fields, $options, $exists, \CharlotteDunois\Validation\Validator $validator) {
        if($exists === false) {
            return null;
        }
        
        if(!isset($_FILES[$key]) OR !file_exists($_FILES[$key]['tmp_name'])) {
            return 'formvalidator_make_image';
        }
        
        $size = getimagesize($FILES[$key]['tmp_name']);
        if($size === false) {
            return 'formvalidator_make_image';
        }
        
        return true;
    }
}