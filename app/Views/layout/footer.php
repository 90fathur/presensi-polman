<footer class="mt-5 mb-3 text-center text-muted small">
    <strong>Pemerintah Kabupaten Polewali Mandar</strong> &copy; 2026 - Dinas Kominfo SP
</footer>
<script>
    const btnToggle = document.getElementById('toggleSidebar');
    const sidebar = document.querySelector('.sidebar');

    if (btnToggle) {
        btnToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>