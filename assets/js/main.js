
function reloadCaptcha(Imgid, textID) {
    const captchaImage = document.getElementById(Imgid);
    const captchaInput = document.getElementById(textID);
    captchaImage.src = '/captcha?' + new Date().getTime(); // Reloads CAPTCHA with a new timestamp
    captchaInput.value = ''; // Clears the CAPTCHA input field
}

// Initialize Toast with custom settings
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
    }
});

function showToast(delay, icon, message) {
    Toast.fire({
        icon: icon, // 'success', 'error', 'warning', etc.
        title: message,
        timer: delay // Set the duration for the toast
    });
}

function showSpinner() {
    document.getElementById('spinner-overlay').style.display = 'flex';
    document.body.classList.add('spin-lock');
}

function hideSpinner() {
    document.getElementById('spinner-overlay').style.display = 'none';
    document.body.classList.remove('spin-lock');
}