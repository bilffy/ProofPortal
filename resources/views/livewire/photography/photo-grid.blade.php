<div x-data="{ selectedImages: @entangle('selectedImages') }" class="grid grid-cols-5 gap-4">
    <x-photography.portrait event="'{{$PhotographyHelper::EV_SELECT_IMAGE}}'" payload="{imageKey: '{{$category}}_1'}" active="{{in_array($category.'_1', $selectedImages)}}" name="Harry Potter - 08A"/>
    <x-photography.portrait event="'{{$PhotographyHelper::EV_SELECT_IMAGE}}'" payload="{imageKey: '{{$category}}_2'}" active="{{in_array($category.'_2', $selectedImages)}}" name="William Jones - 08A"/>
    <x-photography.portrait event="'{{$PhotographyHelper::EV_SELECT_IMAGE}}'" payload="{imageKey: '{{$category}}_3'}" active="{{in_array($category.'_3', $selectedImages)}}" landscape name="Mia Martinez - 08A"/>
    <x-photography.portrait event="'{{$PhotographyHelper::EV_SELECT_IMAGE}}'" payload="{imageKey: '{{$category}}_4'}" active="{{in_array($category.'_4', $selectedImages)}}" name="Daniel Thompson - 08A"/>
    <x-photography.portrait event="'{{$PhotographyHelper::EV_SELECT_IMAGE}}'" payload="{imageKey: '{{$category}}_5'}" active="{{in_array($category.'_5', $selectedImages)}}" landscape name="Ella White - 08A"/>
    <x-photography.portrait event="'{{$PhotographyHelper::EV_SELECT_IMAGE}}'" payload="{imageKey: '{{$category}}_6'}" active="{{in_array($category.'_6', $selectedImages)}}" name="Harry Potter - 08A"/>
    <x-photography.portrait event="'{{$PhotographyHelper::EV_SELECT_IMAGE}}'" payload="{imageKey: '{{$category}}_7'}" active="{{in_array($category.'_7', $selectedImages)}}" landscape name="Ella White - 08A"/>
    <x-photography.portrait event="'{{$PhotographyHelper::EV_SELECT_IMAGE}}'" payload="{imageKey: '{{$category}}_8'}" active="{{in_array($category.'_8', $selectedImages)}}" name="William Jones - 08A"/>
    <x-photography.portrait event="'{{$PhotographyHelper::EV_SELECT_IMAGE}}'" payload="{imageKey: '{{$category}}_9'}" active="{{in_array($category.'_9', $selectedImages)}}" name="Mia Martinez - 08A"/>
    <x-photography.portrait event="'{{$PhotographyHelper::EV_SELECT_IMAGE}}'" payload="{imageKey: '{{$category}}_10'}" active="{{in_array($category.'_10', $selectedImages)}}" landscape name="Ella White - 08A"/>
    <x-photography.portrait event="'{{$PhotographyHelper::EV_SELECT_IMAGE}}'" payload="{imageKey: '{{$category}}_11'}" active="{{in_array($category.'_11', $selectedImages)}}" name="Daniel Thompson - 08A"/>
    <x-photography.portrait event="'{{$PhotographyHelper::EV_SELECT_IMAGE}}'" payload="{imageKey: '{{$category}}_12'}" active="{{in_array($category.'_12', $selectedImages)}}" name="Daniel Thompson - 08A"/>
    <x-photography.portrait event="'{{$PhotographyHelper::EV_SELECT_IMAGE}}'" payload="{imageKey: '{{$category}}_13'}" active="{{in_array($category.'_13', $selectedImages)}}" landscape name="Ella White - 08A"/>
    <x-photography.portrait event="'{{$PhotographyHelper::EV_SELECT_IMAGE}}'" payload="{imageKey: '{{$category}}_14'}" active="{{in_array($category.'_14', $selectedImages)}}" name="Ella White - 08A"/>
</div>