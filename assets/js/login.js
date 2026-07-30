/**
 * assets/js/login.js
 * Menangani interaksi pada halaman login:
 * - Toggle tampilkan/sembunyikan password
 * - Validasi sederhana sebelum submit (Bootstrap validation)
 */

document.addEventListener('DOMContentLoaded', function () {
  // Toggle show/hide password
  const togglePassword = document.querySelector('.toggle-password');
  const passwordInput = document.querySelector('#password');

  if (togglePassword && passwordInput) {
    togglePassword.addEventListener('click', function () {
      const isPassword = passwordInput.getAttribute('type') === 'password';
      passwordInput.setAttribute('type', isPassword ? 'text' : 'password');

      const icon = this.querySelector('i');
      if (icon) {
        icon.classList.toggle('bi-eye');
        icon.classList.toggle('bi-eye-slash');
      }
    });
  }

  // Aktifkan validasi bawaan Bootstrap
  const forms = document.querySelectorAll('.needs-validation');
  Array.from(forms).forEach(function (form) {
    form.addEventListener(
      'submit',
      function (event) {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      },
      false
    );
  });
});
