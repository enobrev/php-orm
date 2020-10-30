<?php
    namespace Enobrev\ORM\Condition;

    use Enobrev\ORM\Field;

    class ColumnBetweenFieldAndValue implements ConditionInterface {
        use ConditionKeyTrait;

        protected string $sSign;
        protected Field  $oLow;

        /** @var mixed */
        protected        $mHigh;

        public function __construct(string $sSign, Field $oLow, $mHigh) {
            $this->sSign = $sSign;
            $this->oLow  = $oLow;
            $this->mHigh = $mHigh;
        }

        public function toSQL(): string {
            $oHigh  = clone $this->oLow;
            $oHigh->setValue($this->mHigh);

            $sSign  = $this->sSign;
            $sLeft  = $this->oLow->toSQLColumn();
            $sLow   = $this->oLow->toSQL();
            $sHigh  = $oHigh->toSQL();

            return "$sLeft $sSign $sLow AND $sHigh";
        }

        public function toSQLLog(): string {
            $sSign  = $this->sSign;
            $sLeft  = $this->oLow->toSQLColumn();
            $sLow   = $this->oLow->toSQLLog();

            return "$sLeft $sSign $sLow AND $sLow";
        }

        public function __clone() {
            $this->oLow  = clone $this->oLow;
        }
    }