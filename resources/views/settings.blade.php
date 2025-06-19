@php
    
@endphp

@extends('layouts.authenticated')

@section('content')
    <div id="settings-root" class="px-4">
        <div class="py-4 flex items-center justify-between">
            <h3 class="text-2xl">App Settings</h3>
            <div></div>
        </div>
        <div id="file-format-section" class="relative mb-8 gap-4">
            {{-- @include('partials.modules.filenameFormat', ['mountContainerId' => 'settings-root']) --}}
            @livewire('settings.filename-format')
        </div>
        <div class="relative mb-8 gap-4">
            @livewire('settings.feature-control')
        </div>
        <div class="relative mb-8 gap-4">
            @livewire('settings.role-permission')
        </div>
    </div>
@endsection

@push('scripts')
<script type="module">
    // const validTags = ['project', 'urgent', 'feature', 'bug'];
    // const validMentions = ['alice', 'bob', 'carol', 'dave'];
    // const input = document.getElementById('tag-input');
    // const suggestions = document.getElementById('suggestions');

    // input.addEventListener('input', function(e) {
    //     const value = input.value;
    //     const cursor = input.selectionStart;
    //     // Find the last # or @ before the cursor
    //     const match = value.slice(0, cursor).match(/([#@])(\w*)$/);
    //     if (match) {
    //         const symbol = match[1];
    //         const query = match[2].toLowerCase();
    //         let list = symbol === '#' ? validTags : validMentions;
    //         let filtered = list.filter(item => item.startsWith(query));
    //         showSuggestions(filtered, symbol, match[0]);
    //     } else {
    //         suggestions.style.display = 'none';
    //     }
    // });

    // function showSuggestions(items, symbol, replaceText) {
    //     if (items.length === 0) {
    //         suggestions.style.display = 'none';
    //         return;
    //     }
    //     suggestions.innerHTML = '';
    //     items.forEach(item => {
    //         const li = document.createElement('li');
    //         li.textContent = symbol + item;
    //         li.style.padding = '4px 8px';
    //         li.style.cursor = 'pointer';
    //         li.onclick = function() {
    //             // Replace the last tag/mention with the selected one
    //             const cursor = input.selectionStart;
    //             const value = input.value;
    //             const before = value.slice(0, cursor).replace(new RegExp(replaceText + '$'), symbol + item + ' ');
    //             const after = value.slice(cursor);
    //             input.value = before + after;
    //             input.focus();
    //             suggestions.style.display = 'none';
    //         };
    //         suggestions.appendChild(li);
    //     });
    //     // Position suggestions below the input
    //     const rect = input.getBoundingClientRect();
    //     suggestions.style.left = rect.left + 'px';
    //     suggestions.style.top = (rect.bottom + window.scrollY) + 'px';
    //     suggestions.style.width = rect.width + 'px';
    //     suggestions.style.display = 'block';
    // }

    // // Hide suggestions on blur
    // input.addEventListener('blur', () => setTimeout(() => suggestions.style.display = 'none', 100));
</script>
@endpush