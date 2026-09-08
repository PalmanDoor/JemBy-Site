function toggleForm(formToShow) {
    document.querySelector('.login-form').classList.toggle('active', formToShow === 'login');
    document.querySelector('.register-form').classList.toggle('active', formToShow === 'register');
}

function acceptCookieNotice() {
    document.querySelector('.cookie-notice').style.display = 'none';
    document.cookie = "cookie_notice_accepted=true; path=/; max-age=" + (60 * 60 * 24 * 365);
}

document.onreadystatechange = function() {
    if (document.readyState === 'complete') {
        document.body.classList.add('loaded');
    }
};