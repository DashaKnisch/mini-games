<?php

use PHPUnit\Framework\TestCase;

class CommentModelTest extends TestCase
{
    private static $testUserId;
    private static $testGameId;
    
    public static function setUpBeforeClass(): void
    {
        // Создаем тестового пользователя
        $username = 'comment_test_' . time();
        User::create($username, password_hash('test123', PASSWORD_DEFAULT), 'test@test.com');
        $user = User::findByUsername($username);
        self::$testUserId = $user['id'];
        
        // Создаем тестовую игру
        $gameTitle = 'Test Game ' . time();
        self::$testGameId = Game::create([
            'user_id' => self::$testUserId,
            'title' => $gameTitle,
            'description' => 'Test description',
            'rules' => 'Test rules',
            'engine' => 'js',
            'path' => 'repository/test',
            'icon_path' => null,
            'is_system' => 0
        ]);
    }
    
    public function testCreateComment()
    {
        $result = Comment::create(self::$testGameId, self::$testUserId, 'Great game!');
        $this->assertTrue($result);
    }
    
    public function testGetCommentsByGame()
    {
        Comment::create(self::$testGameId, self::$testUserId, 'First comment');
        Comment::create(self::$testGameId, self::$testUserId, 'Second comment');
        
        $comments = Comment::getByGame(self::$testGameId);
        
        $this->assertIsArray($comments);
        $this->assertGreaterThanOrEqual(2, count($comments));
        $this->assertArrayHasKey('comment', $comments[0]);
        $this->assertArrayHasKey('username', $comments[0]);
        $this->assertArrayHasKey('created_at', $comments[0]);
    }
    
    public function testCommentsOrderedByDate()
    {
        $gameTitle = 'Order Test Game ' . time();
        $newGameId = Game::create([
            'user_id' => self::$testUserId,
            'title' => $gameTitle,
            'description' => 'Test',
            'rules' => 'Test rules',
            'engine' => 'js',
            'path' => 'repository/test',
            'icon_path' => null,
            'is_system' => 0
        ]);
        
        Comment::create($newGameId, self::$testUserId, 'First');
        sleep(1);
        Comment::create($newGameId, self::$testUserId, 'Second');
        
        $comments = Comment::getByGame($newGameId);
        
        $this->assertEquals('First', $comments[0]['comment']);
        $this->assertEquals('Second', $comments[1]['comment']);
    }
    
    public function testGetCommentsForNonExistentGame()
    {
        $comments = Comment::getByGame(999999);
        $this->assertIsArray($comments);
        $this->assertEmpty($comments);
    }
}
