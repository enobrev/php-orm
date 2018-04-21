<?php
    namespace Enobrev\ORM\Field;

    use \Exception;
    use Enobrev\ORM\Db;
    use Enobrev\ORM\Table;
    use Enobrev\ORM\Field;

    class UUID extends Text {
        /**
         * @param mixed $sValue
         * @return $this
         */
        public function setValue($sValue) {
            parent::setValue($sValue);

            $this->sValue = strtolower(str_replace('-', '', $this->sValue));

            return $this;
        }

        public function generateValue(): void {
            $this->setValue(Db::getInstance()->getUUID());
        }

        /**
         * @param mixed $mValue
         * @return bool
         */
        public function is($mValue):bool {
            if ($mValue instanceof Table) {
                $mValue = $mValue->{$this->sColumn};
            }

            if ($mValue instanceof Field) {
                return $this->is($mValue->getValue());
            }

            if ($mValue === null) {
                return $this->isNull(); // Both Null
            } else if ($this->isNull()) {
                return false;           // My Value is null but comparator is not
            }

            try {
                $mValue = strtolower(str_replace('-', '', (string)$mValue));

                return (string)$this == (string)$mValue;
            } catch (Exception $e) {
                return false;
            }
        }

        /**
         *
         * @return string
         */
        public function __toString() {
            return strtolower(str_replace('-', '', (string) $this->sValue));
        }
    }