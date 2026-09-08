<?php
include("db.php");

if (!isset($_SESSION["username"])) {
    header("location: /login.php");
    exit();
}

$screenshot_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($screenshot_id <= 0) {
    header("location: /screenshots.php");
    exit();
}

try {
    // Получаем данные скриншота и скин автора из таблицы players
    $stmt = $pdo->prepare("SELECT s.*, p.skin_url as author_skin FROM screenshots s LEFT JOIN authy.players p ON s.username COLLATE utf8mb4_unicode_ci = p.username WHERE s.id = :id");
    $stmt->execute(['id' => $screenshot_id]);
    $screenshot = $stmt->fetch();

    if (!$screenshot) {
        header("location: /screenshots.php");
        exit();
    }

    // Получаем теги
    $stmt = $pdo->prepare("SELECT tag FROM screenshot_tags WHERE screenshot_id = :id");
    $stmt->execute(['id' => $screenshot_id]);
    $tags = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Получаем лайки
    $stmt = $pdo->prepare("SELECT username FROM screenshot_likes WHERE screenshot_id = :id");
    $stmt->execute(['id' => $screenshot_id]);
    $likes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $like_count = count($likes);
    $user_liked = in_array($_SESSION["username"], $likes);

    // Получаем комментарии с скинами из таблицы players
    $stmt = $pdo->prepare("SELECT c.*, p.skin_url, (SELECT username FROM screenshot_comments pc WHERE pc.id = c.parent_id) as parent_username FROM screenshot_comments c LEFT JOIN authy.players p ON c.username COLLATE utf8mb4_unicode_ci = p.username WHERE c.screenshot_id = :id ORDER BY c.created_at ASC");
    $stmt->execute(['id' => $screenshot_id]);
    $comments = $stmt->fetchAll();

    // Обновляем счетчик просмотров (только при первом посещении, не при AJAX)
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
        $stmt = $pdo->prepare("UPDATE screenshots SET views = views + 1 WHERE id = :id");
        $stmt->execute(['id' => $screenshot_id]);
        $views = $screenshot['views'] + 1;
    } else {
        $views = $screenshot['views'];
    }

} catch (PDOException $e) {
    if (isset($_POST['action'])) {
        echo json_encode(['success' => false, 'error' => 'Ошибка базы данных: ' . $e->getMessage()]);
        exit();
    }
    die("Ошибка базы данных: " . $e->getMessage());
}

// Обработка POST-запросов (лайки и комментарии)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false];

    try {
        if ($_POST['action'] === 'comment') {
            $comment_text = trim($_POST['comment_text'] ?? '');
            $parent_id = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

            if (!empty($comment_text)) {
                $stmt = $pdo->prepare("INSERT INTO screenshot_comments (screenshot_id, username, comment_text, parent_id) VALUES (:screenshot_id, :username, :comment_text, :parent_id)");
                $stmt->execute([
                    'screenshot_id' => $screenshot_id,
                    'username' => $_SESSION["username"],
                    'comment_text' => $comment_text,
                    'parent_id' => $parent_id
                ]);

                // Получаем ID нового комментария
                $new_comment_id = $pdo->lastInsertId();

                // Получаем данные нового комментария
                $stmt = $pdo->prepare("SELECT c.*, p.skin_url, (SELECT username FROM screenshot_comments pc WHERE pc.id = c.parent_id) as parent_username FROM screenshot_comments c LEFT JOIN authy.players p ON c.username COLLATE utf8mb4_unicode_ci = p.username WHERE c.id = :id");
                $stmt->execute(['id' => $new_comment_id]);
                $new_comment = $stmt->fetch();

                $response = [
                    'success' => true,
                    'comment' => [
                        'id' => $new_comment['id'],
                        'username' => $new_comment['username'],
                        'comment_text' => htmlspecialchars($new_comment['comment_text']),
                        'created_at' => date('d.m.Y H:i', strtotime($new_comment['created_at'])),
                        'parent_id' => $new_comment['parent_id'],
                        'parent_username' => $new_comment['parent_username']
                    ]
                ];
            } else {
                $response['error'] = 'Комментарий не может быть пустым';
            }
        } elseif ($_POST['action'] === 'like') {
            if ($user_liked) {
                $stmt = $pdo->prepare("DELETE FROM screenshot_likes WHERE screenshot_id = :screenshot_id AND username = :username");
                $stmt->execute(['screenshot_id' => $screenshot_id, 'username' => $_SESSION["username"]]);
                $like_count--;
                $user_liked = false;
            } else {
                $stmt = $pdo->prepare("INSERT INTO screenshot_likes (screenshot_id, username) VALUES (:screenshot_id, :username)");
                $stmt->execute(['screenshot_id' => $screenshot_id, 'username' => $_SESSION["username"]]);
                $like_count++;
                $user_liked = true;
            }

            $response = [
                'success' => true,
                'liked' => $user_liked,
                'like_count' => $like_count
            ];
        } else {
            $response['error'] = 'Неверное действие';
        }
    } catch (PDOException $e) {
        $response['error'] = 'Ошибка базы данных: ' . $e->getMessage();
    }

    echo json_encode($response);
    exit();
}

// Получаем информацию о файле
$file_url = '/uploads/screenshots/' . htmlspecialchars($screenshot["filename"]);
$file_extension = strtolower(pathinfo($file_url, PATHINFO_EXTENSION));
$is_image = in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
$is_video = $file_extension === 'mp4';

$file_size = filesize($_SERVER['DOCUMENT_ROOT'] . $file_url) / (1024 * 1024); // Размер в МБ

// Для изображений получаем размеры
if ($is_image) {
    $image_size = getimagesize($_SERVER['DOCUMENT_ROOT'] . $file_url);
    $width = $image_size[0];
    $height = $image_size[1];
} elseif ($is_video) {
    $width = 'N/A';
    $height = 'N/A';
}

$title = htmlspecialchars($screenshot['title']);
include_once 'includes/header.php';
include_once 'includes/lowerheader.php';
?>

<div class="content">
    <div class="screenshot-post">
        <div class="screenshot-media">
            <?php if ($is_image): ?>
                <img src="<?= $file_url ?>" alt="<?= htmlspecialchars($screenshot['title']) ?>">
            <?php elseif ($is_video): ?>
                <video controls>
                    <source src="<?= $file_url ?>" type="video/mp4">
                    Ваш браузер не поддерживает видео.
                </video>
            <?php endif; ?>
        </div>

        <div class="screenshot-info">
            <div class="screenshot-header">
                <h1><?= htmlspecialchars($screenshot['title']) ?></h1>
                <div class="author-block">
                    <div class="author-skin">
                        <img src="https://minotar.net/avatar/<?= htmlspecialchars($screenshot['username']) ?>/40" alt="<?= htmlspecialchars($screenshot['username']) ?>">
                    </div>
                    <div class="author-details">
                        <a href="/user/<?= urlencode($screenshot['username']) ?>" class="author-username"><?= htmlspecialchars($screenshot['username']) ?></a>
                        <span class="post-date">Опубликовано: <?= date('d.m.Y', strtotime($screenshot['created_at'])) ?></span>
                    </div>
                </div>
            </div>

            <div class="screenshot-actions">
                <button class="action-btn like-btn <?= $user_liked ? 'liked' : '' ?>" data-screenshot-id="<?= $screenshot_id ?>">
                    <span class="icon-heart"></span> Лайк (<span class="like-count"><?= $like_count ?></span>)
                </button>
                <a href="<?= $file_url ?>" download class="action-btn download-btn">
                    <span class="icon-download"></span> Скачать
                </a>
            </div>

            <div class="screenshot-details">
                <h3>Описание</h3>
                <p><?= nl2br(htmlspecialchars($screenshot['description'] ?? 'Описание отсутствует')) ?></p>
            </div>

            <div class="screenshot-tags">
                <h3>Теги</h3>
                <?php if (!empty($tags)): ?>
                    <?php foreach ($tags as $tag): ?>
                        <a href="/screenshots.php?tag=<?= urlencode($tag) ?>" class="tag"><?= htmlspecialchars($tag) ?></a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Теги отсутствуют</p>
                <?php endif; ?>
            </div>

            <div class="screenshot-stats">
                <h3>Информация</h3>
                <ul>
                    <li><span>Просмотры:</span> <?= number_format($views) ?></li>
                    <li><span>Размер:</span> <?= number_format($file_size, 2) ?> МБ</li>
                    <?php if ($is_image): ?>
                        <li><span>Разрешение:</span> <?= $width ?> × <?= $height ?> px</li>
                    <?php endif; ?>
                    <li><span>Дата загрузки:</span> <?= date('d.m.Y', strtotime($screenshot['created_at'])) ?></li>
                </ul>
            </div>
        </div>

        <div class="comments-block">
            <h2>Комментарии (<span class="comment-count"><?= count($comments) ?></span>)</h2>
            <div class="comment-form">
                <div class="replying-to" style="display: none;">
                    Отвечаете на комментарий от <span class="reply-username"></span>
                    <button type="button" class="cancel-reply">✖</button>
                    <input type="hidden" name="parent_id" value="">
                </div>
                <ViteaLity('https://cdn.jsdelivr.net/npm/vite@latest/dist/vite.min.js');</script>
                <div class="comment-input-wrapper">
                    <textarea name="comment_text" placeholder="Напишите ваш комментарий..." required></textarea>
                    <button type="button" class="comment-submit" data-screenshot-id="<?= $screenshot_id ?>">Отправить</button>
                </div>
            </div>

            <?php if (empty($comments)): ?>
                <p class="no-comments">Комментариев пока нет. Будьте первым!</p>
            <?php else: ?>
                <div class="comments-list">
                    <?php
                    // Строим дерево комментариев
                    $comment_tree = [];
                    foreach ($comments as $comment) {
                        $comment_tree[$comment['parent_id'] ?? 0][] = $comment;
                    }

										function display_comments($comments, $level = 0, $comment_tree) {
												if (!isset($comments)) return;
												foreach ($comments as $comment) {
														$has_replies = isset($comment_tree[$comment['id']]) && !empty($comment_tree[$comment['id']]);
														?>
														<div class="comment-item" data-comment-id="<?= $comment['id'] ?>" style="margin-left: <?= $level * 20 ?>px;">
																<div class="comment-header">
																		<div class="comment-author-info">
																				<img src="https://minotar.net/avatar/<?= htmlspecialchars($comment['username']) ?>/32" alt="<?= htmlspecialchars($comment['username']) ?>" class="comment-skin">
																				<a href="/user/<?= urlencode($comment['username']) ?>" class="comment-username"><?= htmlspecialchars($comment['username']) ?></a>
																		</div>
																		<span class="comment-date"><?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?></span>
																</div>
																<?php if ($comment['parent_id']): ?>
																		<div class="reply-info">
																				В ответ на <a href="/user/<?= urlencode($comment['parent_username']) ?>" class="reply-username"><?= htmlspecialchars($comment['parent_username']) ?></a>
																		</div>
																<?php endif; ?>
																<div class="comment-body">
																		<p><?= nl2br(htmlspecialchars($comment['comment_text'])) ?></p>
																</div>
																<div class="comment-footer">
																		<button type="button" class="reply-btn">Ответить</button>
																		<?php if ($has_replies): ?>
																				<button type="button" class="toggle-replies-btn" data-comment-id="<?= $comment['id'] ?>">
																						Показать ответы (<span class="replies-count"><?= count($comment_tree[$comment['id']]) ?></span>)
																				</button>
																		<?php endif; ?>
																</div>
																<?php if ($has_replies): ?>
																		<div class="comment-replies" data-replies-for="<?= $comment['id'] ?>" style="display: none;">
																				<?php display_comments($comment_tree[$comment['id']], $level + 1, $comment_tree); ?>
																		</div>
																<?php endif; ?>
														</div>
														<?php
												}
										}

										// Отображаем корневые комментарии
										display_comments($comment_tree[0], 0, $comment_tree);
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Обработка лайков
    document.querySelectorAll('.like-btn').forEach(button => {
        button.addEventListener('click', function() {
            const screenshotId = this.getAttribute('data-screenshot-id');
            const formData = new FormData();
            formData.append('action', 'like');

            fetch('/screenshot_post.php?id=<?= $screenshot_id ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.classList.toggle('liked', data.liked);
                    this.querySelector('.like-count').textContent = data.like_count;
                } else {
                    alert('Ошибка: ' + (data.error || 'Не удалось поставить лайк'));
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                alert('Произошла ошибка при обработке лайка');
            });
        });
    });

    // Обработка комментариев
    const commentForm = document.querySelector('.comment-form');
    const commentInput = commentForm.querySelector('textarea');
    const commentSubmit = commentForm.querySelector('.comment-submit');
    const commentsList = document.querySelector('.comments-list');
    const commentCount = document.querySelector('.comment-count');
    const replyingTo = commentForm.querySelector('.replying-to');
    const replyUsername = replyingTo.querySelector('.reply-username');
    const cancelReply = replyingTo.querySelector('.cancel-reply');
    const parentIdInput = replyingTo.querySelector('input[name="parent_id"]');

		// Обработка кнопок "Показать/Скрыть ответы"
		document.querySelectorAll('.toggle-replies-btn').forEach(button => {
				button.addEventListener('click', function() {
						const commentId = this.getAttribute('data-comment-id');
						const repliesContainer = document.querySelector(`.comment-replies[data-replies-for="${commentId}"]`);
						const isHidden = repliesContainer.style.display === 'none';

						repliesContainer.style.display = isHidden ? 'block' : 'none';
						this.textContent = isHidden 
								? `Скрыть ответы (${this.querySelector('.replies-count').textContent})`
								: `Показать ответы (${this.querySelector('.replies-count').textContent})`;
				});
		});

		// Обновление комментариев после добавления нового
		commentSubmit.addEventListener('click', () => {
				const commentText = commentInput.value.trim();
				if (!commentText) return;

				const formData = new FormData();
				formData.append('action', 'comment');
				formData.append('comment_text', commentText);
				if (parentIdInput.value) {
						formData.append('parent_id', parentIdInput.value);
				}

				fetch('/screenshot_post.php?id=<?= $screenshot_id ?>', {
						method: 'POST',
						body: formData
				})
				.then(response => response.json())
				.then(data => {
						if (data.success) {
								const comment = data.comment;
								const commentItem = document.createElement('div');
								commentItem.classList.add('comment-item');
								commentItem.setAttribute('data-comment-id', comment.id);
								commentItem.style.marginLeft = (comment.parent_id ? 20 : 0) + 'px';
								commentItem.innerHTML = `
										<div class="comment-header">
												<div class="comment-author-info">
														<img src="https://minotar.net/avatar/${comment.username}/32" alt="${comment.username}" class="comment-skin">
														<a href="/user/${comment.username}" class="comment-username">${comment.username}</a>
												</div>
												<span class="comment-date">${comment.created_at}</span>
										</div>
										${comment.parent_id ? `
												<div class="reply-info">
														В ответ на <a href="/user/${comment.parent_username}" class="reply-username">${comment.parent_username}</a>
												</div>
										` : ''}
										<div class="comment-body">
												<p>${comment.comment_text}</p>
										</div>
										<div class="comment-footer">
												<button type="button" class="reply-btn">Ответить</button>
										</div>
								`;

								if (comment.parent_id) {
										let parentReplies = document.querySelector(`.comment-replies[data-replies-for="${comment.parent_id}"]`);
										if (!parentReplies) {
												// Создаем контейнер для ответов, если его нет
												const parentComment = document.querySelector(`.comment-item[data-comment-id="${comment.parent_id}"]`);
												parentReplies = document.createElement('div');
												parentReplies.classList.add('comment-replies');
												parentReplies.setAttribute('data-replies-for', comment.parent_id);
												parentReplies.style.display = 'block';
												parentComment.appendChild(parentReplies);

												// Добавляем кнопку "Показать ответы", если её нет
												const parentFooter = parentComment.querySelector('.comment-footer');
												const toggleButton = document.createElement('button');
												toggleButton.classList.add('toggle-replies-btn');
												toggleButton.setAttribute('data-comment-id', comment.parent_id);
												toggleButton.innerHTML = `Скрыть ответы (<span class="replies-count">1</span>)`;
												parentFooter.appendChild(toggleButton);

												toggleButton.addEventListener('click', function() {
														const repliesContainer = document.querySelector(`.comment-replies[data-replies-for="${comment.parent_id}"]`);
														const isHidden = repliesContainer.style.display === 'none';
														repliesContainer.style.display = isHidden ? 'block' : 'none';
														this.textContent = isHidden 
																? `Скрыть ответы (${this.querySelector('.replies-count').textContent})`
																: `Показать ответы (${this.querySelector('.replies-count').textContent})`;
												});
										} else {
												parentReplies.style.display = 'block';
												const toggleButton = document.querySelector(`.toggle-replies-btn[data-comment-id="${comment.parent_id}"]`);
												if (toggleButton) {
														const count = parseInt(toggleButton.querySelector('.replies-count').textContent) + 1;
														toggleButton.querySelector('.replies-count').textContent = count;
														toggleButton.textContent = `Скрыть ответы (${count})`;
												}
										}
										parentReplies.appendChild(commentItem);
								} else {
										commentsList.appendChild(commentItem);
								}

								commentInput.value = '';
								commentCount.textContent = parseInt(commentCount.textContent) + 1;
								replyingTo.style.display = 'none';
								parentIdInput.value = '';

								// Привязываем обработчик для новой кнопки "Ответить"
								commentItem.querySelector('.reply-btn').addEventListener('click', function() {
										const commentId = commentItem.getAttribute('data-comment-id');
										const username = commentItem.querySelector('.comment-username').textContent;
										replyingTo.style.display = 'flex';
										replyUsername.textContent = username;
										parentIdInput.value = commentId;
								});
						} else {
								alert('Ошибка: ' + (data.error || 'Не удалось добавить комментарий'));
						}
				})
				.catch(error => {
						console.error('Ошибка:', error);
						alert('Произошла ошибка при добавлении комментария');
				});
		});

    // Обработка кнопок "Ответить"
    document.querySelectorAll('.reply-btn').forEach(button => {
        button.addEventListener('click', function() {
            const commentItem = this.closest('.comment-item');
            const commentId = commentItem.getAttribute('data-comment-id');
            const username = commentItem.querySelector('.comment-username').textContent;
            replyingTo.style.display = 'flex';
            replyUsername.textContent = username;
            parentIdInput.value = commentId;
        });
    });

    // Отмена ответа
    cancelReply.addEventListener('click', () => {
        replyingTo.style.display = 'none';
        parentIdInput.value = '';
    });

    // Обновление видимости футера (если есть такая функция в вашем SPA)
    if (typeof updateFooterVisibility === 'function') {
        updateFooterVisibility();
    }
});
</script>

<style>
/* Стили для основного контента скриншота */
.screenshot-post {
    padding: 20px 0;
    font-family: 'Comic Sans MS', sans-serif;
}

.screenshot-media {
    background: #1c1c1c;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
}

.screenshot-media img,
.screenshot-media video {
    width: 100%;
    max-height: 600px;
    object-fit: contain;
    display: block;
}

.screenshot-info {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.screenshot-header {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.screenshot-header h1 {
    font-size: 24px;
    color: #e0e0e0;
    margin: 0;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
}

.author-block {
    display: flex;
    align-items: center;
    gap: 10px;
}

.author-skin {
    width: 40px;
    height: 40px;
    border-radius: 4px;
    overflow: hidden;
    border: 1px solid #333;
}

.author-skin img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.author-details {
    display: flex;
    flex-direction: column;
}

.author-username {
    color: #ba68c8;
    text-decoration: none;
    font-weight: bold;
    font-size: 16px;
}

.author-username:hover {
    text-decoration: underline;
}

.post-date {
    color: #888;
    font-size: 14px;
}

.screenshot-actions {
    display: flex;
    gap: 10px;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px 12px;
    background: #404040;
    color: #e0e0e0;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.3s ease;
}

.action-btn:hover {
    background: #505050;
}

.like-btn.liked {
    background: #ba68c8;
    color: #fff;
}

.like-btn.liked .icon-heart {
    color: #fff;
}

.icon-heart::before {
    content: '♥';
}

.icon-download::before {
    content: '↓';
}

.screenshot-details,
.screenshot-tags,
.screenshot-stats {
    background: #1c1c1c;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
}

.screenshot-details h3,
.screenshot-tags h3,
.screenshot-stats h3 {
    font-size: 18px;
    color: #e0e0e0;
    margin: 0 0 10px 0;
    border-bottom: 1px solid #333;
    padding-bottom: 5px;
}

.screenshot-details p {
    color: #ccc;
    font-size: 14px;
    margin: 0;
}

.screenshot-tags .tag {
    display: inline-block;
    background: #404040;
    color: #e0e0e0;
    padding: 4px 8px;
    border-radius: 6px;
    margin: 0 5px 5px 0;
    text-decoration: none;
    font-size: 13px;
    transition: background 0.3s ease;
}

.screenshot-tags .tag:hover {
    background: #505050;
}

.screenshot-stats ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.screenshot-stats li {
    display: flex;
    justify-content: space-between;
    color: #ccc;
    font-size: 14px;
    margin-bottom: 5px;
}

.screenshot-stats span {
    color: #888;
}

/* Стили для секции комментариев */
.comments-block {
    margin-top: 30px;
    padding: 20px;
    background: #1c1c1c;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
}

/* Улучшение стилей для комментариев */
.comment-replies {
    margin-top: 10px;
    transition: all 0.3s ease;
}

.toggle-replies-btn {
    background: none;
    border: none;
    color: #ba68c8;
    font-size: 13px;
    cursor: pointer;
    padding: 0;
    margin-left: 10px;
    transition: color 0.3s ease;
}

.toggle-replies-btn:hover {
    color: #cb80d5;
}

.toggle-replies-btn .replies-count {
    font-weight: bold;
}

.comments-block h2 {
    font-size: 20px;
    color: #e0e0e0;
    margin: 0 0 20px 0;
    border-bottom: 1px solid #333;
    padding-bottom: 5px;
}

.comment-form {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 20px;
}

.replying-to {
    background: #222222;
    padding: 8px 12px;
    border-radius: 6px 6px 0 0;
    border-bottom: 1px solid #333;
    color: #ccc;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.reply-username {
    color: #ba68c8;
    font-weight: bold;
}

.cancel-reply {
    background: none;
    border: none;
    color: #888;
    cursor: pointer;
    font-size: 12px;
    padding: 0;
    margin-left: auto;
}

.cancel-reply:hover {
    color: #ba68c8;
}

.comment-input-wrapper {
    display: flex;
    flex-direction: row;
    width: 100%;
}

.comment-input-wrapper textarea {
    width: 80%;
    min-height: 50px;
    padding: 10px;
    background: #404040;
    border: 1px solid #333;
    border-right: none;
    border-radius: 6px 0 0 6px;
    color: #e0e0e0;
    font-family: 'Comic Sans MS', sans-serif;
    font-size: 14px;
    resize: vertical;
}

.comment-input-wrapper textarea:focus {
    outline: none;
    border-color: #ba68c8;
}

.comment-input-wrapper .comment-submit {
    width: 20%;
    padding: 8px;
    background: #ba68c8;
    color: #fff;
    border: none;
    border-radius: 0 6px 6px 0;
    cursor: pointer;
    transition: background 0.3s ease;
    font-family: 'Comic Sans MS', sans-serif;
    font-size: 14px;
}

.comment-input-wrapper .comment-submit:hover {
    background: #cb80d5;
}

.no-comments {
    color: #888;
    font-style: italic;
    text-align: center;
    padding: 20px 0;
}

.comments-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.comment-item {
    background: #222222;
    padding: 15px;
    border-radius: 6px;
    border: 1px solid #333;
}

.comment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.comment-author-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.comment-skin {
    width: 32px;
    height: 32px;
    border-radius: 4px;
    object-fit: cover;
    border: 1px solid #333;
}

.comment-username {
    color: #ba68c8;
    text-decoration: none;
    font-weight: bold;
    font-size: 15px;
}

.comment-username:hover {
    text-decoration: underline;
}

.comment-date {
    color: #888;
    font-size: 13px;
}

.reply-info {
    background: #1a1a1a;
    padding: 5px 10px;
    border-radius: 4px;
    margin-bottom: 10px;
    font-size: 13px;
    color: #ccc;
}

.reply-info .reply-username {
    color: #ba68c8;
    text-decoration: none;
}

.reply-info .reply-username:hover {
    text-decoration: underline;
}

.comment-body {
    margin-bottom: 10px;
}

.comment-body p {
    color: #ccc;
    font-size: 14px;
    margin: 0;
    line-height: 1.5;
}

.comment-footer {
    display: flex;
    gap: 10px;
}

.reply-btn {
    background: none;
    border: none;
    color: #888;
    font-size: 13px;
    cursor: pointer;
    padding: 0;
    transition: color 0.3s ease;
}

.reply-btn:hover {
    color: #ba68c8;
}
</style>