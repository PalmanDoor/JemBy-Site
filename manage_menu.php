<?php
include("db.php");

if (!isset($_SESSION["username"])) {
    header("location:username.php");
    exit();
}

$username = $_SESSION["username"];
$stmt = $pdo->prepare("SELECT level, editing FROM authy.players WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['level'] < 4) {
    header("location:index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids = $_POST['id'];
    $names = $_POST['name'];
    $urls = $_POST['url'];
    $orders = $_POST['order'];

    foreach ($ids as $index => $id) {
        $name = htmlspecialchars(trim($names[$index]));
        $url = htmlspecialchars(trim($urls[$index]));
        $order = intval($orders[$index]);

        if ($id) {
            $stmt = $pdo->prepare("UPDATE lowerheader SET name = ?, url = ?, `order` = ? WHERE id = ?");
            $stmt->execute([$name, $url, $order, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO lowerheader (name, url, `order`) VALUES (?, ?, ?)");
            $stmt->execute([$name, $url, $order]);
        }
    }
    header("Location: manage_menu.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $requestData);
    $id = intval($requestData['id']);
    $stmt = $pdo->prepare("DELETE FROM lowerheader WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit();
}

$title = "Управление меню";
include_once 'includes/header.php';
include_once 'includes/lowerheader.php';
?>

<link href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="admin-container">
    <div class="content">
        <h3>Управление меню</h3>
        <form method="POST" action="manage_menu.php" style="text-align: center;">
            <div id="menu-editor">
                <?php
                $stmt = $pdo->query("SELECT * FROM lowerheader ORDER BY `order` ASC");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<div class='menu-item'>";
                    echo "<input type='hidden' name='id[]' value='" . htmlspecialchars($row['id']) . "' />";
                    echo "<input type='text' name='name[]' value='" . htmlspecialchars($row['name']) . "' />";
                    echo "<input type='text' name='url[]' value='" . htmlspecialchars($row['url']) . "' />";
                    echo "<input type='number' name='order[]' value='" . htmlspecialchars($row['order']) . "' />";
                    echo "<button type='button' class='delete-menu-item'>Удалить</button>";
                    echo "</div>";
                }
                ?>
            </div>
            <button type='button' id='add-menu-item'>Добавить элемент</button>
            <input type='submit' value='Сохранить' class='btn-save-list'>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Добавление нового элемента меню
    $('#add-menu-item').on('click', function() {
        Swal.fire({
            title: 'Добавить элемент меню',
            html: `
                <input id="name" class="swal2-input" placeholder="Название" />
                <input id="url" class="swal2-input" placeholder="URL" />
                <input id="order" class="swal2-input" type="number" placeholder="Порядок" />
            `,
            confirmButtonText: 'Добавить',
            focusConfirm: false,
            preConfirm: () => {
                const name = Swal.getPopup().querySelector('#name').value;
                const url = Swal.getPopup().querySelector('#url').value;
                const order = Swal.getPopup().querySelector('#order').value;
                if (!name || !url || !order) {
                    Swal.showValidationMessage(`Пожалуйста, заполните все поля.`);
                }
                return { name: name, url: url, order: order }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const newItem = `
                    <div class='menu-item'>
                        <input type='hidden' name='id[]' value='' />
                        <input type='text' name='name[]' value='${result.value.name}' />
                        <input type='text' name='url[]' value='${result.value.url}' />
                        <input type='number' name='order[]' value='${result.value.order}' />
                        <button type='button' class='delete-menu-item'>Удалить</button>
                    </div>`;
                $('#menu-editor').append(newItem);
                Swal.fire('Добавлено!', 'Новый элемент меню был добавлен.', 'success');
            }
        });
    });

    // Удаление элемента меню
    $(document).on('click', '.delete-menu-item', function() {
        const row = $(this).closest('.menu-item');
        const id = row.find('input[name="id[]"]').val();
        if (id) {
            Swal.fire({
                title: 'Вы уверены?',
                text: 'Вы не сможете восстановить этот элемент!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Удалить',
                cancelButtonText: 'Отменить'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'manage_menu.php',
                        type: 'DELETE',
                        data: { id: id },
                        success: function() {
                            row.remove();
                            Swal.fire('Удалено!', 'Элемент меню был удален.', 'success');
                        }
                    });
                }
            });
        } else {
            row.remove();
        }
    });
});
</script>

<style>
.menu-item {
    margin-bottom: 10px;
}

.menu-item input {
    margin-right: 5px;
}

#add-menu-item {
    margin-top: 10px;
    padding: 10px 20px;
    background-color: #007bff;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

#add-menu-item:hover {
    background-color: #0056b3;
}

.btn-save-list {
    background-color: #28a745;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
}

.btn-save-list:hover {
    background-color: #218838;
}

.delete-menu-item {
    background-color: #dc3545;
    color: #fff;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
    margin-left: 10px;
}

.delete-menu-item:hover {
    background-color: #c82333;
}
</style>

<?php include_once 'includes/footer.php';?>
