<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/core/Controller.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/models/Game.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/models/Result.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/models/Message.php';

class ProfileController extends Controller {
    
    public function index(): void {
        $user = $this->requireAuth();
        $userId = (int)$user['id'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_game_id'])) {
            $deleteId = (int)$_POST['delete_game_id'];
            $game = Game::findByIdAndUser($deleteId, $userId);
            
            if ($game) {
                $gameDir = $_SERVER['DOCUMENT_ROOT'] . '/' . $game['path'];
                if (is_dir($gameDir)) {
                    $it = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($gameDir, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::CHILD_FIRST
                    );
                    foreach ($it as $file) {
                        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
                    }
                    rmdir($gameDir);
                }
                Game::delete($deleteId);
                $this->redirect('/profile');
            }
        }
        
        $messages = Message::getByUser($userId);
        $myGames = Game::getByUser($userId);
        $results = Result::getByUser($userId);
        
        $this->view('profile/profile', [
            'username' => $user['username'],
            'messages' => $messages,
            'myGames' => $myGames,
            'results' => $results
        ]);
    }
}
