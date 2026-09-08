// JavaScript для обновления текста метки файла
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('screenshot');
    const fileNameText = document.querySelector('.file-name');

    input.addEventListener('change', function() {
        if (input.files.length > 0) {
            fileNameText.textContent = input.files[0].name;
        } else {
            fileNameText.textContent = 'Файл не выбран';
        }
    });
});
