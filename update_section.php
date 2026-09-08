<?php
header('Content-Type: application/json');

// Проверка прав пользователя
if (!isset($_SESSION["username"]) || ($_SESSION['level'] < 5 && $_SESSION['editing'] != 1)) {
    echo json_encode(['success' => false, 'error' => 'Недостаточно прав.']);
    exit;
}

require 'db.php';

$requestData = json_decode(file_get_contents('php://input'), true);
$errors = [];

// Определение таблицы в зависимости от параметра `page`
if (isset($_GET['page'])) {
    $page = $_GET['page'];
    switch ($page) {
        case 'index':
        case 'frontpage':
            $table = 'frontpage';
            break;
        case 'project':
            $table = 'project';
            break;
        case 'oldposts':
            $table = 'oldposts';
            break;
        case 'serverinfo':
            $table = 'serverinfo';
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid page parameter.']);
            exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (is_array($requestData)) {
            foreach ($requestData as $sectionData) {
                if (isset($sectionData['section']) && isset($sectionData['content'])) {
                    $section = htmlspecialchars($sectionData['section'], ENT_QUOTES, 'UTF-8');
                    $content = $sectionData['content'];

                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `minecraft-website`.$table WHERE section_name = ?");
                    $stmt->execute([$section]);
                    $exists = $stmt->fetchColumn();

                    if ($exists) {
                        // Обновление секции
                        $query = "UPDATE `minecraft-website`.$table SET section_content = ? WHERE section_name = ?";
                        $stmt = $pdo->prepare($query);
                        try {
                            $stmt->execute([$content, $section]);
                        } catch (PDOException $e) {
                            error_log("Failed to update section: $section (" . $e->getMessage() . ")", 3, '/var/log/php_errors.log');
                            $errors[] = "Failed to update section: $section (" . $e->getMessage() . ")";
                        }
                    } else {
                        // Вставка новой секции с нумерацией
                        $stmt = $pdo->prepare("SELECT MAX(section_order) as max_order FROM `minecraft-website`.$table");
                        $stmt->execute();
                        $maxOrder = $stmt->fetch(PDO::FETCH_ASSOC)['max_order'];
                        $newOrder = $maxOrder ? $maxOrder + 1 : 1;

                        $query = "INSERT INTO `minecraft-website`.$table (section_name, section_content, section_order) VALUES (?, ?, ?)";
                        $stmt = $pdo->prepare($query);
                        try {
                            $stmt->execute([$section, $content, $newOrder]);
                        } catch (PDOException $e) {
                            error_log("Failed to insert section: $section (" . $e->getMessage() . ")", 3, '/var/log/php_errors.log');
                            $errors[] = "Failed to insert section: $section (" . $e->getMessage() . ")";
                        }
                    }
                } else {
                    $errors[] = 'Invalid section data provided.';
                }
            }
        } else {
            $errors[] = 'Invalid request format.';
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (isset($requestData['section'])) {
            $section = htmlspecialchars($requestData['section'], ENT_QUOTES, 'UTF-8');

            $query = "DELETE FROM `minecraft-website`.$table WHERE section_name = ?";
            $stmt = $pdo->prepare($query);

            try {
                $stmt->execute([$section]);
            } catch (PDOException $e) {
                error_log("Failed to delete section: $section (" . $e->getMessage() . ")", 3, '/var/log/php_errors.log');
                $errors[] = "Failed to delete section: $section (" . $e->getMessage() . ")";
            }
            
            // Перенумерация секций после удаления
            $stmt = $pdo->query("SELECT section_name FROM `minecraft-website`.$table ORDER BY section_order");
            $sections = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $order = 1;
            foreach ($sections as $sectionName) {
                $stmt = $pdo->prepare("UPDATE `minecraft-website`.$table SET section_order = ? WHERE section_name = ?");
                $stmt->execute([$order, $sectionName]);
                $order++;
            }
        } else {
            $errors[] = 'Invalid section data provided.';
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        if (isset($requestData['section']) && isset($requestData['direction'])) {
            $section_name = htmlspecialchars($requestData['section'], ENT_QUOTES, 'UTF-8');
            $direction = $requestData['direction'];
            
            $stmt = $pdo->prepare("SELECT section_order FROM `minecraft-website`.$table WHERE section_name = ?");
            $stmt->execute([$section_name]);
            $current_order = $stmt->fetchColumn();
            
            if ($direction == 'up') {
                $new_order = $current_order - 1;
            } else {
                $new_order = $current_order + 1;
            }

            if ($new_order < 1) $new_order = 1;
            $maxOrder = $pdo->query("SELECT MAX(section_order) FROM `minecraft-website`.$table")->fetchColumn();
            if ($new_order > $maxOrder) $new_order = $maxOrder;

            $stmt = $pdo->prepare("UPDATE `minecraft-website`.$table SET section_order = ? WHERE section_order = ?");
            $stmt->execute([0, $new_order]);
            
            $stmt = $pdo->prepare("UPDATE `minecraft-website`.$table SET section_order = ? WHERE section_name = ?");
            $stmt->execute([$new_order, $section_name]);
            
            $stmt = $pdo->prepare("UPDATE `minecraft-website`.$table SET section_order = ? WHERE section_order = 0");
            $stmt->execute([$current_order]);
            
            echo json_encode(['success' => true]);
            exit;
        } else {
            $errors[] = 'Invalid section data provided.';
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
        if (isset($requestData['old_section']) && isset($requestData['new_section'])) {
            $old_section = htmlspecialchars($requestData['old_section'], ENT_QUOTES, 'UTF-8');
            $new_section = htmlspecialchars($requestData['new_section'], ENT_QUOTES, 'UTF-8');

            // Проверка на уникальность нового имени секции
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `minecraft-website`.$table WHERE section_name = ?");
            $stmt->execute([$new_section]);
            $exists = $stmt->fetchColumn();
            if ($exists) {
                echo json_encode(['success' => false, 'error' => 'Секция с таким именем уже существует.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE `minecraft-website`.$table SET section_name = ? WHERE section_name = ?");
            try {
                $stmt->execute([$new_section, $old_section]);
                echo json_encode(['success' => true]);
            } catch (PDOException $e) {
                error_log("Failed to rename section from $old_section to $new_section (" . $e->getMessage() . ")", 3, '/var/log/php_errors.log');
                echo json_encode(['success' => false, 'error' => 'Не удалось переименовать секцию.']);
            }
            exit;
        } else {
            $errors[] = 'Invalid section data provided.';
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // GET запрос для получения данных
        $query = "SELECT section_name, section_content, section_order FROM `minecraft-website`.$table ORDER BY section_order";
        $stmt = $pdo->query($query);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    if (empty($errors)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'errors' => $errors]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid request.']);
exit;
?>