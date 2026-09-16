@extends('layouts.authenticated')

@section('content')
    <div class="container3 p-4">
        @livewire('bulk-invite-users')
    </div>
@endsection

@push('scripts')
<script type="module">
    function initBulkInviteSchoolSelect2() {
        const $school = $('#bulk-invite-school');
        if (!$school.length || $school.hasClass('select2-hidden-accessible')) {
            return;
        }

        $school.select2({
            placeholder: 'Select School',
            allowClear: true,
            width: '100%',
        });

        $school.next('.select2-container').find('.select2-selection').addClass('border-neutral');

        $school.on('change.bulkInviteSchool', function () {
            const value = $(this).val();
            const wireId = $(this).closest('[wire\\:id]').attr('wire:id');
            const component = wireId ? Livewire.find(wireId) : null;
            if (!component) {
                return;
            }
            component.set('schoolId', value ? parseInt(value, 10) : null);
        });
    }

    document.addEventListener('DOMContentLoaded', initBulkInviteSchoolSelect2);
    document.addEventListener('livewire:initialized', initBulkInviteSchoolSelect2);
</script>
@endpush
