<?php
/**
 * DynamicModel is a model class primarily used to support ad hoc data validation.
 * The typical usage of DynamicModel is as follows,
 * ```php
 * $model = new DynamicModel(
 *     ['name', 'email'],
 *     [
 *        ['name, email', 'length', 'max' => 50],
 *     ]
 * );
 * if ($model->hasErrors()) {
 *     // validation fails
 * } else {
 *     // validation succeeds
 * }
 * ```
 */
class DynamicModel extends \CModel
{
    private $_rules = [];
    private $_labels = [];
    private $_attributes = [];

    /**
     * Constructor.
     * @param array  $attributes the dynamic attributes (name-value pairs, or names) being defined.
     * @param array  $rules the validation rules. Please refer to [[CModel::rules()]] on the format of this parameter.
     * @param array  $labels the attribute labels. By default an attribute label is generated using [[generateAttributeLabel]].
     * @param string $scenario name of the scenario that this model is used in.
     * See [[CModel::scenario]] on how scenario is used by models.
     * @see getScenario
     */
    public function __construct(array $attributes = [], $rules = [], $labels = [], $scenario = '')
    {
        foreach ($attributes as $name => $value) {
            if (is_integer($name)) {
                $this->_attributes[$value] = null;
            } else {
                $this->_attributes[$name] = $value;
            }
        }
        $this->_rules = $rules;
        $this->_labels = $labels;
        $this->setScenario($scenario);
        $this->init();
        $this->attachBehaviors($this->behaviors());
        $this->afterConstruct();
    }

    /**
     * Initializes this model.
     * This method is invoked in the constructor right after [[scenario]] is set.
     * You may override this method to provide code that is needed to initialize the model (e.g. setting
     * initial property values.)
     */
    public function init()
    {
    }

    /**
     * Returns the attribute labels.
     * Attribute labels are mainly used in error messages of validation.
     * By default an attribute label is generated using [[generateAttributeLabel]].
     * This method allows you to explicitly specify attribute labels.
     * Note, in order to inherit labels defined in the parent class, a child class needs to
     * merge the parent labels with child labels using functions like array_merge().
     * @return array attribute labels (name=>label)
     * @see generateAttributeLabel
     */
    public function attributeLabels()
    {
        return $this->_labels;
    }

    /**
     * Sets the attribute labels in a massive way.
     * @param array $labels attribute labels (name=>label) to be set.
     */
    public function setAttributeLabels(array $labels)
    {
        foreach ($labels as $name => $label) {
            $this->_labels[$name] = $label;
        }
    }

    /**
     * Returns the list of attribute names.
     * By default, this method returns all public properties of the class.
     * You may override this method to change the default.
     * @return array list of attribute names. Defaults to all public properties of the class.
     */
    public function attributeNames()
    {
        return array_keys($this->_attributes);
    }

    /**
     * Returns all attribute values.
     * @param array $names list of attributes whose value needs to be returned.
     * Defaults to null, meaning all attributes as listed in [[attributeNames]] will be returned.
     * If it is an array, only the attributes in the array will be returned.
     * @return array attribute values (name=>value).
     */
    public function getAttributes($names = null)
    {
        $values = [];
        foreach ($this->attributeNames() as $name) {
            $values[$name] = $this->_attributes[$name];
        }
        if (is_array($names)) {
            $values2 = [];
            foreach ($names as $name) {
                $values2[$name] = isset($values[$name]) ? $values[$name] : null;
            }

            return $values2;
        }

        return $values;
    }

    /**
     * Gets attribute names from rules.
     * @param array  $rules the validation rules. Please refer to [[CModel::rules()]] on the format of this parameter.
     * @param string $scenario name of the scenario that this model is used in.
     * @return array list attribute names.
     * @throws \CException
     */
    public static function getAttributesFromRules(array $rules, $scenario = '')
    {
        $result = [];
        foreach ($rules as $rule) {
            if (!isset($rule[0], $rule[1])) {
                throw new \CException(
                    \Yii::t(
                        'yii',
                        'Invalid validation rule. The rule must specify attributes to be validated and the validator name.'
                    )
                );
            }
            $params = array_slice($rule, 2);
            $attributes = $rule[0];
            if (is_string($attributes)) {
                $attributes = preg_split('/[\s,]+/', $attributes, -1, PREG_SPLIT_NO_EMPTY);
            }

            if (isset($params['on'])) {
                if (is_array($params['on'])) {
                    $on = $params['on'];
                } else {
                    $on = preg_split('/[\s,]+/', $params['on'], -1, PREG_SPLIT_NO_EMPTY);
                }
                if (!in_array($scenario, $on)) {
                    continue;
                }
            }

            if (isset($params['except'])) {
                if (is_array($params['except'])) {
                    $except = $params['except'];
                } else {
                    $except = preg_split('/[\s,]+/', $params['except'], -1, PREG_SPLIT_NO_EMPTY);
                }
                if (in_array($scenario, $except)) {
                    continue;
                }
            }

            $result = \CMap::mergeArray($result, $attributes);
        }

        return $result;
    }

    /**
     * Sets the attribute values in a massive way.
     * @param array   $values attribute values (name=>value) to be set.
     * @param boolean $safeOnly whether the assignments should only be done to the safe attributes.
     * A safe attribute is one that is associated with a validation rule in the current [[scenario]].
     * @see getSafeAttributeNames
     * @see attributeNames
     */
    public function setAttributes($values, $safeOnly = true)
    {
        if (!is_array($values)) {
            return;
        }
        $attributes = array_flip($safeOnly ? $this->getSafeAttributeNames() : $this->attributeNames());
        foreach ($values as $name => $value) {
            if (isset($attributes[$name])) {
                $this->_attributes[$name] = $value;
            } elseif ($safeOnly) {
                $this->onUnsafeAttribute($name, $value);
            }
        }
    }

    /**
     * Sets the attributes to be null.
     * @param array $names list of attributes to be set null. If this parameter is not given,
     * all attributes as specified by [[attributeNames]] will have their values unset.
     * @since 1.1.3
     */
    public function unsetAttributes($names = null)
    {
        if ($names === null) {
            $names = $this->attributeNames();
        }
        foreach ($names as $name) {
            unset($this->_attributes[$name]);
        }
    }

    /**
     * Returns the validation rules for attributes.
     * This method should be overridden to declare validation rules.
     * Each rule is an array with the following structure:
     * <pre>
     * array('attribute list', 'validator name', 'on'=>'scenario name', ...validation parameters...)
     * </pre>
     * where
     * <ul>
     * <li>attribute list: specifies the attributes (separated by commas) to be validated;</li>
     * <li>validator name: specifies the validator to be used. It can be the name of a model class
     *   method, the name of a built-in validator, or a validator class (or its path alias).
     *   A validation method must have the following signature:
     * <pre>
     * // $params refers to validation parameters given in the rule
     * function validatorName($attribute,$params)
     * </pre>
     *   A built-in validator refers to one of the validators declared in [[CValidator::builtInValidators]].
     *   And a validator class is a class extending [[CValidator]].</li>
     * <li>on: this specifies the scenarios when the validation rule should be performed.
     *   Separate different scenarios with commas. If this option is not set, the rule
     *   will be applied in any scenario that is not listed in "except". Please see [[scenario]] for more details about this option.</li>
     * <li>except: this specifies the scenarios when the validation rule should not be performed.
     *   Separate different scenarios with commas. Please see [[scenario]] for more details about this option.</li>
     * <li>additional parameters are used to initialize the corresponding validator properties.
     *   Please refer to individal validator class API for possible properties.</li>
     * </ul>
     * The following are some examples:
     * <pre>
     * array(
     *     array('username', 'required'),
     *     array('username', 'length', 'min'=>3, 'max'=>12),
     *     array('password', 'compare', 'compareAttribute'=>'password2', 'on'=>'register'),
     *     array('password', 'authenticate', 'on'=>'login'),
     * );
     * </pre>
     * Note, in order to inherit rules defined in the parent class, a child class needs to
     * merge the parent rules with child rules using functions like array_merge().
     * @return array validation rules to be applied when [[validate()]] is called.
     * @see scenario
     */
    public function rules()
    {
        return $this->_rules;
    }

    /**
     * Returns a property value, an event handler list or a behavior based on its name.
     * Do not call this method. This is a PHP magic method that we override
     * to allow using the following syntax to read a property or obtain event handlers:
     * <pre>
     * $value=$component->propertyName;
     * $handlers=$component->eventName;
     * </pre>
     * @param string $name the property name or event name
     * @return mixed the property value, event handlers attached to the event, or the named behavior
     * @throws \CException if the property or event is not defined
     * @see __set
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->_attributes)) {
            return $this->_attributes[$name];
        } else {
            return parent::__get($name);
        }
    }

    /**
     * Sets value of a component property.
     * Do not call this method. This is a PHP magic method that we override
     * to allow using the following syntax to set a property or attach an event handler
     * <pre>
     * $this->propertyName=$value;
     * $this->eventName=$callback;
     * </pre>
     * @param string $name the property name or the event name
     * @param mixed  $value the property value or callback
     * @return mixed
     * @throws \CException if the property/event is not defined or the property is read only.
     * @see __get
     */
    public function __set($name, $value)
    {
        if (array_key_exists($name, $this->_attributes)) {
            $this->_attributes[$name] = $value;
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * Checks if a property value is null.
     * Do not call this method. This is a PHP magic method that we override
     * to allow using isset() to detect if a component property is set or not.
     * @param string $name the property name or the event name
     * @return boolean
     */
    public function __isset($name)
    {
        if (array_key_exists($name, $this->_attributes)) {
            return isset($this->_attributes[$name]);
        } else {
            return parent::__isset($name);
        }
    }

    /**
     * Sets a component property to be null.
     * Do not call this method. This is a PHP magic method that we override
     * to allow using unset() to set a component property to be null.
     * @param string $name the property name or the event name
     * @throws \CException if the property is read only.
     * @return mixed
     */
    public function __unset($name)
    {
        if (array_key_exists($name, $this->_attributes)) {
            unset($this->_attributes[$name]);
        } else {
            parent::__unset($name);
        }
    }

    /**
     * Returns whether there is an element at the specified offset.
     * This method is required by the interface ArrayAccess.
     * @param mixed $offset the offset to check on
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->_attributes);
    }

    /**
     * Returns the element at the specified offset.
     * This method is required by the interface ArrayAccess.
     * @param integer $offset the offset to retrieve element.
     * @return mixed the element at the offset, null if no element is found at the offset
     */
    public function offsetGet($offset)
    {
        return $this->_attributes[$offset];
    }

    /**
     * Sets the element at the specified offset.
     * This method is required by the interface ArrayAccess.
     * @param integer $offset the offset to set element
     * @param mixed   $item the element value
     */
    public function offsetSet($offset, $item)
    {
        $this->_attributes[$offset] = $item;
    }

    /**
     * Unsets the element at the specified offset.
     * This method is required by the interface ArrayAccess.
     * @param mixed $offset the offset to unset element
     */
    public function offsetUnset($offset)
    {
        unset($this->_attributes[$offset]);
    }
}
