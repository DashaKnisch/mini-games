<?php

use PHPUnit\Framework\TestCase;

/**
 * Юнит-тесты для модели Game
 * 
 * Тестируем:
 * - Создание игры
 * - Получение всех игр
 * - Поиск игры по ID
 * - Обновление игры
 * - Удаление игры
 * - Получение голосов
 */
class GameModelTest extends TestCase
{
    private static $testUserId;
    private static $testGameId;
    
    public static function setUpBeforeClass(): void
    {
        // Создаём тестового пользователя
        self::$testUserId = User::create('test_game_user_' . time(), 'password');
    }
    
    public static function tearDownAfterClass(): void
    {
        // Удаляем тестового пользователя
        db_query("DELETE FROM users WHERE id = ?", [self::$testUserId]);
    }
    
    protected function setUp(): void
    {
        // Создаём тестовую игру
        self::$testGameId = Game::create([
            'user_id' => self::$testUserId,
            'title' => 'Test Game',
            'description' => 'Test Description',
            'rules' => 'Test Rules',
            'engine' => 'js',
            'path' => 'repository/test',
            'icon_path' => null,
            'is_system' => 0
        ]);
    }
    
    protected function tearDown(): void
    {
        // Удаляем тестовую игру
        if (self::$testGameId) {
            db_query("DELETE FROM games WHERE id = ?", [self::$testGameId]);
        }
    }
    
    /**
     * Тест: Создание игры
     */
    public function testCreateGame(): void
    {
        $gameId = Game::create([
            'user_id' => self::$testUserId,
            'title' => 'New Test Game',
            'description' => 'Description',
            'rules' => 'Rules',
            'engine' => 'unity',
            'path' => 'repository/new-test',
            'icon_path' => 'repository/icon.png',
            'is_system' => 0
        ]);
        
        $this->assertIsInt($gameId);
        $this->assertGreaterThan(0, $gameId);
        
        // Проверяем что игра создана
        $game = Game::findById($gameId);
        $this->assertNotNull($game);
        $this->assertEquals('New Test Game', $game['title']);
        $this->assertEquals('unity', $game['engine']);
        
        // Очистка
        db_query("DELETE FROM games WHERE id = ?", [$gameId]);
    }
    
    /**
     * Тест: Получение всех игр
     */
    public function testGetAllGames(): void
    {
        $games = Game::getAll(self::$testUserId);
        
        $this->assertIsArray($games);
        $this->assertGreaterThan(0, count($games));
        
        // Проверяем структуру данных
        $game = $games[0];
        $this->assertArrayHasKey('id', $game);
        $this->assertArrayHasKey('title', $game);
        $this->assertArrayHasKey('likes', $game);
        $this->assertArrayHasKey('dislikes', $game);
        $this->assertArrayHasKey('user_vote', $game);
    }
    
    /**
     * Тест: Поиск игры по ID
     */
    public function testFindGameById(): void
    {
        $game = Game::findById(self::$testGameId);
        
        $this->assertNotNull($game);
        $this->assertEquals(self::$testGameId, $game['id']);
        $this->assertEquals('Test Game', $game['title']);
        $this->assertArrayHasKey('username', $game);
    }
    
    /**
     * Тест: Обновление игры
     */
    public function testUpdateGame(): void
    {
        $result = Game::update(self::$testGameId, self::$testUserId, [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'rules' => 'Updated Rules',
            'icon_path' => 'new/icon.png'
        ]);
        
        $this->assertTrue($result);
        
        // Проверяем что данные обновились
        $game = Game::findById(self::$testGameId);
        $this->assertEquals('Updated Title', $game['title']);
        $this->assertEquals('Updated Description', $game['description']);
    }
    
    /**
     * Тест: Получение голосов за игру
     */
    public function testGetVotes(): void
    {
        // Добавляем голоса
        Vote::save(self::$testGameId, self::$testUserId, 1);
        
        $votes = Game::getVotes(self::$testGameId, self::$testUserId);
        
        $this->assertIsArray($votes);
        $this->assertArrayHasKey('likes', $votes);
        $this->assertArrayHasKey('dislikes', $votes);
        $this->assertArrayHasKey('user_vote', $votes);
        $this->assertEquals(1, $votes['likes']);
        $this->assertEquals(1, $votes['user_vote']);
        
        // Очистка
        db_query("DELETE FROM game_votes WHERE game_id = ?", [self::$testGameId]);
    }
    
    /**
     * Тест: Удаление игры
     */
    public function testDeleteGame(): void
    {
        $gameId = Game::create([
            'user_id' => self::$testUserId,
            'title' => 'Game to Delete',
            'description' => '',
            'rules' => 'Rules',
            'engine' => 'js',
            'path' => 'repository/delete-test',
            'icon_path' => null,
            'is_system' => 0
        ]);
        
        $result = Game::delete($gameId);
        $this->assertTrue($result);
        
        // Проверяем что игра удалена
        $game = Game::findById($gameId);
        $this->assertNull($game);
    }
}
