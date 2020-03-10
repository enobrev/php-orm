<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;

    class JSONText extends TextNullable {

        /**
         * Same as TextNullable but does a string check before trying to trim
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

            if (is_string($sValue) && empty(trim($sValue))) {
                $sValue = NULL;
            }

            if ($sValue === 'null' || $sValue === 'NULL') {
                $sValue = NULL;
            }

            $this->sValue = $sValue;

            return $this;
        }
    }