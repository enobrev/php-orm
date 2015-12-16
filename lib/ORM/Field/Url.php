<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Field;

    class Url extends Text {

        /**
         *
         * @param mixed $sValue
         * @return Url
         */
        public function setValue($sValue) {
            if ($sValue instanceof Field) {
                $sValue = $sValue->getValue();
            }

            if(filter_var($sValue, FILTER_VALIDATE_URL) !== false) {
                $this->sValue = $sValue;
            }

            return $this;
        }
    }
?>
