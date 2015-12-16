<?php
    namespace Enobrev\ORM;
    
    class ConditionsException extends DbException {}
    class ConditionsNonConditionException extends ConditionsException {}

    /**
     * @throws ConditionsNonConditionException
     * @method static Conditions also()
     * @method static Conditions either()
     *
     */
    class Conditions {
        const TYPE_AND = 'AND';
        const TYPE_OR  = 'OR';

        private static $aTypes = array(
            self::TYPE_AND, self::TYPE_OR
        );

        /**
         * @static
         * @param mixed $sElement
         * @return bool
         */
        private static function isType($sElement) {
            return in_array($sElement, self::$aTypes);
        }

        private $sType;

        /** @var Condition[] $aConditions */
        private $aConditions;

        /**
         * @static
         * @param array $aConditions,... Array of Condition|Conditions
         * @return Conditions
         */
        public static function create($aConditions) {
            $aConditions = func_get_args();
            $oConditions = new self();
            foreach($aConditions as $mCondition) {
                switch(true) {
                    default:
                        $oConditions->add($mCondition);
                        break;

                    case is_array($mCondition):
                        foreach($mCondition as $mArrayCondition) {
                            $oConditions->add($mArrayCondition);
                        }
                        break;

                    case self::isType($mCondition):
                        $oConditions->sType = $mCondition;
                        break;
                }
            }

            return $oConditions;
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
                case 'also':   array_unshift($aArguments, self::TYPE_AND); break;
                case 'either': array_unshift($aArguments, self::TYPE_OR);  break;
            }
            
            return call_user_func_array('self::create', $aArguments);
        }

        public function __construct() {
            $this->sType       = self::TYPE_AND;
            $this->aConditions = array();
        }

        public function add($oCondition) {
            switch(true) {
                case $oCondition instanceof self:
                case $oCondition instanceof Condition:
                    $this->aConditions[] = $oCondition;
                    break;

                case $oCondition instanceof Fields:
                    foreach($oCondition as $oField) {
                        $this->add($oField);
                    }
                    break;

                case $oCondition instanceof Field:
                    $this->add(Condition::eq($oCondition));
                    break;

                case $oCondition === null:
                    // Deliberately skip me, please
                    break;

                case self::isType($oCondition):
                    break;
                default:
                    throw new ConditionsNonConditionException();
                    break;

            }
        }

        public function count() {
            return count($this->aConditions);
        }

        /**
         * @return string
         */
        public function toSQL() {
            $aOutput = array();

            // get rid of double-parentheses
            if ($this->count() == 1
            &&  $this->aConditions[0] instanceof self) {
                return $this->aConditions[0]->toSQL();
            }

            foreach($this->aConditions as $mCondition) {
                if ($mCondition instanceof self) {
                    $aOutput[] = '(' . $mCondition->toSQL() . ')';
                } else {
                    $aOutput[] = $mCondition->toSQL();
                }
            }

            return implode(' ' . $this->sType . ' ', $aOutput);
        }

        /**
         * @return string
         */
        public function toSQLLog() {
            $aOutput = array();

            // get rid of double-parentheses
            if ($this->count() == 1
            &&  $this->aConditions[0] instanceof self) {
                return $this->aConditions[0]->toSQLLog();
            }

            foreach($this->aConditions as $mCondition) {
                if ($mCondition instanceof self) {
                    $aOutput[] = '(' . $mCondition->toSQLLog() . ')';
                } else {
                    $aOutput[] = $mCondition->toSQLLog();
                }
            }

            return implode(' ' . $this->sType . ' ', $aOutput);
        }

        public function toKey() {
            // TODO: Order By Field Name
            return preg_replace('/[^0-9a-zA-Z_.<>=-]/', '', $this->toSQL());
        }
    }
