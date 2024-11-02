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

    // Fetch all fields from rejected_posts with updated column names
    $stmt = $conn->prepare("SELECT id AS post_id, user_id, status AS post_description, img AS tweet_img, created_at, post_on, updated_at FROM rejected_posts");
    $stmt->execute();

    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log the posts before encoding for debugging purposes
    error_log(print_r($posts, true)); // Log the output to check the structure

    // Output JSON
    header('Content-Type: application/json'); // Set the content type to JSON
    echo json_encode($posts);

} catch(PDOException $e) {
    // Return an error message if an exception occurs
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} finally {
    // Close the connection (optional, as PHP will do this automatically at the end)
    $conn = null;
}
?>
