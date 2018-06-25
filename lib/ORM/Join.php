<?php
    namespace Enobrev\ORM;
    
    class JoinException extends DbException {}

    class Join {
        const LEFT_OUTER = 'LEFT OUTER';

        /** @var string  */
        private $sType;
        
        /** @var  Field */
        private $oFrom = null;

        /** @var  Field */
        private $oTo = null;

        /** @var  Condition|Conditions */
        private $oConditions = null;

        /**
         * @param $oFrom
         * @param $oTo
         * @param $oConditions
         * @return Join
         * @throws ConditionsNonConditionException
         */
        public static function create($oFrom, $oTo, $oConditions = null) {
            $oJoin = new self;
            $oJoin->oFrom       = $oFrom;
            $oJoin->oTo         = $oTo;

            $oJoinCondition = new Conditions();
            $oJoinCondition->add(Condition::eq($oFrom, $oTo, Condition::JOIN));

            if ($oConditions instanceof Condition
            ||  $oConditions instanceof Conditions) {
                $oJoinCondition->add($oConditions);
            }

            $oJoin->oConditions = $oJoinCondition;

            return $oJoin;
        }

        public function __construct() {
            $this->sType   = self::LEFT_OUTER;
        }

        public function toSQL(): string {
            if ($this->oTo->hasAlias()) {
                $aResponse = [
                    $this->sType,
                    'JOIN',
                    $this->oTo->sTable,
                    'AS',
                    $this->oTo->sAlias,
                    'ON',
                    $this->oConditions->toSQL()
                ];
            } else {
                $aResponse = [
                    $this->sType,
                    'JOIN',
                    $this->oTo->sTable,
                    'ON',
                    $this->oConditions->toSQL()
                ];
            }

            return implode(' ', $aResponse);
        }
    }
