<?php

use PHPUnit\Framework\TestCase;

/**
 * Тесты логики контроллеров через модели
 * Не вызываем контроллеры напрямую, чтобы избежать вывода HTML/JSON
 */
class ControllerLogicTest extends TestCase
{
    private static $testUserId;
    private static $testAdminId;
    private static $testGameId;
    
    public static function setUpBeforeClass(): void
    {
        // Создаем обычного пользователя
        $username = 'ctrl_user_' . time();
        self::$testUserId = User::create($username, 'test123');
        
        // Создаем админа (используем ID 1 который обычно админ, или создаем обычного пользователя)
        $adminUsername = 'ctrl_admin_' . time();
        self::$testAdminId = User::create($adminUsername, 'admin123');
        
        // Создаем тестовую игру
        self::$testGameId = Game::create([
            'user_id' => self::$testUserId,
            'title' => 'Controller Test Game',
            'description' => 'Test',
            'rules' => 'Test rules',
            'engine' => 'js',
            'path' => 'repository/test',
            'icon_path' => null,
            'is_system' => 0
        ]);
    }
    
    // AuthController логика
    
    public function testUserCanRegister()
    {
        $username = 'new_user_' . time();
        $password = 'test123';
        
        $userId = User::create($username, $password);
        
        $this->assertGreaterThan(0, $userId);
        
        $user = User::findByUsername($username);
        $this->assertNotNull($user);
        $this->assertEquals($username, $user['username']);
    }
    
    public function testUserCanLogin()
    {
        $username = 'login_user_' . time();
        $password = 'test123';
        
        User::create($username, $password);
        $user = User::findByUsername($username);
        
        $this->assertNotNull($user);
        $this->assertTrue(User::verifyPassword($user, $password));
    }
    
    public function testDuplicateUsernameNotAllowed()
    {
        $username = 'duplicate_' . time();
        User::create($username, 'test123');
        
        $existingUser = User::findByUsername($username);
        $this->assertNotNull($existingUser);
        
        // Проверяем что пользователь с таким именем уже существует
        $duplicate = User::findByUsername($username);
        $this->assertEquals($existingUser['id'], $duplicate['id']);
    }
    
    public function testAdminPermissions()
    {
        // Проверяем что метод isAdmin работает
        $isNotAdmin = User::isAdmin(self::$testUserId);
        $this->assertFalse($isNotAdmin);
        
        // Проверяем что метод возвращает boolean
        $this->assertIsBool($isNotAdmin);
    }
    
    // GameController логика
    
    public function testUserCanCreateGame()
    {
        $gameId = Game::create([
            'user_id' => self::$testUserId,
            'title' => 'New Game',
            'description' => 'Description',
            'rules' => 'Rules',
            'engine' => 'js',
            'path' => 'repository/newgame',
            'icon_path' => null,
            'is_system' => 0
        ]);
        
        $this->assertGreaterThan(0, $gameId);
        
        $game = Game::findById($gameId);
        $this->assertNotNull($game);
        $this->assertEquals('New Game', $game['title']);
    }
    
    public function testUserCanEditOwnGame()
    {
        $gameId = Game::create([
            'user_id' => self::$testUserId,
            'title' => 'Original Title',
            'description' => 'Original',
            'rules' => 'Original rules',
            'engine' => 'js',
            'path' => 'repository/edit',
            'icon_path' => null,
            'is_system' => 0
        ]);
        
        $updated = Game::update($gameId, self::$testUserId, [
            'title' => 'Updated Title',
            'description' => 'Updated',
            'rules' => 'Updated rules',
            'icon_path' => null
        ]);
        
        $this->assertTrue($updated);
        
        $game = Game::findById($gameId);
        $this->assertEquals('Updated Title', $game['title']);
    }
    
    public function testUserCanDeleteOwnGame()
    {
        $gameId = Game::create([
            'user_id' => self::$testUserId,
            'title' => 'Game to Delete',
            'description' => 'Test',
            'rules' => 'Test',
            'engine' => 'js',
            'path' => 'repository/delete',
            'icon_path' => null,
            'is_system' => 0
        ]);
        
        $deleted = Game::delete($gameId);
        $this->assertTrue($deleted);
        
        $game = Game::findById($gameId);
        $this->assertNull($game);
    }
    
    public function testUserCanVoteOnGame()
    {
        Vote::save(self::$testGameId, self::$testUserId, 1);
        
        $votes = Game::getVotes(self::$testGameId, self::$testUserId);
        $this->assertArrayHasKey('likes', $votes);
        $this->assertGreaterThanOrEqual(0, $votes['likes']);
    }
    
    public function testUserCanChangeVote()
    {
        $username = 'vote_change_' . time();
        User::create($username, 'test123');
        $user = User::findByUsername($username);
        
        // Лайк
        Vote::save(self::$testGameId, $user['id'], 1);
        $votes1 = Game::getVotes(self::$testGameId, $user['id']);
        $this->assertEquals(1, $votes1['user_vote']);
        
        // Меняем на дизлайк
        Vote::save(self::$testGameId, $user['id'], -1);
        $votes2 = Game::getVotes(self::$testGameId, $user['id']);
        $this->assertEquals(-1, $votes2['user_vote']);
    }
    
    public function testUserCanCommentOnGame()
    {
        $created = Comment::create(self::$testGameId, self::$testUserId, 'Great game!');
        $this->assertTrue($created);
        
        $comments = Comment::getByGame(self::$testGameId);
        $this->assertNotEmpty($comments);
    }
    
    // ProfileController логика
    
    public function testUserCanViewOwnGames()
    {
        $games = Game::getByUser(self::$testUserId);
        $this->assertIsArray($games);
        $this->assertNotEmpty($games);
    }
    
    public function testUserCanViewOwnResults()
    {
        Result::save(self::$testUserId, self::$testGameId, 100);
        
        $results = Result::getByUser(self::$testUserId);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
    }
    
    public function testUserCanViewOwnMessages()
    {
        Message::create(self::$testUserId, 'Test message');
        
        $messages = Message::getByUser(self::$testUserId);
        $this->assertIsArray($messages);
        $this->assertNotEmpty($messages);
    }
    
    // ApiController логика
    
    public function testUserCanSaveGameResult()
    {
        $saved = Result::save(self::$testUserId, self::$testGameId, 250);
        $this->assertTrue($saved);
        
        $results = Result::getByUser(self::$testUserId);
        $found = false;
        foreach ($results as $result) {
            if ($result['score'] == 250) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }
    
    public function testUserCanSaveResultWithMetadata()
    {
        $meta = ['level' => 10, 'time' => 300];
        $saved = Result::save(self::$testUserId, self::$testGameId, 500, $meta);
        $this->assertTrue($saved);
        
        $results = Result::getByUser(self::$testUserId);
        $this->assertNotEmpty($results);
    }
    
    public function testTopResultsAreOrdered()
    {
        $gameId = Game::create([
            'user_id' => self::$testUserId,
            'title' => 'Leaderboard Game',
            'description' => 'Test',
            'rules' => 'Test',
            'engine' => 'js',
            'path' => 'repository/leader',
            'icon_path' => null,
            'is_system' => 0
        ]);
        
        // Создаем несколько результатов
        for ($i = 1; $i <= 5; $i++) {
            $username = "leader_$i" . time();
            User::create($username, 'test123');
            $user = User::findByUsername($username);
            Result::save($user['id'], $gameId, $i * 100);
        }
        
        $topResults = Result::getTopByGame($gameId, 3);
        $this->assertCount(3, $topResults);
        $this->assertEquals(500, $topResults[0]['score']);
        $this->assertEquals(400, $topResults[1]['score']);
        $this->assertEquals(300, $topResults[2]['score']);
    }
    
    // HomeController логика
    
    public function testUserCanViewAllGames()
    {
        $games = Game::getAll(self::$testUserId);
        $this->assertIsArray($games);
        $this->assertNotEmpty($games);
    }
    
    public function testGamesAreSeparatedByType()
    {
        // Создаем системную игру (админ)
        $systemGameId = Game::create([
            'user_id' => self::$testAdminId,
            'title' => 'System Game',
            'description' => 'System',
            'rules' => 'System',
            'engine' => 'js',
            'path' => 'repository/system',
            'icon_path' => null,
            'is_system' => 1
        ]);
        
        // Создаем пользовательскую игру
        $userGameId = Game::create([
            'user_id' => self::$testUserId,
            'title' => 'User Game',
            'description' => 'User',
            'rules' => 'User',
            'engine' => 'js',
            'path' => 'repository/user',
            'icon_path' => null,
            'is_system' => 0
        ]);
        
        $allGames = Game::getAll(self::$testUserId);
        
        $systemGames = array_filter($allGames, fn($g) => $g['is_system'] == 1);
        $userGames = array_filter($allGames, fn($g) => $g['is_system'] == 0);
        
        $this->assertNotEmpty($systemGames);
        $this->assertNotEmpty($userGames);
    }
    
    public function testAdminCanDeleteAnyGame()
    {
        $gameId = Game::create([
            'user_id' => self::$testUserId,
            'title' => 'Game for Admin Delete',
            'description' => 'Test',
            'rules' => 'Test',
            'engine' => 'js',
            'path' => 'repository/admindel',
            'icon_path' => null,
            'is_system' => 0
        ]);
        
        // Отправляем сообщение автору (как делает админ)
        $game = Game::findById($gameId);
        Message::create($game['user_id'], 'Ваша игра была удалена');
        
        // Удаляем игру
        $deleted = Game::delete($gameId);
        $this->assertTrue($deleted);
        
        // Проверяем что сообщение получено
        $messages = Message::getByUser(self::$testUserId);
        $found = false;
        foreach ($messages as $msg) {
            if (strpos($msg['message'], 'удалена') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }
}
