<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;

    class TextTrimmed extends Text {

        /**
         *
         * @return string
         */
        public function getValue(): string {
            return trim($this->sValue);
        }

        /**
         *
         * @param mixed $sValue
         * @return $this
         */
        public function setValue($sValue) {
            if ($sValue instanceof Table) {
                $sValue = $sValue->{$this->sColumn};
            }

            if ($sValue instanceof Field) {
                $sValue = $sValue->getValue();
            }

            $this->sValue = trim($sValue);
            return $this;
        }

        /**
         *
         * @return string
         */
        public function __toString() {
            return trim($this->sValue);
        }
    }