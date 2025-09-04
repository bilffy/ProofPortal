@props(['icon' => 'ban'])

<i {{ $attributes->merge([ 'class' => "fa fa-$icon" ]) }}></i>