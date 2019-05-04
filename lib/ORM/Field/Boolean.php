<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\DbException;
    use Enobrev\ORM\Escape;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;

    use PDO;

    class Boolean extends Number {
        /**
         *
         * @return string
         */
        public function __toString():string {
            return $this->sValue ? '1' : '0';
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

            if ($sValue === 'false') {
                $sValue = false;
            } else if ($sValue === 'true') {
                $sValue = true;
            }

            $this->sValue = $sValue ? true : false;

            return $this;
        }

        /**
         * @return bool
         */
        public function isTrue():bool {
            return $this->sValue ? true : false;
        }

        /**
         * @return bool
         */
        public function isFalse():bool {
            return !$this->isTrue();
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

            return Escape::string($this->__toString(), PDO::PARAM_BOOL);
        }
    }