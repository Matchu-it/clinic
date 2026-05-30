<?php $isLoggedIn = Auth::check(); ?>

<?php if ($isLoggedIn): ?>
        </main><!-- /.main-content -->
    </div><!-- /.content-wrapper -->
</div><!-- /.wrapper -->
<?php else: ?>
</div><!-- /.auth-wrapper -->
<?php endif; ?>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Sidebar toggle
const toggle = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
if (toggle && sidebar) {
    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        document.querySelector('.content-wrapper')?.classList.toggle('expanded');
    });
}

// Auto-dismiss alerts
document.querySelectorAll('.alert-auto-dismiss').forEach(el => {
    setTimeout(() => {
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 300);
    }, 4000);
});
</script>
</body>
</html>
