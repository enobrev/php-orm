<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Field;

    class TextTrimmed extends Text {

        /**
         *
         * @return string|NULL
         */
        public function getValue() {
            return trim($this->sValue);
        }

        /**
         *
         * @param mixed $sValue
         * @return TextTrimmed
         */
        public function setValue($sValue) {
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
?>
