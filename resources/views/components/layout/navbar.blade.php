<div class="flex flex-col w-[210px] mt-2">
    <div>
        <img :src="Logo" alt="">
    </div>
    <NavItem imgSrc="Home" :href="getRoute('dashboard')" :activeNav="activeNav === HOME" :isBlade="isBladeRender">Home</NavItem>
    <span class="text-sm ml-4 font-bold mt-4">PHOTOGRAPHY</span>
    <NavItem imgSrc="Portraits" :href="getRoute('dashboard')" :activeNav="activeNav === PORTRAITS" :isBlade="isBladeRender">Portraits</NavItem>
    <NavItem imgSrc="Groups" :href="getRoute('dashboard')" :activeNav="activeNav === GROUP" :isBlade="isBladeRender">Group</NavItem>
    <NavItem imgSrc="Special Events" :href="getRoute('dashboard')" :activeNav="activeNav === SPECIAL_EVENTS" :isBlade="isBladeRender">Special Events</NavItem>
    <NavItem imgSrc="Promo Photos" :href="getRoute('dashboard')" :activeNav="activeNav === PROMO_PHOTOS" :isBlade="isBladeRender">Promo Photos</NavItem>
    <span class="text-sm ml-4 font-bold mt-4">PROOFING</span>
    <NavItem imgSrc="Proofing" :href="getRoute('proofing')" :activeNav="activeNav === PROOFING" :isBlade="true" >Proofing</NavItem>
    <span class="text-sm ml-4 font-bold mt-4">ADMIN TOOLS</span>
    <NavItem imgSrc="Manage Users" :href="getRoute('users.manage')" :activeNav="activeNav === MANAGE_USERS" :isBlade="isBladeRender">Manage Users</NavItem>
    <NavItem imgSrc="Reports" :href="getRoute('dashboard')" :activeNav="activeNav === REPORTS" :isBlade="isBladeRender">Reports</NavItem>
</div>

<!-- @push('scripts')
  <script src="{{ asset('js/welcome.js') }}">

  </script>
@endpush -->
