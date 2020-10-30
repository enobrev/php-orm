<?php
    namespace Enobrev;

    require __DIR__ . '/../../vendor/autoload.php';


    use Enobrev\ORM\Field;


    use Enobrev\ORM\Table;
    use PHPUnit\Framework\TestCase;
 
    class ORMTextTrimmedTest extends TestCase {
        public function testLefTOuterJoin(): void {
            $oUsers = new ORMTextTrimmedTestTags;

            $oUsers->tag_title->setValue(' I am trimmed ');

            $this->assertEquals('I am trimmed', $oUsers->tag_title->getValue());
        }
    }

    class ORMTextTrimmedTestTags extends Table {
        protected string $sTitle = 'users';

        public Field\Id $tag_id;
        public Field\TextTrimmed $tag_title;
        public Field\Boolean $tag_indexed;

        public static function getTables() {
            // TODO: Implement getTables() method.
        }

        protected function init(): void {
            $this->addFields(
                new Field\Id('tag_id'),
                new Field\TextTrimmed('tag_title'),
                new Field\Boolean('tag_indexed')
            );
        }
    }