<?php
    namespace Enobrev\ORM;
    
    use function Enobrev\dbg;

    class ConditionException extends DbException {}
    class ConditionInvalidTypeException extends ConditionException {}
    class ConditionMissingBetweenValueException extends ConditionException {}
    class ConditionMissingInValueException extends ConditionException {}
    class ConditionMissingFieldException extends ConditionException {}

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

        /** @var string  */
        private $sSign;

        /** @var array  */
        private $aElements = [];

        /** @var array  */
        private static $aSigns = [
            self::NOTNULL, self::LT, self::LTE, self::GT, self::GTE, self::EQUAL, self::NEQ, self::LIKE, self::NLIKE, self::ISNULL, self::BETWEEN, self::IN, self::NIN
        ];

        public function __clone() {
            foreach ($this->aElements as $iElement => $mElement) {
                if ($mElement instanceof Field) {
                    $this->aElements[$iElement] = clone $mElement;
                } else {
                    $this->aElements[$iElement] = $mElement;
                }
            }
        }

        /**
         * @param mixed $sElement
         * @return bool
         */
        private static function isSign($sElement) {
            return in_array($sElement, self::$aSigns);
        }

        /**
         * @param string $sSign
         * @param  mixed ...$aElements,... As many args as necessary.  Field MUST come before values.  Condition Type can come in any order, defaults to Equals
         * @return Condition
         * @throws ConditionInvalidTypeException
         * @throws ConditionMissingBetweenValueException
         * @throws ConditionMissingFieldException
         * @throws ConditionMissingInValueException
         */
        private static function create(string $sSign, ...$aElements) {
            if (!self::isSign($sSign)) {
                throw new ConditionInvalidTypeException();
            }

            $oCondition = new self;
            $oCondition->sSign = $sSign;

            foreach($aElements as $mElement) {
                if ($mElement instanceof Field) {
                    $oCondition->aElements[] = $mElement;
                } else if (isset($oCondition->aElements[0])
                       &&        $oCondition->aElements[0] instanceof Field) { // Value should be of the same field type as Field
                    /** @var Field $oField */
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

        public static function eq(...$aArguments): Condition {
            return self::create(self::EQUAL, ...$aArguments);
        }

        public static function neq(...$aArguments): Condition {
            return self::create(self::NEQ, ...$aArguments);
        }

        public static function lt(...$aArguments): Condition {
            return self::create(self::LT, ...$aArguments);
        }

        public static function lte(...$aArguments): Condition {
            return self::create(self::LTE, ...$aArguments);
        }

        public static function gt(...$aArguments): Condition {
            return self::create(self::GT, ...$aArguments);
        }

        public static function gte(...$aArguments): Condition {
            return self::create(self::GTE, ...$aArguments);
        }

        public static function like(...$aArguments): Condition {
            return self::create(self::LIKE, ...$aArguments);
        }

        public static function nlike(...$aArguments): Condition {
            return self::create(self::NLIKE, ...$aArguments);
        }

        public static function in(...$aArguments): Condition {
            return self::create(self::IN, ...$aArguments);
        }

        public static function nin(...$aArguments): Condition {
            return self::create(self::NIN, ...$aArguments);
        }

        public static function nul(...$aArguments): Condition {
            return self::create(self::ISNULL, ...$aArguments);
        }

        public static function nnul(...$aArguments): Condition {
            return self::create(self::NOTNULL, ...$aArguments);
        }

        public static function between(...$aArguments): Condition {
            return self::create(self::BETWEEN, ...$aArguments);
        }

        public function __construct() {
            $this->sSign     = self::EQUAL;
            $this->aElements = array();
        }

        /**
         * @return string
         */
        public function toSQL() {
            if (!count($this->aElements)) {
                return '';
            }

            /** @var Field $oField */
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
                /** @var Field $oField1 */
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
                    /** @var Field $oField2 */
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
                /** @var Field $oField1 */
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
            /** @var Field $oField */
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
                /** @var Field $oField1 */
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
                    /** @var Field $oField2 */
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
                /** @var Field $oField1 */
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

        public function toKey(): string {
            $sKey = str_replace(' ', '_', $this->toSQL());
            return preg_replace('/[^a-zA-Z0-9_=<>!]/', '-', $sKey);
        }
    }
