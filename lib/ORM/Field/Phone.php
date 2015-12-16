<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Field;

    class Phone extends Text {
        /**
         *
         * @return string
         */
        public function __toString() {
            return $this->sValue;
        }
        
        /**
         *
         * @param mixed $sValue 
         */
        public function setValue($sValue) {
            if ($sValue instanceof Field) {
                $sValue = $sValue->getValue();
            }

            if ($sValue instanceof Phone === false) {
                $sValue = new Phone($sValue);
            }

            $this->sValue = $sValue;
        }
    }
?>