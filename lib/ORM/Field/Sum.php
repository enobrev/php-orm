<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Field;

    class Sum extends Field {

        public function __construct($sSumParam, $sSumAlias) {
            parent::__construct('sum(' . $sSumParam . ') as ' . $sSumAlias);
            $this->sAlias = $sSumAlias;
        }

        public function __toString() {
            if ($this->sValue) {
                return $this->sValue;
            }

            return '';
        }

        public function toSQL() {
            return $this->sColumn;
        }

        public function toSQLColumnForSelect($bWithTable = true) {
            return $this->toSQL();
        }

        public function toSQLColumn($bWithTable = true) {
            return $this->sAlias;
        }

    }