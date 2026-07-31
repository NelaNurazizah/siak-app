/**
 * assets/js/main.js
 * JavaScript umum yang dipakai di seluruh halaman dashboard
 * (admin, dosen, mahasiswa).
 *
 * Saat ini menangani:
 * - Toggle sidebar pada tampilan mobile
 */

document.addEventListener('DOMContentLoaded', function () {
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebarMenu = document.getElementById('sidebarMenu');

  if (sidebarToggle && sidebarMenu) {
    sidebarToggle.addEventListener('click', function () {
      sidebarMenu.classList.toggle('d-none');
    });
  }

  // Auto-dismiss alert setelah 5 detik (jika ada)
  const autoAlerts = document.querySelectorAll('.alert-auto-dismiss');
  autoAlerts.forEach(function (alertEl) {
    setTimeout(function () {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
      bsAlert.close();
    }, 5000);
  });

  // Aktifkan validasi bawaan Bootstrap untuk semua form CRUD (modal tambah/edit, dsb)
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
