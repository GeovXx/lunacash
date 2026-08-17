{{-- Applied before paint to avoid a flash of the wrong theme. No dependency added: plain JS. --}}
<script>
    (function () {
        const stored = localStorage.getItem('lunacash-theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const isDark = stored ? stored === 'dark' : prefersDark;

        document.documentElement.classList.toggle('dark', isDark);
    })();
</script>
