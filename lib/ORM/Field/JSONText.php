<?php
    namespace Enobrev\ORM\Field;

    class JSONText extends TextNullable {

        /**
         *
         * @param mixed $sValue
         * @return $this
         */
        public function setValue($sValue) {
            if (is_array($sValue) || is_object($sValue)) {
                $sValue = json_encode($sValue);
            }

            return parent::setValue($sValue);
        }
    }