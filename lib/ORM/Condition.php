<?php
    namespace Enobrev\ORM;
    
    class ConditionException extends DbException {}
    class ConditionMissingBetweenValueException extends ConditionException {}
    class ConditionMissingInValueException extends ConditionException {}
    class ConditionMissingFieldException extends ConditionException {}

    /**
     * @throws ConditionMissingBetweenValueException|ConditionMissingFieldException
     * @method static Condition eq()
     * @method static Condition neq()
     * @method static Condition lt()
     * @method static Condition lte()
     * @method static Condition gt()
     * @method static Condition gte()
     * @method static Condition like()
     * @method static Condition nul()
     * @method static Condition in()
     * @method static Condition nin()
     * @method static Condition between()
     */
    class Condition {
        const LT           = '<';
        const LTE          = '<=';
        const GT           = '>';
        const GTE          = '>=';
        const EQUAL        = '=';
        const NEQ          = '<>';
        const IN           = 'IN';
        const NIN          = 'NOT IN';
        const LIKE         = 'LIKE';
        const NLIKE        = 'NOT LIKE';
        const ISNULL       = 'IS NULL';
        const NOTNULL      = 'IS NOT NULL';
        const BETWEEN      = 'BETWEEN';

        private $sSign;
        private $aElements;

        private static $aSigns = array(
            self::NOTNULL, self::LT, self::LTE, self::GT, self::GTE, self::EQUAL, self::NEQ, self::LIKE, self::NLIKE, self::ISNULL, self::BETWEEN, self::IN, self::NIN
        );

        /**
         * @param mixed $sElement
         * @return bool
         */
        private static function isSign($sElement) {
            return in_array($sElement, self::$aSigns);
        }

        /**
         * @param mixed $aElements,... As many args as necessary.  Field MUST come before values.  Condition Type can come in any order, defaults to Equals
         * @return Condition
         * @throws ConditionMissingInValueException
         * @throws ConditionMissingFieldException
         * @throws ConditionMissingBetweenValueException
         */
        public static function create($aElements) {
            $aElements = func_get_args();

            $oCondition = new self;
            foreach($aElements as $mElement) {
                switch(true) {
                    case !is_int($mElement)
                      && !is_bool($mElement)
                      && self::isSign($mElement):
                        $oCondition->sSign = $mElement;
                        break;

                    case $mElement instanceof Field:
                        $oCondition->aElements[] = $mElement;
                        break;

                    default:
                        // Value should be of the same field type as Field
                        if (isset($oCondition->aElements[0])
                        &&        $oCondition->aElements[0] instanceof Field) {
                            /** @var Field $oField  */
                            $oField = clone $oCondition->aElements[0];

                            if (is_array($mElement)) {
                                $oCondition->aElements[] = $mElement;
                            } else {
                                $oField->setValue($mElement);
                                $oCondition->aElements[] = $oField;
                            }
                        } else {
                            $oCondition->aElements[] = $mElement;
                        }
                        break;
                }
            }

            if ($oCondition->sSign == self::BETWEEN) {
                if (count($oCondition->aElements) < 2) {
                    throw new ConditionMissingBetweenValueException;
                }
            } else if ($oCondition->sSign == self::IN
                   ||  $oCondition->sSign == self::NIN) {

                if (count($oCondition->aElements) < 1) {
                    throw new ConditionMissingFieldException;
                }

                if (!is_array($oCondition->aElements[1])) {
                    throw new ConditionMissingInValueException;
                }
            } else {
                if (count($oCondition->aElements) < 1) {
                    throw new ConditionMissingFieldException;
                }
            }

            return $oCondition;
        }

        /**
         * Wrapper method defining condition types in method name
         * @static
         * @param string $sName
         * @param array $aArguments
         * @return Condition
         */
        public static function __callStatic($sName, $aArguments) {
            switch($sName) {
                default:
                case 'eq':      array_unshift($aArguments, self::EQUAL);    break;
                case 'neq':     array_unshift($aArguments, self::NEQ);      break;
                case 'lt':      array_unshift($aArguments, self::LT);       break;
                case 'lte':     array_unshift($aArguments, self::LTE);      break;
                case 'gt':      array_unshift($aArguments, self::GT);       break;
                case 'gte':     array_unshift($aArguments, self::GTE);      break;
                case 'like':    array_unshift($aArguments, self::LIKE);     break;
                case 'nlike':   array_unshift($aArguments, self::NLIKE);     break;
                case 'in':      array_unshift($aArguments, self::IN);       break;
                case 'nin':     array_unshift($aArguments, self::NIN);       break;
                case 'nul':     array_unshift($aArguments, self::ISNULL);   break;
                case 'notnul':  array_unshift($aArguments, self::NOTNULL);   break;
                case 'between': array_unshift($aArguments, self::BETWEEN);  break;
            }
            
            return call_user_func_array('self::create', $aArguments);
        }

        public function __construct() {
            $this->sSign     = self::EQUAL;
            $this->aElements = array();
        }

        /**
         * @return string
         */
        public function toSQL() {
            /** @var Field $oField  */
            $oField = $this->aElements[0];

            if (count($this->aElements) == 1) {
                if ($this->sSign == self::ISNULL
                ||  $this->sSign == self::NOTNULL) {
                    return implode(' ',
                        array(
                            $oField->toSQLColumn(),
                            $this->sSign
                        )
                    );
                } else {
                    return implode(' ',
                        array(
                            $oField->toSQLColumn(),
                            $this->sSign,
                            $oField->toSQL()
                        )
                    );
                }
            } else if ($this->sSign == self::BETWEEN) {
                /** @var Field $oField1  */
                $oField1 = $this->aElements[1];
                if (count($this->aElements) == 2) {
                    return implode(' ',
                        array(
                            $oField->toSQLColumn(),
                            $this->sSign,
                            $oField->toSQL(),
                            'AND',
                            $oField1->toSQL()
                        )
                    );
                } else {
                    /** @var Field $oField2  */
                    $oField2 = $this->aElements[2];
                    return implode(' ',
                        array(
                            $oField->toSQLColumn(),
                            $this->sSign,
                            $oField1->toSQL(),
                            'AND',
                            $oField2->toSQL()
                        )
                    );
                }
            } else if ($this->sSign == self::IN
                   ||  $this->sSign == self::NIN) {
                // format values
                $aValues = $this->aElements[1];
                foreach ($aValues as &$sValue) {
                    $oField->setValue($sValue);
                    $sValue = $oField->toSQL();
                }

                return implode(' ',
                    array(
                        $oField->toSQLColumn(),
                        $this->sSign,
                        '(',
                        implode(', ', $aValues),
                        ')'
                    )
                );
            } else {
                /** @var Field $oField1  */
                $oField1 = $this->aElements[1];

                return implode(' ',
                    array(
                        $oField->toSQLColumn(),
                        $this->sSign,
                        $oField1->toSQL()
                    )
                );
            }
        }

        /**
         * @return string
         */
        public function toSQLLog() {
            /** @var Field $oField  */
            $oField = $this->aElements[0];

            if (count($this->aElements) == 1) {
                if ($this->sSign == self::ISNULL) {
                    return implode(' ',
                        array(
                            $oField->toSQLColumn(),
                            $this->sSign
                        )
                    );
                } else {
                    return implode(' ',
                        array(
                            $oField->toSQLColumn(),
                            $this->sSign,
                            $oField->toSQLLog()
                        )
                    );
                }
            } else if ($this->sSign == self::BETWEEN) {
                /** @var Field $oField1  */
                $oField1 = $this->aElements[1];
                if (count($this->aElements) == 2) {
                    return implode(' ',
                        array(
                            $oField->toSQLColumn(),
                            $this->sSign,
                            $oField->toSQLLog(),
                            'AND',
                            $oField1->toSQLLog()
                        )
                    );
                } else {
                    /** @var Field $oField2  */
                    $oField2 = $this->aElements[2];
                    return implode(' ',
                        array(
                            $oField->toSQLColumn(),
                            $this->sSign,
                            $oField1->toSQLLog(),
                            'AND',
                            $oField2->toSQLLog()
                        )
                    );
                }
            } else if ($this->sSign == self::IN
                ||  $this->sSign == self::NIN) {
                // format values
                $aValues = $this->aElements[1];
                foreach ($aValues as &$sValue) {
                    $oField->setValue($sValue);
                    $sValue = $oField->toSQLLog();
                }

                return implode(' ',
                    array(
                        $oField->toSQLColumn(),
                        $this->sSign,
                        '(Array)'
                    )
                );
            } else {
                /** @var Field $oField1  */
                $oField1 = $this->aElements[1];

                return implode(' ',
                    array(
                        $oField->toSQLColumn(),
                        $this->sSign,
                        $oField1->toSQLLog()
                    )
                );
            }
        }

        public function toKey() {
            $sKey = str_replace(' ', '_', $this->toSQL());
            return preg_replace('/[^a-zA-Z0-9_=<>!]/', '-', $sKey);
        }
    }
