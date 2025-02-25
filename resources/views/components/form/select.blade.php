@props(['context' => '', 'options' => [], 'required' => false, 'value' => null])

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
        'class' => "bg-gray-50 border border-neutral rounded-md block p-2.5"
    ]) }}
>
    @foreach ($options as $id => $name)
        <option @if($value == $id): selected @endif value="{{$id}}">{{$name}}</option>
    @endforeach
</select>