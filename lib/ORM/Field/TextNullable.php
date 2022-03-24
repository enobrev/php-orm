<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Exceptions\DbException;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;

    class TextNullable extends Text {
        /**
         *
         * @return string|NULL
         */
        public function getValue(): ?string {
            $sValue = $this->sValue;

            if ($sValue !== null) {
                if (trim($sValue) === '') {
                    $sValue = NULL;
                }

                if (strtolower($sValue) === 'null') {
                    $sValue = NULL;
                }
            }

            return $sValue;
        }

        /**
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

        /**
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