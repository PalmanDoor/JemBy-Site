<?php
$title = "Наши приложения";
$_SESSION["prevpage"] = "my_app.php";
include_once 'includes/header.php';
include_once 'includes/lowerheader.php';
include "db.php";

$editing = false;
$editing = isset($_SESSION['editing']) && $_SESSION['editing'];
$apps_dir = "my_app";

$apps = [];
if (is_dir($apps_dir)) {
    foreach (scandir($apps_dir) as $folder) {
        if ($folder !== "." && $folder !== ".." && is_dir("$apps_dir/$folder")) {
            $info_file = "$apps_dir/$folder/info.json";
            $zip_file = "$apps_dir/$folder/app.zip";
            $app_info = file_exists($info_file) ? json_decode(file_get_contents($info_file), true) : [];
            $app_info['folder'] = $folder;
            $app_info['has_zip'] = file_exists($zip_file);
            $apps[] = $app_info;
        }
    }
}
?>

<div class="content">

	<div class="menu">
		<h2>Наши приложения</h2>
		<?php if ($editing): ?>
			<button id="add-app">Добавить приложение</button>
		<?php endif; ?>
	</div>

    <div class="app-list">
        <?php foreach ($apps as $app): ?>
            <div class="app">
                <h3 contenteditable="<?= $editing ? 'true' : 'false' ?>" class="editable" data-field="name" data-folder="<?= $app['folder'] ?>">
                    <?= htmlspecialchars($app['name'] ?? 'Без названия') ?>
                </h3>
                <p contenteditable="<?= $editing ? 'true' : 'false' ?>" class="editable" data-field="description" data-folder="<?= $app['folder'] ?>">
                    <?= htmlspecialchars($app['description'] ?? 'Нет описания') ?>
                </p>
                <p>Версия: 
                    <span contenteditable="<?= $editing ? 'true' : 'false' ?>" class="editable" data-field="version" data-folder="<?= $app['folder'] ?>">
                        <?= htmlspecialchars($app['version'] ?? '1.0') ?>
                    </span>
                </p>

                <?php if ($app['has_zip']): ?>
                    <?php if (isset($_SESSION["username"])): ?>
                        <a href="<?= $apps_dir ?>/<?= $app['folder'] ?>/app.zip" class="download-btn">Скачать</a>
                    <?php else: ?>
                        <a href="https://project-echo.ru/auth" class="login-required">Войти</a>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="missing-zip">Файл <b>app.zip</b> отсутствует</p>
                <?php endif; ?>

                <?php if ($editing): ?>
                    <form class="upload-form" data-folder="<?= $app['folder'] ?>" enctype="multipart/form-data">
                        <input type="file" name="zipfile" accept=".zip">
                        <button type="submit">Загрузить ZIP</button>
                    </form>
                    <button class="delete-app" data-folder="<?= $app['folder'] ?>">Удалить</button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    <?php if ($editing): ?>
    document.getElementById('add-app').addEventListener('click', function () {
        let appName = prompt("Введите название приложения:");
        if (appName) {
            fetch('manage_apps.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'add', name: appName })
            }).then(() => location.reload());
        }
    });

    document.querySelectorAll('.delete-app').forEach(button => {
        button.addEventListener('click', function () {
            if (confirm("Удалить это приложение?")) {
                fetch('manage_apps.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', folder: this.dataset.folder })
                }).then(() => location.reload());
            }
        });
    });

    document.querySelectorAll('.editable').forEach(field => {
        field.addEventListener('blur', function () {
            fetch('manage_apps.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'edit',
                    folder: this.dataset.folder,
                    field: this.dataset.field,
                    value: this.innerText.trim()
                })
            });
        });
    });

    document.querySelectorAll('.upload-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            let formData = new FormData();
            let fileInput = this.querySelector('input[type="file"]');
            if (fileInput.files.length > 0) {
                formData.append("zipfile", fileInput.files[0]);
                formData.append("folder", this.dataset.folder);
                fetch('upload_app.php', {
                    method: 'POST',
                    body: formData
                }).then(() => location.reload());
            }
        });
    });
	
	document.querySelectorAll('.upload-form').forEach(form => {
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			let formData = new FormData();
			let fileInput = this.querySelector('input[type="file"]');
			let statusMessage = document.createElement("p");
			statusMessage.style.color = "red";

			if (fileInput.files.length > 0) {
				let file = fileInput.files[0];

				if (file.size > 100 * 1024 * 1024) { // 100 MB
					statusMessage.textContent = "Файл слишком большой (более 100 МБ). Загрузите вручную или обратитесь к администратору.";
					this.appendChild(statusMessage);
					return;
				}

				formData.append("zipfile", file);
				formData.append("folder", this.dataset.folder);

				fetch('upload_app.php', {
					method: 'POST',
					body: formData
				})
				.then(response => response.text())
				.then(text => {
					if (text.includes("Файл превышает допустимый размер")) {
						statusMessage.textContent = text;
					} else {
						location.reload();
					}
				})
				.catch(error => {
					statusMessage.textContent = "Ошибка при загрузке файла.";
				});

				this.appendChild(statusMessage);
			}
		});
	});
    <?php endif; ?>
});
</script>

<style>
.app-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 20px;
  padding: 20px;
  background-color: #2b2b2b;
}

.app {
  background-color: #3a3a3a;
  padding: 20px;
  border: 1px solid #4a4a4a;
  border-radius: 8px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.5);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  display: flex; /* Make app a flex container */
  flex-direction: column; /* Stack items vertically */
}

.app:hover {
  transform: translateY(-10px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.7);
}

.app h3 {
  font-size: 1.3em;
  color: #e0e0e0;
  margin-bottom: 10px;
}

.app p {
  font-size: 1em;
  color: #b0b0b0;
  margin-bottom: 5px;
}

/* Version should be above the download button */
.app p:has(span[data-field="version"]) {
  margin-top: auto; /* Push this to the bottom of the flex container */
  margin-bottom: 5px;
}

.download-btn {
  display: inline-block;
  padding: 10px 20px;
  background: linear-gradient(45deg, #ff1e56, #ff8a00);
  color: white;
  text-decoration: none;
  border-radius: 5px;
  transition: all 0.3s ease;
  box-shadow: 0 3px 10px rgba(255, 106, 0, 0.4);
  margin-top: auto; /* This will push the button to the bottom */
}

.download-btn:hover {
  background: linear-gradient(45deg, #ff8a00, #ff1e56);
  transform: scale(1.05);
}

.missing-zip {
  color: #ff4136;
  font-style: italic;
}

.login-required {
  color: #ffdc00;
}

.upload-form {
  margin-top: 15px;
}

.upload-form input[type="file"] {
  margin-bottom: 10px;
  color: #e0e0e0;
}

/* Menu-like structure for additional controls */
.menu {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  background-color: #1a1a1a;
  color: #e0e0e0;
  margin-bottom: 20px;
  border-bottom: 2px solid #4a4a4a;
}

.menu h2 {
  font-size: 1.5em;
  margin: 0;
}

.menu button {
  padding: 8px 15px;
  background-color: #4a4a4a;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.3s, transform 0.3s;
  box-shadow: 0 2px 5px rgba(0,0,0,0.3);
}

.menu button:hover {
  background-color: #5a5a5a;
  transform: translateY(-2px);
}
</style>

<?php include_once 'includes/footer.php'; ?>