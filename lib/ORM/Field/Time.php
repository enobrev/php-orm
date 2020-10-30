<?php
    namespace Enobrev\ORM\Field;

    use DateTime as PHP_DateTime;
    use Exception;

    use Enobrev\ORM\Exceptions\DbException;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;

    class Time extends DateTime {

        protected const DEFAULT_FORMAT = 'H:i:s';
        protected const NULL_VALUE     = '00:00:00';

        /**
         * @var PHP_DateTime|null
         */
        public $sValue;

        /**
         *
         * @param mixed $sValue
         *
         * @return $this
         * @throws Exception
         * @noinspection PhpMissingReturnTypeInspection
         */
        public function setValue($sValue) {
            if ($sValue instanceof Table) {
                $sValue = $sValue->{$this->sColumn};
            }

            if ($sValue instanceof Field) {
                $sValue = $sValue->getValue();
            }

            if ($sValue === 'NULL'
            ||  $sValue === NULL) {
                $this->sValue = NULL;
            } else {
                parent::setValue($sValue);
            }

            return $this;
        }


        public function isNull():bool {
            $sValue = $this->sValue instanceof PHP_DateTime ? $this->sValue->format(self::DEFAULT_FORMAT) : self::NULL_VALUE;

            if (strpos($sValue, '-') === 0) {
                return true;
            }

            return parent::isNull();
        }

        public function hasValue(): bool {
            return parent::hasValue() && (string) $this !== self::NULL_VALUE;
        }

        public function __toString():string {
            $sValue = $this->sValue instanceof PHP_DateTime ? $this->sValue->format(self::DEFAULT_FORMAT) : self::NULL_VALUE;

            if (strpos($sValue, '-') === 0) {
                $sValue = 'NULL';
            }

            return $sValue;
        }

        /**
         * @return string
         * @throws DbException
         */
        public function toSQL():string {
            if ($this->isNull()) {
                return 'NULL';
            }

            return parent::toSQL();
        }

        /**
         * @param mixed $mValue
         *
         * @return bool
         * @throws Exception
         */
        public function is($mValue):bool {
            if ($mValue instanceof PHP_DateTime) {
                $mValue = $mValue->format(self::DEFAULT_FORMAT);
            }

            return parent::is($mValue);
        }
    }