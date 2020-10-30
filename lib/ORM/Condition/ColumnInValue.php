<?php
    namespace Enobrev\ORM\Condition;

    use Enobrev\ORM\Exceptions\ConditionMissingInValueException;
    use Enobrev\ORM\Field;

    class ColumnInValue implements ConditionInterface {
        use ConditionKeyTrait;

        protected string $sSign;
        protected Field  $oLeft;
        protected array  $aRight;

        public function __construct(string $sSign, Field $oField, array $aValue) {
            assert(count($aValue) > 0, new ConditionMissingInValueException());

            $this->sSign  = $sSign;
            $this->oLeft  = $oField;
            $this->aRight = $aValue;
        }

        public function toSQL(): string {
            $oRight = clone $this->oLeft;
            $aRight = [];
            foreach($this->aRight as $mValue) {
                $oRight->setValue($mValue);
                $aRight[] = $oRight->toSQL();
            }

            $sSign  = $this->sSign;
            $sLeft  = $this->oLeft->toSQLColumn();
            $sRight = implode(', ', $aRight);

            return "$sLeft $sSign ( $sRight )";
        }

        public function toSQLLog(): string {
            $sSign  = $this->sSign;
            $sLeft  = $this->oLeft->toSQLColumn();
            $sRight = $this->oLeft->toSQLLog();

            return "$sLeft $sSign ( $sRight\[] )";
        }

        public function __clone() {
            $this->oLeft = clone $this->oLeft;
        }
    }