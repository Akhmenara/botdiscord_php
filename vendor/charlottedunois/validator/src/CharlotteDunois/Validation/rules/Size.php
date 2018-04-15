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

class Size implements \CharlotteDunois\Validation\ValidationRule {
    function validate($value, $key, $fields, $options, $exists, \CharlotteDunois\Validation\Validator $validator) {
        if($exists === false) {
            return null;
        }
        
        if(isset($_FILES[$key]) AND file_exists($_FILES[$key]['tmp_name']) AND $_FILES[$key]['error'] == 0) {
            $v = round((filesize($_FILES[$key]['tmp_name']) / 1024));
        } elseif(is_array($value)) {
            $v = count($value);
        } elseif(is_numeric($value)) {
            $v = $value;
        } else {
            $v = mb_strlen($value);
        }
        
        if($v != $options) {
            return array('formvalidator_make_size', array('{0}' => $options));
        }
        
        return true;
    }
}