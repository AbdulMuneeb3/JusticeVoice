<?php
$servername = "localhost";
$db_name = "tweet";
$username = "root";
$password = "";

try {
    // Create a new PDO connection
    $conn = new PDO("mysql:host=$servername;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle AJAX request to update post status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (isset($data['post_id']) && isset($data['status'])) {
        $postId = $data['post_id'];
        $status = $data['status'];

        try {
            // Begin transaction
            $conn->beginTransaction();

            if ($status === 'approved') {
                // Update only the status for approved posts
                $stmt = $conn->prepare("UPDATE posts SET statuss = :status WHERE id = :post_id");
                $stmt->execute(['status' => $status, 'post_id' => $postId]);
                
            } elseif ($status === 'rejected') {
                // Fetch the post data to copy it
                $stmt = $conn->prepare("SELECT p.id, p.user_id, p.post_on, t.status as post_description, t.img, p.created_at, p.updated_at 
                                         FROM posts p 
                                         JOIN tweets t ON t.post_id = p.id 
                                         WHERE p.id = :post_id");
                $stmt->execute(['post_id' => $postId]);
                $post = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($post) {
                    // Insert post data into the rejected_posts table, including the post ID and tweet data
                    $insertStmt = $conn->prepare("INSERT INTO rejected_posts (id, user_id, post_on, post_description, img, created_at, updated_at)
                                                   VALUES (:id, :user_id, :post_on, :post_description, :img, :created_at, :updated_at)");
                    $insertStmt->execute([
                        'id' => $post['id'], // Copy the ID from the posts table
                        'user_id' => $post['user_id'],
                        'post_on' => $post['post_on'],
                        'post_description' => $post['post_description'], // Set the description from tweets
                        'img' => $post['img'], // Get image data from tweets
                        'created_at' => date('Y-m-d H:i:s'), // Use current timestamp or fetch from the post
                        'updated_at' => date('Y-m-d H:i:s') // Use current timestamp
                    ]);

                    // Delete the post from the posts table
                    $deleteStmt = $conn->prepare("DELETE FROM posts WHERE id = :post_id");
                    $deleteStmt->execute(['post_id' => $postId]);
                }
            }

            // Commit the transaction
            $conn->commit();
            echo json_encode(['success' => true]);
            exit;

        } catch (PDOException $e) {
            // Roll back the transaction on error
            $conn->rollBack();
            error_log("Database error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
    }
}
?>
