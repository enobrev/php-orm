<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Exceptions\DbException;
    use Enobrev\ORM\Escape;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;

    class Decimal extends Number {
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

            if (strtolower($sValue) === 'null') {
                $sValue = null;
            }

            if ($sValue !== null) {
                $sValue = (float) $sValue;
            }

            $this->sValue = $sValue;

            return $this;
        }

        /**
         * @return string
         * @throws DbException
         */
        public function toSQL():string {
            if (!$this->hasValue()) {
                return 'NULL';
            }

            return Escape::string($this->__toString());
        }
    }