<?php
    namespace Enobrev\ORM\Condition;

    use Enobrev\ORM\Field;

    class ColumnNull implements ConditionInterface {
        use ConditionKeyTrait;

        protected string $sSign;
        protected Field  $oLeft;

        public function __construct(string $sSign, Field $oField) {
            $this->sSign = $sSign;
            $this->oLeft = $oField;
        }

        public function toSQL(): string {
            $sSign  = $this->sSign;
            $sLeft  = $this->oLeft->toSQLColumn();

            return "$sLeft $sSign";
        }

        public function toSQLLog(): string {
            $sSign  = $this->sSign;
            $sLeft = $this->oLeft->toSQLLog();

            return "$sLeft $sSign";
        }

        public function __clone() {
            $this->oLeft = clone $this->oLeft;
        }
    }