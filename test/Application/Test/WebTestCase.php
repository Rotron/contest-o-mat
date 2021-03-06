<?php

namespace Application\Test;

use Silex\WebTestCase as SilexWebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * @author Borut Balažek <bobalazek124@gmail.com>
 */
class WebTestCase extends SilexWebTestCase
{
    /**
     * @return \Silex\Application
     */
    public function createApplication()
    {
        $app = require dirname(__FILE__).'/../../../app/bootstrap.php';

        $app['debug'] = false;
        $app['session.test'] = true;
        unset($app['exception_handler']);

        return $app;
    }

    /**
     * @return \Symfony\Component\HttpKernel\Client
     */
    public function doLogin($username, array $roles = array())
    {
        $app = $this->createApplication();
        $client = $this->createClient();
        $client->followRedirects();

        $session = $app['session'];
        $firewall = 'members-area';

        $token = new UsernamePasswordToken($username, null, $firewall, $roles);
        $session->set('_security_'.$firewall, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);

        return $client;
    }
}
