<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/core/Controller.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/models/Game.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/models/Vote.php';

class HomeController extends Controller {
    
    public function index(): void {
        $user = $this->requireAuth();
        $userId = (int)$user['id'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['game_id'], $_POST['vote'])) {
            $gameId = (int)$_POST['game_id'];
            $vote = (int)$_POST['vote'];
            
            if ($gameId > 0 && in_array($vote, [-1, 1])) {
                Vote::save($gameId, $userId, $vote);
            }
            $this->redirect($_SERVER['REQUEST_URI']);
        }
        
        $games = Game::getAll($userId);
        $systemGames = array_filter($games, fn($g) => $g['is_system'] == 1);
        $userGames = array_filter($games, fn($g) => $g['is_system'] == 0);
        
        $this->view('home/games_list', [
            'systemGames' => $systemGames,
            'userGames' => $userGames,
            'username' => $user['username']
        ]);
    }
}
