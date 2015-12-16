# orm

Installation

    composer.phar require enobrev/orm

Example Usage:
    
      <?php
          require_once __DIR__ .'/../vendor/autoload.php';
      
          use Enobrev\ORM\Field\Boolean   as F_Boolean;
          use Enobrev\ORM\Field\DateTime  as F_DateTime;
          use Enobrev\ORM\Field\Id        as F_Id;
          use Enobrev\ORM\Field\Text      as F_Text;
          use Enobrev\ORM\Table;
          use Enobrev\SQL;
      
          class User extends Table {
              protected $sTitle = 'users';
      
              /** @var  F_Id */
              public $user_id;
      
              /** @var  F_Text */
              public $user_name;
      
              /** @var  F_Text */
              public $user_email;
      
              /** @var  F_DateTime */
              public $user_date_added;
      
              /** @var  F_Boolean */
              public $happy;
      
              protected function init() {
                  $this->addPrimaries(
                      new F_Id('user_id')
                  );
      
                  $this->addFields(
                      new F_Text('user_name'),
                      new F_Text('user_email'),
                      new F_DateTime('user_date_added'),
                      new F_Boolean('happy')
                  );
      
                  $this->happy->setDefault(false);
              }
          }
      
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
      
          $oUser = new User();
          $oSQL  = SQL::select(
              $oUser,
              $oUser->user_id,
              $oUser->user_name,
              $oUser->user_email,
              Address::Field('address_city', 'billing'),
              Address::Field('address_city', 'shipping'),
              SQL::join($oUser->user_id, Address::Field('user_id', 'billing')),
              SQL::join($oUser->user_id, Address::Field('user_id', 'shipping')),
              SQL::either(
                  SQL::also(
                      SQL::eq($oUser->user_id, 1),
                      SQL::eq($oUser->user_email, 'test@example.com')
                  ),
                  SQL::between($oUser->user_date_added, new \DateTime('2015-01-01'), new \DateTime('2015-06-01'))
              ),
              SQL::asc($oUser->user_name),
              SQL::desc($oUser->user_email),
              SQL::group($oUser->user_id),
              SQL::limit(5)
          );
      
          echo (string) $oSQL;
