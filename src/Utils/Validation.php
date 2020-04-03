<?php

namespace Wolff\Utils;

final class Validation
{

    /**
     * The data to evaluate.
     *
     * @var array
     */
    private $data = [];

    /**
     * The fields rules.
     *
     * @var array
     */
    private $fields = [];

    /**
     * The fields rules.
     *
     * @var array
     */
    private $invalid_values = [];


    /**
     * Sets the data array to validate
     *
     * @param  array  $data  the data array
     *
     * @return self
     */
    public function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }


    /**
     * Sets the fields rules
     *
     * @param  array  $fields  the associative array
     * with the fields rules
     *
     * @return self
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;
        return $this;
    }


    /**
     * Returns an associative array with all the invalid values.
     * This method runs the isValid method.
     *
     * @return array an associative array with all the invalid values
     */
    public function getInvalidValues()
    {
        $this->isValid();
        return $this->invalid_values;
    }


    /**
     * Returns true if the current data complies all the fields rules,
     * false otherwise
     *
     * @return bool true if the current data complies all the fields rules,
     * false otherwise
     */
    public function isValid()
    {
        foreach ($this->fields as $key => $val) {
            $this->validateField($key);
        }

        return empty($this->invalid_values);
    }



    /**
     * Returns true if the given data key value matches
     * the current rules, false otherwise
     *
     * @param  string  $key  the data key value to evaluate
     * with the field rules
     */
    private function validateField(string $key)
    {
        $val = $this->data[$key] ?? null;

        foreach ($this->fields[$key] as $rule => $rule_val) {
            $rule = trim(strtolower($rule));

            if (!$this->compliesType($rule_val, $val) ||
                !$this->compliesVal($rule, $rule_val, $val)) {
                $this->addInvalidValue($key, $rule);
            }
        }
    }


    /**
     * Returns true if the given value complies with the
     * specified rule, false otherwise
     *
     * @param  string  $rule  the rule that must be complied
     * @param  mixed  $rule_val  the rule value
     * @param  mixed  $val the value to check
     *
     * @return bool true if the given value complies with the
     * specified rule, false otherwise
     */
    private function compliesVal(string $rule, $rule_val, $val)
    {
        switch ($rule) {
            case 'minlen':
                return strlen($val) >= $rule_val;
            case 'maxlen':
                return strlen($val) <= $rule_val;
            case 'minval':
                return $val >= $rule_val;
            case 'maxval':
                return $val <= $rule_val;
            case 'regex':
                return preg_match($rule_val, $val);
            default:
                return true;
        }
    }


    /**
     * Returns true if the given value complies with the
     * specified type, false otherwise
     *
     * @param  string  $type  the type that must be complied
     * @param  mixed  $val the value to check
     *
     * @return bool true if the given value complies with the
     * specified type, false otherwise
     */
    private function compliesType(string $type, $val)
    {
        switch ($type) {
            case 'email':
                return filter_var($val, FILTER_VALIDATE_EMAIL) !== false;
            case 'alphanumeric':
                return preg_match('/[A-Za-z0-9 ]+$/', $val);
            case 'alpha':
                return preg_match('/[A-Za-z ]+$/', $val);
            case 'int':
                return filter_var($val, FILTER_VALIDATE_INT) !== false;
            case 'float':
                return filter_var($val, FILTER_VALIDATE_FLOAT) !== false;
            case 'bool':
                $val = strval($val);
                return $val === 'true' || $val === 'false' || $val === '1' || $val === '0';
            default:
                return true;
        }
    }


    /**
     * Adds an invalid value
     *
     * @param  string  $key  the value key
     * @param  string  $field  the field that the value doesn't complies
     */
    private function addInvalidValue(string $key, string $field)
    {
        $this->invalid_values[$key][] = $field;
    }


    /**
     * Returns true if the given data matches the fields.
     * This is a proxy method to the setFields, setData
     * and isValid methods (in that order)
     *
     * @param  array  $fields  the associative array
     * with the fields rules
     *
     * @param  array  $data  the data array to validate
     *
     * @return bool true if the current data complies all the fields rules,
     * false otherwise
     */
    public function check($fields, $data)
    {
        return $this->setFields($fields)
                    ->setData($data)
                    ->isValid();
    }
}
