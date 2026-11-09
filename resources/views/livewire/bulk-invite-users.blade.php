<div class="bulk-invite-page pb-8">
    <div class="py-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-2xl">Bulk Invite</h3>
            <p class="text-sm text-xs mt-1">
                @if ($showSchoolSelector && $lockedSchoolName === '') 
                    Assign the school, upload the user details, and then send the invitations.
                @else
                    Upload the user details, and then send the invitations.
                @endif
            </p>
        </div>
        <!-- <button
            type="button"
            onclick="window.location='{{ route('users') }}'"
            class="inline-flex items-center gap-2 rounded-lg border border-neutral-400 bg-white px-4 py-2 text-sm font-semibold text-gray-800 transition-all hover:bg-neutral-200"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Back to Users
        </button> -->
    </div>

    <div class="relative w-full space-y-5">
        @if ($successMessage)
            <div class="rounded-xl border border-success-300 bg-success-100 px-4 py-3 text-sm text-success" role="status">
                {{ $successMessage }}
            </div>
        @endif

        @if (!empty($topErrors))
            <div role="alert" class="biu-errors">
                <p class="font-semibold mb-1">Please fix the following before inviting:</p>
                <ul class="list-disc pl-5 mb-0">
                    @foreach ($topErrors as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 items-stretch biu-upload-import-grid">
        {{-- Upload users --}}
        <section class="biu-box flex flex-col">
            <div class="biu-box-head">
                <h4 class="biu-box-title flex items-center gap-2">
                    <i class="fa fa-upload"></i>
                    Upload Users
                </h4>
            </div>
            <div class="biu-box-body flex-1 flex flex-col">

                @if ($showSchoolSelector)
                    <div class="mb-4">
                        <div class="rounded-xl border border-neutral-300 p-4" style="background:#fff;">
                            <div class="mb-4">
                                <h4 class="biu-box-title mb-0.5">Assign School</h4>
                                <p class="mt-5 text-xs mb-0">Select the school the users are to be invited to.</p>
                            </div>
                            <div class="max-w-xl">
                                <label for="bulk-invite-school" class="mb-2 block text-sm font-semibold text-gray-800">
                                    Select School <span class="text-alert">*</span>
                                </label>
                                <div wire:ignore class="w-full">
                                    {{-- Options are loaded via AJAX search (schools.search) instead of
                                         rendering every school up front - with 10,000+ schools, that made
                                         the page huge and select2's client-side filtering slow to type into. --}}
                                    <select id="bulk-invite-school" class="bg-white border border-neutral-400 rounded-lg block w-full p-2.5">
                                        <option value="">Select School</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if (!$showSchoolSelector && $lockedSchoolName !== '')
                    <div class="mb-4">
                        <div class="rounded-xl border border-neutral-300 p-4" style="background:#fff;">
                            <div style="display:flex;flex-wrap:wrap;align-items:baseline;gap:2px 8px;">
                                <h4 class="biu-box-title mb-0.5">School Assigned :</h4>
                                <h4 class="biu-box-title mb-0" style="min-width:0;word-break:break-word;overflow-wrap:anywhere;">{{ $lockedSchoolName }}</h4>
                            </div>
                            <div style="font-weight:500;margin-top:0.25rem;">Imported users will be assigned to this school.</div>
                        </div>
                    </div>
                @endif

            {{-- Column format, designated roles and sample download --}}
            <div class="flex-1 flex flex-col rounded-xl border border-neutral-300 p-4" style="background:#fff;">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div
                        class="bulk-invite-format-row"
                        style="display:flex;flex-direction:column;align-items:flex-start;gap:4px;min-width:0;max-width:100%;font-size:14px;line-height:1.4;color:#273444;"
                    >
                        <h4 class="biu-box-title mb-0.5">Column Format:</h4>
                        <span class="bulk-invite-format-code" style="display:inline-block;box-sizing:border-box;max-width:100%;padding:4px 8px;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:12px;line-height:1.45;color:#111827;background:#ffffff;border-radius:6px;overflow-wrap:anywhere;word-break:break-word;">firstname, lastname, email address, user role</span>
                    </div>

                    <a
                        href="#"
                        wire:click.prevent="downloadSampleCsv"
                        style="display:inline-flex;align-items:center;justify-content:center;gap:8px;width:max-content;max-width:100%;padding:8px 14px;font-size:13px;font-weight:600;line-height:1.2;text-decoration:none;background:#005890;color:#fff;border-radius:8px;box-shadow:0 1px 2px rgba(15, 23, 42, 0.12);white-space:nowrap;"
                    >
                        Download Sample CSV
                    </a>
                </div>

                <hr class="w-full my-3" style="border-color:#cbd5e1;">

                <p class="mb-0" style="font-size:13px;color:#273444;">
                    <strong style="font-weight:600;">Designated User Roles &ndash; </strong>
                    <span style="font-weight:500;">{{ implode(', ', $roleOptions) }}</span>
                </p>

            {{-- Drop area (same look as the /order artwork dropzone). Choosing or dropping a file imports it straight away. --}}
            <label
                for="bulk-invite-file-input"
                x-data="{
                    dragging: false,
                    dragDepth: 0,
                    onDragEnter() {
                        this.dragDepth++;
                        this.dragging = true;
                    },
                    onDragLeave() {
                        this.dragDepth = Math.max(0, this.dragDepth - 1);
                        if (this.dragDepth === 0) {
                            this.dragging = false;
                        }
                    },
                    onDrop(event) {
                        this.dragDepth = 0;
                        this.dragging = false;
                        const dropped = event.dataTransfer.files[0];
                        if (dropped) {
                            $wire.upload('file', dropped);
                        }
                    }
                }"
                x-on:dragenter.prevent="onDragEnter()"
                x-on:dragover.prevent="dragging = true"
                x-on:dragleave.prevent="onDragLeave()"
                x-on:drop.prevent="onDrop($event)"
                class="biu-dropzone flex-1 mt-4 cursor-pointer"
                :class="{ 'is-dragover': dragging }"
                wire:loading.class="opacity-70 pointer-events-none"
                wire:target="file"
            >
                <span class="text-sm text-gray-600 mt-1" x-text="dragging ? 'Drop your file to import' : 'Drag & drop spreadsheet here, or click to browse'">Drag &amp; drop spreadsheet here, or click to browse</span>
                <span class="text-xs text-gray-400 mt-1">.xlsx, .xls, .csv, .txt</span>
                <input
                    type="file"
                    id="bulk-invite-file-input"
                    class="hidden"
                    accept=".xlsx,.xls,.csv,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                    wire:model="file"
                    x-on:change="setTimeout(() => $el.value = '', 0)"
                />

                <span wire:loading wire:target="file" class="mt-3 mb-0 text-sm text-gray-900">Importing&hellip;</span>

                @error('file')
                    <span class="mt-3 text-sm text-alert mb-0">{{ $message }}</span>
                @enderror
            </label>
            </div>
            </div>
        </section>

        {{-- Imported users --}}
        <section class="biu-box flex flex-col">
            <div class="biu-box-head flex items-center justify-between gap-3 flex-shrink-0">
                <h4 class="biu-box-title">User Invitation Details</h4>
                <span
                    class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold"
                >
                    {{ count($rows) }} Uploaded {{ count($rows) === 1 ? 'User' : 'Users' }}
                </span>
            </div>

            <div class="biu-box-body flex-1 flex flex-col">
            @if (empty($rows))
                <div
                    style="display:flex;flex:1 1 auto;flex-direction:column;align-items:center;justify-content:center;box-sizing:border-box;min-height:160px;padding:28px 16px;text-align:center;background:#fff;border:1px dashed #D9DDE2;border-radius:12px;"
                >
                    <!-- <div
                        style="display:flex;align-items:center;justify-content:center;width:52px;height:52px;margin-bottom:12px;color:#4A90BC;background:#B9E4FF;border-radius:14px;"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    </div> -->
                    <p style="margin:0;font-size:12px;line-height:1.4;">After uploading the user details spreadsheet, you can preview the user details here.</p>
                </div>
            @else
                <div id="biu-imported-users-panel" class="w-full">
                    @php
                        // Rows without a "Send Invitation with proofing" checkbox at all
                        // (School Administrator rows show "N/A" instead) are excluded from
                        // this computation, so the header checkbox only ever reflects the
                        // rows that actually have a checkbox to check.
                        $sendInvitationApplicableRows = collect($rows)->reject(
                            fn ($row) => ($row['role'] ?? '') === 'School Administrator'
                        );
                        $allSendInvitationChecked = $sendInvitationApplicableRows->isNotEmpty()
                            && $sendInvitationApplicableRows->every(fn ($row) => !empty($row['send_invitation_with_proofing']));
                    @endphp
                    <div class="relative overflow-x-auto">
                    <table id="biu-imported-users-table" class="w-full text-sm text-left rtl:text-right">
                        <thead>
                            <tr>
                                <x-table.headerCell :sortable="false" :clickable="false" class="w-[16%]">First Name</x-table.headerCell>
                                <x-table.headerCell :sortable="false" :clickable="false" class="w-[16%]">Last Name</x-table.headerCell>
                                <x-table.headerCell :sortable="false" :clickable="false" class="w-[28%] min-w-[14rem]">Email</x-table.headerCell>
                                <x-table.headerCell :sortable="false" :clickable="false" class="w-[18%] min-w-[11rem]">Role</x-table.headerCell>
                                <x-table.headerCell :sortable="false" :clickable="false" :centered="true" class="min-w-[10.5rem]">
                                    <input
                                        type="checkbox"
                                        wire:click="toggleAllSendInvitationWithProofing($event.target.checked)"
                                        @checked($allSendInvitationChecked)
                                        class="h-4 w-4 rounded border-neutral text-primary focus:ring-primary cursor-pointer"
                                    />
                                    <span>Hold Invitation Until Proofing</span>
                                </x-table.headerCell>
                                <x-table.headerCell :sortable="false" :clickable="false" :centered="true" class="min-w-[13rem] whitespace-nowrap">Actions</x-table.headerCell>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pagedRows as $index => $row)
                                @php $isEditing = !empty($editingRows[$index]); @endphp
                                <tr wire:key="biu-row-{{ $index }}" @class(['biu-row-error' => !empty($row['errors'])])>
                                    <x-table.cell>
                                        @if ($isEditing)
                                            <input type="text" wire:model="rows.{{ $index }}.firstname" class="bg-gray-50 border border-neutral rounded-md block w-full min-w-[8rem] p-2.5 text-sm" />
                                        @else
                                            {{ $row['firstname'] }}
                                        @endif
                                    </x-table.cell>
                                    <x-table.cell>
                                        @if ($isEditing)
                                            <input type="text" wire:model="rows.{{ $index }}.lastname" class="bg-gray-50 border border-neutral rounded-md block w-full min-w-[8rem] p-2.5 text-sm" />
                                        @else
                                            {{ $row['lastname'] }}
                                        @endif
                                    </x-table.cell>
                                    <x-table.cell>
                                        @if ($isEditing)
                                            <input type="email" wire:model="rows.{{ $index }}.email" class="bg-gray-50 border border-neutral rounded-md block w-full min-w-[12rem] p-2.5 text-sm" />
                                        @else
                                            {{ $row['email'] }}
                                        @endif
                                    </x-table.cell>
                                    <x-table.cell>
                                        @if ($isEditing)
                                            <select wire:model="rows.{{ $index }}.role" class="bg-gray-50 border border-neutral rounded-md block w-full min-w-[12rem] p-2.5 text-sm">
                                                @if (!array_key_exists($row['role'], $roleOptions))
                                                    <option value="{{ $row['role'] }}" selected>
                                                        {{ $row['role'] !== '' ? $row['role'] . ' (invalid)' : 'Select role' }}
                                                    </option>
                                                @endif
                                                @foreach ($roleOptions as $label)
                                                    <option value="{{ $label }}" @selected($row['role'] === $label)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            @if ($row['role'] === '')
                                                <span class="text-neutral">—</span>
                                            @elseif (!array_key_exists($row['role'], $roleOptions))
                                                <span class="biu-invalid-role">{{ $row['role'] }} (invalid)</span>
                                            @else
                                                {{ $row['role'] }}
                                            @endif
                                        @endif
                                    </x-table.cell>
                                    <x-table.cell class="text-center align-middle">
                                        @if ($row['role'] === 'School Administrator')
                                            <span class="text-neutral text-xs font-medium">N/A</span>
                                        @else
                                            <input
                                                type="checkbox"
                                                wire:model.live="rows.{{ $index }}.send_invitation_with_proofing"
                                                class="h-4 w-4 rounded border-neutral text-primary focus:ring-primary cursor-pointer"
                                            />
                                        @endif
                                    </x-table.cell>
                                    <x-table.cell class="text-center">
                                        <div class="inline-flex items-center gap-2 whitespace-nowrap">
                                            @if ($isEditing)
                                                <button type="button" wire:click="finishEdit({{ $index }})" class="biu-row-btn biu-row-btn-edit">Done</button>
                                            @else
                                                <button type="button" wire:click="startEdit({{ $index }})" class="biu-row-btn biu-row-btn-edit">Edit User</button>
                                            @endif
                                            <span style="color:#536c79;">|</span>
                                            <button type="button" wire:click="removeRow({{ $index }})" class="biu-row-btn biu-row-btn-remove">Remove User</button>
                                        </div>
                                    </x-table.cell>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>

                    <div class="w-full flex items-center justify-center py-4">
                        {{ $pagedRows->links('vendor.livewire.pagination') }}
                    </div>
                </div>
            @endif
            </div>
        </section>
        </div>

        @php
            $canSubmit = !empty($rows) && empty($topErrors) && !empty($schoolId);
        @endphp
        <div class="flex flex-row flex-wrap justify-end gap-3 pt-2">
            <x-button.secondary onclick="window.location='{{ route('users') }}'">Cancel</x-button.secondary>
            @if ($canSubmit)
                <button
                    type="button"
                    wire:click="submit"
                    wire:loading.attr="disabled"
                    wire:target="submit"
                    class="inline-flex items-center rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition-all disabled:opacity-50"
                    style="background:#005890;"
                >
                    <span wire:loading.remove wire:target="submit">
                        Send invitations
                    </span>
                    <span wire:loading wire:target="submit">Sending…</span>
                </button>
            @else
                <button type="button" disabled class="inline-flex items-center rounded-lg px-4 py-2.5 text-sm font-semibold text-white opacity-50 cursor-not-allowed" style="background:#005890;">
                    Send invitations
                </button>
            @endif
        </div>
    </div>

    @push('styles')
    <style>
        /* Boxes match the franchise dashboard (.fd-box): same border, radius, shadow and header strip. */
        .biu-box {
            background: #fff;
            border: 1px solid #c2cfd6;
            border-radius: 0.25rem;
            box-shadow: none;
            overflow: hidden;
            min-width: 0;
        }

        /* Top error list: same look as the proofing alert-danger (e.g. the reports page notice). */
        .biu-errors {
            padding: 0.5rem 0.75rem;
            background: #f8d7da;
            border: 1px solid #f1aeb5;
            border-radius: 0.375rem;
            color: #991b1b;
        }

        /* Row action buttons look like the /proofing "Open Job" link. */
        .biu-row-btn {
            display: inline;
            border: 0;
            padding: 0;
            background: transparent;
            font-size: 0.875rem;
            font-weight: 400;
            color: rgb(0, 88, 144);
            text-decoration: none;
            cursor: pointer;
        }
        .biu-row-btn:hover {
            color: #007693;
            text-decoration: underline;
        }
        .biu-row-error td,
        .biu-row-error td .text-neutral,
        .biu-invalid-role {
            color: #991b1b;
        }

        .biu-box-head {
            padding: 0.75rem 1.25rem;
            background: #f0f3f5;
        }

        .biu-box-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: #1A2B4A;
        }

        .biu-box-body {
            padding: 1rem 1.1rem 1.1rem;
            border-top: 1px solid #c2cfd6;
        }

        .biu-dropzone {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 1.25rem;
            min-height: 12rem;
            border: 2px dashed #cbd5e1;
            border-radius: 0.5rem;
            background-color: #f9fafb;
            transition: border-color 0.15s ease, background-color 0.15s ease;
        }

        .biu-dropzone.is-dragover {
            border-color: #005890;
            background-color: #B9E4FF;
        }

        /* Upload spreadsheet = 1/3 of row, Imported users = 2/3 of row
           (Upload spreadsheet is first in markup, Imported users is second). */
        @media (min-width: 1024px) {
            .biu-upload-import-grid {
                grid-template-columns: minmax(0, 1fr) minmax(0, 2fr) !important;
            }
        }
        @media (max-width: 640px) {
            .bulk-invite-page .bulk-invite-format-row {
                display: flex !important;
                flex-direction: column !important;
                align-items: flex-start !important;
                width: 100% !important;
            }
            .bulk-invite-page .bulk-invite-format-code {
                width: 100% !important;
                white-space: normal !important;
            }
        }
        .bulk-invite-page .select2-container .select2-selection--single {
            height: 46px;
            border: 1px solid #D9DDE2;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            padding: 0.25rem 0.5rem;
            background: #fff;
        }
        .bulk-invite-page .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.4;
            color: #273444;
            padding-left: 0.35rem;
        }
        .bulk-invite-page .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 44px;
            right: 6px;
        }
        .bulk-invite-page .select2-container--default .select2-selection--single .select2-selection__clear {
            margin-right: 1.5rem;
        }
    </style>
    @endpush
</div>
