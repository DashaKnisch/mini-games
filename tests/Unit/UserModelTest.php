<?php

use PHPUnit\Framework\TestCase;

/**
 * Юнит-тесты для модели User
 * 
 * Тестируем:
 * - Создание пользователя
 * - Поиск по username
 * - Поиск по ID
 * - Проверку пароля
 * - Проверку прав администратора
 */
class UserModelTest extends TestCase
{
    private static $testUserId;
    
    /**
     * Выполняется один раз перед всеми тестами класса
     */
    public static function setUpBeforeClass(): void
    {
        // Очищаем тестовую БД
        db_query("DELETE FROM users WHERE username LIKE 'test_%'");
    }
    
    /**
     * Выполняется перед каждым тестом
     */
    protected function setUp(): void
    {
        // Создаём тестового пользователя
        self::$testUserId = User::create('test_user_' . time(), 'password123');
    }
    
    /**
     * Выполняется после каждого теста
     */
    protected function tearDown(): void
    {
        // Удаляем тестовые данные
        if (self::$testUserId) {
            db_query("DELETE FROM users WHERE id = ?", [self::$testUserId]);
        }
    }
    
    /**
     * Тест: Создание пользователя
     */
    public function testCreateUser(): void
    {
        $username = 'test_new_user_' . time();
        $userId = User::create($username, 'password123');
        
        $this->assertIsInt($userId);
        $this->assertGreaterThan(0, $userId);
        
        // Проверяем что пользователь создан
        $user = User::findById($userId);
        $this->assertNotNull($user);
        $this->assertEquals($username, $user['username']);
        
        // Очистка
        db_query("DELETE FROM users WHERE id = ?", [$userId]);
    }
    
    /**
     * Тест: Поиск пользователя по username
     */
    public function testFindByUsername(): void
    {
        $user = User::findById(self::$testUserId);
        $username = $user['username'];
        
        $foundUser = User::findByUsername($username);
        
        $this->assertNotNull($foundUser);
        $this->assertEquals(self::$testUserId, $foundUser['id']);
        $this->assertEquals($username, $foundUser['username']);
    }
    
    /**
     * Тест: Поиск несуществующего пользователя
     */
    public function testFindNonExistentUser(): void
    {
        $user = User::findByUsername('nonexistent_user_12345');
        
        $this->assertNull($user);
    }
    
    /**
     * Тест: Проверка правильного пароля
     */
    public function testVerifyCorrectPassword(): void
    {
        $user = User::findById(self::$testUserId);
        
        $isValid = User::verifyPassword($user, 'password123');
        
        $this->assertTrue($isValid);
    }
    
    /**
     * Тест: Проверка неправильного пароля
     */
    public function testVerifyIncorrectPassword(): void
    {
        $user = User::findById(self::$testUserId);
        
        $isValid = User::verifyPassword($user, 'wrong_password');
        
        $this->assertFalse($isValid);
    }
    
    /**
     * Тест: Проверка прав администратора
     */
    public function testIsAdmin(): void
    {
        // Обычный пользователь не админ
        $isAdmin = User::isAdmin(self::$testUserId);
        $this->assertFalse($isAdmin);
        
        // Делаем пользователя админом
        db_query("UPDATE users SET is_admin = 1 WHERE id = ?", [self::$testUserId]);
        
        $isAdmin = User::isAdmin(self::$testUserId);
        $this->assertTrue($isAdmin);
    }
}
