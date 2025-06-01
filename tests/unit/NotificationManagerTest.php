<?php
use PHPUnit\Framework\TestCase;
use CalendrierRdv\Domain\Notification\NotificationManager;
use CalendrierRdv\Database\QueryBuilder;

class NotificationManagerTest extends TestCase
{
    public function testHandleFailedNotificationLogsToRdvEmailFailuresOnMaxAttempts()
    {
        $mockQueryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['update', 'insert', 'where', 'execute'])
            ->getMock();

        // On s'attend à ce que insert soit appelé pour rdv_email_failures
        $mockQueryBuilder->expects($this->once())
            ->method('insert')
            ->with(
                $this->equalTo('rdv_email_failures'),
                $this->arrayHasKey('recipient')
            )
            ->willReturnSelf();
        $mockQueryBuilder->method('update')->willReturnSelf();
        $mockQueryBuilder->method('where')->willReturnSelf();
        $mockQueryBuilder->method('execute')->willReturn(1);

        $notificationData = [
            'id' => 1,
            'recipient' => 'test@example.com',
            'subject' => 'Test',
            'data' => 'Body',
            'last_error' => 'SMTP Error',
            'attempts' => 2 // max_attempts sera 2, donc on atteint le max
        ];

        $config = ['max_attempts' => 2];
        $manager = new NotificationManager($mockQueryBuilder, $config);
        $managerReflection = new ReflectionClass($manager);
        $method = $managerReflection->getMethod('handleFailedNotification');
        $method->setAccessible(true);
        $method->invoke($manager, $notificationData);
    }
}
