<?php

namespace Anva\MailClientBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    public function testLogin()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');
    }

    public function testDologin()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/dologin');
    }

    public function testLogout()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/logout');
    }

    public function testCreateimap()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/createimap');
    }

    public function testDocreateimap()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/docreateimap');
    }

}
