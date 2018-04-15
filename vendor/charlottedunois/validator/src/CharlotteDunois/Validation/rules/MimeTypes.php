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

class MimeTypes implements \CharlotteDunois\Validation\ValidationRule {
    function validate($value, $key, $fields, $options, $exists, \CharlotteDunois\Validation\Validator $validator) {
        if($exists === false) {
            return null;
        }
        
        if(!isset($_FILES[$key]) OR !file_exists($_FILES[$key]['tmp_name']) OR $_FILES[$key]['error'] != 0) {
            return 'formvalidator_make_invalid_file';
        }
        
        $mime = mime_content_type($_FILES[$key]['tmp_name']);
        
        $val = explode(',', $options);
        if(empty($val)) {
            return true;
        }
        
        $result = explode('/', $mime);
        
        foreach($val as $mime) {
            $mime = explode('/', $mime);
            if(count($mime) == 2 AND count($result) == 2) {
                if(($mime[0] == "*" OR $mime[0] == $result[0]) AND ($mime[1] == "*" OR $mime[1] == $result[1])) {
                    return true;
                }
            }
        }
        
        return 'formvalidator_make_invalid_file';
    }
}