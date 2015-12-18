<?php
    namespace Enobrev\ORM\Field;

    use DateTime;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;

    class Date extends Text {

        const DEFAULT_FORMAT = 'Y-m-d';
        const NULL_VALUE     = '0000-00-00 00:00:00';

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
            return parent::hasValue() && (string) $this != self::NULL_VALUE;
        }

        /**
         *
         * @return string
         */
        public function __toString() {
            $sValue = $this->sValue instanceof \DateTime ? $this->sValue->format(self::DEFAULT_FORMAT) : self::NULL_VALUE;
            
            if (substr($sValue, 0, 1) == '-') {
                $sValue = self::NULL_VALUE;
            }
            
            return $sValue;
        }

        /**
         *
         * @param mixed $sValue
         * @return Date
         */
        public function setValue($sValue) {
            if ($sValue instanceof Table) {
                $sValue = $sValue->{$this->sColumn};
            }

            if ($sValue instanceof Field) {
                $sValue = $sValue->getValue();
            }
            
            switch(true) {
                case $sValue == self::NULL_VALUE:
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