<div class="flex flex-col w-[210px] mt-2">
    <div>
        <img :src="Logo" alt="">
    </div>
    <x-layout.navItem imgSrc="Home" href="dashboard" activeNav={{true}}>Home</x-layout.navItem>
    <span class="text-sm ml-4 font-bold mt-4">PHOTOGRAPHY</span>
    <x-layout.navItem imgSrc="Portraits" href="dashboard" activeNav={{false}}>Portraits</x-layout.navItem>
    <x-layout.navItem imgSrc="Groups" href="dashboard" activeNav={{false}}>Group</x-layout.navItem>
    <x-layout.navItem imgSrc="Special Events" href="dashboard" activeNav={{false}}>Special Events</x-layout.navItem>
    <x-layout.navItem imgSrc="Promo Photos" href="dashboard" activeNav={{false}}>Promo Photos</x-layout.navItem>
    <span class="text-sm ml-4 font-bold mt-4">PROOFING</span>
    <x-layout.navItem imgSrc="Proofing" href="proofing" activeNav={{false}}>Proofing</x-layout.navItem>
    <span class="text-sm ml-4 font-bold mt-4">ADMIN TOOLS</span>
    <x-layout.navItem imgSrc="Manage Users" href="usersanage')" activeNav={{false}}>Manage Users</x-layout.navItem>
    <x-layout.navItem imgSrc="Reports" href="dashboard" activeNav={{false}}>Reports</x-layout.navItem>
</div>

<!-- @push('scripts')
  <script src="{{ asset('js/welcome.js') }}">

  </script>
@endpush -->
