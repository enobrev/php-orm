<?php
    namespace Enobrev\ORM\Mock;

    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;

    class Address extends Table {
        protected $sTitle = 'addresses';

        /** @var  Field\Id */
        public $address_id;

        /** @var  Field\Id */
        public $user_id;

        /** @var  Field\TextNullable */
        public $address_line_1;

        /** @var  Field\TextNullable */
        public $address_city;

        protected function init() {
            $this->addPrimaries(
                new Field\Id('address_id')
            );

            $this->addFields(
                new Field\Id('user_id'),
                new Field\TextNullable('address_line_1'),
                new Field\TextNullable('address_city')
            );
        }
    }