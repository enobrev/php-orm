<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;

    class HashNullable extends Hash {
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
         * @return $this
         */
        public function setValue($sValue) {
            if ($sValue instanceof Table) {
                $sValue = $sValue->{$this->sColumn};
            }

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
        public function toSQL():string {
            if ($this->isNull()) {
                return 'NULL';
            } else {
                return parent::toSQL();
            }
        }
    }