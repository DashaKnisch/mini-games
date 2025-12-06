<?php

use PHPUnit\Framework\TestCase;

class MessageModelTest extends TestCase
{
    private static $testUserId;
    
    public static function setUpBeforeClass(): void
    {
        // Создаем тестового пользователя
        $username = 'message_test_' . time();
        User::create($username, password_hash('test123', PASSWORD_DEFAULT), 'test@test.com');
        $user = User::findByUsername($username);
        self::$testUserId = $user['id'];
    }
    
    public function testCreateMessage()
    {
        $result = Message::create(self::$testUserId, 'Test message');
        $this->assertTrue($result);
    }
    
    public function testGetMessagesByUser()
    {
        Message::create(self::$testUserId, 'Message 1');
        Message::create(self::$testUserId, 'Message 2');
        
        $messages = Message::getByUser(self::$testUserId);
        
        $this->assertIsArray($messages);
        $this->assertGreaterThanOrEqual(2, count($messages));
        $this->assertArrayHasKey('message', $messages[0]);
        $this->assertArrayHasKey('created_at', $messages[0]);
    }
    
    public function testMessagesOrderedByDateDesc()
    {
        $username = 'order_test_' . time();
        User::create($username, password_hash('test123', PASSWORD_DEFAULT), 'test@test.com');
        $user = User::findByUsername($username);
        $userId = $user['id'];
        
        Message::create($userId, 'Old message');
        sleep(1);
        Message::create($userId, 'New message');
        
        $messages = Message::getByUser($userId);
        
        $this->assertEquals('New message', $messages[0]['message']);
        $this->assertEquals('Old message', $messages[1]['message']);
    }
    
    public function testGetMessagesForNonExistentUser()
    {
        $messages = Message::getByUser(999999);
        $this->assertIsArray($messages);
        $this->assertEmpty($messages);
    }
    
    public function testCreateMultipleMessages()
    {
        $username = 'multi_msg_' . time();
        User::create($username, password_hash('test123', PASSWORD_DEFAULT), 'test@test.com');
        $user = User::findByUsername($username);
        $userId = $user['id'];
        
        for ($i = 1; $i <= 5; $i++) {
            Message::create($userId, "Message $i");
        }
        
        $messages = Message::getByUser($userId);
        $this->assertCount(5, $messages);
    }
}
