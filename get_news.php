<?php
include("db.php");

$pdo = $GLOBALS['pdo'];
$stmt = $pdo->query("SELECT * FROM frontpage ORDER BY created DESC");

while ($info = $stmt->fetch()) { 
    if ($info['onfront'] == 1) {
        echo "<div class='newsItem' data-id='" . htmlspecialchars($info['id']) . "'>";
        echo "<h3 style='color:" . htmlspecialchars($info['color']) . ";'>" . htmlspecialchars($info['title']) . "</h3>";
        if ($info['image']) {
            echo "<img class='brodcast' src='" . htmlspecialchars($info['image']) . "' alt='Image' />";
        }
        echo "<div class='textdescription'>" . html_entity_decode($info['content']) . "</div>";
        echo "<hr class='separator2' />";
        echo "</div>";
    }
}
?>
