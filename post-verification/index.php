<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$db_name = "tweet"; // Your database name
$username = "root"; // Your database username
$password = ""; // Your database password

try {
    $conn = new PDO("mysql:host=$servername;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Function to fetch posts by status
    function fetchPostsByStatus($conn, $status) {
        $stmt = $conn->prepare("
            SELECT t.status AS post_description, t.img AS tweet_img, u.username, u.img AS user_img, p.id AS post_id
            FROM tweets t
            JOIN posts p ON t.post_id = p.id
            JOIN users u ON p.user_id = u.id
            WHERE p.statuss = :status
        ");
        $stmt->execute(['status' => $status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Function to fetch rejected posts
    function fetchRejectedPosts($conn) {
        $stmt = $conn->prepare("
            SELECT 
                r.id AS post_id, 
                r.post_description, 
                r.img AS tweet_img, 
                u.username, 
                u.img AS user_img 
            FROM 
                rejected_posts r
            JOIN 
                users u ON r.user_id = u.id
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Handle AJAX request to update post status
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['post_id']) && isset($data['status'])) {
            $postId = $data['post_id'];
            $statuss = $data['status']; // This is the status for the posts table

            // Update the statuss column in the posts table
            $stmt = $conn->prepare("UPDATE posts SET statuss = :statuss WHERE id = :post_id");
            $stmt->execute(['statuss' => $statuss, 'post_id' => $postId]);

            // If statuss is "rejected", copy data to rejected_posts table
            if ($statuss === 'rejected') {
                // Fetch status and img from the tweets table before deletion
                $fetchStmt = $conn->prepare("SELECT status, img FROM tweets WHERE post_id = :post_id");
                $fetchStmt->execute(['post_id' => $postId]);
                $tweetData = $fetchStmt->fetch(PDO::FETCH_ASSOC);

                // Check if tweet data is available
                if ($tweetData) {
                    // Insert into rejected_posts table
                    $insertStmt = $conn->prepare("
                        INSERT INTO rejected_posts (id, post_description, img, user_id, created_at, post_on, updated_at)
                        VALUES (:post_id, :tweet_status, :img, (SELECT user_id FROM posts WHERE id = :post_id), NOW(), NOW(), NOW())
                    ");
                    
                    $insertStmt->execute([
                        'post_id' => $postId,
                        'tweet_status' => $tweetData['status'], // Copying the actual description from tweets
                        'img' => $tweetData['img'] // Copying `img` from tweets table
                    ]);

                    // Check if the insert was successful
                    if ($insertStmt->rowCount() > 0) {
                        // Delete the entry from tweets table after copying data
                        $deleteStmt = $conn->prepare("DELETE FROM tweets WHERE post_id = :post_id");
                        $deleteStmt->execute(['post_id' => $postId]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to copy data to rejected_posts']);
                        exit;
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'No tweet data found for the given post_id']);
                    exit;
                }
            }

            echo json_encode(['success' => true]);
            exit;
        }
    }

    // Fetch posts by their statuses
    $pendingPosts = fetchPostsByStatus($conn, 'pending');
    $approvedPosts = fetchPostsByStatus($conn, 'approved');
    $rejectedPosts = fetchRejectedPosts($conn); // Fetching rejected posts from rejected_posts table

} catch(PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} finally {
    $conn = null; // Close the connection
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Verification System</title>
    <link rel="shortcut icon" type="image/png" href="jc.png">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav>
            <div><img src="jc.png" id="logo" alt="logo of jc"></div>
            <div class="logo">Post Verification</div>
            <div class="nav-links">
                <a href="#" data-section="pending" aria-current="page" class="active">Pending</a>
                <a href="#" data-section="approved">Approved</a>
                <a href="#" data-section="rejected">Rejected</a>
            </div>
        </nav>
    </header>
    <main>
        <section id="pending-section" class="section active" aria-labelledby="pending-posts-title">
            <h2 id="pending-posts-title">Pending Posts</h2>
            <div id="pending-posts">
                <?php foreach ($pendingPosts as $post): ?>
                    <article class="post" data-post-id="<?= $post['post_id'] ?>">
                        <div class="post-header">
                            <img src="http://localhost/JusticeVoice/assets/images/users/<?php echo htmlspecialchars($post['user_img']); ?>" alt="" aria-hidden="true">
                            <div>
                                <strong><?php echo htmlspecialchars($post['username']); ?></strong>
                            </div>
                        </div>
                        <p><?php echo htmlspecialchars($post['post_description']); ?></p>
                        <?php if ($post['tweet_img']): ?>
                            <img src="http://localhost/JusticeVoice/assets/images/tweets/<?php echo htmlspecialchars($post['tweet_img']); ?>" alt="Post image" class="post-image">
                        <?php endif; ?>
                        <div class="post-actions">
                            <button class="approve-btn" aria-label="Approve post">Approve</button>
                            <button class="reject-btn" aria-label="Reject post">Reject</button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <section id="approved-section" class="section" aria-labelledby="approved-posts-title">
            <h2 id="approved-posts-title">Approved Posts</h2>
            <div id="approved-posts">
                <?php foreach ($approvedPosts as $post): ?>
                    <article class="post" data-post-id="<?= $post['post_id'] ?>">
                        <div class="post-header">
                            <img src="http://localhost/JusticeVoice/assets/images/users/<?php echo htmlspecialchars($post['user_img']); ?>" alt="" aria-hidden="true">
                            <div>
                                <strong><?php echo htmlspecialchars($post['username']); ?></strong>
                            </div>
                        </div>
                        <p><?php echo htmlspecialchars($post['post_description']); ?></p>
                        <?php if ($post['tweet_img']): ?>
                            <img src="http://localhost/JusticeVoice/assets/images/tweets/<?php echo htmlspecialchars($post['tweet_img']); ?>" alt="Post image" class="post-image">
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <section id="rejected-section" class="section" aria-labelledby="rejected-posts-title">
            <h2 id="rejected-posts-title">Rejected Posts</h2>
            <div id="rejected-posts">
                <?php foreach ($rejectedPosts as $post): ?>
                    <article class="post" data-post-id="<?= $post['post_id'] ?>">
                        <div class="post-header">
                            <img src="http://localhost/JusticeVoice/assets/images/users/<?php echo htmlspecialchars($post['user_img']); ?>" alt="" aria-hidden="true">
                            <div>
                                <strong><?php echo htmlspecialchars($post['username']); ?></strong>
                            </div>
                        </div>
                        <p><?php echo htmlspecialchars($post['post_description']); ?></p>
                        <?php if ($post['tweet_img']): ?>
                            <img src="http://localhost/JusticeVoice/assets/images/tweets/<?php echo htmlspecialchars($post['tweet_img']); ?>" alt="Post image" class="post-image">
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
    <script src="script.js"></script>
</body>
</html>
