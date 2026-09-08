
<script>
function toggleSidebar() {
    var sidebar = document.getElementById("sidebar");
    var overlay = document.getElementById("overlay");
    
    if (sidebar && overlay) {
        sidebar.classList.toggle("open");
        overlay.classList.toggle("show");
    }
}
</script>
</body>
</html>
