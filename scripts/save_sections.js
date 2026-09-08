document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.btn-save').addEventListener('click', function(event) {
        event.preventDefault();

        const sections = document.querySelectorAll('.editable');
        const data = [];

        sections.forEach(section => {
            const editorInstance = CKEDITOR.instances[section.id];
            if (editorInstance) {
                data.push({
                    section: section.id,
                    content: editorInstance.getData(),
                    class_name: section.className,
                    element_type: section.nodeName.toLowerCase()
                });
            }
        });

        fetch('update_section.php?page=frontpage', {
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
