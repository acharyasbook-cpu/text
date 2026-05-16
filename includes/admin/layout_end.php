    </main>
  </div>
</div>
<div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>
<script>
document.getElementById('sidebarToggle')?.addEventListener('click', function () {
  document.getElementById('adminSidebar')?.classList.toggle('-translate-x-full');
  document.getElementById('sidebarOverlay')?.classList.toggle('hidden');
});
document.getElementById('sidebarOverlay')?.addEventListener('click', function () {
  document.getElementById('adminSidebar')?.classList.add('-translate-x-full');
  this.classList.add('hidden');
});
(function () {
  var meta = document.querySelector('meta[name="csrf-token"]');
  if (!meta || !meta.content) return;
  document.querySelectorAll('form[method="post"]').forEach(function (form) {
    if (form.querySelector('input[name="_csrf"]')) return;
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = '_csrf';
    input.value = meta.content;
    form.appendChild(input);
  });
})();
</script>
</body>
</html>
