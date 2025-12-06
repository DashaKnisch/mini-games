<?php

use PHPUnit\Framework\TestCase;

/**
 * Интеграционные тесты для работы с играми
 * 
 * Тестируем полный flow:
 * - Создание игры
 * - Голосование за игру
 * - Комментирование
 * - Сохранение результатов
 * - Удаление игры
 */
class GameFlowTest extends TestCase
{
    private static $testUserId;
    private static $testGameId;
    
    public static function setUpBeforeClass(): void
    {
        self::$testUserId = User::create('game_flow_test_' . time(), 'password');
    }
    
    public static function tearDownAfterClass(): void
    {
        db_query("DELETE FROM users WHERE id = ?", [self::$testUserId]);
    }
    
    /**
     * Тест: Полный жизненный цикл игры
     */
    public function testCompleteGameLifecycle(): void
    {
        // 1. Создание игры
        $gameId = Game::create([
            'user_id' => self::$testUserId,
            'title' => 'Integration Test Game',
            'description' => 'Test game for integration testing',
            'rules' => 'Click to win',
            'engine' => 'js',
            'path' => 'repository/integration-test',
            'icon_path' => null,
            'is_system' => 0
        ]);
        
        $this->assertGreaterThan(0, $gameId);
        
        // 2. Проверяем что игра создана
        $game = Game::findById($gameId);
        $this->assertNotNull($game);
        $this->assertEquals('Integration Test Game', $game['title']);
        
        // 3. Голосование за игру
        Vote::save($gameId, self::$testUserId, 1);
        
        $votes = Game::getVotes($gameId, self::$testUserId);
        $this->assertEquals(1, $votes['likes']);
        $this->assertEquals(0, $votes['dislikes']);
        $this->assertEquals(1, $votes['user_vote']);
        
        // 4. Изменение голоса
        Vote::save($gameId, self::$testUserId, -1);
        
        $votes = Game::getVotes($gameId, self::$testUserId);
        $this->assertEquals(0, $votes['likes']);
        $this->assertEquals(1, $votes['dislikes']);
        $this->assertEquals(-1, $votes['user_vote']);
        
        // 5. Добавление комментария
        Comment::create($gameId, self::$testUserId, 'Great game!');
        
        $comments = Comment::getByGame($gameId);
        $this->assertCount(1, $comments);
        $this->assertEquals('Great game!', $comments[0]['comment']);
        
        // 6. Сохранение результата
        Result::save(self::$testUserId, $gameId, 100, ['level' => 5]);
        
        $results = Result::getByUser(self::$testUserId);
        $this->assertGreaterThan(0, count($results));
        
        $topResults = Result::getTopByGame($gameId);
        $this->assertCount(1, $topResults);
        $this->assertEquals(100, $topResults[0]['score']);
        
        // 7. Обновление игры
        Game::update($gameId, self::$testUserId, [
            'title' => 'Updated Game Title',
            'description' => 'Updated description',
            'rules' => 'Updated rules',
            'icon_path' => 'new/icon.png'
        ]);
        
        $game = Game::findById($gameId);
        $this->assertEquals('Updated Game Title', $game['title']);
        
        // 8. Удаление игры
        Game::delete($gameId);
        
        $game = Game::findById($gameId);
        $this->assertNull($game);
        
        // Проверяем что связанные данные тоже удалились (CASCADE)
        $votes = Game::getVotes($gameId, self::$testUserId);
        $this->assertEquals(0, $votes['likes']);
        $this->assertEquals(0, $votes['dislikes']);
    }
    
    /**
     * Тест: Получение игр пользователя
     */
    public function testGetUserGames(): void
    {
        // Создаём несколько игр
        $gameId1 = Game::create([
            'user_id' => self::$testUserId,
            'title' => 'User Game 1',
            'description' => '',
            'rules' => 'Rules',
            'engine' => 'js',
            'path' => 'repository/user-game-1',
            'icon_path' => null,
            'is_system' => 0
        ]);
        
        $gameId2 = Game::create([
            'user_id' => self::$testUserId,
            'title' => 'User Game 2',
            'description' => '',
            'rules' => 'Rules',
            'engine' => 'unity',
            'path' => 'repository/user-game-2',
            'icon_path' => null,
            'is_system' => 0
        ]);
        
        $games = Game::getByUser(self::$testUserId);
        
        $this->assertGreaterThanOrEqual(2, count($games));
        
        // Очистка
        Game::delete($gameId1);
        Game::delete($gameId2);
    }
}
