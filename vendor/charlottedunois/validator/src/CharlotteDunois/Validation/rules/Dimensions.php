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

class Dimensions implements \CharlotteDunois\Validation\ValidationRule {
    function validate($value, $key, $fields, $options, $exists, \CharlotteDunois\Validation\Validator $validator) {
        if($exists === false) {
            return null;
        }
        
        if(!isset($_FILES[$key]) OR !file_exists($_FILES[$key]['tmp_name'])) {
            return 'formvalidator_make_invalid_file';
        }
        
        $size = getimagesize($FILES[$key]['tmp_name']);
        
        $n = explode(',', $options);
        foreach($n as $x) {
            $k = explode('=', $x);
            switch($k[0]) {
                case 'min_width':
                    if($k[1] > $size[0]) {
                        return array('formvalidator_make_min_width', array('{0}' => $options));
                    }
                break;
                case 'min_height':
                    if($k[1] > $size[1]) {
                        return array('formvalidator_make_min_height', array('{0}' => $options));
                    }
                break;
                case 'width':
                    if($k[1] != $size[0]) {
                        return array('formvalidator_make_width', array('{0}' => $options));
                    }
                break;
                case 'height':
                    if($k[1] != $size[1]) {
                        return array('formvalidator_make_height', array('{0}' => $options));
                    }
                break;
                case 'max_width':
                    if($k[1] < $size[0]) {
                        return array('formvalidator_make_max_width', array('{0}' => $options));
                    }
                break;
                case 'max_height':
                    if($k[1] < $size[1]) {
                        return array('formvalidator_make_max_height', array('{0}' => $options));
                    }
                break;
                case 'ratio':
                    if(mb_strpos($k[1], '/') !== false) {
                        $k[1] = explode('/', $k[1]);
                        $k[1] = $k[1][0] / $k[1][1];
                    }
                    
                    if(number_format(($size[0] / $size[1]), 1) != number_format($k[1], 1)) {
                        return array('formvalidator_make_ratio', array('{0}' => $options));
                    }
                break;
            }
        }
        
        return true;
    }
}