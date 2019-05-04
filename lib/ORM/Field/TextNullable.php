<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\DbException;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;

    class TextNullable extends Text {

        /**
         *
         * @return string|NULL
         */
        public function getValue(): ?string {
            $sValue = $this->sValue;

            if (trim($sValue) === '') {
                $sValue = NULL;
            }

            if (strtolower($sValue) === 'null') {
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

            if (trim($sValue) === '') {
                $sValue = NULL;
            }

            if (strtolower($sValue) === 'null') {
                $sValue = NULL;
            }

            $this->sValue = $sValue;

            return $this;
        }

        /**
         *
         * @return string
         * @throws DbException
         */
        public function toSQL():string {
            if ($this->isNull()) {
                return 'NULL';
            }

            return parent::toSQL();
        }
    }