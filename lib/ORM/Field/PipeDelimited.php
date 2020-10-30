<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;

    class PipeDelimited extends Text {

        /**
         * @var array|null
         */
        public $sValue;

        /**
         *
         * @param array|string|Field|Table $sValue
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

            if (!is_array($sValue)) {
                $sValue = explode('|', $sValue);
            }

            if (!is_array($sValue)) {
                $sValue = array();
            }

            $this->sValue = $sValue;

            return $this;
        }

        /**
         * @return array|null
         */
        public function getValue(): ?array {
            return $this->sValue;
        }

        public function hasValue():bool {
            return is_array($this->sValue) && count($this->sValue);
        }

        public function __toString(): string {
            $sValue = is_array($this->sValue) ? $this->sValue : array();

            return implode('|', $sValue);
        }
    }