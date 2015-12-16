<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Field;

    class DateTime extends Date {

        /**
         * @var \DateTime
         */
        public $sValue;

        /**
         *
         * @param mixed $sValue
         * @return DateTime
         */
        public function setValue($sValue) {
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
            $sValue = $this->sValue instanceof \DateTime ? $this->sValue->format('Y-m-d H:i:s') : '0000-00-00 00:00:00';

            if (substr($sValue, 0, 1) == '-') {
                return true;
            }

            return parent::isNull();
        }

        /**
         * @return bool
         */
        public function hasValue() {
            return parent::hasValue() && (string) $this != '0000-00-00 00:00:00';
        }

        /**
         *
         * @return string|integer
         */
        public function __toString() {
            $sValue = $this->sValue instanceof \DateTime ? $this->sValue->format('Y-m-d H:i:s') : '0000-00-00 00:00:00';

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