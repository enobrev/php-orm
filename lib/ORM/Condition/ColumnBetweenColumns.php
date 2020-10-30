<?php
    namespace Enobrev\ORM\Condition;

    use Enobrev\ORM\Field;

    class ColumnBetweenColumns implements ConditionInterface {
        use ConditionKeyTrait;

        protected string $sSign;
        protected Field  $oLeft;
        protected Field  $oLow;
        protected Field  $oHigh;

        public function __construct(string $sSign, Field $oLeft,  Field $oLow, Field $oHigh) {
            $this->sSign = $sSign;
            $this->oLeft = $oLeft;
            $this->oLow  = $oLow;
            $this->oHigh = $oHigh;
        }

        public function toSQL(): string {
            $sSign  = $this->sSign;
            $sLeft  = $this->oLeft->toSQLColumn();
            $sLow   = $this->oLow->toSQLColumn();
            $sHigh  = $this->oHigh->toSQLColumn();

            return "$sLeft $sSign $sLow AND $sHigh";
        }

        public function toSQLLog(): string {
            $sSign  = $this->sSign;
            $sLeft  = $this->oLow->toSQLColumn();
            $sLow   = $this->oLow->toSQLColumn();
            $sHigh  = $this->oHigh->toSQLColumn();

            return "$sLeft $sSign $sLow AND $sHigh";
        }

        public function __clone() {
            $this->oLeft = clone $this->oLeft;
            $this->oLow  = clone $this->oLow;
            $this->oHigh = clone $this->oHigh;
        }
    }