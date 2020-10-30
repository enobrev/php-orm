<?php
    namespace Enobrev\ORM\Condition;

    use Enobrev\ORM\Field;

    class ColumnBetweenValues implements ConditionInterface {
        use ConditionKeyTrait;

        protected string $sSign;
        protected Field  $oLeft;

        /** @var mixed */
        protected        $mLow;

        /** @var mixed */
        protected        $mHigh;

        public function __construct(string $sSign, Field $oField, $mLow, $mHigh) {
            $this->sSign  = $sSign;
            $this->oLeft  = $oField;
            $this->mLow   = $mLow;
            $this->mHigh  = $mHigh;
        }

        public function toSQL(): string {
            $oLow = clone $this->oLeft;
            $oLow->setValue($this->mLow);

            $oHigh = clone $this->oLeft;
            $oHigh->setValue($this->mHigh);

            $sSign  = $this->sSign;
            $sLeft  = $this->oLeft->toSQLColumn();
            $sLow   = $oLow->toSQL();
            $sHigh  = $oHigh->toSQL();

            return "$sLeft $sSign $sLow AND $sHigh";
        }

        public function toSQLLog(): string {
            $sSign  = $this->sSign;
            $sLeft  = $this->oLeft->toSQLColumn();
            $sRight = $this->oLeft->toSQLLog();

            return "$sLeft $sSign $sRight AND $sRight";
        }

        public function __clone() {
            $this->oLeft = clone $this->oLeft;
        }
    }