<?php

declare(strict_types=1);

namespace Subscriber;

use App\Api\Label\NullLabelApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Model\Repository;
use App\Subscriber\RemoveStaledLabelOnCommentSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class RemoveStaledLabelOnCommentSubscriberTest extends TestCase
{
    private $subscriber;

    private $repository;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    protected function setUp()
    {
        $this->subscriber = new RemoveStaledLabelOnCommentSubscriber(new NullLabelApi(), 'carsonbot');
        $this->repository = new Repository('carsonbot-playground', 'symfony', null);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($this->subscriber);
    }

    public function testOnComment()
    {
        $event = new GitHubEvent([
            'issue' => ['number' => 1234, 'labels' => []], 'comment' => ['user' => ['login' => 'nyholm']],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::ISSUE_COMMENT);

        $responseData = $event->getResponseData();
        $this->assertEmpty($responseData);
    }

    public function testOnCommentOnStale()
    {
        $event = new GitHubEvent([
            'issue' => ['number' => 1234, 'labels' => [['name' => 'Foo'], ['name' => 'Staled']]], 'comment' => ['user' => ['login' => 'nyholm']],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::ISSUE_COMMENT);

        $responseData = $event->getResponseData();
        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['issue']);
        $this->assertSame(true, $responseData['removed_staled_label']);
    }

    public function testOnBotCommentOnStale()
    {
        $event = new GitHubEvent([
            'issue' => ['number' => 1234, 'labels' => [['name' => 'Foo'], ['name' => 'Staled']]], 'comment' => ['user' => ['login' => 'carsonbot']],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::ISSUE_COMMENT);

        $responseData = $event->getResponseData();
        $this->assertEmpty($responseData);
    }
}
