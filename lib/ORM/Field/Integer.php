<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\DbException;
    use Enobrev\ORM\Escape;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;

    use PDO;

    class Integer extends Number {

        public function increment():void {
            if (!$this->isNull()) {
                $this->sValue += 1;
            }
        }

        public function decrement(): void {
            if (!$this->isNull()) {
                $this->sValue -= 1;
            }
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

            if (strtolower($sValue) === "null") {
                $sValue = null;
            }

            if (strlen(trim($sValue)) === 0) {
                $sValue = null;
            }

            if ($sValue !== null) {
                $sValue = (int) $sValue;
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
            if (!$this->hasValue()) {
                return 'NULL';
            }

            return Escape::string($this->__toString(), PDO::PARAM_INT);
        }
    }