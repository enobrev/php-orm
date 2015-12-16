<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Field;

    class Min extends Field {

        public function __construct($sMinParam, $sMinAlias) {
            parent::__construct('min(' . $sMinParam . ') as ' . $sMinAlias);
            $this->sAlias = $sMinAlias;
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