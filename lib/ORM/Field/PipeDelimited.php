<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;

    class PipeDelimited extends Text {

        /**
         * @var array
         */
        public $sValue;

        /**
         *
         * @param array|string|Field $sValue
         * @return PipeDelimited
         */
        public function setValue($sValue) {
            if ($sValue instanceof Table) {
                $sValue = $sValue->{$this->sColumn};
            }

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

            return $this;
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
