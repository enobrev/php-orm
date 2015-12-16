<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Field;

    class Decimal extends Number {
        /**
         *
         * @param mixed $sValue
         * @return Decimal
         */
        public function setValue($sValue) {
            if ($sValue instanceof Field) {
                $sValue = $sValue->getValue();
            }

            if ($sValue !== null) {
                $sValue = (float) $sValue;
            }

            $this->sValue = $sValue;

            return $this;
        }
    }
?>