<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;

    class Time extends Date {

        const DEFAULT_FORMAT = 'H:i:s';
        const NULL_VALUE     = '00:00:00';

        /**
         * @var \DateTime
         */
        public $sValue;

        /**
         *
         * @param mixed $sValue
         * @return self
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
        public function isNull() {
            $sValue = $this->sValue instanceof \DateTime ? $this->sValue->format(self::DEFAULT_FORMAT) : self::NULL_VALUE;

            if (substr($sValue, 0, 1) == '-') {
                return true;
            }

            return parent::isNull();
        }

        /**
         * @return bool
         */
        public function hasValue() {
            return parent::hasValue() && (string) $this != self::NULL_VALUE;
        }

        /**
         *
         * @return string|integer
         */
        public function __toString() {
            $sValue = $this->sValue instanceof \DateTime ? $this->sValue->format(self::DEFAULT_FORMAT) : self::NULL_VALUE;

            if (substr($sValue, 0, 1) == '-') {
                $sValue = 'NULL';
            }

            return $sValue;
        }

        /**
         *
         * @return string
         */
        public function toSQL() {
            if ($this->isNull()) {
                return 'NULL';
            } else {
                return parent::toSQL();
            }
        }
    }
?>