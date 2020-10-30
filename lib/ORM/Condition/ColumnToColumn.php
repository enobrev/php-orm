<?php
    namespace Enobrev\ORM\Condition;

    use Enobrev\ORM\Field;

    class ColumnToColumn implements ConditionInterface {
        use ConditionKeyTrait;

        protected string $sSign;
        protected Field $oLeft;
        protected Field $oRight;

        public function __construct(string $sSign, Field $oField1, Field $oField2) {
            $this->sSign  = $sSign;
            $this->oLeft  = $oField1;
            $this->oRight = $oField2;
        }

        public function toSQL(): string {
            $sSign  = $this->sSign;
            $sLeft  = $this->oLeft->toSQLColumn();
            $sRight = $this->oRight->toSQLColumn();

            return "$sLeft $sSign $sRight";
        }

        public function toSQLLog(): string {
            $sSign  = $this->sSign;
            $sLeft  = $this->oLeft->toSQLColumn();
            $sRight = $this->oRight->toSQLColumn();

            return "$sLeft $sSign $sRight";
        }

        public function __clone() {
            $this->oLeft  = clone $this->oLeft;
            $this->oRight = clone $this->oRight;
        }
    }