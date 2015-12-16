<?php
    namespace Enobrev\Mock;

    use Enobrev\ORM\Field\Id        as F_Id;
    use Enobrev\ORM\Field\Text      as F_Text;
    use Enobrev\ORM\Table;

    class Address extends Table {
        protected $sTitle = 'addresses';

        /** @var  F_Id */
        public $address_id;

        /** @var  F_Id */
        public $user_id;

        /** @var  F_Text */
        public $address_1;

        /** @var  F_Text */
        public $address_city;

        protected function init() {
            $this->addPrimaries(
                new F_Id('address_id')
            );

            $this->addFields(
                new F_Id('user_id'),
                new F_Text('address_1'),
                new F_Text('address_city')
            );
        }
    }