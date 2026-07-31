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
  const sidebarBackdrop = document.getElementById('sidebarBackdrop');

  function bukaSidebar() {
    sidebarMenu.classList.remove('d-none');
    sidebarBackdrop.classList.remove('d-none');
  }

  function tutupSidebar() {
    sidebarMenu.classList.add('d-none');
    sidebarBackdrop.classList.add('d-none');
  }

  if (sidebarToggle && sidebarMenu && sidebarBackdrop) {
    sidebarToggle.addEventListener('click', function () {
      if (sidebarMenu.classList.contains('d-none')) {
        bukaSidebar();
      } else {
        tutupSidebar();
      }
    });

    // Tutup sidebar saat area gelap (backdrop) di-klik
    sidebarBackdrop.addEventListener('click', tutupSidebar);

    // Tutup sidebar otomatis saat salah satu menu diklik (khusus layar mobile)
    sidebarMenu.querySelectorAll('a.nav-link').forEach(function (link) {
      link.addEventListener('click', function () {
        if (window.innerWidth < 992) {
          tutupSidebar();
        }
      });
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
