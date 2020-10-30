<?php
    namespace Enobrev\ORM\Condition;

    use Enobrev\ORM\Field;

    class ColumnToValue implements ConditionInterface {
        use ConditionKeyTrait;

        protected string $sSign;
        protected Field  $oLeft;
        protected        $mRight;

        public function __construct(string $sSign, Field $oField, $mValue) {
            $this->sSign  = $sSign;
            $this->oLeft  = $oField;
            $this->mRight = $mValue;
        }

        public function toSQL(): string {
            $oRight = clone $this->oLeft;
            $oRight->setValue($this->mRight);

            $sSign  = $this->sSign;
            $sLeft  = $this->oLeft->toSQLColumn();
            $sRight = $oRight->toSQL();

            return "$sLeft $sSign $sRight";
        }

        public function toSQLLog(): string {
            $sSign  = $this->sSign;
            $sLeft  = $this->oLeft->toSQLColumn();
            $sRight = $this->oLeft->toSQLLog();

            return "$sLeft $sSign $sRight";
        }

        public function __clone() {
            $this->oLeft = clone $this->oLeft;
        }
    }