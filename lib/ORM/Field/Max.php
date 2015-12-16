<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Field;

    class Max extends Field {

        public function __construct($sMaxParam, $sMaxAlias) {
            parent::__construct('max(' . $sMaxParam . ') as ' . $sMaxAlias);
            $this->sAlias = $sMaxAlias;
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