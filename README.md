# enobrev/php-orm

The `Enobrev\ORM` library is a small framework of classes meant to be used for simply mapping a mysql database to PHP classes, and for creating simply SQL statements using those classes.  There's a script for parsing a mysql database and creating a file called `sql.json` which can be easily used by the library and other tools to understand the structure of the database, as well as a script for generating ORM class files that represent each table.  The `Enobrev\API` library is built to interact closely and easily with this library to allow for an immediate REST-ish API frontend to your database.

## Installation

    composer.phar require enobrev/orm


## sql_to_json

This script generates a file called sql.json, which holds an easy-to-query cache of the mysql database structure.  Other tools like php-orm’s `generate_tables` and php-api’s `generate_data_map` utilize sql.json.  This script should be run every time you make a change to the mysql database.

When calling this from your project, you can call it as such:

    php vendor/bin/sql_to_json.php -h [host] -u [user] -d [database name] -p

The `-p` at the end will ask you for your database password.  Once you’ve entered it, the script will ask you which tables are Many-To-Many tables, and then create a file called .sql.m2m.json to cache your response.  This file merely holds a JSON array of the tables you’ve stated to be Many-To-Many tables.  If, in the future, you add more m2m tables, remove that file and re-run the script.

After setting which tables are M2M, sql.json will be created or updated in the same path from which you called the script.


## generate_tables

Since we have all this useful information stored in SQL.json, we may as well use it to our benefit.   This script will generate two classes per chosen table, one named after the table in singular form, and the second in plural.

The singular form is meant to represent a single record in your database.  If you get a record from the database, using this class, it will be returned as an instance of that class.  So a `Table\Car::getById($sCarId)` will return an instance of `Table\Car`.

The plural form is meant to represent multiple records in your database.  `Table\Cars::get()` would return an array containing multiple instances of `Table\Car`.

You can, of course, hand-type these classes.  They simply extend `Enobrev\ORM\Table` and `Enobrev\ORM\Tables`, but they also hold database specific information, such as column names, and methods you’d like to call upon the data held within or to generate said data (like for UUIDs).  Most of this can be considered boilerplate, if at least as a starting point, and can be generated using the `generate_tables` script.

From your project root, after generating `sql.json`  as explained in the previous section, you can call to generate table classes for your project.

    php vendor/bin/generate_tables.php -j ./sql.json -n [namespace] -o [output path]

An example:

    php vendor/bin/generate_tables.php -j ./sql.json -n Enobrev\\Table -o lib/Table

This will generate tables in the path `lib/Table` with the PHP Namespace of `Enobrev\Table`, presumably for a project with its own namespace of `Enobrev`.

Once you enter this command, it will explicitly list all the available tables and ask you to enumerate which table classes you’d like to generate.

Keep in mind that it will simply generate the files and overwrite whatever is there.  So if you’ve modified a previously generated class file, be sure to check it into your source control before running this script so you can `diff` it afterwards.


## SQLBuilder and SQL

Once you’ve generated your class files, you’ll want to general SQL statements using them.  This is what the `Enobrev\php-orm` library is all about.  The library itself is made up of multiple classes meant to be tied together to generate a proper SQL statement.  All of the functionality in these classes has been encapsulated into two classes that are meant to be used in tandem: `SQLBuilder` and `SQL`

Overall, this library is meant for simple queries and simple `LEFT OUTER JOIN`s .  Anything requiring more advanced SQL functionality, including SQL methods is beyond the scope of this library classes and should instead be written out by hand.  That said, it’s still possible to use the `SQL` class to help with some of your hand-written SQL.

For plain string SQL, you can simply call `Db::getInstance()->query(``"``SELECT * FROM cars``"``);`, which will return a `PDOResult` instance which you can use in the normal way.  `Db::query` also accepts instances of `SQL` and `SQLBuilder`.

These classes are very simple and are meant to be used together.  For instance, it’s not possible to generate a large tree of conditions in `SQLBuilder` without using the `SQL` class.  In essence, these two classes are a UI built on top of the inner-workings of the `Enobrev\ORM` library.

For an example usage, let’s create a couple table classes to play with.  Since the primary purpose of this library is to generate SQL, this code can work just fine without an actual database.

```php
        require_once __DIR__ .'/../vendor/autoload.php';

        use DateTime;
        use Enobrev\ORM\Field as Field;
        use Enobrev\ORM\Table;
        use Enobrev\SQL;

        class User extends Table {
            protected $sTitle = 'users';

            /** @var  Field\Id */
            public $user_id;

            /** @var  Field\Text */
            public $user_name;

            /** @var  Field\Text */
            public $user_email;

            /** @var  Field\DateTime */
            public $user_date_added;

            /** @var  Field\Boolean */
            public $happy;

            protected function init() {
                $this->addPrimaries(
                    new Field\Id('user_id')
                );

                $this->addFields(
                    new Field\Text('user_name'),
                    new Field\Text('user_email'),
                    new Field\DateTime('user_date_added'),
                    new Field\Boolean('happy')
                );

                $this->happy->setDefault(false);
            }
        }

        class Address extends Table {
            protected $sTitle = 'addresses';

            /** @var  Field\Id */
            public $address_id;

            /** @var  Field\Id */
            public $user_id;

            /** @var  Field\Text */
            public $address_1;

            /** @var  Field\Text */
            public $address_city;

            protected function init() {
                $this->addPrimaries(
                    new Field\Id('address_id')
                );

                $this->addFields(
                    new Field\Id('user_id'),
                    new Field\Text('address_1'),
                    new Field\Text('address_city')
                );
            }
        }
```

Here’s how to generate a SQL query using the `SQL` class:

```php
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
                SQL::between($oUser->user_date_added, new DateTime('2015-01-01'), new DateTime('2015-06-01'))
            ),
            SQL::asc($oUser->user_name),
            SQL::desc($oUser->user_email),
            SQL::group($oUser->user_id),
            SQL::limit(5)
        );

        echo (string) $oSQL;
```

Here’s that same example using `SQLBuilder`

```php
        $oUser = new User();

        $oSQL = SQLBuilder::select($oUser);
        $oSQL->fields(
            $oUser->user_id,
            $oUser->user_name,
            $oUser->user_email,
            Address::Field('address_city', 'billing'),
            Address::Field('address_city', 'shipping'),
        );
        $oSQL->join($oUser->user_id, Address::Field('user_id', 'billing'));
        $oSQL->join($oUser->user_id, Address::Field('user_id', 'shipping'));
        $oSQL->either(
                SQL::also(
                    SQL::eq($oUser->user_id, 1),
                    SQL::eq($oUser->user_email, 'test@example.com')
                ),
                SQL::between($oUser->user_date_added, new DateTime('2015-01-01'), new DateTime('2015-06-01'))
        );

        // SQLBuilder returns an instance of itself, so you can also string calls like so:
        $oSQL->asc($oUser->user_name)->desc($oUser->user_email)->group($oUser->user_id)
        $oSQL->limit(5);

        echo (string) $oSQL;
```

The order of all these method calls doesn’t matter.

The primary difference between `SQL` and `SQLBuilder`, besides the interface, is that `SQL` will return `Enobrev\ORM` objects, while `SQLBuilder` returns an instance of itself.  Those objects can be used outside of the realm of creating one full SQL string.

For instance:

```php
    $oCondition = SQL::also(
        SQL::eq($oUser->user_id, 1),
        SQL::eq($oUser->user_email, 'test@example.com')
    );

    // $oCondition now has an instance of Enobrev\ORM\Condition
    echo $oCondition->toSQL();
    // echos: users.user_id = 1 AND users.user_email = 'test@example.com`

    echo SQL::limit(5, 10)->toSQL(); // LIMIT 5, 10
    echo SQL::select($oUsers); // SELECT * FROM users

    $oUser = new User();
    $oUser->user_id   = 1;
    $oUser->user_name = 'Mark';
    echo SQL::update($oUser); // UPDATE users SET user_name = 'Mark' WHERE user_id = 1
    echo SQL::insert($oUser); // INSERT INTO users (user_id, user_name) VALUES (1, 'Mark')
```