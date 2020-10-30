<?php
    namespace Enobrev\ORM\Condition;

    use Enobrev\ORM\Field;

    class ValueBetweenColumns implements ConditionInterface {
        use ConditionKeyTrait;

        protected string $sSign;
        protected        $mLeft;
        protected Field  $oLow;
        protected Field  $oHigh;

        public function __construct(string $sSign, $mValue, Field $oLow, Field $oHigh) {
            $this->sSign  = $sSign;
            $this->mLeft  = $mValue;
            $this->oLow   = $oLow;
            $this->oHigh  = $oHigh;
        }

        public function toSQL(): string {
            $oValue = clone $this->oLow;
            $oValue->setValue($this->mLeft);

            $sSign  = $this->sSign;
            $sLeft  = $oValue->toSQL();
            $sLow   = $this->oLow->toSQLColumn();
            $sHigh  = $this->oLow->toSQLColumn();

            return "$sLeft $sSign $sLow AND $sHigh";
        }

        public function toSQLLog(): string {
            $sSign  = $this->sSign;
            $sLeft  = $this->oLow->toSQLLog();
            $sLow   = $this->oLow->toSQLColumn();
            $sHigh  = $this->oHigh->toSQLColumn();

            return "$sLeft $sSign $sLow AND $sHigh";
        }

        public function __clone() {
            $this->oLeft = clone $this->oLow;
            $this->oHigh = clone $this->oHigh;
        }
    }