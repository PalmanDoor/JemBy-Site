function createNewPost() {
    fetch('create_news.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            'slika': 'blank.png',
            'naslov': 'Hello World!',
            'sazetak': 'Здесь может быть любой контент'
        })
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              window.location.href = 'clanak.php?id=' + data.id;
          } else {
              alert('Ошибка при создании новости: ' + data.error);
          }
      }).catch(error => {
          console.error('Error:', error);
          alert('Ошибка при создании новости.');
      });
}

function deleteNews(id) {
    if (confirm('Вы действительно хотите удалить эту новость?')) {
        fetch('delete_news.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  alert('Новость успешно удалена.');
                  location.reload();
              } else {
                  alert('Ошибка при удалении новости: ' + data.error);
              }
          }).catch(error => {
              console.error('Error:', error);
              alert('Ошибка при удалении новости.');
          });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.delete-news').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            deleteNews(this.getAttribute('data-id'));
        });
    });

    const searchInput = document.getElementById('search-input');
    const articles = document.querySelectorAll('.sport article');
    
    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        
        articles.forEach(article => {
            const title = article.querySelector('h4');
            const content = article.querySelector('h5');
            
            title.innerHTML = title.textContent;
            if (content) content.innerHTML = content.textContent;
            
            if (searchTerm) {
                const highlight = (text, search) => {
                    const regex = new RegExp(search, 'gi');
                    return text.replace(regex, match => `<mark>${match}</mark>`);
                };

                title.innerHTML = highlight(title.textContent, searchTerm);
                if (content) content.innerHTML = highlight(content.textContent, searchTerm);
                
                if (title.textContent.toLowerCase().includes(searchTerm) || (content && content.textContent.toLowerCase().includes(searchTerm))) {
                    article.style.display = 'block';
                } else {
                    article.style.display = 'none';
                }
            } else {
                article.style.display = 'block';
            }
        });
    });
    
    searchInput.dispatchEvent(new Event('input'));
});