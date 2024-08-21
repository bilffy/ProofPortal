@props(['value'=>'labssselsss', 'id'=>'id'])

<div class="flex gap-1">
    <input
        type="checkbox"
        value={{ $value }}
        name={{ $value }}
        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
    >
    </input>
    <label for={{ $id }}>{{$value}}</label>
</div>