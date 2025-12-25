<?php
/**
 * Input Validation Class
 * 
 * Comprehensive input validation with sanitization
 * 
 * @author Dana Baradie
 * @course IT404
 */

class Validator {
    private $errors = [];
    private $data = [];

    /**
     * Validate data against rules
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return bool
     */
    public function validate($data, $rules) {
        $this->errors = [];
        $this->data = $data;

        foreach ($rules as $field => $ruleString) {
            $rulesArray = explode('|', $ruleString);
            
            foreach ($rulesArray as $rule) {
                $this->applyRule($field, $rule);
            }
        }

        return empty($this->errors);
    }

    /**
     * Apply validation rule
     * 
     * @param string $field
     * @param string $rule
     */
    private function applyRule($field, $rule) {
        $value = $this->data[$field] ?? null;

        // Parse rule with parameters (e.g., max:255)
        if (strpos($rule, ':') !== false) {
            list($ruleName, $param) = explode(':', $rule, 2);
        } else {
            $ruleName = $rule;
            $param = null;
        }

        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->addError($field, "The $field field is required.");
                }
                break;

            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "The $field must be a valid email address.");
                }
                break;

            case 'min':
                if (!empty($value) && strlen($value) < (int)$param) {
                    $this->addError($field, "The $field must be at least $param characters.");
                }
                break;

            case 'max':
                if (!empty($value) && strlen($value) > (int)$param) {
                    $this->addError($field, "The $field must not exceed $param characters.");
                }
                break;

            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    $this->addError($field, "The $field must be a number.");
                }
                break;

            case 'integer':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addError($field, "The $field must be an integer.");
                }
                break;

            case 'url':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, "The $field must be a valid URL.");
                }
                break;

            case 'in':
                if (!empty($value) && !in_array($value, explode(',', $param))) {
                    $this->addError($field, "The $field must be one of: $param.");
                }
                break;

            case 'regex':
                if (!empty($value) && !preg_match($param, $value)) {
                    $this->addError($field, "The $field format is invalid.");
                }
                break;

            case 'password':
                if (!empty($value)) {
                    $minLength = (int)($param ?? 8);
                    if (strlen($value) < $minLength) {
                        $this->addError($field, "The $field must be at least $minLength characters.");
                    }
                    if (!preg_match('/[A-Z]/', $value)) {
                        $this->addError($field, "The $field must contain at least one uppercase letter.");
                    }
                    if (!preg_match('/[a-z]/', $value)) {
                        $this->addError($field, "The $field must contain at least one lowercase letter.");
                    }
                    if (!preg_match('/[0-9]/', $value)) {
                        $this->addError($field, "The $field must contain at least one number.");
                    }
                }
                break;
        }
    }

    /**
     * Add validation error
     * 
     * @param string $field
     * @param string $message
     */
    private function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    /**
     * Get validation errors
     * 
     * @return array
     */
    public function errors() {
        return $this->errors;
    }

    /**
     * Get first error message
     * 
     * @return string|null
     */
    public function firstError() {
        if (empty($this->errors)) {
            return null;
        }
        $firstField = array_key_first($this->errors);
        return $this->errors[$firstField][0] ?? null;
    }

    /**
     * Check if validation passed
     * 
     * @return bool
     */
    public function passes() {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     * 
     * @return bool
     */
    public function fails() {
        return !empty($this->errors);
    }

    /**
     * Sanitize string input
     * 
     * @param mixed $data
     * @return mixed
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }

    /**
     * Validate email
     * 
     * @param string $email
     * @return bool
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate password strength
     * 
     * @param string $password
     * @param int $minLength
     * @return bool
     */
    public static function isValidPassword($password, $minLength = 8) {
        return strlen($password) >= $minLength &&
               preg_match('/[A-Z]/', $password) &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/[0-9]/', $password);
    }
}

