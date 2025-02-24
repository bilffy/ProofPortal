<div>
    <div class="py-4 flex items-center justify-between">
        <h3 class="text-2xl">Manage Users</h3>
        <div class="flex justify-center">
            <div class="relative">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <x-icon icon="search"/>
                </div>
                <input
                    id="user-search"
                    wire:model.live.debounce.300ms="search"
                    wire:keydown.enter="performSearch"
                    type="search"
                    class="block w-full p-4 py-2 ps-10 text-sm text-gray-900 rounded-lg bg-neutral-300 border-0"
                    placeholder="Search..." 
                />
            </div>
            @can($PermissionHelper->toPermission($PermissionHelper::ACT_CREATE, $PermissionHelper::SUB_USER))
                <div class="ml-4 mr-4 border-r-2 border-[#D9DDE2] my-3"></div>
                <x-button.primary onclick="window.location='{{ route('users.create') }}'">Add New User</x-button.primary>
            @endcan
        </div>
    </div>

    <div class="relative">
        <table class="w-full text-sm text-left rtl:text-right">
            <thead>
                <tr>
                    <x-table.headerCell id="email" filterable="{{false}}" isLivewire="{{true}}" wireEvent="sortColumn('email')" sortBy="{{$sortBy}}" sortDirection="{{$sortDirection}}">Email</x-table.headerCell>
                    <x-table.headerCell class="whitespace-nowrap" id="firstname" filterable="{{false}}" isLivewire="{{true}}" wireEvent="sortColumn('firstname')" sortBy="{{$sortBy}}" sortDirection="{{$sortDirection}}">First Name</x-table.headerCell>
                    <x-table.headerCell class="whitespace-nowrap" id="lastname" filterable="{{false}}" isLivewire="{{true}}" wireEvent="sortColumn('lastname')" sortBy="{{$sortBy}}" sortDirection="{{$sortDirection}}">Last Name</x-table.headerCell>
                    <x-table.headerCell id="role" isLivewire="{{true}}" wireEvent="sortColumn('role')" filterModel="filterBy.role" :filterOptions="$roleOptions" sortBy="{{$sortBy}}" sortDirection="{{$sortDirection}}">Role</x-table.headerCell>
                    <x-table.headerCell id="organization" isLivewire="{{true}}" wireEvent="sortColumn('organization')" sortBy="{{$sortBy}}" sortDirection="{{$sortDirection}}">Franchise/School</x-table.headerCell>
                    <x-table.headerCell id="status" isLivewire="{{true}}" wireEvent="sortColumn('status')" filterModel="filterBy.status" :filterOptions="$statusOptions" sortBy="{{$sortBy}}" sortDirection="{{$sortDirection}}">Status</x-table.headerCell>
                    <x-table.headerCell class="w-[40px]" sortable="{{false}}" filterable="{{false}}"></x-table.headerCell>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        @php
                            $status = $user->status;
                            $badge = $UserStatusHelper->getBadge($status);
                            //$userId = $user->id;
                            $userId = $user->getHashedIdAttribute();
                            $inviteRoute = route("invite.single", ["id" => $userId ]);
                            $impersonateRoute = route("impersonate.store", ["id" => $userId ]);
                            $checkStatusRoute = route('invite.check-user-status', ['id' => $userId]);
                            $role = is_null($user->getRole()) ? '' : $user->getRole();
                            $dropDownId = "optionsDropdown" . $userId;
                        @endphp
                        <x-table.cell>{{ $user->email }}</x-table.cell>
                        <x-table.cell>{{ $user->firstname }}</x-table.cell>
                        <x-table.cell>{{ $user->lastname }}</x-table.cell>
                        <x-table.cell>{{ $role }}</x-table.cell>
                        <x-table.cell>{{ $user->getSchoolOrFranchise() }}</x-table.cell>
                        <x-table.cell>
                            <x-badge text="{{ ucfirst($status) }}" badge="{{ $badge }}" />
                        </x-table.cell>
                        <x-table.cell class="w-[40px] relative">
                            <x-table.userOptions
                                id="options_{{$userId}}"
                                dropDownId="{{$dropDownId}}"
                                role="{{$role}}"
                                userId="{{$userId}}"
                                userEmail="{{$user->email}}"
                                status="{{$status}}"
                                inviteRoute="{{$inviteRoute}}"
                                impersonateRoute="{{ $impersonateRoute }}"
                                checkStatusRoute="{{ $checkStatusRoute }}"
                                user="{{ $user }}"
                            />
                        </x-table.cell>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center p-4">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Main modal -->
    <x-modal.base id="inviteModal" title="{{ $configMessages['invite_user']['title'] }}" body="components.modal.body" footer="components.modal.footer">
        <x-slot name="body">
            <x-modal.body>
                <p id="modal-email"></p>
            </x-modal.body>
        </x-slot>
        <x-slot name="footer">
            <x-modal.footer>
                <x-button.secondary data-modal-hide="inviteModal">Cancel</x-button.secondary>
                <x-button.primary  id="accept-invite" data-invite-route="">Invite</x-button.primary>
            </x-modal.footer>
        </x-slot>
    </x-modal.base>

    <x-modal.base id="impersonateModal" title="{{ $configMessages['impersonate']['title'] }}" body="components.modal.body" footer="components.modal.footer">
        <x-slot name="body">
            <x-modal.body>
                <p id="impersonate-modal-email"></p>
            </x-modal.body>
        </x-slot>
        <x-slot name="footer">
            <x-modal.footer>
                <x-button.secondary data-modal-hide="impersonateModal">Cancel</x-button.secondary>
                <x-button.primary  id="accept-impersonate" data-impersonate-route="">Yes</x-button.primary>
            </x-modal.footer>
        </x-slot>
    </x-modal.base>

    <div class="w-full flex items-center justify-center py-4">
        {{ $users->onEachSide(1)->links('vendor.livewire.pagination') }}
    </div>

    <script type="module">

        async function checkUserStatus(route) {
            const response = await fetch(route, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            
            return response.json();
        }
        
        function initialScripts() {
            $('[data-modal-toggle="inviteModal"]').on('click', async function () {

                $('#modal-email').html(`<x-spinner.button />`);
                const userId = $(this).attr('data-user-id')
                const inviteCheckUserStatusRoute = $(this).attr('data-invite-check-user-status-route');
                const checkStatus = await checkUserStatus(inviteCheckUserStatusRoute);

                if (checkStatus.status === 'active') {
                    $('#accept-invite').html("Ok");
                    $('#modal-email').html("{{ $configMessages['check_status']['message'] }}")
                    $('#accept-invite').on('click', function() {
                        $(this).@disabled(true);
                        $(this).html(`<x-spinner.button />`);
                        // reload the current page
                        window.location.reload();
                    });
                } else {
                    const email = $(this).closest('tr').find('td:first-child').text().trim();
                    const fname = $(this).closest('tr').find('td:nth-child(2)').text().trim();
                    const lname = $(this).closest('tr').find('td:nth-child(3)').text().trim();
                    $('#accept-invite').attr('data-invite-route', $(this).attr('data-invite-route'));
                    $('#modal-email').html("{{ $configMessages['invite_user']['message'] }} <b>" + fname + " " + lname + " (" + email + ")?</b>");

                    $('#accept-invite').on('click', function() {
                        $(this).@disabled(true);
                        $(this).html(`<x-spinner.button />`);
                        window.location.href = $(this).attr('data-invite-route');
                    });
                }
            });
        
            $('[data-modal-toggle="impersonateModal"]').on('click', function() {
                const email = $(this).closest('tr').find('td:first-child').text().trim();
                const fname = $(this).closest('tr').find('td:nth-child(2)').text().trim();
                const lname = $(this).closest('tr').find('td:nth-child(3)').text().trim();
                $('#accept-impersonate').attr('data-impersonate-route', $(this).attr('data-impersonate-route'));
                $('#impersonate-modal-email').html("{{ $configMessages['impersonate']['message'] }}  <b>" + fname + " " + lname + " (" + email + ")?</b>");
            });

            $('#accept-impersonate').on('click', function() {
                $(this).@disabled(true);
                $(this).html(`<x-spinner.button />`);
                window.location.href = $(this).attr('data-impersonate-route');
            });
        }

        function debounce(func, delay) {
            let timer;
            return function (...args) {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    func.apply(this, args);
                }, delay);
            };
        }

        window.addEventListener("load", initialScripts, false);
        window.addEventListener('livewire:init', () => {
            const debouncedInitFlow = debounce(() => {
                initFlowbite();
                initialScripts();
            }, 300);
            Livewire.hook('morph.updated', ({ el, component }) => {
                debouncedInitFlow();
            });
        });

        window.checkUserStatus = checkUserStatus;
    </script>
</div>
