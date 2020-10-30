<?php
    namespace Enobrev\ORM;
    
    use Enobrev\ORM\Condition\ConditionInterface;
    use Enobrev\ORM\Exceptions\ConditionsNonConditionException;

    class Conditions {
        // TODO: An ENUM would be great here
        protected const TYPE_AND = 'AND';
        protected const TYPE_OR  = 'OR';

        private static array $aTypes = [
            self::TYPE_AND, self::TYPE_OR
        ];

        private string $sType;

        /** @var ConditionInterface[]|Conditions[] */
        private array $aConditions;

        public function __clone() {
            foreach($this->aConditions as $iIndex => $mCondition) {
                $this->aConditions[$iIndex] = clone $mCondition;
            }
        }

        /**
         * @param string $sType
         * @param ConditionInterface[]|ConditionInterface|Conditions|Field|Field[] $aConditions
         *
         * @return Conditions
         */
        private static function create(string $sType, ...$aConditions): Conditions {
            assert(in_array($sType, self::$aTypes, true));

            $oConditions = new self($sType);
            foreach($aConditions as $mCondition) {
                if (is_array($mCondition)) {
                    foreach($mCondition as $mArrayCondition) {
                        $oConditions->add($mArrayCondition);
                    }
                } else {
                    $oConditions->add($mCondition);
                }
            }

            return $oConditions;
        }

        /**
         * @param ConditionInterface[]|ConditionInterface|Conditions|Field|Field[] $aArguments
         *
         * @return Conditions
         */
        public static function also(...$aArguments): Conditions {
            return self::create(self::TYPE_AND, ...$aArguments);
        }

        /**
         * @param ConditionInterface[]|ConditionInterface|Conditions|Field|Field[] $aArguments
         *
         * @return Conditions
         */
        public static function either(...$aArguments): Conditions {
            return self::create(self::TYPE_OR, ...$aArguments);
        }

        public function __construct(string $sType = self::TYPE_AND) {
            $this->sType       = $sType;
            $this->aConditions = [];
        }

        /**
         * @param ConditionInterface[]|ConditionInterface|Conditions|Field|Field[] $oCondition
         */
        public function add($oCondition):void {
            assert($oCondition instanceof Conditions || $oCondition instanceof ConditionInterface || is_array($oCondition), new ConditionsNonConditionException());

            switch(true) {
                case $oCondition instanceof Conditions:
                case $oCondition instanceof ConditionInterface:
                    $this->aConditions[] = $oCondition;
                    break;

                case $oCondition instanceof Field:
                    $this->aConditions[] = ConditionFactory::eq($oCondition);
                    break;

                case is_array($oCondition):
                    foreach($oCondition as $oField) {
                        $this->add($oField);
                    }
                    break;

                default:
                case $oCondition === null:
                    // Deliberately skip me, please
                    break;
            }
        }

        public function count():int {
            return count($this->aConditions);
        }

        public function toSQL(): string {
            $aOutput = array();

            // get rid of double-parentheses
            if ($this->count() === 1
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

        public function toSQLLog(): string {
            $aOutput = array();

            // get rid of double-parentheses
            if ($this->count() === 1
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
