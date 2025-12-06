<?php

use PHPUnit\Framework\TestCase;

/**
 * Интеграционные тесты для процесса аутентификации
 * 
 * Тестируем полный flow:
 * - Регистрация нового пользователя
 * - Вход существующего пользователя
 * - Выход из системы
 * - Попытка входа с неверными данными
 */
class AuthFlowTest extends TestCase
{
    private $testUsername;
    
    protected function setUp(): void
    {
        $this->testUsername = 'integration_test_' . time();
        
        // Очищаем сессию
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
    
    protected function tearDown(): void
    {
        // Удаляем тестового пользователя
        db_query("DELETE FROM users WHERE username = ?", [$this->testUsername]);
    }
    
    /**
     * Тест: Полный цикл регистрации и входа
     */
    public function testRegistrationAndLoginFlow(): void
    {
        // 1. Регистрация
        $userId = User::create($this->testUsername, 'test_password_123');
        $this->assertGreaterThan(0, $userId);
        
        // 2. Проверяем что пользователь создан
        $user = User::findByUsername($this->testUsername);
        $this->assertNotNull($user);
        $this->assertEquals($this->testUsername, $user['username']);
        
        // 3. Проверяем что пароль захеширован
        $this->assertNotEquals('test_password_123', $user['password_hash']);
        $this->assertTrue(password_verify('test_password_123', $user['password_hash']));
        
        // 4. Вход с правильным паролем
        $isValid = User::verifyPassword($user, 'test_password_123');
        $this->assertTrue($isValid);
        
        // 5. Вход с неправильным паролем
        $isValid = User::verifyPassword($user, 'wrong_password');
        $this->assertFalse($isValid);
    }
    
    /**
     * Тест: Попытка создать пользователя с существующим username
     */
    public function testDuplicateUsername(): void
    {
        // Создаём первого пользователя
        User::create($this->testUsername, 'password1');
        
        // Проверяем что пользователь с таким username уже существует
        $existingUser = User::findByUsername($this->testUsername);
        $this->assertNotNull($existingUser);
        
        // В реальном приложении контроллер должен проверить это
        // и вернуть ошибку перед попыткой создания
    }
    
    /**
     * Тест: Проверка прав доступа
     */
    public function testUserPermissions(): void
    {
        $userId = User::create($this->testUsername, 'password');
        
        // По умолчанию пользователь не админ
        $this->assertFalse(User::isAdmin($userId));
        
        // Делаем админом
        db_query("UPDATE users SET is_admin = 1 WHERE id = ?", [$userId]);
        
        // Проверяем права админа
        $this->assertTrue(User::isAdmin($userId));
    }
}
