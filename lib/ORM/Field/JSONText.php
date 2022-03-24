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
         * @noinspection PhpMissingReturnTypeInspection
         */
        public function setValue($sValue) {
            if ($sValue instanceof Table) {
                $sValue = $sValue->{$this->sColumn};
            }

            if ($sValue instanceof Field) {
                $sValue = $sValue->getValue();
            }

            if (is_array($sValue) || is_object($sValue)) {
                $sValue = json_encode($sValue);
            }

            if ($sValue !== null) {
                if (empty(trim($sValue))) {
                    $sValue = null;
                }

                if ($sValue === 'null' || $sValue === 'NULL') {
                    $sValue = null;
                }
            }

            $this->sValue = $sValue;

            return $this;
        }
    }