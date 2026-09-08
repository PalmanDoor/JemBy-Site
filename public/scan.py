import os
import json

def get_file_info(directory):
    file_info = {}
    for root, dirs, files in os.walk(directory):
        for file in files:
            file_path = os.path.join(root, file)
            file_size = os.path.getsize(file_path)
            file_info[file_path] = file_size
    return file_info

# Замените 'path/to/your/directory' на путь к вашей папке
directory = 'mods'
file_dict = get_file_info(directory)

# Запись данных в JSON файл
with open('mods.json', 'w') as json_file:
    json.dump(file_dict, json_file, indent=4)
