<?php
$title = "Информация";
$_SESSION["prevpage"] = "serverinfo.php";
include_once 'includes/header.php';
include "db.php";

// Функция для получения содержимого секции
function getSectionContent($section_name) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT section_content FROM serverinfo WHERE section_name = ?");
    $stmt->execute([$section_name]);
    $row = $stmt->fetch();
    return $row ? $row['section_content'] : null;
}

// Функция для проверки доступа к секции на основе имени
function hasAccessToSection($section_name) {
    if (strpos($section_name, '_login') !== false) {
        return isset($_SESSION['username']);
    }
    if (strpos($section_name, '_admin') !== false) {
        return isset($_SESSION['username']) && $_SESSION['level'] >= 5;
    }
    if (strpos($section_name, '_guest') !== false) {
        return !isset($_SESSION['username']);
    }
    if (strpos($section_name, '_level') !== false) {
        $level = (int)substr($section_name, strrpos($section_name, '_') + 1);
        return isset($_SESSION['level']) && $_SESSION['level'] >= $level;
    }
    if (strpos($section_name, '_public') !== false) {
        return true;
    }
    if (strpos($section_name, '_private') !== false) {
        return isset($_SESSION['username']) && in_array($_SESSION['username'], ['user1', 'user2']);
    }
    return true;
}

// Функция для рендеринга секции
function renderEditableSection($section_name) {
    global $editing;
    $content = getSectionContent($section_name);
    if ($content !== null) {
        if (hasAccessToSection($section_name)) {
            if ($editing) {
                echo "<div class='editable' data-section='$section_name' id='$section_name' contenteditable='true'>$content</div>";
                echo "<button class='delete-section' data-section='$section_name'>Удалить</button>";
                echo "<button class='move-section' data-section='$section_name' data-direction='up'>Вверх</button>";
                echo "<button class='move-section' data-section='$section_name' data-direction='down'>Вниз</button>";
                echo "<button class='rename-section' data-section='$section_name'>Переименовать</button>";
            } else {
                echo $content;
            }
        }
    }
}


// Включаем lowerheader.php
include_once 'includes/lowerheader.php';

// Получаем все секции из базы данных
$stmt = $pdo->query("SELECT section_name FROM serverinfo ORDER BY section_order");
$sections = $stmt->fetchAll(PDO::FETCH_COLUMN);

$editing = isset($_SESSION['editing']) && $_SESSION['editing'];
?>

<!-- End Header -->

<div class="content">
    <style>
        .add-section-btn {
            width: 64px;
            height: 64px;
            display: block;
            margin: 15px auto;
            padding: 5px;
        }

        .add-section-btn img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .delete-section, .move-section {
            display: inline-block;
            margin: 10px 5px;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .delete-section {
            background-color: red;
            color: white;
            border: none;
        }

        .move-section {
            background-color: blue;
            color: white;
        }

        .move-section.up {
            background-color: orange;
        }

        .move-section.down {
            background-color: green;
        }
		
		.rename-section {
			display: inline-block;
			margin: 10px 5px;
			padding: 5px 10px;
			background-color: orange;
			color: white;
			border: none;
			border-radius: 5px;
			cursor: pointer;
		}
    </style>
    <?php
    if (!isset($_SESSION["username"])) {
        renderEditableSection('header_login');
    } else {
        renderEditableSection('header_welcome');
        renderEditableSection('server_info');
    }

    foreach ($sections as $section_name) {
        if (!in_array($section_name, ['header_login', 'header_welcome', 'server_info'])) {
            renderEditableSection($section_name);
        }
    }
    ?>
    <?php if ($editing): ?>
    <button id="add-section-bottom" class="add-section-btn">
        <img src="./images/icons/add.png" alt="Добавить">
    </button>
    <?php endif; ?>
</div>

<?php if ($editing): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    CKEDITOR.disableAutoInline = true;

    document.querySelectorAll('.editable').forEach(div => {
        CKEDITOR.inline(div);
    });

    function addNewSection(position) {
        const newSectionName = prompt("Введите название новой секции:");
        if (newSectionName) {
            fetch('update_section.php?page=serverinfo', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify([{ section: newSectionName, content: 'Начальный контент', order: position }])
            }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                      toastr.success('Новая секция успешно добавлена.');
                      setTimeout(() => location.reload(), 2000);
                  } else {
                      toastr.error('Ошибка при добавлении секции.');
                      console.error(data.errors);
                  }
              }).catch(error => {
                  toastr.error('Ошибка при добавлении секции.');
                  console.error('Error:', error);
              });
        }
    }

    document.getElementById('add-section-bottom').addEventListener('click', function() {
        addNewSection('bottom');
    });

    document.querySelectorAll('.delete-section').forEach(button => {
        button.addEventListener('click', function() {
            const sectionName = this.getAttribute('data-section');
            if (confirm(`Вы уверены, что хотите удалить секцию: ${sectionName}?`)) {
                fetch('update_section.php?page=serverinfo', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ section: sectionName })
                }).then(response => response.json())
                  .then(data => {
                      if (data.success) {
                          toastr.success('Секция успешно удалена.');
                          setTimeout(() => location.reload(), 2000);
                      } else {
                          toastr.error('Ошибка при удалении секции.');
                          console.error(data.errors);
                      }
                  }).catch(error => {
                      toastr.error('Ошибка при удалении секции.');
                      console.error('Error:', error);
                  });
            }
        });
    });

    document.querySelectorAll('.move-section').forEach(button => {
        button.addEventListener('click', function() {
            const sectionName = this.getAttribute('data-section');
            const direction = this.getAttribute('data-direction');
            fetch('update_section.php?page=serverinfo', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ section: sectionName, direction: direction })
            }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                      toastr.success('Секция успешно перемещена.');
                      setTimeout(() => location.reload(), 2000);
                  } else {
                      toastr.error('Ошибка при перемещении секции.');
                      console.error(data.errors);
                  }
              }).catch(error => {
                  toastr.error('Ошибка при перемещении секции.');
                  console.error('Error:', error);
              });
        });
    });

	document.querySelectorAll('.rename-section').forEach(button => {
		button.addEventListener('click', function() {
			const sectionName = this.getAttribute('data-section');
			const newSectionName = prompt('Введите новое название секции:', sectionName);
			if (newSectionName && newSectionName !== sectionName) {
				fetch('update_section.php?page=serverinfo', {
					method: 'PATCH',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({ old_section: sectionName, new_section: newSectionName })
				}).then(response => response.json())
				  .then(data => {
					  if (data.success) {
						  toastr.success('Секция успешно переименована.');
						  setTimeout(() => location.reload(), 2000);
					  } else {
						  toastr.error('Ошибка при переименовании секции.');
						  console.error(data.errors);
					  }
				  }).catch(error => {
					  toastr.error('Ошибка при переименовании секции.');
					  console.error('Error:', error);
				  });
			}
		});
	});

    document.querySelector('.btn-save').addEventListener('click', function(event) {
        event.preventDefault();
        const sections = document.querySelectorAll('.editable');
        const data = [];

        sections.forEach(section => {
            const editorInstance = CKEDITOR.instances[section.id];
            if (editorInstance) {
                data.push({
                    section: section.id,
                    content: editorInstance.getData()
                });
            }
        });

        fetch('update_section.php?page=serverinfo', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  toastr.success('Секции успешно обновлены.');
                  setTimeout(() => location.reload(), 2000);
              } else {
                  toastr.error('Ошибка при обновлении секций.');
                  console.error(data.errors);
              }
          }).catch(error => {
              toastr.error('Ошибка при обновлении секций.');
              console.error('Error:', error);
          });
    });
});
</script>
<?php endif; ?>
<?php 
include_once 'includes/footer.php';
?>
