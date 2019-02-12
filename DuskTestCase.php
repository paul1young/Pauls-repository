<?php

namespace Tests;

use Laravel\Dusk\TestCase as BaseTestCase;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use App\User;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use Msurguy\Honeypot\HoneypotFacade;
use Illuminate\Foundation\Testing\DatabaseTransactions;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;// DatabaseTransactions;


    /**
     * Prepare for Dusk test execution.
     *
     * @beforeClass
     * @return void
     */
    public static function prepare()
    {
        static::startChromeDriver();
    }


    /**
     * Default preparation for each test
     */
    public function setUp()
    {
        parent::setUp(); // Don't forget this!

        Artisan::call('migrate');
        if (User::count() == 0) {
            Artisan::call('db:seed', ['--class' => 'TestSeeder']);
        }
        exec('rm -f ' . storage_path('framework/sessions/*'));
    }

    /**
     * Create the RemoteWebDriver instance.
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected function driver()
    {

        $chromeOptions = new ChromeOptions();
        $chromeOptions->addArguments(['--window-size=1466,1224']);

        return RemoteWebDriver::create(
            env('CHROMEDRIVER', 'http://localhost:9515'),
            $chromeOptions->toCapabilities()
        );
    }

    public static function runServer()
    {
        if (PHP_OS == 'Darwin') {
            // we are on mac
            $env = 'testing';
        } else {
            // linux
            $env = 'phpci';
        }
        $command = (new ProcessBuilder())
            ->setTimeout(null)
            ->setEnv('APP_ENV', $env)
            ->add('exec')
            ->add(PHP_BINARY)
            ->add('artisan')
            ->add('serve')
            ->add('--port=8081')
            ->add('--env=' . $env)
            ->getProcess()
            ->getCommandLine();

        $webserver = new Process($command . ' &');
        $webserver->disableOutput();
        $webserver->run();
        sleep(1);
    }


    public static function setUpBeforeClass()
    {
        self::runServer();
    }

    public static function tearDownAfterClass()
    {
        $webserverProcess = new Process("ps ax|grep 8081|grep -v grep|awk '{print $1}'|xargs kill");
        $webserverProcess->disableOutput();
        $webserverProcess->run();
    }

    public function loginAsUser()
    {

        // see https://github.com/laravel/dusk/issues/100
        $user = User::first();

        $this->browse(function ($browser) use ($user) {

            $browser->logout();
            $browser->loginAs($user->id);

        });
    }
}
