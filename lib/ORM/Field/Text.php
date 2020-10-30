<?php
    namespace Enobrev\ORM\Field;


    use Enobrev\ORM\Exceptions\DbException;
    use Enobrev\ORM\Escape;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;

    class Text extends Field {

        /** @var string  */
        public $sValue;

        /**
         *
         * @return mixed
         */
        public function getValue() {
            // Remove invalid UTF-8 Characters
            return mb_convert_encoding($this->sValue, 'UTF-8', 'UTF-8');
        }

        public function __toString(): string {
            return (string) $this->sValue;
        }

        /**
         *
         * @return string
         * @throws DbException
         */
        public function toSQL():string {
            if ($this->isNull()) {
                return '""';
            }

            return Escape::string((string) $this);
        }

        public function hasValue():bool {
            return parent::hasValue() && (string)$this !== '';
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

            if ($sValue !== null) {
                $sValue = (string) $sValue;
            }

            $this->sValue = $sValue;

            return $this;
        }
    }