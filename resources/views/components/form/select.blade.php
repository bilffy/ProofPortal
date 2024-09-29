@props(['context' => '', 'options' => [], 'required' => false])

<label id="select_{{$context}}_label" for="{{ $context }}" class="block mb-2">
    {{ $slot }}
    @if ($required)
        <span class="text-alert">*</span>
    @endif
</label>
<select 
    {{ $attributes->merge([
        'id' => "select_{$context}",
        'name' => $context,
        'class' => "bg-gray-50 border border-neutral rounded-md block w-full p-2.5"
    ]) }}
>
    @foreach ($options as $id => $name)
        <option value="{{$id}}">{{$name}}</option>
    @endforeach
</select>