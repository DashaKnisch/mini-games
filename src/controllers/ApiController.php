<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/core/Controller.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/models/Result.php';

class ApiController extends Controller {
    
    public function saveResult(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (empty($_SESSION['user'])) {
            $this->json(['error' => 'Unauthorized'], 401);
        }
        
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        
        $game_id = isset($data['game_id']) ? (int)$data['game_id'] : 0;
        $score = isset($data['score']) ? (int)$data['score'] : 0;
        $meta = isset($data['meta']) ? $data['meta'] : null;
        
        if ($game_id <= 0) {
            $this->json(['error' => 'Invalid game_id'], 400);
        }
        
        $userId = (int)$_SESSION['user']['id'];
        
        try {
            Result::save($userId, $game_id, $score, $meta);
            $this->json(['ok' => true]);
        } catch (Exception $e) {
            $this->json(['error' => 'DB error', 'message' => $e->getMessage()], 500);
        }
    }
}
