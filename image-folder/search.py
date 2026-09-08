import os
import time
import random
import string
from ddgs import DDGS
import requests
from PIL import Image
from io import BytesIO

SAVE_DIR = r'C:\ospanel\domains\mods\image-folder'
URLS_FILE = 'downloaded.txt'
DELAY = 5  # секунд между поисками
RETRY_LIMIT = 3  # попытки скачать
MAX_PER_QUERY = 20  # сколько максимум изображений скачивать с одного запроса

# Приоритетные запросы
# Приоритетные запросы
PRIORITY_QUERIES = [
    # Природа и ландшафты
    "Природа", "Парк", "Лес", "Озеро", "Океан", "Горы", "Пляж", "Река", "Водопад", 
    "Пустыня", "Поле", "Степь", "Тайга", "Джунгли", "Поляна", "Утес", "Каньон", "Луг",
    
    # Астрономия
    "Космос", "Небо", "Звезды", "Галактика", "Луна", "Солнце", "Закат", "Рассвет", 
    "Млечный путь", "Комета", "Туманность", "Созвездие",
    
    # Архитектура
    "Дом", "Город", "Замок", "Мост", "Небоскреб", "Храм", "Статуя", "Фонтан", 
    "Улица", "Площадь", "Деревня", "Дворец", "Собор", "Руины",
    
    # Растения
    "Цветы", "Дерево", "Сад", "Бонсай", "Кактус", "Тюльпан", "Роза", "Орхидея", 
    "Солнечник", "Лотос", "Сакура",
    
    # Вода и атмосфера
    "Дождь", "Снег", "Туман", "Радуга", "Облака", "Гроза", "Молния", "Иней", 
    "Ледник", "Айсберг", "Роса", "Град",
    
    # Животные
    "Птицы", "Бабочка", "Тигр", "Лев", "Собака", "Кошка", "Дельфин", "Олень", 
    "Лиса", "Волк", "Орел", "Кит", "Панда", "Енот",
    
    # Времена года
    "Весна", "Лето", "Осень", "Зима", 
    
    # Еда и напитки
    "Еда", "Кофе", "Фрукты", "Десерт", "Суши", "Пицца", "Шоколад", "Вино", 
    "Коктейль", "Торт", "Сыр", "Выпечка",
    
    # Абстрактные понятия
    "Свобода", "Любовь", "Счастье", "Тишина", "Покой", "Пустота", "Одиночество", 
    "Радость", "Мечта", "Таинство", "Бесконечность",
    
    # Человек и эмоции
    "Портрет", "Лицо", "Улыбка", "Дети", "Семья", "Танец", "Музыка", "Искусство", 
    "Спорт", "Йога", "Медитация", "Путешествие",
    
    # Технологии
    "Киберпанк", "архитектура чипов",
    
    # Стихии
    "Огонь", "Вода", "Земля", "Воздух", "Вулкан", "Торнадо", "Цунами",
    
    # Другое
    "Книги", "Карта", "Лабиринт", "Симметрия", "Паутина", "Кристаллы", "Алмаз"
]

# Глобальная очередь запросов
query_queue = PRIORITY_QUERIES.copy()

# Загружаем уже скачанные URL (если файл существует)
DOWNLOADED_URLS = set()
if os.path.exists(URLS_FILE):
    with open(URLS_FILE, 'r', encoding='utf-8') as f:
        DOWNLOADED_URLS = set(line.strip() for line in f if line.strip())

# Убедимся, что папка для сохранения существует
os.makedirs(SAVE_DIR, exist_ok=True)

def save_downloaded_url(url):
    with open(URLS_FILE, 'a', encoding='utf-8') as f:
        f.write(url + '\n')
    DOWNLOADED_URLS.add(url)

def is_1080p_image(url):
    if url in DOWNLOADED_URLS:
        return None
    try:
        for _ in range(RETRY_LIMIT):
            response = requests.get(url, timeout=10)
            if response.status_code == 200:
                img = Image.open(BytesIO(response.content))
                if img.size == (1920, 1080):
                    return img
    except Exception:
        pass
    return None

def save_image(img, url):
    name = ''.join(random.choices(string.ascii_letters + string.digits, k=12)) + '.jpg'
    path = os.path.join(SAVE_DIR, name)
    img.save(path, format='JPEG')
    save_downloaded_url(url)
    print(f'[+] Сохранено: {path}')

def get_next_query():
    # Циклический перебор приоритетных запросов
    query = query_queue.pop(0)
    query_queue.append(query)
    return query

def main_loop():
    print('[*] Запуск бесконечного поиска картинок 1920x1080...')
    while True:
        query = get_next_query()
        print(f'[*] Поиск: {query}')

        try:
            with DDGS() as ddgs:
                results = ddgs.images(query=query, max_results=MAX_PER_QUERY)
                for result in results:
                    url = result.get('image')
                    if not url or url in DOWNLOADED_URLS:
                        continue
                    img = is_1080p_image(url)
                    if img:
                        save_image(img, url)
        except Exception as e:
            print(f'[!] Ошибка поиска: {e}')

        print(f'[*] Ожидание {DELAY} секунд...')
        time.sleep(DELAY)

if __name__ == '__main__':
    main_loop()
