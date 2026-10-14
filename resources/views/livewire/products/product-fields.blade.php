<div>
    <p class="text-sm text-gray-600 mb-4">
        Drag fields from "Available" into "Assigned" to show them on the ordering page for this product, and drag
        within "Assigned Fields" to set the order they appear in. Changes are only saved when you click
        "Save Changes" or "Create Product" below.
    </p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h5 class="text-sm font-semibold text-gray-700 mb-2">Available Fields</h5>
            <ul id="pf-available" data-pf-list="available" class="pf-sortable-list min-h-[160px] border border-dashed border-neutral rounded-md p-2 space-y-2 bg-gray-50">
                @foreach ($availableFields as $field)
                    <li wire:key="available-{{ $field->id }}" data-field-id="{{ $field->id }}" class="pf-field-item bg-white border border-neutral rounded-md px-3 py-2 text-sm cursor-move flex items-center justify-between">
                        <span>{{ $field->label }}</span>
                        <span class="text-xs text-neutral">{{ \App\Models\CustomField::TYPES[$field->type] ?? $field->type }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
        <div>
            <h5 class="text-sm font-semibold text-gray-700 mb-2">Assigned Fields <span class="text-neutral font-normal">(shown in this order)</span></h5>
            <ul id="pf-assigned" data-pf-list="assigned" class="pf-sortable-list min-h-[160px] border border-dashed border-primary rounded-md p-2 space-y-2 bg-white">
                @foreach ($assignedFields as $field)
                    <li wire:key="assigned-{{ $field->id }}" data-field-id="{{ $field->id }}" class="pf-field-item bg-white border border-neutral rounded-md px-3 py-2 text-sm cursor-move flex items-center justify-between">
                        <span>{{ $field->label }}</span>
                        <span class="text-xs text-neutral">{{ \App\Models\CustomField::TYPES[$field->type] ?? $field->type }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

@assets
<script src="{{ URL::asset('proofing-assets/js/drag_drop_jquery-ui.min.js') }}"></script>
<style>
    .pf-sortable-placeholder {
        border: 2px dashed #9CA3AF;
        border-radius: 0.375rem;
        height: 2.5rem;
        list-style: none;
    }
</style>
@endassets

{{-- The Livewire "run once this component actually mounts" script block
     below replaces the old push-to-stack approach, which only ever fired
     for content present at the very first full page load. Manage Fields
     now only appears later, after an Add Product / Edit click re-renders
     the parent component over AJAX, so the old approach never ran for it;
     this directive pair is the Livewire-native fix for exactly this
     "nested, added-later" case. --}}
@script
<script>
(function () {
    function getAssignedIds($) {
        return $('#pf-assigned').find('> li').map(function () {
            return $(this).data('field-id');
        }).get();
    }

    // No server call here: hand the new order to the parent form's
    // assignedFieldIds (third argument false = update locally, don't send a
    // request). It is submitted along with the form on Create / Save.
    function persistOrder($) {
        $wire.$parent.$set('assignedFieldIds', getAssignedIds($), false);
    }

    function initSortables() {
        if (typeof window.jQuery === 'undefined' || !window.jQuery.fn || !window.jQuery.fn.sortable) {
            return false;
        }
        var $ = window.jQuery;
        var lists = $('#pf-available, #pf-assigned');
        if (!lists.length) {
            return false;
        }

        // Destroy first in case this is a re-init after a Livewire morph
        // replaced the <li> markup (same field re-render pattern used for
        // the DataTables + Livewire integration on the bulk-invite page).
        lists.each(function () {
            if ($(this).hasClass('ui-sortable')) {
                $(this).sortable('destroy');
            }
        });

        lists.sortable({
            connectWith: '.pf-sortable-list',
            placeholder: 'pf-sortable-placeholder',
            items: '> li',
            stop: function () {
                persistOrder($);
            },
        }).disableSelection();

        return true;
    }

    // initSortables() needs the jQuery UI asset above to have finished
    // loading; the asset block runs before this script block, but poll
    // briefly too in case of a slow network load.
    var attempts = 0;
    var timer = setInterval(function () {
        attempts++;
        if (initSortables() || attempts > 20) {
            clearInterval(timer);
        }
    }, 150);

    if (window.Livewire && typeof window.Livewire.hook === 'function') {
        window.Livewire.hook('morph.updated', initSortables);
    }
})();
</script>
@endscript
