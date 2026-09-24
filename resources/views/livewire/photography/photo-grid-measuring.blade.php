{{-- Query-free render pass: shown for the single instant between the lazy-mount
     completing and the real image query running. The empty grid below has the
     exact same wrapper markup/classes as the real grid in photo-grid.blade.php,
     so the browser resolves the same repeat(auto-fit,195px) column count here -
     accurately, since it IS this component's real container width - without us
     ever having queried a single image yet. x-init reports that count once via
     $wire.setDynamicPerPage(), which is what unlocks the real render(). --}}
<div class="w-full text-center" x-data="{}" x-init="
    $nextTick(() => {
        const probe = $el.querySelector('[data-photo-grid-probe]');
        if (!probe) { return; }
        const resolved = window.getComputedStyle(probe).gridTemplateColumns.trim();
        const tracks = resolved.length ? resolved.split(' ').filter(Boolean) : [];
        const allPx = tracks.length > 0 && tracks.every((t) => t.indexOf('px') !== -1);
        $wire.setDynamicPerPage(allPx ? tracks.length : 0);
    });
">
    <div class="w-full flex justify-center py-12">
        <x-spinner.icon :size="10"/>
    </div>
    <div class="grid grid-cols-[repeat(auto-fit,195px)] gap-auto" data-photo-grid-probe style="visibility:hidden; height:0; overflow:hidden;"></div>
</div>
