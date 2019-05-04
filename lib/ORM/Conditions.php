<?php
    namespace Enobrev\ORM;
    
    class ConditionsException extends DbException {}
    class ConditionsNonConditionException extends ConditionsException {}

    class Conditions {
        const TYPE_AND = 'AND';
        const TYPE_OR  = 'OR';

        /** @var array  */
        private static $aTypes = [
            self::TYPE_AND, self::TYPE_OR
        ];

        /**
         * @param mixed $sElement
         * @return bool
         */
        private static function isType($sElement) {
            return in_array($sElement, self::$aTypes);
        }

        /** @var string  */
        private $sType;

        /** @var Condition[]|Conditions[] */
        private $aConditions;

        public function __clone() {
            foreach($this->aConditions as $iIndex => $mCondition) {
                $this->aConditions[$iIndex] = clone $mCondition;
            }
        }

        /**
         * @param Condition[]|Conditions|string[] $aConditions
         * @return Conditions
         * @throws ConditionsNonConditionException
         * @psalm-suppress RawObjectIteration
         * @psalm-suppress MismatchingDocblockParamType
         */
        private static function create(...$aConditions) {
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
                        /** @var string $mCondition */
                        $oConditions->sType = $mCondition;
                        break;
                }
            }

            return $oConditions;
        }

        /**
         * @param array ...$aArguments
         * @return Conditions
         */
        public static function also(...$aArguments) {
            return self::create(self::TYPE_AND, ...$aArguments);
        }

        /**
         * @param array ...$aArguments
         * @return Conditions
         */
        public static function either(...$aArguments) {
            return self::create(self::TYPE_OR, ...$aArguments);
        }

        public function __construct() {
            $this->sType       = self::TYPE_AND;
            $this->aConditions = [];
        }

        /**
         * @param Condition|Conditions|Field|Condition[]|Field[]|Conditions[]|string $oCondition
         * @throws ConditionsNonConditionException
         */
        public function add($oCondition):void {
            switch(true) {
                case $oCondition instanceof self:
                case $oCondition instanceof Condition:
                    $this->aConditions[] = $oCondition;
                    break;

                case $oCondition instanceof Field:
                    $this->add(Condition::eq($oCondition));
                    break;

                case is_array($oCondition):
                    foreach($oCondition as $oField) {
                        $this->add($oField);
                    }
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

        public function count():int {
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

        public function toKey():string {
            // TODO: Order By Field Name
            return preg_replace('/[^0-9a-zA-Z_.<>=-]/', '', $this->toSQL());
        }
    }
