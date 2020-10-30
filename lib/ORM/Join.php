<?php
    namespace Enobrev\ORM;
    
    use Enobrev\ORM\Condition\ConditionInterface;

    class Join {
        protected const LEFT_OUTER = 'LEFT OUTER';

        private string $sType;

        private Field $oFrom;

        private Field $oTo;

        private Conditions $oConditions;

        /**
         * @param Field $oFrom
         * @param Field $oTo
         * @param ConditionInterface|Conditions|null $oConditions
         *
         * @return Join
         */
        public static function create(Field $oFrom, Field $oTo, $oConditions = null): Join {
            $oJoin = new self;
            $oJoin->oFrom       = $oFrom;
            $oJoin->oTo         = $oTo;

            $oJoinCondition = new Conditions();
            $oJoinCondition->add(ColumnsConditionFactory::eq($oFrom, $oTo));

            if ($oConditions instanceof ConditionInterface
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
