<?php
    namespace Enobrev\ORM\Field;

    use DateTime as PHP_DateTime;
    use Enobrev\ORM\DbException;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;
    use Exception;

    class Time extends DateTime {

        const DEFAULT_FORMAT = 'H:i:s';
        const NULL_VALUE     = '00:00:00';

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


        /**
         * @return bool
         */
        public function isNull():bool {
            $sValue = $this->sValue instanceof PHP_DateTime ? $this->sValue->format(self::DEFAULT_FORMAT) : self::NULL_VALUE;

            if (substr($sValue, 0, 1) == '-') {
                return true;
            }

            return parent::isNull();
        }

        /**
         * @return bool
         */
        public function hasValue(): bool {
            return parent::hasValue() && (string) $this != self::NULL_VALUE;
        }

        /**
         *
         * @return string
         */
        public function __toString():string {
            $sValue = $this->sValue instanceof PHP_DateTime ? $this->sValue->format(self::DEFAULT_FORMAT) : self::NULL_VALUE;

            if (substr($sValue, 0, 1) == '-') {
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
            } else {
                return parent::toSQL();
            }
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