<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/dbconnect.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

$database = new Database();
$conn = $database->getConnect(); // PDO

define('SUPPORT_ADMIN_ID', 9999);

class SupportWS implements MessageComponentInterface {
    protected $clients;
    protected $db;
    protected $connections;

    public function __construct($db) {
        $this->clients = new \SplObjectStorage;
        $this->db = $db;
        $this->connections = [];
        echo "Support WS running...\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection: {$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        if (!$data || !isset($data['type'])) return;

        switch ($data['type']) {

            case 'register':
                $this->connections[$from->resourceId] = [
                    'conn' => $from,
                    'user_id' => $data['user_id'],
                    'user_name' => $data['user_name'] ?? 'SYSTEM',
                    'is_admin' => $data['is_admin'] ?? 0,
                    'room' => $data['room'] ?? 'support',
                    'target_user_id' => $data['target_user_id'] ?? null
                ];

                // Load previous messages for this conversation
                if ($data['room'] === 'support') {
                    if ($data['is_admin']) {
                        // Admin sees all messages
                        $stmt = $this->db->prepare("
                            SELECT * FROM user_admin_msgs
                            ORDER BY created_at ASC
                        ");
                        $stmt->execute();
                    } else {
                        // User sees only messages involving themselves and admin
                        $stmt = $this->db->prepare("
                            SELECT * FROM user_admin_msgs
                            WHERE (sender_id = ? AND receiver_id = ?)
                               OR (sender_id = ? AND receiver_id = ?)
                            ORDER BY created_at ASC
                        ");
                        $stmt->execute([
                            $data['user_id'], SUPPORT_ADMIN_ID,
                            SUPPORT_ADMIN_ID, $data['user_id']
                        ]);
                    }

                    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($messages as $m) {
                        $from->send(json_encode([
                            'channel' => 'support',
                            'sender_id' => $m['sender_id'],
                            'receiver_id' => $m['receiver_id'],
                            'sender_name' => $m['sender_name'],
                            'message' => $m['message'],
                            'created_at' => $m['created_at']
                        ]));
                    }
                }
                break;

            case 'chat_message':
                $receiver_id = $data['is_admin'] ? $data['receiver_id'] : SUPPORT_ADMIN_ID;

                // Insert into DB
                $stmt = $this->db->prepare("
                    INSERT INTO user_admin_msgs (sender_id, receiver_id, sender_name, message)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['sender_id'],
                    $receiver_id,
                    $data['sender_name'],
                    $data['message']
                ]);

                $payload = [
                    'channel' => 'support',
                    'sender_id' => $data['sender_id'],
                    'receiver_id' => $receiver_id,
                    'sender_name' => $data['sender_name'],
                    'message' => $data['message'],
                    'created_at' => date("Y-m-d H:i:s")
                ];

                // Broadcast logic
                foreach ($this->connections as $c) {
                    if ($c['room'] !== 'support') continue;

                    // Global announcement logic untouched
                    if (isset($data['is_announcement']) && $data['is_announcement']) {
                        $c['conn']->send(json_encode($payload));
                        continue;
                    }

                    // Admin messages go to all users
                    if ($data['is_admin']) {
                        $c['conn']->send(json_encode($payload));
                        continue;
                    }

                    // User messages go to admin and the sender themselves
                    if ($c['is_admin'] || $c['user_id'] == $data['sender_id']) {
                        $c['conn']->send(json_encode($payload));
                    }
                }
                break;

            case 'admin_announcement':
                // Keep announcement logic intact
                $stmt = $this->db->prepare("INSERT INTO announcements (user_name, message) VALUES (?, ?)");
                $stmt->execute([$data['user_name'], $data['message']]);
                $announcement_id = $this->db->lastInsertId();

                $payload = [
                    'channel' => 'announcements',
                    'announcement_id' => $announcement_id,
                    'user_name' => $data['user_name'],
                    'message' => $data['message'],
                    'created_at' => date("Y-m-d H:i:s")
                ];

                foreach ($this->connections as $c) {
                    if ($c['room'] === 'announcements') {
                        $c['conn']->send(json_encode($payload));
                    }
                }
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        unset($this->connections[$conn->resourceId]);
        echo "Connection {$conn->resourceId} closed\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        $conn->close();
    }
}

$server = \Ratchet\Server\IoServer::factory(
    new \Ratchet\Http\HttpServer(
        new \Ratchet\WebSocket\WsServer(new SupportWS($conn))
    ),
    8080
);

$server->run();

?>