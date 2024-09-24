<x-tabs.tabContainer>
    <x-tabs.tab id="Portrait">Portrait</x-tabs.tab>
    <x-tabs.tab id="Groups">Groups</x-tabs.tab>
    <x-tabs.tab id="SpecialEvent">Special Event</x-tabs.tab>
    <x-tabs.tab id="PromoPhotos">Promo Photos</x-tabs.tab>
</x-tabs.tabContainer>
<x-Tabs.tabContentContainer>
    <x-tabs.tabContent id="Portrait">
        <div class="flex flex-row gap-4">
            <div>
                <div class="mb-4">
                    <x-form.input.search/>
                </div>
                <x-form.input.text
                    placeholder="test"
                    labelText="Year"/>
                <x-form.input.text
                    placeholder="test"
                    labelText="View"/>
                <x-form.input.text
                    placeholder="test"
                    labelText="Classess"/>
            </div>
            <div>
                <div class="flex justify-between gap-2 pb-4">
                    <x-photography.portrait name="Harry Potter - 08A" active/>
                    <x-photography.portrait name="William Jones - 08A"/>
                    <x-photography.portrait name="Mia Martinez - 08A"/>
                    <x-photography.portrait name="Daniel Thompson - 08A"/>
                    <x-photography.portrait name="Ella White - 08A"/>
                </div>
                <div class="flex justify-between gap-2 pb-4">
                    <x-photography.portrait name="Harry Potter - 08A" active/>
                    <x-photography.portrait name="William Jones - 08A"/>
                    <x-photography.portrait name="Mia Martinez - 08A"/>
                    <x-photography.portrait name="Daniel Thompson - 08A"/>
                    <x-photography.portrait name="Ella White - 08A"/>
                </div>
                <div class="text-center mt-4 mb-4">Insert Pagination here</div>
            </div>
        </div>
    </x-tabs.tabContent>
    <x-tabs.tabContent id="Groups">
        <h1>Groups</h1>
    </x-tabs.tabContent>
    <x-tabs.tabContent id="SpecialEvent">
        <h1>Special Event</h1>
    </x-tabs.tabContent>
    <x-tabs.tabContent id="PromoPhotos">
        <h1>Promo Photos</h1>
    </x-tabs.tabContent>
</x-Tabs.tabContentContainer>
