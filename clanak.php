<?php
// $title = "";
session_start();
$_SESSION["prevpage"] = "clanak.php";
include_once 'includes/header.php';
include_once 'includes/lowerheader.php';
include "db.php"; // Используем файл db.php для подключение к базе данных

$editing = isset($_SESSION['editing']) && $_SESSION['editing'];
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        .editable { 
            outline: none;
            border: 1px solid transparent;
        }
        .editable:focus {
            border-color: #0000FF;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background-color: #f3f3f3;
            border-radius: 10px;
            position: relative;
            display: none;
        }
        .progress {
            height: 100%;
            width: 0%;
            background-color: #4CAF50;
            border-radius: 10px;
            transition: width 0.3s;
        }
        #content-editor {
            min-height: 300px;
        }
    </style>
</head>
<body>

<div class="content">
	<?php
		$id = isset($_GET['id']) ? intval($_GET['id']) : 0; // Защита от SQL-инъекций
		$row = null; // Инициализация переменной $row

		if ($dbc) {
			$query = "SELECT * FROM vijesti WHERE id = ?";
			$stmt = mysqli_prepare($dbc, $query);
			mysqli_stmt_bind_param($stmt, "i", $id);
			mysqli_stmt_execute($stmt);
			$result = mysqli_stmt_get_result($stmt);

			if ($row = mysqli_fetch_assoc($result)) {
	?>
	<div class="menu">
			<h2 class="editable" contenteditable="<?php echo $editing ? 'true' : 'false'; ?>" data-field="naslov"><?php echo htmlspecialchars($row['naslov']); ?></h2>
			<p class='datetime'>Опубликовано <?php echo htmlspecialchars($row['datum']); ?> в <?php echo htmlspecialchars($row['vrijeme']); ?></p>
	</div>
	<div class="wrapper">
		<article>
			<img class='clanak_image editable-image' src='img/<?php echo htmlspecialchars($row['slika']); ?>' alt='Изображение новости' data-field="slika"/>
			<div class="progress-bar"><div class="progress"></div></div>
			<?php if ($editing): ?>
					<div id="content-editor" contenteditable="true" data-field="sazetak"><?php echo htmlspecialchars_decode($row['sazetak']); ?></div>
			<?php else: ?>
					<div id="content-viewer"><?php echo $row['sazetak']; ?></div>
			<?php endif; ?>
		</article>
	</div>
	<?php
			} else {
				echo "<p>Новость не найдена.</p>";
			}
			mysqli_stmt_close($stmt);
		} else {
			echo "<p>Ошибка подключения к базе данных.</p>";
		}
	?>
</div>
	<?php
		if ($editing) {
			// Сканирование директории для изображений
			$dir = 'img/';
			$images = array_filter(scandir($dir), function($item) {
					return !is_dir($dir . $item) && preg_match('/\.(jpg|jpeg|png|gif)$/i', $item);
			});
	?>
	<div class="image-selector">
		<button class="close-selector">×</button>
		<?php foreach ($images as $image): ?>
			<div class="image-item" data-image="<?php echo htmlspecialchars($image); ?>">
				<img src="img/<?php echo htmlspecialchars($image); ?>" alt="Превью изображения">
				<span><?php echo htmlspecialchars(substr($image, 0, 16) . (strlen($image) > 16 ? '...' : '')); ?></span>
			</div>
		<?php endforeach; ?>
	</div>
	<?php } ?>

<?php if ($editing): ?>
<?php endif; ?>
</body>
<script>
document.addEventListener('DOMContentLoaded', function() {
	// Инициализация CKEditor для контента
	CKEDITOR.replace('content-editor', {
			toolbarGroups: [
					{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
					{ name: 'paragraph',   groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ] },
					{ name: 'links' },
					{ name: 'insert' },
					{ name: 'styles' },
					{ name: 'colors' },
					{ name: 'tools' },
					{ name: 'others' },
					{ name: 'about' }
			],
			extraPlugins: 'uploadimage', // Добавляет загрузку изображений
			uploadUrl: 'upload_image.php', // Путь для загрузки изображений, нужно настроить
			removeButtons: 'Source,Save,NewPage,ExportPdf,Preview,Print,Templates,Cut,Copy,Paste,PasteText,PasteFromWord,Find,Replace,SelectAll,Scayt,Form,Checkbox,Radio,TextField,Textarea,Select,Button,ImageButton,HiddenField,Flash,Smiley,PageBreak,Iframe,BidiLtr,BidiRtl,Language,Anchor,Unlink,CreateDiv,ShowBlocks,About', // Убираем ненужные кнопки
			height: 300
	});

	// Загрузка изображения
	document.querySelector('.editable-image').addEventListener('click', function() {
			let fileInput = document.createElement('input');
			fileInput.type = 'file';
			fileInput.accept = 'image/*';
			fileInput.onchange = function() {
					let file = this.files[0];
					if (file) {
							let formData = new FormData();
							formData.append('image', file);
							formData.append('news_id', <?php echo $id; ?>);

							let progressBar = document.querySelector('.progress-bar');
							let progress = document.querySelector('.progress');
							progressBar.style.display = 'block';

							let xhr = new XMLHttpRequest();
							xhr.upload.addEventListener('progress', function(e) {
									if (e.lengthComputable) {
											let percent = (e.loaded / e.total) * 100;
											progress.style.width = percent + '%';
									}
							});

							xhr.onload = function() {
									if (xhr.status === 200) {
											let response = JSON.parse(xhr.responseText);
											if (response.success) {
													document.querySelector('.clanak_image').src = 'img/' + response.filename;
													toastr.success('Изображение успешно загружено.');
													saveChanges({ 'slika': response.filename });
											} else {
													toastr.error('Ошибка при загрузке изображения.');
											}
									}
									progressBar.style.display = 'none';
							};

							xhr.open('POST', 'upload_image.php', true);
							xhr.send(formData);
					}
			};
			fileInput.click();
	});

	// Функциональность выбора изображения
	const imageSelector = document.querySelector('.image-selector');
	const mainImage = document.querySelector('.clanak_image');
	const closeButton = document.querySelector('.close-selector');

	mainImage.addEventListener('click', function() {
		imageSelector.classList.toggle('active');
	});

	closeButton.addEventListener('click', function() {
		imageSelector.classList.remove('active');
	});

	document.querySelectorAll('.image-item').forEach(item => {
		item.addEventListener('click', function() {
			const imageSrc = 'img/' + this.getAttribute('data-image');
			mainImage.src = imageSrc;
			saveChanges({ 'slika': this.getAttribute('data-image') });
			imageSelector.classList.remove('active');
		});
	});

	// Функция для автосохранения
	function saveChanges(changes) {
			const id = <?php echo $id; ?>;
			fetch('save_news.php', {
					method: 'POST',
					headers: {
							'Content-Type': 'application/json',
					},
					body: JSON.stringify({ id: id, changes: changes })
			}).then(response => response.json())
				.then(data => {
						if (data.success) {
								toastr.success('Изменения сохранены.');
						} else {
								toastr.error('Ошибка при сохранении.');
								console.error(data.error);
						}
				}).catch(error => {
						toastr.error('Ошибка при сохранении.');
						console.error('Error:', error);
				});
	}

	// Автоматическое сохранение для заголовка и контента
	let timeoutId;
	document.querySelector('[data-field="naslov"]').addEventListener('input', function() {
			clearTimeout(timeoutId);
			timeoutId = setTimeout(() => {
					const changes = {
							[this.getAttribute('data-field')]: this.innerText
					};
					saveChanges(changes);
			}, 500);
	});

	CKEDITOR.instances['content-editor'].on('change', function(event) {
			clearTimeout(timeoutId);
			timeoutId = setTimeout(() => {
					const changes = {
							[event.editor.element.getAttribute('data-field')]: event.editor.getData()
					};
					saveChanges(changes);
			}, 500);
	});
});
</script>
</html>
<?php include_once 'includes/footer.php'; ?>