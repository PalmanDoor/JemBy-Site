<?php
$title = "Скриншоты";
$_SESSION["prevpage"] = "screenshots.php";

include("db.php");

$pdo_authy->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!isset($_SESSION["username"])) {
    header("location: /login.php");
    exit();
}

try {
    $stmt = $pdo_authy->prepare("SELECT level, demo FROM players WHERE username = :login");
    $stmt->execute(['login' => $_SESSION["username"]]);
    $user = $stmt->fetch();
    $user_level = $user['level'];
    $is_demo_user = $user['demo'] == 1;
} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$is_demo_user) {
    $target_dir = "uploads/screenshots/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $target_file = $target_dir . basename($_FILES["screenshot"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $uploadOk = 1;

    if ($_FILES["screenshot"]["size"] > 10485760) {
        $message = "Файл слишком большой. Максимум 10 МБ.";
        $message_type = 'error';
        $uploadOk = 0;
    }

    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4'])) {
        $message = "Разрешены только JPG, JPEG, PNG, GIF, WEBP и MP4.";
        $message_type = 'error';
        $uploadOk = 0;
    }

    if ($uploadOk && move_uploaded_file($_FILES["screenshot"]["tmp_name"], $target_file)) {
        $score = calculate_halflife_score($target_file);
        $title = $_POST['title'] ?? 'Без названия';
        $description = $_POST['description'] ?? '';
        $tags = isset($_POST['tags']) ? explode(',', $_POST['tags']) : [];

        try {
            $stmt = $pdo->prepare("INSERT INTO screenshots (filename, username, title, description, score) VALUES (:filename, :username, :title, :description, :score)");
            $stmt->execute([
                'filename' => basename($_FILES["screenshot"]["name"]),
                'username' => $_SESSION["username"],
                'title' => $title,
                'description' => $description,
                'score' => $score
            ]);
            $screenshot_id = $pdo->lastInsertId();

            foreach ($tags as $tag) {
                $tag = trim($tag);
                if (!empty($tag)) {
                    $stmt = $pdo->prepare("INSERT INTO screenshot_tags (screenshot_id, tag) VALUES (:screenshot_id, :tag)");
                    $stmt->execute(['screenshot_id' => $screenshot_id, 'tag' => $tag]);
                }
            }

            $message = "Скриншот загружен.";
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = "Ошибка базы данных: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}

$screenshots_per_page = 24;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $screenshots_per_page;

try {
    // Основной запрос для получения скриншотов
    $stmt = $pdo->prepare("SELECT id, filename, username, title, created_at, views FROM screenshots ORDER BY created_at DESC LIMIT :offset, :limit");
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $screenshots_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $screenshots = $stmt->fetchAll();

    // Получаем количество лайков и комментариев для каждого скриншота
    $screenshot_ids = array_column($screenshots, 'id');
    if (!empty($screenshot_ids)) {
        // Лайки
        $stmt = $pdo->prepare("SELECT screenshot_id, COUNT(*) as like_count FROM screenshot_likes WHERE screenshot_id IN (" . implode(',', array_fill(0, count($screenshot_ids), '?')) . ") GROUP BY screenshot_id");
        $stmt->execute($screenshot_ids);
        $likes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Комментарии
        $stmt = $pdo->prepare("SELECT screenshot_id, COUNT(*) as comment_count FROM screenshot_comments WHERE screenshot_id IN (" . implode(',', array_fill(0, count($screenshot_ids), '?')) . ") GROUP BY screenshot_id");
        $stmt->execute($screenshot_ids);
        $comments = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } else {
        $likes = [];
        $comments = [];
    }

    $total_screenshots = $pdo->query("SELECT COUNT(*) FROM screenshots")->fetchColumn();
    $total_pages = ceil($total_screenshots / $screenshots_per_page);
} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
}

include_once 'includes/header.php';
include_once 'includes/lowerheader.php';
?>

<div class="content">
    <section class="upload-section">
        <?php if ($is_demo_user): ?>
            <div class="demo-notice">Demo-аккаунты не могут загружать скриншоты.</div>
        <?php else: ?>
            <div class="upload-area" ondragover="event.preventDefault()" ondrop="handleDrop(event)">
                <div class="upload-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                </div>
                <p>Перетащите изображение сюда или <span class="upload-link" onclick="openUploadModal()">выберите файл</span></p>
                <p class="upload-hint">Поддерживаются JPG, PNG, GIF, WEBP, MP4 (до 10MB)</p>
            </div>
            <div class="modal" id="uploadModal" style="display: none;">
                <div class="modal-content">
                    <span class="close-btn" onclick="closeUploadModal()">×</span>
                    <h2>Загрузить скриншот</h2>
                    <form action="/screenshots.php" method="post" enctype="multipart/form-data">
                        <div class="file-upload-wrapper">
                            <input type="file" name="screenshot" id="screenshotInput" accept=".jpg,.jpeg,.png,.gif,.webp,.mp4" required>
                            <label for="screenshotInput" class="file-upload-label">
                                <span class="file-upload-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                </span>
                                <span class="file-upload-text">Выбрать файл</span>
                            </label>
                            <div id="fileNameDisplay"></div>
                        </div>
                        <div class="form-group">
                            <label for="title">Заголовок</label>
                            <input type="text" name="title" id="title" placeholder="Введите заголовок" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Описание</label>
                            <textarea name="description" id="description" placeholder="Добавьте описание (необязательно)"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="tags">Теги</label>
                            <input type="text" name="tags" id="tags" placeholder="тег1, тег2, тег3">
                            <small>Разделяйте теги запятыми</small>
                        </div>
                        <button type="submit" class="submit-btn">Опубликовать</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </section>

		<div class="screenshot-gallery">
				<?php 
				$row = 0; // Счетчик для строк
				$items_per_row = 5; // Количество элементов в строке (как на картинке)
				$item_count = 0; // Счетчик элементов

				foreach ($screenshots as $screenshot): 
						if ($item_count % $items_per_row === 0): // Начало новой строки
								if ($item_count > 0) echo '</div>'; // Закрываем предыдущую строку
								echo '<div class="gallery-row">';
						endif;

						$file_url = '/uploads/screenshots/' . htmlspecialchars($screenshot["filename"]);
						$file_extension = strtolower(pathinfo($file_url, PATHINFO_EXTENSION));
						$is_image = in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
						$is_video = $file_extension === 'mp4';
						$like_count = $likes[$screenshot['id']] ?? 0;
						$comment_count = $comments[$screenshot['id']] ?? 0;
				?>
						<div class="gallery-item">
								<a href="/screenshot_post.php?id=<?= $screenshot['id'] ?>" class="gallery-item-link">
										<?php if ($is_image): ?>
												<img src="<?= $file_url ?>" alt="<?= htmlspecialchars($screenshot['title']) ?>" loading="lazy">
										<?php elseif ($is_video): ?>
												<video src="<?= $file_url ?>" muted></video>
										<?php endif; ?>
										<div class="gallery-item-overlay">
												<div class="gallery-item-meta">
														<div class="gallery-item-title"><?= htmlspecialchars($screenshot['title']) ?></div>
														<div class="gallery-item-stats">
																<span class="stat">
																		<svg class="stat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
																				<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
																		</svg>
																		<?= $like_count ?>
																</span>
																<span class="stat">
																		<svg class="stat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
																				<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
																		</svg>
																		<?= $comment_count ?>
																</span>
														</div>
												</div>
												<div class="gallery-item-author">
														<span><?= htmlspecialchars($screenshot['username']) ?></span>
												</div>
										</div>
								</a>
						</div>
				<?php 
						$item_count++;
						if ($item_count % $items_per_row === 0 || $item_count === count($screenshots)) {
								echo '</div>'; // Закрываем строку
						}
				endforeach; 
				?>
		</div>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="/screenshots.php?page=<?= $page - 1 ?>" class="pagination-arrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="/screenshots.php?page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="/screenshots.php?page=<?= $page + 1 ?>" class="pagination-arrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($message)): ?>
    <script>
        Swal.fire({
            icon: '<?= $message_type ?>',
            title: '<?= $message_type === 'success' ? 'Успех' : 'Ошибка' ?>',
            text: '<?= htmlspecialchars($message) ?>',
            timer: 5000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    </script>
<?php endif; ?>
<?php
function calculate_halflife_score($file_path) {
    $image = @getimagesize($file_path);
    if ($image) {
        $width = $image[0];
        $height = $image[1];
        $score = ($width * $height) / (1920 * 1080) * 100;
        return max(0, min(250, round($score)));
    }
    return 0;
}
?>

<?php include_once 'includes/footer.php'; ?>