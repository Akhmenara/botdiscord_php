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

class Mimes implements \CharlotteDunois\Validation\ValidationRule {
    private static $extmap = NULL;
    
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
        
        if(empty(self::$extmap)) {
            if(is_array(self::$extmap)) {
                return true;
            }
            
            self::fillMap();
        }
        
        foreach($val as $extension) {
            if(isset(self::$extmap[$extension])) {
                if($mime == self::$extmap[$extension]) {
                    return true;
                }
            }
        }
        
        return 'formvalidator_make_invalid_file';
    }
    
    private static function fillMap() { //Taken from https://secure.php.net/manual/en/function.mime-content-type.php#107798
        self::$extmap = array();
        
        $list = @file_get_contents('https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types');
        if(!$list) {
            return;
        }
        
        $arr = explode("\n", $list);
        foreach($arr as $x) {
            $c = count($out[1]);
            if(isset($x[0]) AND $x[0] !== '#' AND preg_match_all('#([^\s]+)#', $x, $out) AND isset($out[1]) AND $c > 1) {
                for($i = 1; $i < $c; $i++) {
                    self::$extmap[$out[1][$i]] = $out[1][0];
                }
            }
        }
    }
}