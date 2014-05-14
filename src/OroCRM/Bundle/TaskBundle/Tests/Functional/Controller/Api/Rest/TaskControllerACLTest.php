<?php

namespace OroCRM\Bundle\TaskBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class TaskControllerACLTest extends WebTestCase
{
    const USER_NAME = 'user_wo_permissions';
    const USER_PASSWORD = 'user_api_key';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var int
     */
    protected static $taskId;

    public function setUp()
    {
        $this->client = self::createClient(
            [],
            $this->generateWsseAuthHeader(self::USER_NAME, self::USER_PASSWORD)
        );

        $this->client->appendFixturesOnce(__DIR__ . DIRECTORY_SEPARATOR . 'DataFixtures');

        if (!self::$taskId) {
            self::$taskId = $this->client->getContainer()
                ->get('doctrine')
                ->getManager()
                ->getRepository('OroCRMTaskBundle:Task')
                ->findOneBySubject('Acl task')
                ->getId();
        }
    }

    public function testCreate()
    {
        $request = [
            'task' => [
                'subject' => 'New task',
                'description' => 'New description',
                'dueDate' => '2014-03-04T20:00:00+0000',
                'taskPriority' => 'high',
                'owner' => '1',
                'reporter' => '1'
            ]
        ];

        $this->client->request(
            'POST',
            $this->client->generate('orocrm_api_post_task'),
            $request,
            [],
            $this->generateWsseAuthHeader(self::USER_NAME, self::USER_PASSWORD)
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 403);
    }

    /**
     * @depends testCreate
     */
    public function testCget()
    {
        $this->client->request(
            'GET',
            $this->client->generate('orocrm_api_get_tasks'),
            [],
            [],
            $this->generateWsseAuthHeader(self::USER_NAME, self::USER_PASSWORD)
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 403);
    }

    /**
     * @depends testCreate
     */
    public function testGet()
    {
        $this->client->request(
            'GET',
            $this->client->generate('orocrm_api_get_task', ['id' => self::$taskId]),
            [],
            [],
            $this->generateWsseAuthHeader(self::USER_NAME, self::USER_PASSWORD)
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 403);
    }

    /**
     * @depends testCreate
     */
    public function testPut()
    {
        $updatedTask = ['subject' => 'Updated subject'];
        $this->client->request(
            'PUT',
            $this->client->generate('orocrm_api_put_task', ['id' => self::$taskId]),
            ['task' => $updatedTask],
            [],
            $this->generateWsseAuthHeader(self::USER_NAME, self::USER_PASSWORD)
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 403);
    }

    /**
     * @depends testCreate
     */
    public function testDelete()
    {
        $this->client->request(
            'DELETE',
            $this->client->generate('orocrm_api_delete_task', ['id' => self::$taskId]),
            [],
            [],
            $this->generateWsseAuthHeader(self::USER_NAME, self::USER_PASSWORD)
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 403);
    }
}
