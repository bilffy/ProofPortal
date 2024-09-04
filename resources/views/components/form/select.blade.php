@props(['context' => ''])

<label for="{{ $context }}" class="block mb-2">
    {{ $slot }}
</label>
<select id="{{ $context }}" class="bg-gray-50  border  border-neutral rounded-md block w-full p-2.5">
    <option>option 1</option>
    <option>option 2</option>
    <option>option 3</option>
    <option>option 4</option>
</select>