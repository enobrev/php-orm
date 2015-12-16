<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Db;

    class UUID extends Hash {
        /**
         * @param mixed $sValue
         * @return UUID
         */
        public function setValue($sValue) {
            parent::setValue($sValue);

            $this->sValue = strtolower(str_replace('-', '', $this->sValue));

            return $this;
        }

        public function generateValue() {
            $this->setValue(Db::getInstance()->getUUID());
        }

        /**
         * @param mixed $mValue
         * @return bool
         */
        public function is($mValue) {
            if ($mValue instanceof self) {
                return $this->is($mValue->getValue());
            }

            if ($mValue === null && $this->isNull()) {
                return true;
            }

            $mValue = strtolower(str_replace('-', '', $mValue));

            return (string) $this == (string) $mValue;
        }

        /**
         *
         * @return string
         */
        public function __toString() {
            return strtolower(str_replace('-', '', (string) $this->sValue));
        }
    }
?>
