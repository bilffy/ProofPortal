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
                    <x-table.cell>
                        @php
                            if ($user->status == 'active') :
                                $badge = 'gray';
                            elseif ($user->status == 'invited') :
                                $badge = 'blue';
                            elseif ($user->status == 'disabled') :
                                $badge = 'red';
                            else :
                                $badge = 'green';
                            endif;
                        @endphp
                        <x-badge text="{{ ucfirst($user->status) }}" badge="{{ $badge }}" />
                    </x-table.cell>
                    <x-table.cell class="w-[100px] relative">
                        <x-button.link>
                            <x-icon class="px-2 cursor-pointer" icon="ellipsis" data-dropdown-toggle="userDropdownAction-{{ $user->id }}" />
                        </x-button.link>
                        <!-- Dropdown menu -->
                        <div id="userDropdownAction-{{ $user->id }}" class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-44 dark:bg-gray-700">
                            <ul class="py-2 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownDefaultButton-{{ $user->id }}">
                                <li>
                                    <a href="#" data-invite-route="{{ route("invite.single", ["id" => $user->id ]) }}"  data-modal-target="inviteModal" data-modal-toggle="inviteModal" data-user-id="{{ $user->id }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Invite</a>
                                </li>
                                <li>
                                    <a href="#"  class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Edit</a>
                                </li>
                            </ul>
                        </div>
                    </x-table.cell>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Main modal -->
<x-modal.base id="inviteModal" title="Invite new User" body="components.modal.body" footer="components.modal.footer">
    <x-slot name="body">
        <x-modal.body>
            <p id="modal-email"></p>
        </x-modal.body>
    </x-slot>
    <x-slot name="footer">
        <x-modal.footer>
            <x-button.secondary data-modal-hide="inviteModal">Cancel</x-button.secondary>
            <x-button.primary id="accept-invite" data-invite-route="" data-modal-hide="inviteModal">Invite</x-button.primary>
        </x-modal.footer>
    </x-slot>
</x-modal.base>

@push('scripts')
<script type="module">
    window.addEventListener("load", function () {
        $('[data-modal-toggle="inviteModal"]').on('click', function() {
            const email = $(this).closest('tr').find('td:first-child').text().trim();
            const fname = $(this).closest('tr').find('td:nth-child(2)').text().trim();
            const lname = $(this).closest('tr').find('td:nth-child(3)').text().trim();
            $('#accept-invite').attr('data-invite-route', $(this).attr('data-invite-route'));
            $('#modal-email').html("Are you sure you want to invite <b>" + fname + " " + lname + " (" + email + ")?</b>");
        });
    
        $('#accept-invite').on('click', function() {
            window.location.href = $(this).attr('data-invite-route');
        });
    }, false);
</script>
@endpush