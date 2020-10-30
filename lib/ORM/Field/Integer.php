<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Exceptions\DbException;
    use Enobrev\ORM\Escape;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;

    use PDO;

    class Integer extends Number {
        public function increment(int $iBy = 1):void {
            if (!$this->isNull()) {
                $this->sValue += $iBy;
            } else {
                $this->sValue = $iBy;
            }
        }

        public function decrement(int $iBy = 1): void {
            if (!$this->isNull()) {
                $this->sValue -= $iBy;
            }
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

            if (strtolower($sValue) === 'null') {
                $sValue = null;
            }

            if (trim($sValue) === '') {
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