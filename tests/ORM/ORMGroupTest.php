<?php
    namespace Enobrev;

    require __DIR__ . '/../../vendor/autoload.php';


    use Enobrev\ORM\Field;
    use Enobrev\ORM\Group;
    use Enobrev\ORM\Table;
    use PHPUnit\Framework\TestCase;

 
    class ORMGroupTest extends TestCase {
        
        public function testGroupOneField(): void {
            $oPosts = new ORMGroupTestPosts();
            $oGroup = Group::create($oPosts->post_id);
            $this->assertEquals('posts.post_id', $oGroup->toSQL());
        }

        public function testGroupTwoFields(): void {
            $oPosts = new ORMGroupTestPosts();
            $oGroup = Group::create($oPosts->post_id, $oPosts->user_id);
            $this->assertEquals('posts.post_id, posts.user_id', $oGroup->toSQL());
        }
    }

    class ORMGroupTestPosts extends Table {
        protected string $sTitle = 'posts';

        public Field\Integer $post_id;
        public Field\Integer $user_id;

        public static function getTables() {
            // TODO: Implement getTables() method.
        }

        protected function init(): void {
            $this->addFields(
                new Field\Integer('post_id'),
                new Field\Integer('user_id')
            );
        }
    }