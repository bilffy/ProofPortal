@props(['label'=>'label', 'placeholder'=>'placeholder'])


<div class="flex flex-col mb-4">
    <label class="mb-2" for="">{{ $label }}</label>
    <input
        class="border rounded-md p-2 border-neutral"
        placeholder={{ $placeholder }}
        ref="input"
        {{-- value={{ $value }} --}}
    />
</div>