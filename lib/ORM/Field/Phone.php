<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;

    class Phone extends Text {
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

            if ($sValue instanceof self === false) {
                $sValue = new self($sValue);
            }

            $this->sValue = $sValue;

            return $this;
        }
    }