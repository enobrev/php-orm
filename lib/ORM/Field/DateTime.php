<?php
    namespace Enobrev\ORM\Field;

    use DateTime as PHP_DateTime;
    use Enobrev\ORM\DbException;
    use Enobrev\ORM\Escape;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;
    use Enobrev\ORM\DateFunction;
    use Exception;
    use stdClass;

    class DateTime extends Date {

        protected const DEFAULT_FORMAT = PHP_DateTime::RFC3339;
        protected const NULL_VALUE     = '0000-00-00 00:00:00';

        /**
         * @var PHP_DateTime|null
         */
        public $sValue;

        /**
         * @return bool
         */
        public function isNull():bool {
            $sValue = $this->sValue instanceof PHP_DateTime ? $this->sValue->format(self::DEFAULT_FORMAT) : self::NULL_VALUE;

            if (strpos($sValue, '-') === 0) {
                return true;
            }

            return parent::isNull();
        }

        /**
         * @return bool
         */
        public function hasValue(): bool {
            return parent::hasValue() && (string) $this !== self::NULL_VALUE;
        }


        public function __toString():string {
            $sValue = self::NULL_VALUE;

            if ($this->sValue instanceof PHP_DateTime) {
                $sValue = $this->sValue->format(self::DEFAULT_FORMAT);
            } else if ($this->sValue instanceof DateFunction) {
                $sValue = (new PHP_DateTime())->format(self::DEFAULT_FORMAT);
            }

            if (strpos($sValue, '-') === 0) {
                $sValue = 'NULL';
            }

            return $sValue;
        }

        /**
         *
         * @return string
         * @throws DbException
         */
        public function toSQL():string {
            if ($this->isNull()) {
                return 'NULL';
            }

            if ($this->sValue instanceof DateFunction) {
                return $this->sValue->getName();
            }

            if ($this->sValue instanceof PHP_DateTime) {
                return Escape::string($this->sValue->format('Y-m-d H:i:s'));
            }

            return 'NULL';
        }

        /**
         * @param mixed $mValue
         *
         * @return bool
         * @throws Exception
         */
        public function is($mValue): bool {
            if ($mValue instanceof Table) {
                $mValue = $mValue->{$this->sColumn};
            }

            if ($mValue instanceof self) {
                $mValue = $mValue->getValue();
            }

            if ($mValue instanceof PHP_DateTime) {
                $mValue = $mValue->format(self::DEFAULT_FORMAT);
            }

            if (($mValue instanceof stdClass) && property_exists($mValue, 'date')) { // coming from json
                $mValue = $mValue->date;
            }

            if ($mValue === null) {
                return $this->isNull(); // Both Null
            }

            if ($this->isNull()) {
                return false;           // My Value is null but comparator is not
            }

            /** @noinspection TypeUnsafeComparisonInspection */
            return (string) $this == (string) (new self($this->sTable))->setValue($mValue);
        }
    }