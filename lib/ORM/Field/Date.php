<?php
    namespace Enobrev\ORM\Field;

    use DateTime;
    use Enobrev\ORM\Field;

    class Date extends Text {

        /**
         * @var \DateTime
         */
        public $sValue;

        /**
         * @return \DateTime
         */
        public function getValue() {
            return $this->sValue;
        }

        /**
         * @return bool
         */
        public function hasValue() {
            return parent::hasValue() && (string) $this != '0000-00-00';
        }

        /**
         *
         * @return string
         */
        public function __toString() {
            $sValue = $this->sValue instanceof \DateTime ? $this->sValue->format('Y-m-d') : '0000-00-00';
            
            if (substr($sValue, 0, 1) == '-') {
                $sValue = '0000-00-00';
            }
            
            return $sValue;
        }

        /**
         *
         * @param mixed $sValue
         * @return Date
         */
        public function setValue($sValue) {
            if ($sValue instanceof Field) {
                $sValue = $sValue->getValue();
            }
            
            switch(true) {
                case $sValue == '0000-00-00':
                    $this->sValue = null;
                    break;

                case $sValue === null:
                case $sValue instanceof DateTime:
                    $this->sValue = $sValue;
                    break;

                default:
                    $this->sValue = new DateTime($sValue);
                    break;
            }

            return $this;
        }

        /**
         * @param mixed $mValue
         * @return bool
         */
        public function is($mValue) {
            if ($mValue instanceof \stdClass) {
                if (property_exists($mValue, 'date')) { // coming from json
                    $mValue = $mValue->date;
                }
            }

            return parent::is($mValue);
        }
    }
?>