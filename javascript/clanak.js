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