<div class="relative overflow-x-auto">
    <table class="w-full text-sm text-left rtl:text-right">
        <thead>
            <tr>
                <x-table.headerCell id="email" filterable="{{false}}">Email</x-table.headerCell>
                <x-table.headerCell id="firstname" filterable="{{false}}">First Name</x-table.headerCell>
                <x-table.headerCell id="lastname" filterable="{{false}}">Last Name</x-table.headerCell>
                <x-table.headerCell id="role">Role</x-table.headerCell>
                <x-table.headerCell id="organization">Franchise/School</x-table.headerCell>
                <x-table.headerCell id="status">User Status</x-table.headerCell>
                <x-table.headerCell class="w-[60px]" sortable="{{false}}" filterable="{{false}}"></x-table.headerCell>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <x-table.cell>{{ $user->email }}</x-table.cell>
                    <x-table.cell>{{ $user->firstname }}</x-table.cell>
                    <x-table.cell>{{ $user->lastname }}</x-table.cell>
                    <x-table.cell>[role]</x-table.cell>
                    <x-table.cell>[school/franchise]</x-table.cell>
                    <x-table.cell>[status]</x-table.cell>
                    <x-table.cell class="w-[100px]">
                        <x-button.link>
                            <x-icon class="px-2" icon="ellipsis" />
                        </x-button.link>
                    </x-table.cell>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
