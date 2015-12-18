<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;

    class Decimal extends Number {
        /**
         *
         * @param mixed $sValue
         * @return Decimal
         */
        public function setValue($sValue) {
            if ($sValue instanceof Table) {
                $sValue = $sValue->{$this->sColumn};
            }

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