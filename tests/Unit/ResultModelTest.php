<?php

use PHPUnit\Framework\TestCase;

class ResultModelTest extends TestCase
{
    private static $testUserId;
    private static $testGameId;
    
    public static function setUpBeforeClass(): void
    {
        // Создаем тестового пользователя
        $username = 'result_test_' . time();
        User::create($username, password_hash('test123', PASSWORD_DEFAULT), 'test@test.com');
        $user = User::findByUsername($username);
        self::$testUserId = $user['id'];
        
        // Создаем тестовую игру
        $gameTitle = 'Result Test Game ' . time();
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
    
    public function testSaveResult()
    {
        $result = Result::save(self::$testUserId, self::$testGameId, 100);
        $this->assertTrue($result);
    }
    
    public function testSaveResultWithMeta()
    {
        $meta = ['level' => 5, 'time' => 120];
        $result = Result::save(self::$testUserId, self::$testGameId, 250, $meta);
        $this->assertTrue($result);
    }
    
    public function testGetResultsByUser()
    {
        Result::save(self::$testUserId, self::$testGameId, 100);
        Result::save(self::$testUserId, self::$testGameId, 200);
        
        $results = Result::getByUser(self::$testUserId);
        
        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(2, count($results));
        $this->assertArrayHasKey('score', $results[0]);
        $this->assertArrayHasKey('game_id', $results[0]);
        $this->assertArrayHasKey('title', $results[0]);
    }
    
    public function testResultsOrderedByDateDesc()
    {
        $username = 'order_result_' . time();
        User::create($username, password_hash('test123', PASSWORD_DEFAULT), 'test@test.com');
        $user = User::findByUsername($username);
        $userId = $user['id'];
        
        Result::save($userId, self::$testGameId, 100);
        sleep(1);
        Result::save($userId, self::$testGameId, 200);
        
        $results = Result::getByUser($userId);
        
        $this->assertEquals(200, $results[0]['score']);
        $this->assertEquals(100, $results[1]['score']);
    }
    
    public function testGetTopResultsByGame()
    {
        $gameTitle = 'Top Scores Game ' . time();
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
        
        // Создаем несколько пользователей с разными результатами
        for ($i = 1; $i <= 5; $i++) {
            $username = "top_user_$i" . time();
            User::create($username, password_hash('test123', PASSWORD_DEFAULT), 'test@test.com');
            $user = User::findByUsername($username);
            Result::save($user['id'], $newGameId, $i * 100);
        }
        
        $topResults = Result::getTopByGame($newGameId, 3);
        
        $this->assertCount(3, $topResults);
        $this->assertEquals(500, $topResults[0]['score']);
        $this->assertEquals(400, $topResults[1]['score']);
        $this->assertEquals(300, $topResults[2]['score']);
    }
    
    public function testGetTopResultsWithCustomLimit()
    {
        $gameTitle = 'Limit Test Game ' . time();
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
        
        for ($i = 1; $i <= 10; $i++) {
            $username = "limit_user_$i" . time();
            User::create($username, password_hash('test123', PASSWORD_DEFAULT), 'test@test.com');
            $user = User::findByUsername($username);
            Result::save($user['id'], $newGameId, $i * 10);
        }
        
        $topResults = Result::getTopByGame($newGameId, 5);
        $this->assertCount(5, $topResults);
    }
    
    public function testGetTopResultsForGameWithNoResults()
    {
        $gameTitle = 'Empty Game ' . time();
        $emptyGameId = Game::create([
            'user_id' => self::$testUserId,
            'title' => $gameTitle,
            'description' => 'Test',
            'rules' => 'Test rules',
            'engine' => 'js',
            'path' => 'repository/test',
            'icon_path' => null,
            'is_system' => 0
        ]);
        
        $topResults = Result::getTopByGame($emptyGameId);
        $this->assertIsArray($topResults);
        $this->assertEmpty($topResults);
    }
    
    public function testGetResultsForNonExistentUser()
    {
        $results = Result::getByUser(999999);
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }
}
