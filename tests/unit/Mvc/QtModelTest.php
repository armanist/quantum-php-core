<?php

namespace Quantum\Models {

    use Quantum\Mvc\QtModel;

    class ProfileModel extends QtModel
    {

        public $table = 'profiles';
        protected $fillable = [
            'firstname',
            'lastname'
        ];

    }

}

namespace Quantum\Test\Unit {

    use Mockery;
    use PHPUnit\Framework\TestCase;
    use Quantum\Libraries\Database\Database;
    use Quantum\Libraries\Database\IdiormDbal;
    use Quantum\Models\ProfileModel;
    use Quantum\Exceptions\ModelException;
    use Quantum\Libraries\Storage\FileSystem;
    use Quantum\Loader\Loader;
    use Quantum\Di\Di;

    /**
     * @runTestsInSeparateProcesses
     * @preserveGlobalState disabled
     */
    class QtModelTest extends TestCase
    {

        private $model;

        private $testObject = [
            'firstname' => 'John',
            'lastname' => 'Doe'
        ];

        private $dbConfigs = [
            'current' => 'sqlite',
            'sqlite' => array(
                'driver' => 'sqlite',
                'database' => ':memory:'
            ),
        ];

        public function setUp(): void
        {

            (new idiormDbal('profiles'))->execute("CREATE TABLE profiles (
                        id INTEGER PRIMARY KEY,
                        firstname VARCHAR(255),
                        lastname VARCHAR(255),
                        created_at DATETIME
                    )");

            $loader = new Loader(new FileSystem);

            $loader->loadDir(dirname(__DIR__, 3) . DS . 'src' . DS . 'Helpers' . DS . 'functions');

            $loaderMock = Mockery::mock('Quantum\Loader\Loader');

            $loaderMock->shouldReceive('setup')->andReturn($loaderMock);

            $loaderMock->shouldReceive('load')->andReturn($this->dbConfigs);

            $db = Database::getInstance($loaderMock);

            $db->getORM('users');

            Di::loadDefinitions();

            $this->model = (new \Quantum\Factory\ModelFactory)->get(ProfileModel::class);
        }

        public function tearDown(): void
        {
            Mockery::close();
        }

        public function testModelInstance()
        {
            $this->assertInstanceOf('Quantum\Mvc\QtModel', $this->model);
        }

        public function testFillObjectProps()
        {
            $this->model->fillObjectProps($this->testObject);

            $this->assertEquals('John', $this->model->firstname);

            $this->assertEquals('Doe', $this->model->lastname);
        }

        public function testFillObjectPropsWithUndefinedFillable()
        {
            $this->expectException(ModelException::class);

            $this->expectExceptionMessage('Inappropriate property `age` for fillable object');

            $this->model->fillObjectProps(['age' => 30]);
        }

        public function testSetterAndGetter()
        {
            $this->assertNull($this->model->undefinedProperty);

            $this->model->undefinedProperty = 'Something';

            $this->assertEquals('Something', $this->model->undefinedProperty);
        }

    }

}