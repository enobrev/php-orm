<?php
    namespace Enobrev\ORM\Condition;

    use Enobrev\ORM\Field;

    class ColumnBetweenOtherFieldValues implements ConditionInterface {
        use ConditionKeyTrait;

        protected string $sSign;
        protected Field  $oLeft;
        protected Field  $oLow;
        protected Field  $oHigh;

        public function __construct(string $sSign, Field $oField, Field $oLow, Field $oHigh) {
            $this->sSign  = $sSign;
            $this->oLeft  = $oField;
            $this->oLow   = $oLow;
            $this->oHigh  = $oHigh;
        }

        public function toSQL(): string {
            $sSign  = $this->sSign;
            $sLeft  = $this->oLeft->toSQLColumn();
            $sLow   = $this->oLow->toSQL();
            $sHigh  = $this->oHigh->toSQL();

            return "$sLeft $sSign $sLow AND $sHigh";
        }

        public function toSQLLog(): string {
            $sSign  = $this->sSign;
            $sLeft  = $this->oLeft->toSQLColumn();
            $sLow   = $this->oLow->toSQLLog();
            $sHigh  = $this->oHigh->toSQLLog();

            return "$sLeft $sSign $sLow AND $sHigh";
        }

        public function __clone() {
            $this->oLeft = clone $this->oLeft;
        }
    }