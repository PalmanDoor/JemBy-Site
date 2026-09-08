document.addEventListener('DOMContentLoaded', () => {
		const players = Plyr.setup('.gallery-item video', {
				controls: ['play'],
				muted: true
		});
});

function handleDrop(event) {
		event.preventDefault();
		const files = event.dataTransfer.files;
		if (files.length > 0) {
				const fileInput = document.getElementById('screenshotInput');
				fileInput.files = files;
				updateFileNameDisplay(files[0].name);
				openUploadModal();
		}
}

function openUploadModal() {
		document.getElementById('uploadModal').style.display = 'block';
}

function closeUploadModal() {
		document.getElementById('uploadModal').style.display = 'none';
}

document.getElementById('screenshotInput').addEventListener('change', function(e) {
		if (this.files.length > 0) {
				updateFileNameDisplay(this.files[0].name);
		}
});

function updateFileNameDisplay(fileName) {
		const display = document.getElementById('fileNameDisplay');
		display.textContent = fileName;
		display.style.display = 'block';
}

window.onclick = function(event) {
		const modal = document.getElementById('uploadModal');
		if (event.target === modal) {
				modal.style.display = 'none';
		}
}