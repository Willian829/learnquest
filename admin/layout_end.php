  </div><!-- /content -->
</div><!-- /main -->

<script>
// Mobile sidebar toggle
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
}
// Confirm delete
function confirmDelete(form, name) {
  if (confirm('Excluir "' + name + '"? Esta ação não pode ser desfeita.')) {
    form.submit();
  }
}
</script>
</body>
</html>
