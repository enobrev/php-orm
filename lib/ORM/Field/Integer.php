<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;

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
         * @return Integer;
         */
        public function setValue($sValue) {
            if ($sValue instanceof Table) {
                $sValue = $sValue->{$this->sColumn};
            }

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

            return $this;
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