<?php

namespace Quantum\Tests\Unit\Environment;

use Quantum\Environment\Exceptions\EnvException;
use Quantum\Environment\Environment;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\App\AppContext;
use Quantum\App\App;

class EnvironmentTest extends AppTestCase
{
    private Environment $env;

    public function setUp(): void
    {
        parent::setUp();

        $this->env = environment();
    }

    public function testEnvironmentGetAppEnv(): void
    {
        $this->assertEquals('testing', $this->env->getAppEnv());
    }

    public function testLoadReportsMissingBootstrapConfig(): void
    {
        $context = App::getContext();

        try {
            App::setContext(new AppContext(PROJECT_ROOT . DS . 'cron-command-tests-empty', $context->getContainer()));

            $this->expectException(EnvException::class);
            $this->expectExceptionMessage('shared' . DS . 'config' . DS . 'env.php');

            (new Environment())->load();
        } finally {
            App::setContext($context);
        }
    }

    public function testLoadIgnoresModuleBootstrapConfig(): void
    {
        $moduleConfig = PROJECT_ROOT . DS . 'modules' . DS . 'Test' . DS . 'config' . DS . 'env.php';
        $this->assertFileDoesNotExist($moduleConfig);

        try {
            $this->createFile($moduleConfig, "<?php\n\nreturn ['app_env' => 'staging'];\n");
            $this->testRequest('/');

            $environment = new Environment();
            $environment->load();

            $this->assertSame('testing', $environment->getAppEnv());
        } finally {
            $this->removeFile($moduleConfig);
        }
    }

    public function testLoadDoesNotReadBootstrapAgain(): void
    {
        $environment = new Environment();
        $environment->load();
        $context = App::getContext();

        try {
            App::setContext(new AppContext(PROJECT_ROOT . DS . 'cron-command-tests-empty', $context->getContainer()));

            $environment->load();

            $this->assertSame('testing', $environment->getAppEnv());
        } finally {
            App::setContext($context);
        }
    }

    public function testFileExistsIsTrueWhenTheEnvironmentFileExists(): void
    {
        $this->assertFileExists(PROJECT_ROOT . DS . '.env.testing');

        $this->assertTrue((new Environment())->fileExists());
    }

    public function testFileExistsIsFalseWhenTheEnvironmentFileIsMissing(): void
    {
        $this->withoutEnvironmentFile(function (): void {
            $this->assertFalse((new Environment())->fileExists());
        });
    }

    public function testFileExpectsTheEnvironmentSpecificFileName(): void
    {
        $this->withoutEnvironmentFile(function (): void {
            $plainFile = PROJECT_ROOT . DS . '.env';

            $this->assertFileDoesNotExist($plainFile);

            try {
                $this->createFile($plainFile, "APP_KEY=plain\n");

                $this->assertFalse((new Environment())->fileExists());
            } finally {
                $this->removeFile($plainFile);
            }
        });
    }

    public function testFileExistsIsFalseWhenTheBootstrapConfigIsMissing(): void
    {
        $context = App::getContext();

        try {
            App::setContext(new AppContext(PROJECT_ROOT . DS . 'cron-command-tests-empty', $context->getContainer()));

            $this->assertFalse((new Environment())->fileExists());
        } finally {
            App::setContext($context);
        }
    }

    public function testLoadReportsTheMissingEnvironmentFile(): void
    {
        $this->withoutEnvironmentFile(function (): void {
            $this->expectException(EnvException::class);
            $this->expectExceptionMessage('.env.testing');

            (new Environment())->load();
        });
    }

    /**
     * Hides the environment file of the test app for the duration of the callback
     */
    private function withoutEnvironmentFile(callable $test): void
    {
        $file = PROJECT_ROOT . DS . '.env.testing';
        $hidden = $file . '.hidden';

        $this->assertTrue(rename($file, $hidden));

        try {
            $test();
        } finally {
            rename($hidden, $file);
        }
    }

    public function testEnvironmentGetValue(): void
    {
        $this->assertNull($this->env->getValue('NON_EXISTING_KEY'));

        $this->assertNotNull($this->env->getValue('APP_KEY'));

        $this->assertEquals('TRUE', $this->env->getValue('DEBUG'));

        $this->assertEquals('XYZ1234567890ABCDEFG123456789HIGKLMNOPQRSTUVWXYZ0123456789abcdefgh', $this->env->getValue('APP_KEY'));
    }

    public function testEnvironmentHasKey(): void
    {
        $this->assertTrue($this->env->hasKey('APP_KEY'));

        $this->assertFalse($this->env->hasKey('NON_EXISTING_KEY'));
    }

    public function testEnvironmentGetRow(): void
    {
        $this->assertNull($this->env->getRow('NON_EXISTING_KEY'));

        $this->assertEquals('APP_KEY=XYZ1234567890ABCDEFG123456789HIGKLMNOPQRSTUVWXYZ0123456789abcdefgh', $this->env->getRow('APP_KEY'));
    }

    public function testEnvironmentIsTestingInTestEnv(): void
    {
        $this->assertTrue($this->env->isTesting());
        $this->assertFalse($this->env->isProduction());
        $this->assertFalse($this->env->isStaging());
        $this->assertFalse($this->env->isDevelopment());
        $this->assertFalse($this->env->isLocal());
    }

    public function testEnvironmentCheckMethodsWithDifferentEnvs(): void
    {
        $this->setPrivateProperty($this->env, 'appEnv', 'production');
        $this->assertTrue($this->env->isProduction());
        $this->assertFalse($this->env->isTesting());

        $this->setPrivateProperty($this->env, 'appEnv', 'staging');
        $this->assertTrue($this->env->isStaging());
        $this->assertFalse($this->env->isProduction());

        $this->setPrivateProperty($this->env, 'appEnv', 'development');
        $this->assertTrue($this->env->isDevelopment());
        $this->assertFalse($this->env->isProduction());

        $this->setPrivateProperty($this->env, 'appEnv', 'local');
        $this->assertTrue($this->env->isLocal());
        $this->assertFalse($this->env->isProduction());

        $this->setPrivateProperty($this->env, 'appEnv', 'testing');
    }

    public function testEnvironmentAddAndUpdateRow(): void
    {
        $envFilePath = App::getBaseDir() . DS . '.env.testing';
        $originalContent = $this->fs->get($envFilePath);

        $this->assertNull($this->env->getValue('SOMETHING'));

        $this->env->updateRow('SOMETHING', 'Something');

        $this->assertEquals('Something', $this->env->getValue('SOMETHING'));

        $this->env->updateRow('SOMETHING', 'Something_else');

        $this->assertEquals('Something_else', $this->env->getValue('SOMETHING'));

        $this->fs->put($envFilePath, $originalContent);
    }
}
