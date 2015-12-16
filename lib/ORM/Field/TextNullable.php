<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Field;

    class TextNullable extends Text {

        /**
         *
         * @return string|NULL
         */
        public function getValue() {
            $sValue = $this->sValue;

            if (strlen(trim($sValue)) == 0) {
                $sValue = NULL;
            }

            if (strtolower($sValue) == 'null') {
                $sValue = NULL;
            }

            return $sValue;
        }

        /**
         *
         * @param mixed $sValue
         * @return TextNullable
         */
        public function setValue($sValue) {
            if ($sValue instanceof Field) {
                $sValue = $sValue->getValue();
            }

            if (strlen(trim($sValue)) == 0) {
                $sValue = NULL;
            }

            if (strtolower($sValue) == 'null') {
                $sValue = NULL;
            }

            $this->sValue = $sValue;

            return $this;
        }
        
        /**
         *
         * @return string
         */
        public function toSQL() {
            if ($this->isNull()) {
                return 'NULL';
            } else {
                return parent::toSQL();
            }
        }
    }
?>
