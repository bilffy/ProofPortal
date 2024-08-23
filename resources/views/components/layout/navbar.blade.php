<div class="flex flex-col w-[210px] mt-2">
    <div>
        <img :src="Logo" alt="">
    </div>
    <x-layout.navItem navIcon="home" href="dashboard" activeNav={{true}}>Home</x-layout.navItem>
    <span class="text-sm text-neutral-600 ml-4 font-bold mt-4">PHOTOGRAPHY</span>
    <x-layout.navItem navIcon="user" href="dashboard" activeNav={{false}}>Portraits</x-layout.navItem>
    <x-layout.navItem navIcon="users" href="dashboard" activeNav={{false}}>Group</x-layout.navItem>
    <x-layout.navItem navIcon="graduation-cap" href="dashboard" activeNav={{false}}>Special Events</x-layout.navItem>
    <x-layout.navItem navIcon="camera" href="dashboard" activeNav={{false}}>Promo Photos</x-layout.navItem>
    <span class="text-sm text-neutral-600 ml-4 font-bold mt-4">PROOFING</span>
    <x-layout.navItem navIcon="th" href="proofing" activeNav={{false}}>Proofing</x-layout.navItem>
    <span class="text-sm text-neutral-600 ml-4 font-bold mt-4">ADMIN TOOLS</span>
    <x-layout.navItem navIcon="user-plus" href="usersanage')" activeNav={{false}}>Manage Users</x-layout.navItem>
    <x-layout.navItem navIcon="list-ul" href="dashboard" activeNav={{false}}>Reports</x-layout.navItem>
</div>

<!-- @push('scripts')
  <script src="{{ asset('js/welcome.js') }}">

  </script>
@endpush -->
