<?php
/**
 * Validator
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Docs: https://laravel.com/docs/5.2/validation
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Validator/blob/master/LICENSE
**/

namespace CharlotteDunois\Validation;

include_once(__DIR__.'/ValidationRule.php');

/**
 * Pure PHP implementation based on Laravel's Validator.
 */
class Validator {
    private $errors = array();
    private $fields = array();
    private $rules = array();
    
    private $lang = NULL;
    private $lang_words = array();
    
    private static $rulesets = array();
    
    private static $langrules = array();
    
    private function __construct($fields, $rules, $lang) {
        $this->fields = $fields;
        $this->rules = $rules;
        $this->lang = $lang;
        
        if(empty(self::$rulesets)) {
            $rules = glob(__DIR__.'/rules/*.php');
            foreach($rules as $rule) {
                try {
                    $arrname = explode('/', $rule);
                    $name = mb_substr(array_pop($arrname), 0, -4);
                    include_once($rule);
                    
                    $class = '\\CharlotteDunois\\Validation\\Rule\\'.$name;
                    $ruleset = new $class();
                    $interfaces = class_implements($ruleset);
                    
                    if(in_array('CharlotteDunois\\Validation\\ValidationRule', $interfaces)) {
                        $rname = str_replace('rule', '', mb_strtolower($name));
                        self::$rulesets[$rname] = $ruleset;
                        
                        if(mb_stripos($name, 'rule') !== false) {
                            self::$langrules[] = $rname;
                        }
                    }
                } catch(Exception $e) {
                    /* Continue regardless of error */
                }
            }
        }
    }
    
    /**
     * Create a new Validator instance.
     *
     * @param  array    $fields   The fields you wanna run the validation against.
     * @param  array    $rules    The validation rules.
     * @param  string   $lang     The language for error messages (included are 'en' or 'de').
     * @return Validator
     */
    static function make($fields, $rules, $lang = 'en') {
        return new Validator($fields, $rules, $lang);
    }
    
    /**
     * Return errors
     *
     * @return array
     */
    function errors() {
        return $this->errors;
    }
    
    /**
     * Determine if the data passes the validation rules.
     *
     * @return bool
     */
    function passes() {
        return $this->startValidation();
    }
    
    /**
     * Determine if the data fails the validation rules.
     *
     * @return bool
     */
    function fails() {
        return !($this->startValidation());
    }
    
    /**
     * Determines if the data passes the validation rules, or throws.
     *
     * @return bool
     * @throws \RuntimeException
     */
    function throw() {
        return $this->startValidation(true);
    }
    
    /**
     * @throws \Exception
     */
    private function startValidation($throws = false) {
        if(!is_array($this->fields) OR !is_array($this->rules)) {
            return false;
        }
        
        $istate = array();
        foreach($this->rules as $key => $rule) {
            $set = explode('|', $rule);
            
            $exists = array_key_exists($key, $this->fields);
            $value = ($exists ? $this->fields[$key] : NULL);
            
            $passedLang = false;
            $failedOtherRules = false;
            
            $nullable = false;
            foreach($set as $r) {
                $r = explode(':', $r);
                if($r[0] == 'nullable') {
                    $nullable = true;
                    continue;
                } elseif(!isset(self::$rulesets[$r[0]])) {
                   throw new \Exception('Validation Rule "'.$r[0].'" does not exist');
                }
                
                $passed = true;
                
                $return = self::$rulesets[$r[0]]->validate($value, $key, $this->fields, (array_key_exists(1, $r) ? $r[1] : NULL), $exists, $this);
                if(is_string($return) OR is_array($return)) {
                    $passed = false;
                }
                
                if(in_array($r[0], self::$langrules)) {
                    if($passed === true) {
                        $passedLang = true;
                    } else {
                        if($passedLang === false) {
                            $passed = false;
                        }
                    }
                } else {
                    if($passed === false) {
                        $failedOtherRules = true;
                    }
                }
                
                if($passed === false) {
                    $istate[] = false;
                    if(is_array($return)) {
                        $this->errors[$key] = $this->language($return[0], $return[1]);
                    } else {
                        $this->errors[$key] = $this->language($return);
                    }
                }
            }
            
            if($passedLang === true AND $failedOtherRules === false) {
                unset($this->errors[$key]);
            }
            
            if($exists === true AND is_null($value)) {
                if($nullable === false) {
                    $istate[] = false;
                    $this->errors[$key] = $this->language('formvalidator_make_nullable');
                } elseif($nullable === true AND isset($this->errors[$key])) {
                    unset($this->errors[$key]);
                }
            }
            
            if(!empty($this->errors[$key]) AND $throws === true) {
                throw new \RuntimeException($key.' '.lcfirst($this->errors[$key]));
            }
        }
        
        if(empty($this->errors)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Return the error message based on the language key (language based).
     *
     * @param  string  $key
     * @param  array   $replacements
     * @return string
     */
    function language($key, $replacements = array()) {
        if(empty($this->lang_words)) {
            include(dirname(__FILE__).'/languages/'.$this->lang.'.lang.php');
            if(!empty($l)) {
                $this->lang_words = $l;
            }
        }
        
        if(isset($this->lang_words[$key])) {
            $lang = $this->lang_words[$key];
            
            if(!empty($replacements)) {
                foreach($replacements as $key => $val) {
                    $lang = str_replace($key, $val, $lang);
                }
            }
            
            return $lang;
        }
        
        return $key;
    }
}
