<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Escape;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;

    use PDO;

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

            if (strtolower($sValue) === "null") {
                $sValue = null;
            }

            if ($sValue !== null) {
                $sValue = (float) $sValue;
            }

            $this->sValue = $sValue;

            return $this;
        }

        /**
         *
         * @return string
         */
        public function toSQL() {
            if (!$this->hasValue()) {
                return 'NULL';
            }

            return Escape::string($this->__toString(), PDO::PARAM_STR);
        }
    }
?>