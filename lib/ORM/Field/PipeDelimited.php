<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Field;

    class PipeDelimited extends Text {

        /**
         * @var array
         */
        public $sValue;

        /**
         *
         * @param array|string|Field $sValue
         */
        public function setValue($sValue) {
            if ($sValue instanceof Field) {
                $sValue = $sValue->getValue();
            }

            if (!is_array($sValue)) {
                $sValue = explode('|', $sValue);
            }

            if (!is_array($sValue)) {
                $sValue = array();
            }

            $this->sValue = $sValue;
        }

        /**
         * @return array
         */
        public function getValue() {
            return $this->sValue;
        }

        /**
         * @return bool
         */
        public function hasValue() {
            return is_array($this->sValue) && count($this->sValue);
        }

        /**
         *
         * @return string
         */
        public function __toString() {
            $sValue = is_array($this->sValue) ? $this->sValue : array();

            return implode('|', $sValue);
        }
    }
?>
