<div>
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
</div>
