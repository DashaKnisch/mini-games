<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/core/Controller.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/models/Game.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/models/User.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/models/Vote.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/models/Comment.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/models/Result.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/models/Message.php';

class GameController extends Controller {
    
    public function play(string $id): void {
        $user = $this->requireAuth();
        $userId = (int)$user['id'];
        $gameId = (int)$id;
        
        if ($gameId <= 0) {
            $this->redirect('/');
        }
        
        $isAdmin = User::isAdmin($userId);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['vote'])) {
                $vote = (int)$_POST['vote'];
                if (in_array($vote, [-1,1])) {
                    Vote::save($gameId, $userId, $vote);
                }
                $this->redirect($_SERVER['REQUEST_URI']);
            }
            
            if (!empty($_POST['comment_text'])) {
                $text = trim($_POST['comment_text']);
                Comment::create($gameId, $userId, $text);
                $this->redirect($_SERVER['REQUEST_URI']);
            }
            
            if ($isAdmin && isset($_POST['delete_game'], $_POST['delete_reason'])) {
                $reason = trim($_POST['delete_reason']);
                if ($reason !== '') {
                    $gameData = Game::findById($gameId);
                    if ($gameData && (int)$gameData['is_system'] === 0) {
                        $authorId = $gameData['user_id'];
                        Message::create($authorId, "Ваша игра была удалена по причине: $reason");
                        Game::delete($gameId);
                    }
                    $this->redirect('/');
                }
            }
        }
        
        $game = Game::findById($gameId);
        if (!$game) {
            http_response_code(404);
            echo 'Игра не найдена.';
            exit;
        }
        
        $isSystemGame = (int)$game['is_system'] === 1;
        $votes = Game::getVotes($gameId, $userId);
        $comments = Comment::getByGame($gameId);
        $topResults = Result::getTopByGame($gameId);
        
        $gamePath = rtrim($game['path'], '/');
        $absPath = realpath($_SERVER['DOCUMENT_ROOT'] . '/' . $gamePath);
        $indexHtml = null;
        
        if ($absPath && is_dir($absPath)) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absPath));
            foreach ($it as $file) {
                if (preg_match('/^index(\.[a-z0-9]+)?$/i', $file->getFilename())) {
                    $indexHtml = $file->getPathname();
                    break;
                }
            }
        }
        
        if (!$indexHtml) {
            echo '<h2>Игра недоступна</h2><p>В каталоге игры отсутствует index.html.</p>';
            exit;
        }
        
        $projectRoot = realpath($_SERVER['DOCUMENT_ROOT']);
        $rel = '/' . ltrim(str_replace('\\','/', substr($indexHtml, strlen($projectRoot))), '/');
        
        $this->view('game/play', [
            'game' => $game,
            'votes' => $votes,
            'comments' => $comments,
            'topResults' => $topResults,
            'gameUrl' => $rel,
            'isAdmin' => $isAdmin,
            'isSystemGame' => $isSystemGame,
            'gameId' => $gameId,
            'username' => $user['username']
        ]);
    }
    
    public function showAddForm(): void {
        $user = $this->requireAuth();
        $this->view('game/add', ['errors' => [], 'username' => $user['username']]);
    }
    
    public function add(): void {
        $user = $this->requireAuth();
        $userId = (int)$user['id'];
        
        $errors = [];
        $title = trim($_POST['title'] ?? '');
        $rules = trim($_POST['rules'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $selectedEngine = $_POST['engine'] ?? '';
        
        if ($title === '') $errors[] = 'Название обязательно для заполнения.';
        if ($rules === '') $errors[] = 'Правила обязательно для заполнения.';
        if (!isset($_FILES['zip']) || $_FILES['zip']['error'] !== UPLOAD_ERR_OK) $errors[] = 'ZIP с игрой обязателен.';
        
        if (empty($errors)) {
            $isAdmin = User::isAdmin($userId);
            
            $slug = preg_replace('/[^a-z0-9-_]/i', '-', mb_strtolower($title));
            $slug = preg_replace('/-+/', '-', $slug);
            $slug = trim($slug, '-');
            if ($slug === '') $slug = 'game-' . time();
            
            $finalDir = $_SERVER['DOCUMENT_ROOT'] . '/repository/' . $slug;
            $i = 1;
            while (file_exists($finalDir)) {
                $finalDir = $_SERVER['DOCUMENT_ROOT'] . '/repository/' . $slug . '-' . $i;
                $i++;
            }
            
            if (!mkdir($finalDir, 0775, true) && !is_dir($finalDir)) {
                $errors[] = 'Не удалось создать папку для игры. Проверьте права.';
            }
            
            $iconRelPath = null;
            if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
                $iconName = preg_replace('/[^a-z0-9.\-_]/i', '_', basename($_FILES['icon']['name']));
                $iconDest = $finalDir . '/icon_' . $iconName;
                if (move_uploaded_file($_FILES['icon']['tmp_name'], $iconDest)) {
                    $iconRelPath = 'repository/' . basename($finalDir) . '/icon_' . $iconName;
                }
            }
            
            $zip = new ZipArchive();
            if ($zip->open($_FILES['zip']['tmp_name']) === true) {
                $fileList = [];
                for ($j = 0; $j < $zip->numFiles; $j++) {
                    $fileList[] = $zip->getNameIndex($j);
                }
                
                if ($selectedEngine === 'js') {
                    foreach ($fileList as $f) {
                        if (preg_match('#^Build/#', $f)) {
                            $errors[] = 'Выбрана игра JavaScript, но в архиве обнаружена папка Build/.';
                            break;
                        }
                    }
                }
                
                if ($selectedEngine === 'unity') {
                    $hasBuild = false;
                    foreach ($fileList as $f) {
                        if (preg_match('#^Build/#', $f)) {
                            $hasBuild = true;
                            break;
                        }
                    }
                    if (!$hasBuild) $errors[] = 'Выбрана игра Unity, но в архиве не найдена папка Build/.';
                }
                
                if (empty($errors)) {
                    $zip->extractTo($finalDir);
                    $zip->close();
                    
                    $indexHtml = null;
                    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($finalDir));
                    foreach ($it as $file) {
                        if (preg_match('/^index(\.[a-z0-9]+)?$/i', $file->getFilename())) {
                            $indexHtml = $file->getPathname();
                            break;
                        }
                    }
                    
                    if ($indexHtml === null && $selectedEngine === 'js') {
                        $errors[] = 'В архиве не найден index.html.';
                    } else {
                        $relativePath = 'repository/' . basename($finalDir);
                        if ($indexHtml !== $finalDir) {
                            $sub = str_replace($finalDir, '', dirname($indexHtml));
                            $relativePath = rtrim($relativePath, '/') . str_replace('\\', '/', $sub);
                        }
                        
                        $isSystem = $isAdmin ? 1 : 0;
                        
                        Game::create([
                            'user_id' => $userId,
                            'title' => $title,
                            'description' => $description,
                            'rules' => $rules,
                            'engine' => $selectedEngine,
                            'path' => $relativePath,
                            'icon_path' => $iconRelPath,
                            'is_system' => $isSystem
                        ]);
                        
                        $this->redirect('/');
                    }
                } else {
                    $zip->close();
                }
            } else {
                $errors[] = 'Невозможно открыть ZIP файл.';
            }
        }
        
        $this->view('game/add', ['errors' => $errors, 'username' => $user['username']]);
    }
    
    public function showEditForm(string $id): void {
        $user = $this->requireAuth();
        $userId = (int)$user['id'];
        $gameId = (int)$id;
        
        $game = Game::findByIdAndUser($gameId, $userId);
        if (!$game) {
            $this->redirect('/profile');
        }
        
        $this->view('game/edit', ['game' => $game, 'errors' => [], 'username' => $user['username']]);
    }
    
    public function edit(string $id): void {
        $user = $this->requireAuth();
        $userId = (int)$user['id'];
        $gameId = (int)$id;
        
        $game = Game::findByIdAndUser($gameId, $userId);
        if (!$game) {
            $this->redirect('/profile');
        }
        
        $errors = [];
        $title = trim($_POST['title'] ?? '');
        $rules = trim($_POST['rules'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if ($title === '') $errors[] = 'Название обязательно для заполнения.';
        if ($rules === '') $errors[] = 'Правила обязательно для заполнения.';
        
        $iconRelPath = $game['icon_path'];
        $gamePath = $game['path'];
        
        if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
            $iconName = preg_replace('/[^a-z0-9.\-_]/i', '_', basename($_FILES['icon']['name']));
            $iconDest = $_SERVER['DOCUMENT_ROOT'] . '/' . $gamePath . '/icon_' . $iconName;
            if (move_uploaded_file($_FILES['icon']['tmp_name'], $iconDest)) {
                $iconRelPath = $gamePath . '/icon_' . $iconName;
            }
        }
        
        if (isset($_FILES['zip']) && $_FILES['zip']['error'] === UPLOAD_ERR_OK) {
            $zip = new ZipArchive();
            if ($zip->open($_FILES['zip']['tmp_name']) === true) {
                $it = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($_SERVER['DOCUMENT_ROOT'] . '/' . $gamePath, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($it as $file) {
                    $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
                }
                
                $zip->extractTo($_SERVER['DOCUMENT_ROOT'] . '/' . $gamePath);
                $zip->close();
            } else {
                $errors[] = 'Невозможно открыть ZIP файл.';
            }
        }
        
        if (empty($errors)) {
            Game::update($gameId, $userId, [
                'title' => $title,
                'description' => $description,
                'rules' => $rules,
                'icon_path' => $iconRelPath
            ]);
            $this->redirect('/profile');
        }
        
        $this->view('game/edit', ['game' => $game, 'errors' => $errors, 'username' => $user['username']]);
    }
}
