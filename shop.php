<?php 
include "db.php";

// Проверка авторизации пользователя
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Выбор случайного изображения
$imageIndex = rand(1, 21);
$randomImage = "./images/input/gifinfoblock/{$imageIndex}.gif";

// Массив с путями к изображениям фонов товаров
$backgroundImages = ["./sprite/item1.png", "./sprite/item2.png", "./sprite/item3.png", "./sprite/item4.png"];
$title = "Донат";
$_SESSION["prevpage"] = "shop.php";
include_once 'includes/header.php';
include_once 'includes/lowerheader.php';

// Получение всех категорий из базы данных
$stmt = $pdo->query("SELECT DISTINCT category FROM shop");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Получение всех товаров из базы данных
$stmt = $pdo->query("SELECT * FROM shop");
$items = $stmt->fetchAll();
?>

<!-- Конец заголовка -->
<div class="content page-shop">
    <!-- Кнопка добавления нового товара -->
    <?php if ($_SESSION['editing'] ?? false): ?>
        <button id="addNewItemBtn">Добавить новый товар</button>
    <?php endif; ?>
    <!-- Секция, где отображаются товары -->
    <div class="shop-container">
        <div class="category-filter">
            <label for="category-select">Категория:</label>
            <select id="category-select">
                <option value="all">Все</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php foreach ($items as $item): 
            // Выбор случайного изображения фона
            $backgroundImage = $backgroundImages[array_rand($backgroundImages)];
        ?>
            <div class="shop-item" 
                 data-id="<?php echo htmlspecialchars($item['id']); ?>" 
                 data-category="<?php echo htmlspecialchars($item['category']); ?>" 
                 data-title="<?php echo htmlspecialchars($item['title']); ?>" 
                 data-description="<?php echo htmlspecialchars($item['description']); ?>" 
                 data-price="<?php echo htmlspecialchars($item['price']); ?>" 
                 data-image="<?php echo htmlspecialchars($item['image']); ?>" 
                 data-command="<?php echo htmlspecialchars($item['command']); ?>"
                 data-rotation="<?php echo $rotation; ?>"> <!-- Передаем угол поворота -->
                <div class="item-image-container">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" onerror="this.onerror=null;this.src='./shop/incorect/default.png';">
                </div>
                <style>
                    .shop-item[data-id="<?php echo htmlspecialchars($item['id']); ?>"] {
                        background-image: url('<?php echo $backgroundImage; ?>');
                    }
                </style>
            </div>
        <?php endforeach; ?>
    </div>
    <div id="info-panel" class="info-panel">
        <img src="<?php echo $randomImage; ?>" alt="Info Image" class="info-image">
        <h3 id="info-title"></h3>
        <p id="info-description"></p>
        <p id="info-price" class="orice"></p>
        <p id="info-command" class="orece-command"></p>
        <button class="buy-button">Купить</button>
        <?php if ($_SESSION['editing'] ?? false): ?>
            <button id="edit-button" class="edit-button">Редактировать</button>
            <button id="delete-button" class="delete-button">Удалить</button>
        <?php endif; ?>
    </div>

    <div class="info-menu">
        <h4>Информация для пользователя:</h4>
        <p>Для получения информации о товаре просто щелкните на его изображение, и справа на странице появятся все необходимые детали.</p>
        <p>Чтобы получить товар, отправьте команду в чат, написав <strong class="cartget">/cart get</strong>.</p>
        <p>Чтобы получить товары или предметы, необходимо, чтобы в вашем инвентаре были доступные пустые слоты.</p>
        <p class="text-warning">Мы заранее вас проинформировали! В случае случайной утраты товара, вы несёте полную ответственность за это.</p>
    </div>
</div>

<!-- Форма для добавления нового товара -->
<div id="addItemModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Добавить новый товар</h2>
        <form id="addItemForm">
            <label for="itemName">Название товара:</label>
            <input type="text" id="itemName" name="itemName" required>

            <label for="itemDescription">Описание товара:</label>
            <textarea id="itemDescription" name="itemDescription" required></textarea>

            <label for="itemPrice">Цена товара:</label>
            <input type="number" id="itemPrice" name="itemPrice" required>

            <label for="itemImage">URL изображения товара:</label>
            <input type="text" id="itemImage" name="itemImage" required>

            <label for="itemCategory">Категория товара:</label>
            <select id="itemCategory" name="itemCategory">
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="newCategory">Или создайте новую категорию:</label>
            <input type="text" id="newCategory" name="newCategory">

            <label for="itemCommand">Команда (give {username} diamond (количество)):</label>
            <input type="text" id="itemCommand" name="itemCommand" required>

            <button type="submit">Создать</button>
        </form>
    </div>
</div>

<!-- Форма для редактирования товара -->
<div id="editItemModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Редактировать товар</h2>
        <form id="editItemForm">
            <input type="hidden" id="editItemId" name="editItemId">
            
            <label for="editItemName">Название товара:</label>
            <input type="text" id="editItemName" name="editItemName" required>

            <label for="editItemDescription">Описание товара:</label>
            <textarea id="editItemDescription" name="editItemDescription" required></textarea>

            <label for="editItemPrice">Цена товара:</label>
            <input type="number" id="editItemPrice" name="editItemPrice" required>

            <label for="editItemImage">URL изображения товара:</label>
            <input type="text" id="editItemImage" name="editItemImage" required>

            <label for="editItemCategory">Категория товара:</label>
            <select id="editItemCategory" name="editItemCategory">
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="editNewCategory">Или создайте новую категорию:</label>
            <input type="text" id="editNewCategory" name="editNewCategory">

            <label for="editItemCommand">Команда (give {username} diamond (количество)):</label>
            <input type="text" id="editItemCommand" name="editItemCommand" required>

            <button type="submit">Сохранить изменения</button>
        </form>
    </div>
</div>

<style>
/* Стили для контейнера, содержащего элементы .shop-item */
.shop-container {
    display: flex;
    flex-wrap: wrap;
    border: 1px solid #444;
    border-radius: 10px;
    margin-left: 10px;
    margin-right: 10px;
    background-color: #2e2e2e; /* Темный фон */
    justify-content: center; /* Центрируем товары по горизонтали */
    padding: 10px; /* Добавлен внутренний отступ */
    min-width: 920px;
}

/* Стили для каждого товара в магазине */
.shop-item {
    position: relative;
    width: 130px;
    height: 130px;
    transition: transform 0.3s, box-shadow 0.3s, background-color 0.2s, opacity 0.3s, visibility 0.3s;
    padding: 20px;
    overflow: hidden;
    display: flex;
    justify-content: center;
    align-items: center;
    background-size: cover;
    margin: 10px;
}

/* Убедитесь, что скрытые элементы не влияют на макет */
.shop-item.hidden {
    display: none; /* Добавьте класс для скрытия элементов */
}

/* Стили для контейнера изображения товара */
.item-image-container {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 105px;
    height: 105px;
}

/* Стили для изображения товара */
.item-image-container img {
    width: 100%;
    height: 100%;
    object-fit: cover; /* Изображение покрывает весь контейнер */
    transition: transform 0.3s;
}

/* Эффекты для товара при наведении */
.shop-item:hover {
    transform: scale(1.05); /* Увеличиваем контейнер товара */
}

.shop-item:hover .item-image-container img {
    transform: scale(1.05); /* Увеличиваем изображение товара при наведении */
}

/* Стили для кнопок редактирования и удаления */
.edit-button,
.delete-button {
    margin-top: 10px;
    display: block;
    background-color: #4CAF50; /* Зеленый для редактирования */
    border: none;
    color: white;
    padding: 10px;
    text-align: center;
    text-decoration: none;
    font-size: 14px;
    border-radius: 5px;
    cursor: pointer;
}

.shop-item .delete-button {
    background-color: #f44336; /* Красный для удаления */
}

/* Стили для фильтра категории */
.category-filter {
    width: 100%;
    padding: 10px;
    text-align: center;
    background-color: #3c3c3c; /* Темный фон для фильтра */
    border-radius: 5px;
    margin-bottom: 10px;
}

.category-filter label {
    color: #e0e0e0; /* Светлый цвет текста */
    font-size: 16px;
}

.category-filter select {
    padding: 5px;
    font-size: 16px;
    background-color: #1e1e1e; /* Темный фон */
    color: #e0e0e0; /* Светлый цвет текста */
    border: 1px solid #444;
    border-radius: 5px;
}

/* Стили для заголовка товара */
.heading {
    font-size: 14px;
    margin: 10px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
    color: #e0e0e0; /* Светлый цвет текста */
}

/* Стили для кнопки покупки */
.buy-button {
    background-color: #9e2c44; /* Темно-зеленый цвет */
    color: white;
    padding: 10px;
	width: 90%;
    border: none;
    cursor: pointer;
    font-size: 14px;
    border-radius: 5px;
    margin-top: 10px;
}

.buy-button:hover {
    background-color: #9e7d11; /* Более темный оттенок синего */
}

/* Кнопка добавления нового товара */
#addNewItemBtn {
    background-color: #5c6bc0; /* Темно-синий цвет */
    color: white;
    padding: 10px;
    border: none;
    cursor: pointer;
    font-size: 14px;
    border-radius: 5px;
    margin-top: 10px;
    margin-left: 10px;
    margin-bottom: 10px;
}

#addNewItemBtn:hover {
    background-color: #3f51b5; /* Более темный оттенок синего */
}

/* Кнопка редактирования товара */
.edit-button {
    background-color: #fbc02d; /* Желтый цвет */
    color: white;
    padding: 10px;
	width: 90%;
    border: none;
    cursor: pointer;
    font-size: 14px;
    border-radius: 5px;
    margin-top: 10px;
}

.edit-button:hover {
    background-color: #f9a825; /* Более яркий желтый */
}

/* Кнопка удаления товара */
.delete-button {
    background-color: #d32f2f; /* Красный цвет */
    color: white;
    padding: 10px;
	width: 90%;
    border: none;
    cursor: pointer;
    font-size: 14px;
    border-radius: 5px;
    margin-top: 10px;
}

.delete-button:hover {
    background-color: #c62828; /* Более темный красный */
}

/* Стили для модальных окон */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
    justify-content: center;
    align-items: center;
	color: #fff;
}

.modal-content {
    background-color: #2b2d31;
    margin: auto;
    padding: 20px;
    border: 1px solid #888;
    width: 95%;
    max-width: 500px;
    border-radius: 10px;
	color: #fff;
}

.modal, #itemCategory {
	color: #fff;
}

.modal-content form {
    display: flex;
    flex-direction: column;
    gap: 10px; /* Расстояние между элементами */
}

.modal-content label {
    font-size: 14px;
	color: #fff;
}

.modal-content input[type="text"],
.modal-content input[type="number"],
.modal-content textarea,
.modal-content select {
    width: 95%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 14px;
	background-color: #1e1f22;
	color: #fff;
}

.modal-content button {
    background-color: #4650c3;
    color: white;
    padding: 10px;
    border: none;
    cursor: pointer;
    font-size: 14px;
    border-radius: 5px;
}

.modal-content button:hover {
    background-color: #4752f9;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
}

.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

.orice {
	color: #5454fc;
}

.orece-command {
	color: #555555;
	font-size: 14px;
}

.info-panel {
    font-family: 'Minecraft Rus';
    src: url('fonts/minecraft.ttf') format('truetype');
    position: fixed;
    right: -330px;
    top: 50%;
    transform: translateY(-50%);
    width: 250px;
    padding: 20px;
    background-color: #110210;
	border: 3px solid #2c0863;
    color: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.5);
    transition: right 0.3s ease;
}

.info-panel.visible {
    right: 0;
}

.info-panel h3 {
    margin-top: 0;
}

.info-image {
    position: absolute;
    top: -49px;
    left: -32px;
    width: 64px;
    height: 64px;
}

/* Стили для информационного меню */
.info-menu {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 450px;
    padding: 20px;
    background: linear-gradient(135deg, #4b79a1, #283e51);
    color: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.6);
    z-index: 1000;
    font-size: 14px;
    font-family: 'Arial', sans-serif;
    opacity: 0.9;
    transition: opacity 0.3s ease;
}

.info-menu:hover {
    opacity: 1;
}

.info-menu h4 {
    margin-top: 0;
    font-size: 18px;
    font-weight: bold;
    color: #ffdd57;
}

.info-menu p {
    margin: 10px 0;
    line-height: 1.5;
}

/* Стиль для текста /cart get */
.cartget {
    color: #32cd32;
    font-weight: bold;
}

/* Анимация для информационного меню */
@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.info-menu {
    animation: slideIn 0.5s ease-out;
}

.text-warning {
	color: yellow;
	font-weight: bold;
}
</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js"></script>
<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css'>
<script src='https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js'></script>
<script>
// Настройка Toastr
toastr.options = {
    "closeButton": true,
    "debug": false,
    "newestOnTop": false,
    "progressBar": true,
    "positionClass": "toast-top-right",
    "preventDuplicates": false,
    "onclick": null,
    "showDuration": "300",
    "hideDuration": "1000",
    "timeOut": "5000",
    "extendedTimeOut": "1000",
    "showEasing": "swing",
    "hideEasing": "linear",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut"
};

document.addEventListener('DOMContentLoaded', () => {
    const items = document.querySelectorAll('.shop-item');
    const buyButton = document.querySelector('.buy-button');
    const categorySelect = document.getElementById('category-select');
    const infoPanel = document.getElementById('info-panel');
    const infoTitle = document.getElementById('info-title');
    const infoDescription = document.getElementById('info-description');
    const infoPrice = document.getElementById('info-price');
    const infoCommand = document.getElementById('info-command');
    const addItemModal = document.getElementById('addItemModal');
    const editItemModal = document.getElementById('editItemModal');
    const addNewItemBtn = document.getElementById('addNewItemBtn');
    const addItemForm = document.getElementById('addItemForm');
    const editItemForm = document.getElementById('editItemForm');
    const closeButtons = document.querySelectorAll('.close');
    const editButton = document.getElementById('edit-button');
    const deleteButton = document.getElementById('delete-button');

    // Показываем панель информации при клике на .shop-item
    items.forEach(item => {
        item.addEventListener('click', () => {
            const title = item.getAttribute('data-title');
            const description = item.getAttribute('data-description');
            const price = item.getAttribute('data-price');
            const command = item.getAttribute('data-command');
            const itemId = item.getAttribute('data-id');

            infoTitle.textContent = title;
            infoDescription.textContent = description;
            infoPrice.textContent = `Цена: ${price}`;
            infoCommand.textContent = `${command}`;

            // Сохраняем id товара в info-panel для последующего использования
            infoPanel.setAttribute('data-id', itemId);
            infoPanel.setAttribute('data-price', price);

            infoPanel.classList.add('visible');
        });
    });

    // Закрытие панели информации
    if (infoPanel) {
        infoPanel.addEventListener('click', (e) => {
            if (e.target === infoPanel) {
                infoPanel.classList.remove('visible');
            }
        });
    }

    // Присваиваем обработчик кнопке покупки
    if (buyButton) {
        buyButton.addEventListener('click', async () => {
            const itemId = infoPanel.getAttribute('data-id');
            const price = infoPanel.getAttribute('data-price');

            if (!itemId || !price) {
                toastr.error('Ошибка: не удалось получить информацию о товаре.');
                return;
            }

            try {
                const response = await fetch('buy.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ itemId, totalPrice: price })
                });
                const data = await response.json();
                if (data.success) {
                    toastr.success('Товар успешно куплен!');
                } else {
                    toastr.error(data.message);
                }
            } catch (error) {
                console.error('Ошибка при выполнении запроса на покупку:', error);
                toastr.error('Произошла ошибка при выполнении запроса на покупку.');
            }
        });
    }

    // Присваиваем обработчик кнопке редактирования
    if (editButton) {
        editButton.addEventListener('click', () => {
            const itemId = infoPanel.getAttribute('data-id');
            const item = document.querySelector(`.shop-item[data-id="${itemId}"]`);

            if (!item) return;

            const title = item.getAttribute('data-title');
            const description = item.getAttribute('data-description');
            const price = item.getAttribute('data-price');
            const image = item.getAttribute('data-image');
            const category = item.getAttribute('data-category');
            const command = item.getAttribute('data-command');

            document.getElementById('editItemId').value = itemId;
            document.getElementById('editItemName').value = title;
            document.getElementById('editItemDescription').value = description;
            document.getElementById('editItemPrice').value = price;
            document.getElementById('editItemImage').value = image;
            document.getElementById('editItemCategory').value = category;
            document.getElementById('editItemCommand').value = command;

            editItemModal.style.display = 'flex';
        });
    }

    // Присваиваем обработчик кнопке удаления
    if (deleteButton) {
        deleteButton.addEventListener('click', async () => {
            const itemId = infoPanel.getAttribute('data-id');

            if (!itemId) {
                alert('Неверный ID товара');
                return;
            }

            const confirmed = confirm('Вы уверены, что хотите удалить этот товар?');

            if (confirmed) {
                try {
                    const response = await fetch('delete_item.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({ itemId })
                    });
                    const data = await response.json();
                    if (data.success) {
                        alert('Товар удален!');
                        const item = document.querySelector(`.shop-item[data-id="${itemId}"]`);
                        if (item) item.remove();
                        infoPanel.classList.remove('visible');
                    } else {
                        alert(data.message);
                    }
                } catch (error) {
                    console.error('Ошибка при удалении товара:', error);
                }
            }
        });
    }

	// Фильтрация товаров по категориям
	if (categorySelect) {
		categorySelect.addEventListener('change', () => {
			const selectedCategory = categorySelect.value;
			items.forEach(item => {
				if (selectedCategory === 'all' || item.getAttribute('data-category') === selectedCategory) {
					item.classList.remove('hidden'); // Показываем элемент
				} else {
					item.classList.add('hidden'); // Скрываем элемент
				}
			});
		});
	}

    if (addNewItemBtn) {
        addNewItemBtn.addEventListener('click', () => {
            addItemModal.style.display = 'flex';
        });
    }

    closeButtons.forEach(button => {
        button.addEventListener('click', () => {
            addItemModal.style.display = 'none';
            editItemModal.style.display = 'none';
            infoPanel.classList.remove('visible');
        });
    });

    if (addItemForm) {
        addItemForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const formData = new FormData(addItemForm);

            fetch('add_item.php', {
                method: 'POST',
                body: formData
            }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Товар добавлен!');
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        });
    }

    if (editItemForm) {
        editItemForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const formData = new FormData(editItemForm);

            fetch('edit_item.php', {
                method: 'POST',
                body: formData
            }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Товар обновлен!');
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        });
    }

    // Убедимся, что информационное меню всегда будет видно
    const infoMenu = document.querySelector('.info-menu');
    if (infoMenu) {
        infoMenu.style.display = 'block';
    }

});
</script>

<?php 
include_once 'includes/footer.php';
?>