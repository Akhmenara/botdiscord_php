<?php
/**
 * Validator
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Docs: https://laravel.com/docs/5.2/validation
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Validator/blob/master/LICENSE
**/

namespace CharlotteDunois\Validation;

/**
 * The ValidationRule interface that every rule has to implement.
 */
interface ValidationRule {
    /**
     * This method validates the value using the rule's implementation.
     * @param mixed                                  $value       The value of the field to validate.
     * @param string                                 $key         The key of the field.
     * @param array                                  $fields      The fields.
     * @param mixed                                  $options     Any rule options.
     * @param \CharlotteDunois\Validation\Validator  $validator   The Validator instance
     */
    function validate($value, $key, $fields, $options, \CharlotteDunois\Validation\Validator $validator);
}
