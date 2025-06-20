<?php

namespace Tests\Feature\Events;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use jdavidbakr\MailTracker\Events\ComplaintMessageEvent;
use jdavidbakr\MailTracker\Events\EmailDeliveredEvent;
use jdavidbakr\MailTracker\Events\EmailSentEvent;
use jdavidbakr\MailTracker\Events\LinkClickedEvent;
use jdavidbakr\MailTracker\Events\ViewEmailEvent;
use jdavidbakr\MailTracker\Model\SentEmail;
use Mockery;
use ProcessMaker\Events\EmailComplaint;
use ProcessMaker\Events\EmailDelivered;
use ProcessMaker\Events\EmailLinkClicked;
use ProcessMaker\Events\EmailSent;
use ProcessMaker\Events\EmailViewed;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\ProcessRequest;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * Tests for all email event handlers
 */
class EmailEventTest extends TestCase
{
    use RequestHelper;

    protected ProcessRequest $request;

    protected $emailId = 'email-123';

    protected $requestId = 456;

    protected $messageEventId = 'message-event-789';

    /**
     * Setup for all tests
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Reset any mocks
        Mockery::close();

        // Create a test process request with an integer ID
        $this->request = ProcessRequest::factory()->create([
            'id' => $this->requestId,
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * Test each email event handler individually
     */
    public function testEmailSentHandling()
    {
        $this->runEventTest(
            EmailSent::class,
            EmailSentEvent::class,
            'X-ProcessMaker-Sent-Message-Event-ID'
        );
    }

    /**
     * Test EmailDelivered event handling
     */
    public function testEmailDeliveredHandling()
    {
        $this->runEventTest(
            EmailDelivered::class,
            EmailDeliveredEvent::class,
            'X-ProcessMaker-Delivered-Message-Event-ID'
        );
    }

    /**
     * Test EmailComplaint event handling
     */
    public function testEmailComplaintHandling()
    {
        $this->runEventTest(
            EmailComplaint::class,
            ComplaintMessageEvent::class,
            'X-ProcessMaker-Complaint-Message-Event-ID'
        );
    }

    /**
     * Test EmailViewed event handling
     */
    public function testEmailViewedHandling()
    {
        $this->runEventTest(
            EmailViewed::class,
            ViewEmailEvent::class,
            'X-ProcessMaker-Viewed-Message-Event-ID'
        );
    }

    /**
     * Test EmailLinkClicked event handling
     */
    public function testEmailLinkClickedHandling()
    {
        $this->runEventTest(
            EmailLinkClicked::class,
            LinkClickedEvent::class,
            'X-ProcessMaker-Link-Clicked-Message-Event-ID'
        );
    }

    /**
     * Common test runner for all event types
     */
    private function runEventTest(string $handlerClass, string $eventClass, string $messageEventIdHeader)
    {
        // Create the event handler
        $handler = new $handlerClass();

        // Set expectations for the WorkflowManager facade
        WorkflowManager::shouldReceive('triggerMessageEvent')
            ->once()
            ->with(Mockery::type(ProcessRequest::class), $this->messageEventId, ['email_id' => $this->emailId])
            ->andReturnTrue();

        // Create an event mock with appropriate tracker
        $trackerMock = $this->createTrackerMock($messageEventIdHeader);
        $eventMock = Mockery::mock($eventClass);
        $eventMock->sent_email = $trackerMock;

        // Call the handler
        $handler->handle($eventMock);
    }

    /**
     * Test email event header validation for EmailSent
     */
    public function testEmailSentHeaderMatch()
    {
        $this->runHeaderTest(
            EmailSent::class,
            EmailSentEvent::class,
            'X-ProcessMaker-Sent-Message-Event-ID'
        );
    }

    /**
     * Test email event header validation for EmailDelivered
     */
    public function testEmailDeliveredHeaderMatch()
    {
        $this->runHeaderTest(
            EmailDelivered::class,
            EmailDeliveredEvent::class,
            'X-ProcessMaker-Delivered-Message-Event-ID'
        );
    }

    /**
     * Test email event header validation for EmailComplaint
     */
    public function testEmailComplaintHeaderMatch()
    {
        $this->runHeaderTest(
            EmailComplaint::class,
            ComplaintMessageEvent::class,
            'X-ProcessMaker-Complaint-Message-Event-ID'
        );
    }

    /**
     * Test email event header validation for EmailViewed
     */
    public function testEmailViewedHeaderMatch()
    {
        $this->runHeaderTest(
            EmailViewed::class,
            ViewEmailEvent::class,
            'X-ProcessMaker-Viewed-Message-Event-ID'
        );
    }

    /**
     * Test email event header validation for EmailLinkClicked
     */
    public function testEmailLinkClickedHeaderMatch()
    {
        $this->runHeaderTest(
            EmailLinkClicked::class,
            LinkClickedEvent::class,
            'X-ProcessMaker-Link-Clicked-Message-Event-ID'
        );
    }

    /**
     * Common header test runner for all event types
     */
    private function runHeaderTest(string $handlerClass, string $eventClass, string $messageEventIdHeader)
    {
        // Create the event handler
        $handler = new $handlerClass();

        // Create a special tracker mock that will only accept exact header names
        $trackerMock = Mockery::mock(SentEmail::class);

        $trackerMock->shouldReceive('getHeader')
            ->with('X-ProcessMaker-Email-ID')
            ->once()
            ->andReturn($this->emailId);

        $trackerMock->shouldReceive('getHeader')
            ->with($messageEventIdHeader)
            ->once()
            ->andReturn($this->messageEventId);

        $trackerMock->shouldReceive('getHeader')
            ->with('X-ProcessMaker-Request-ID')
            ->once()
            ->andReturn($this->requestId);

        // Create event mock
        $eventMock = Mockery::mock($eventClass);
        $eventMock->sent_email = $trackerMock;

        // Call the handler
        $handler->handle($eventMock);
    }

    /**
     * Test missing process request handling for EmailSent
     */
    public function testEmailSentNoProcessRequest()
    {
        $this->runMissingRequestTest(
            EmailSent::class,
            EmailSentEvent::class,
            'X-ProcessMaker-Sent-Message-Event-ID'
        );
    }

    /**
     * Test missing process request handling for EmailDelivered
     */
    public function testEmailDeliveredNoProcessRequest()
    {
        $this->runMissingRequestTest(
            EmailDelivered::class,
            EmailDeliveredEvent::class,
            'X-ProcessMaker-Delivered-Message-Event-ID'
        );
    }

    /**
     * Test missing process request handling for EmailComplaint
     */
    public function testEmailComplaintNoProcessRequest()
    {
        $this->runMissingRequestTest(
            EmailComplaint::class,
            ComplaintMessageEvent::class,
            'X-ProcessMaker-Complaint-Message-Event-ID'
        );
    }

    /**
     * Test missing process request handling for EmailViewed
     */
    public function testEmailViewedNoProcessRequest()
    {
        $this->runMissingRequestTest(
            EmailViewed::class,
            ViewEmailEvent::class,
            'X-ProcessMaker-Viewed-Message-Event-ID'
        );
    }

    /**
     * Test missing process request handling for EmailLinkClicked
     */
    public function testEmailLinkClickedNoProcessRequest()
    {
        $this->runMissingRequestTest(
            EmailLinkClicked::class,
            LinkClickedEvent::class,
            'X-ProcessMaker-Link-Clicked-Message-Event-ID'
        );
    }

    /**
     * Common test runner for missing process request scenarios
     */
    private function runMissingRequestTest(string $handlerClass, string $eventClass, string $messageEventIdHeader)
    {
        // Delete the process request to simulate it not being found
        $this->request->delete();

        // Create the event handler
        $handler = new $handlerClass();

        // WorkflowManager should NOT be called when request isn't found
        WorkflowManager::shouldReceive('triggerMessageEvent')->never();

        // Create event mock with normal tracker
        $trackerMock = $this->createTrackerMock($messageEventIdHeader);
        $eventMock = Mockery::mock($eventClass);
        $eventMock->sent_email = $trackerMock;

        // Call the handler - should not error when no request found
        $handler->handle($eventMock);
    }

    /**
     * Test missing headers handling for EmailSent
     */
    public function testEmailSentMissingHeaders()
    {
        $this->runMissingHeadersTest(
            EmailSent::class,
            EmailSentEvent::class,
            'X-ProcessMaker-Sent-Message-Event-ID'
        );
    }

    /**
     * Test missing headers handling for EmailDelivered
     */
    public function testEmailDeliveredMissingHeaders()
    {
        $this->runMissingHeadersTest(
            EmailDelivered::class,
            EmailDeliveredEvent::class,
            'X-ProcessMaker-Delivered-Message-Event-ID'
        );
    }

    /**
     * Test missing headers handling for EmailComplaint
     */
    public function testEmailComplaintMissingHeaders()
    {
        $this->runMissingHeadersTest(
            EmailComplaint::class,
            ComplaintMessageEvent::class,
            'X-ProcessMaker-Complaint-Message-Event-ID'
        );
    }

    /**
     * Test missing headers handling for EmailViewed
     */
    public function testEmailViewedMissingHeaders()
    {
        $this->runMissingHeadersTest(
            EmailViewed::class,
            ViewEmailEvent::class,
            'X-ProcessMaker-Viewed-Message-Event-ID'
        );
    }

    /**
     * Test missing headers handling for EmailLinkClicked
     */
    public function testEmailLinkClickedMissingHeaders()
    {
        $this->runMissingHeadersTest(
            EmailLinkClicked::class,
            LinkClickedEvent::class,
            'X-ProcessMaker-Link-Clicked-Message-Event-ID'
        );
    }

    /**
     * Common test runner for missing headers scenarios
     */
    private function runMissingHeadersTest(string $handlerClass, string $eventClass, string $messageEventIdHeader)
    {
        // Create the event handler
        $handler = new $handlerClass();

        // WorkflowManager should NOT be called when headers missing
        WorkflowManager::shouldReceive('triggerMessageEvent')->never();

        // Create tracker mock with missing header
        $trackerMock = Mockery::mock(SentEmail::class);
        $trackerMock->shouldReceive('getHeader')
            ->with('X-ProcessMaker-Email-ID')
            ->andReturn($this->emailId);
        $trackerMock->shouldReceive('getHeader')
            ->with($messageEventIdHeader)
            ->andReturn(null); // Missing header!
        $trackerMock->shouldReceive('getHeader')
            ->with('X-ProcessMaker-Request-ID')
            ->andReturn($this->requestId);

        // Create event mock
        $eventMock = Mockery::mock($eventClass);
        $eventMock->sent_email = $trackerMock;

        // Call the handler - should not error when header missing
        $handler->handle($eventMock);
    }

    /**
     * Helper method to create a tracker mock with standard headers
     */
    protected function createTrackerMock(string $messageEventIdHeader)
    {
        $trackerMock = Mockery::mock(SentEmail::class);
        $trackerMock->shouldReceive('getHeader')
            ->with('X-ProcessMaker-Email-ID')
            ->andReturn($this->emailId);
        $trackerMock->shouldReceive('getHeader')
            ->with($messageEventIdHeader)
            ->andReturn($this->messageEventId);
        $trackerMock->shouldReceive('getHeader')
            ->with('X-ProcessMaker-Request-ID')
            ->andReturn($this->requestId);

        return $trackerMock;
    }

    /**
     * Test that emails without tracking enabled don't generate warning logs
     */
    public function testEmailWithoutTrackingEnabled()
    {
        // Mock the Log facade using Laravel's built-in support
        Log::shouldReceive('debug')
            ->withAnyArgs()
            ->atLeast(0);

        Log::shouldReceive('error')
            ->withAnyArgs()
            ->atLeast(0);

        // The important part - we should not see warnings about missing headers
        // when no tracking headers are present
        Log::shouldReceive('warning')
            ->with(Mockery::pattern('/.*missing required headers.*/'), Mockery::any())
            ->never();

        // Create an event handler (using EmailSent as an example)
        $handler = new EmailSent();

        // Create tracker mock with no tracking headers
        $trackerMock = Mockery::mock(SentEmail::class);
        $trackerMock->shouldReceive('getHeader')
            ->with('X-ProcessMaker-Email-ID')
            ->andReturn(null); // No email ID header = tracking not enabled
        $trackerMock->shouldReceive('getHeader')
            ->with('X-ProcessMaker-Sent-Message-Event-ID')
            ->andReturn(null);
        $trackerMock->shouldReceive('getHeader')
            ->with('X-ProcessMaker-Request-ID')
            ->andReturn(null);

        // Create event mock
        $eventMock = Mockery::mock(EmailSentEvent::class);
        $eventMock->sent_email = $trackerMock;

        // WorkflowManager should NOT be called when tracking not enabled
        WorkflowManager::shouldReceive('triggerMessageEvent')->never();

        // Call the handler - this should not throw an exception
        $handler->handle($eventMock);
    }

    /**
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
