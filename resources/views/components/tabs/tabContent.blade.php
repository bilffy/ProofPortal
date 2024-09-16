@props(['id' => 'idTab'])

<div class="hidden p-4 " id="{{$id}}" role="tabpanel" aria-labelledby="profile-tab">
    {{ $slot }}
</div>