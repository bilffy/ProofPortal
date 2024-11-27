@extends('layouts.authenticated')

@section('content')
    <div class="container3 p-4">
        <x-tabs.tabContainer>
            <x-tabs.tab id="configure">Configure</x-tabs.tab>
            <x-tabs.tab id="portrait">Portrait</x-tabs.tab>
            <x-tabs.tab id="groups">Groups</x-tabs.tab>
            <x-tabs.tab id="others">Others</x-tabs.tab>
            <div class="absolute right-2 h-full flex align-middle justify-center items-center gap-4">
                <x-button.primary hollow class="border-none">Clear Selection</x-button.primary>
                <x-button.primary>Download Selected</x-button.primary>
            </div>
        </x-tabs.tabContainer>
        <x-tabs.tabContentContainer>
            <x-tabs.tabContent id="configure">
                <h1>Configure</h1>
            </x-tabs.tabContent>
            <x-tabs.tabContent id="portrait">
                <div class="flex flex-row gap-4">
                    <div class="w-[200px]">
                        <div class="mb-4">
                            <x-form.input.search/>
                        </div>
                        <x-form.input.text
                            placeholder="test"
                            class="mb-4"
                            label="Year"/>
                        <x-form.input.text
                            placeholder="test"
                            class="mb-4"
                            label="View"/>
                        <x-form.input.text
                            placeholder="test"
                            class="mb-4"
                            label="Classess"/>
                    </div>


                    <div class="grid grid-cols-5 gap-4">
                        <x-photography.portrait name="Harry Potter - 08A" active/>
                        <x-photography.portrait name="William Jones - 08A"/>
                        <x-photography.portrait landscape name="Mia Martinez - 08A"/>
                        <x-photography.portrait name="Daniel Thompson - 08A"/>
                        <x-photography.portrait landscape name="Ella White - 08A"/>
                        <x-photography.portrait name="Harry Potter - 08A" active/>
                        <x-photography.portrait landscape name="Ella White - 08A"/>
                        <x-photography.portrait name="William Jones - 08A"/>
                        <x-photography.portrait name="Mia Martinez - 08A"/>
                        <x-photography.portrait landscape name="Ella White - 08A"/>
                        <x-photography.portrait name="Daniel Thompson - 08A"/>
                        <x-photography.portrait name="Daniel Thompson - 08A"/>
                        <x-photography.portrait landscape name="Ella White - 08A"/>
                        <x-photography.portrait name="Ella White - 08A"/>
                    </div>

                </div>
                <div class="text-center mt-4 mb-4">Insert Pagination here</div>
            </x-tabs.tabContent>
            <x-tabs.tabContent id="groups">
                <h1>Groups</h1>
            </x-tabs.tabContent>
            <x-tabs.tabContent id="others">
                <h1>Others</h1>
            </x-tabs.tabContent>
        </x-tabs.tabContentContainer>
    </div>
@endsection
