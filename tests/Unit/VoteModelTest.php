<?php

use PHPUnit\Framework\TestCase;

/**
 * Юнит-тесты для модели Vote
 * 
 * Тестируем:
 * - Сохранение лайка
 * - Сохранение дизлайка
 * - Изменение голоса
 */
class VoteModelTest extends TestCase
{
    private static $testUserId;
    private static $testGameId;
    
    public static function setUpBeforeClass(): void
    {
        self::$testUserId = User::create('test_vote_user_' . time(), 'password');
        self::$testGameId = Game::create([
            'user_id' => self::$testUserId,
            'title' => 'Vote Test Game',
            'description' => '',
            'rules' => 'Rules',
            'engine' => 'js',
            'path' => 'repository/vote-test',
            'icon_path' => null,
            'is_system' => 0
        ]);
    }
    
    public static function tearDownAfterClass(): void
    {
        db_query("DELETE FROM games WHERE id = ?", [self::$testGameId]);
        db_query("DELETE FROM users WHERE id = ?", [self::$testUserId]);
    }
    
    protected function tearDown(): void
    {
        // Очищаем голоса после каждого теста
        db_query("DELETE FROM game_votes WHERE game_id = ? AND user_id = ?", 
            [self::$testGameId, self::$testUserId]);
    }
    
    /**
     * Тест: Сохранение лайка
     */
    public function testSaveLike(): void
    {
        $result = Vote::save(self::$testGameId, self::$testUserId, 1);
        
        $this->assertTrue($result);
        
        // Проверяем что голос сохранён
        $stmt = db_query("SELECT vote FROM game_votes WHERE game_id = ? AND user_id = ?", 
            [self::$testGameId, self::$testUserId]);
        $vote = $stmt->fetchColumn();
        
        $this->assertEquals(1, $vote);
    }
    
    /**
     * Тест: Сохранение дизлайка
     */
    public function testSaveDislike(): void
    {
        $result = Vote::save(self::$testGameId, self::$testUserId, -1);
        
        $this->assertTrue($result);
        
        $stmt = db_query("SELECT vote FROM game_votes WHERE game_id = ? AND user_id = ?", 
            [self::$testGameId, self::$testUserId]);
        $vote = $stmt->fetchColumn();
        
        $this->assertEquals(-1, $vote);
    }
    
    /**
     * Тест: Изменение голоса с лайка на дизлайк
     */
    public function testChangeVote(): void
    {
        // Сначала лайк
        Vote::save(self::$testGameId, self::$testUserId, 1);
        
        // Потом дизлайк
        Vote::save(self::$testGameId, self::$testUserId, -1);
        
        $stmt = db_query("SELECT vote FROM game_votes WHERE game_id = ? AND user_id = ?", 
            [self::$testGameId, self::$testUserId]);
        $vote = $stmt->fetchColumn();
        
        $this->assertEquals(-1, $vote);
    }
}
