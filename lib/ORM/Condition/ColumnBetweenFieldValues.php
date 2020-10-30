<?php
    namespace Enobrev\ORM\Condition;

    use Enobrev\ORM\Field;

    class ColumnBetweenFieldValues implements ConditionInterface {
        use ConditionKeyTrait;

        protected string $sSign;
        protected Field  $oLow;
        protected Field  $oHigh;

        public function __construct(string $sSign, Field $oLow, Field $oHigh) {
            $this->sSign = $sSign;
            $this->oLow  = $oLow;
            $this->oHigh = $oHigh;
        }

        public function toSQL(): string {
            $sSign  = $this->sSign;
            $sLeft  = $this->oLow->toSQLColumn();
            $sLow   = $this->oLow->toSQL();
            $sHigh  = $this->oHigh->toSQL();

            return "$sLeft $sSign $sLow AND $sHigh";
        }

        public function toSQLLog(): string {
            $sSign  = $this->sSign;
            $sLeft  = $this->oLow->toSQLColumn();
            $sLow   = $this->oLow->toSQLLog();
            $sHigh  = $this->oHigh->toSQLLog();

            return "$sLeft $sSign $sLow AND $sHigh";
        }

        public function __clone() {
            $this->oLow  = clone $this->oLow;
            $this->oHigh = clone $this->oHigh;
        }
    }