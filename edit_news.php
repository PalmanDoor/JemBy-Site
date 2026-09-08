<?php
include "db.php";

if (!isset($_SESSION['username']) || $_SESSION['level'] < 5) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];
$news = getNewsById($id);

if (!$news) {
    header("Location: index.php");
    exit();
}

$title = "Редактирование новости";
include_once 'includes/header.php';
?>

<div class="content">
    <h1>Редактирование новости</h1>
    <form id="edit-news-form">
        <input type="hidden" name="id" value="<?= $news['id'] ?>">
        <label for="title">Заголовок:</label>
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($news['title']) ?>" required>
        <label for="content">Содержание:</label>
        <textarea id="content" name="content" required><?= htmlspecialchars($news['content']) ?></textarea>
        <button type="submit">Сохранить</button>
    </form>
</div>

<script>
document.getElementById('edit-news-form').addEventListener('submit', function(event) {
    event.preventDefault();
    const formData = new FormData(this);
    fetch('update_news.php', {
        method: 'POST',
        body: formData
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              toastr.success('Новость успешно обновлена.');
              setTimeout(() => location.href = 'index.php', 2000);
          } else {
              toastr.error('Ошибка при обновлении новости.');
              console.error(data.errors);
          }
      }).catch(error => {
          toastr.error('Ошибка при обновлении новости.');
          console.error('Error:', error);
      });
});
</script>

<?php 
include_once 'includes/footer.php';
?>