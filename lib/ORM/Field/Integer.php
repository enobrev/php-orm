<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Field;

    class Integer extends Number {

        public function increment() {
            if (!$this->isNull()) {
                $this->sValue += 1;
            }
        }
        public function decrement() {
            if (!$this->isNull()) {
                $this->sValue -= 1;
            }
        }

        /**
         *
         * @param mixed $sValue
         */
        public function setValue($sValue) {
            if ($sValue instanceof Field) {
                $sValue = $sValue->getValue();
            }

            if (strtolower($sValue) === "null") {
                $sValue = null;
            }

            if ($sValue !== null) {
                $sValue = (int) $sValue;
            }

            $this->sValue = $sValue;
        }

        /**
         *
         * @return string
         */
        public function toSQL() {
            if (!$this->hasValue()) {
                return 'NULL';
            }

            return parent::toSQL();
        }
    }
?>