<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;

    class Boolean extends Number {
        /**
         *
         * @return string
         */
        public function __toString() {
            return $this->sValue ? '1' : '0';
        }

        /**
         *
         * @param mixed $sValue
         * @return Boolean
         */
        public function setValue($sValue) {
            if ($sValue instanceof Table) {
                $sValue = $sValue->{$this->sColumn};
            }

            if ($sValue instanceof Field) {
                $sValue = $sValue->getValue();
            }

            $this->sValue = $sValue ? true : false;

            return $this;
        }

        /**
         * @return bool
         */
        public function isTrue() {
            return $this->sValue ? true : false;
        }

        /**
         * @return bool
         */
        public function isFalse() {
            return !$this->isTrue();
        }
    }
?>