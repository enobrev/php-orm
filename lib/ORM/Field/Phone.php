<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;

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
         * @return $this
         */
        public function setValue($sValue) {
            if ($sValue instanceof Table) {
                $sValue = $sValue->{$this->sColumn};
            }

            if ($sValue instanceof Field) {
                $sValue = $sValue->getValue();
            }

            if ($sValue instanceof Phone === false) {
                $sValue = new Phone($sValue);
            }

            $this->sValue = $sValue;

            return $this;
        }
    }