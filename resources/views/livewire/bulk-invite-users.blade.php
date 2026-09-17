<div class="bulk-invite-page pb-8">
    <div class="py-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-2xl font-semibold text-gray-900">Bulk Invite</h3>
            <p class="text-sm text-neutral mt-1">Import from CSV or Excel, assign a school, then send invitations.</p>
            
                    @if (!$showSchoolSelector && $lockedSchoolName !== '')
                        <div style="display:inline-flex;align-items:center;gap:8px;font-size:14px;line-height:2.5;color:#273444;">
                            <!-- <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#005890" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" />
                            </svg> -->
                            <span>
                                <span style="color:#6F6F6E;margin-right:4px;">School Assigned:</span>
                                <strong>{{ $lockedSchoolName }}</strong>
                            </span>
                        </div>
                    @endif
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
            <div class="rounded-xl border border-alert-300 bg-alert-100 px-4 py-3 text-sm text-alert" role="alert">
                <p class="font-semibold mb-2">Please fix the following before inviting:</p>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($topErrors as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 items-stretch biu-upload-import-grid">
        {{-- Upload spreadsheet --}}
        <section class="rounded-2xl border border-neutral-400 bg-white p-6 shadow-sm">
            <h4 class="text-lg font-semibold text-gray-900 mb-4">Upload spreadsheet</h4>

            {{-- Light-blue info strip (inline styles beat Bootstrap/Tailwind conflicts) --}}
            <div
                class="mb-5"
                data-bulk-invite-info
                style="display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:12px 16px;box-sizing:border-box;width:100%;padding:14px 16px;overflow:visible;background:#f0f3f5;border:none;border-radius:12px;"
            >
                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px 24px;min-width:0;">
                    
                    <div
                        class="bulk-invite-format-row"
                        style="display:inline-flex;flex-wrap:wrap;align-items:center;gap:8px;min-width:0;max-width:100%;{{ !$showSchoolSelector && $lockedSchoolName !== '' ? 'flex-basis:100%;' : '' }}font-size:14px;line-height:1.4;color:#273444;"
                    >
                        <span style="color:#6F6F6E;flex-shrink:0;">Column Format:</span>
                        <span class="bulk-invite-format-code" style="display:inline-block;box-sizing:border-box;max-width:100%;padding:4px 8px;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:12px;line-height:1.45;color:#111827;background:#ffffff;border-radius:6px;overflow-wrap:anywhere;word-break:break-word;">firstname, lastname, email, role</span>
                    </div>
                </div>
                <a
                    href="#"
                    wire:click.prevent="downloadSampleCsv"
                    style="display:inline-flex;align-items:center;justify-content:center;gap:8px;width:max-content;max-width:100%;padding:8px 12px;font-size:12px;font-weight:600;line-height:1.2;text-decoration:none;background:#005890;color:#fff;border-radius:8px;box-shadow:0 1px 2px rgba(15, 23, 42, 0.12);white-space:nowrap;"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Download Sample CSV
                </a>

                <div style="grid-column:1 / -1;margin-top:4px;padding-top:12px;border-top:1px solid #DDE3EA;">
                    <p style="margin:0;font-size:13px;color:#273444;">
                        <strong style="font-weight:600;">Designated Roles &ndash; </strong>
                        <span style="font-weight:500;">{{ implode(', ', $roleOptions) }}</span>
                    </p>
                </div>
            </div>

            <div
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
                class="relative overflow-hidden rounded-2xl border-2 border-dashed px-6 py-12 text-center transition-all duration-200"
                :style="dragging
                    ? 'border-color:#005890;background:#B9E4FF;'
                    : 'border-color:transparent;background:#f0f3f5;'"
                wire:loading.class="opacity-70 pointer-events-none"
                wire:target="file"
            >
                <div class="relative z-10 mx-auto flex max-w-xl flex-col items-center">

                    <p class="text-base font-semibold text-gray-900" x-text="dragging ? 'Drop your file to import' : 'Drag and drop your spreadsheet here'"></p>
                    <p class="mt-1 text-sm text-neutral">or browse files from your computer</p>

                    <label class="mt-5 inline-flex cursor-pointer">
                        <span
                            class="inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all"
                            style="background:#005890;"
                        >
                            Choose file
                        </span>
                        <input
                            type="file"
                            class="hidden"
                            accept=".xlsx,.xls,.csv,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                            wire:model="file"
                        />
                    </label>

                    <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                        @foreach (['.xlsx', '.xls', '.csv'] as $ext)
                            <span
                                class="px-3 py-1 text-xs font-semibold"
                            >{{ $ext }}</span>
                        @endforeach
                    </div>
                    @error('file')
                        <p class="mt-3 text-sm text-alert mb-0">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>

        {{-- Imported users --}}
        <section class="rounded-2xl border border-neutral-400 bg-white p-6 shadow-sm flex flex-col">
            <div class="mb-4 flex items-center justify-between gap-3 flex-shrink-0">
                <h4 class="text-lg font-semibold text-gray-900">Imported users</h4>
                <span
                    class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold"
                >
                    {{ count($rows) }} {{ count($rows) === 1 ? 'user' : 'users' }} imported
                </span>
            </div>

            <div class="flex-1 flex flex-col">
            @if (empty($rows))
                <div
                    style="display:flex;flex:1 1 auto;flex-direction:column;align-items:center;justify-content:center;box-sizing:border-box;min-height:160px;padding:28px 16px;text-align:center;background:#f0f3f5;border:1px dashed #D9DDE2;border-radius:12px;"
                >
                    <!-- <div
                        style="display:flex;align-items:center;justify-content:center;width:52px;height:52px;margin-bottom:12px;color:#4A90BC;background:#B9E4FF;border-radius:14px;"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    </div> -->
                    <p style="margin:0 0 4px;font-size:14px;font-weight:600;line-height:1.4;color:#273444;">No users imported yet</p>
                    <p style="margin:0;font-size:12px;line-height:1.4;color:#6F6F6E;">Upload a spreadsheet to preview and edit users here before sending invitations.</p>
                </div>
            @else
                <div id="biu-imported-users-panel" class="w-full">
                    <table id="biu-imported-users-table" class="w-full text-sm text-left">
                        <thead>
                            <tr>
                                <th scope="col">First Name</th>
                                <th scope="col">Last Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Role</th>
                                <th scope="col" class="text-center">Send Invitation with proofing</th>
                                <th scope="col" class="text-center w-[140px]">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-300 bg-white">
                            @foreach ($rows as $index => $row)
                                @php $isEditing = !empty($editingRows[$index]); @endphp
                                <tr class="{{ !empty($row['errors']) ? 'bg-alert-100' : '' }}">
                                    <td class="px-4 py-3">
                                        @if ($isEditing)
                                            <input type="text" wire:model="rows.{{ $index }}.firstname" class="bg-gray-50 border border-neutral rounded-md block w-full min-w-[8rem] p-2.5 text-sm" />
                                        @else
                                            {{ $row['firstname'] }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($isEditing)
                                            <input type="text" wire:model="rows.{{ $index }}.lastname" class="bg-gray-50 border border-neutral rounded-md block w-full min-w-[8rem] p-2.5 text-sm" />
                                        @else
                                            {{ $row['lastname'] }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 {{ $isEditing ? '' : 'text-neutral' }}">
                                        @if ($isEditing)
                                            <input type="email" wire:model="rows.{{ $index }}.email" class="bg-gray-50 border border-neutral rounded-md block w-full min-w-[12rem] p-2.5 text-sm" />
                                        @else
                                            {{ $row['email'] }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
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
                                                <span class="text-alert">{{ $row['role'] }} (invalid)</span>
                                            @else
                                                {{ $row['role'] }}
                                            @endif
                                        @endif
                                        @if (!empty($row['errors']))
                                            <p class="mt-1 text-sm text-alert mb-0">{{ $row['errors'][0] }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center align-middle">
                                        @if ($row['role'] === 'School Administrator')
                                            <span class="text-neutral text-xs font-medium">N/A</span>
                                        @else
                                            <input
                                                type="checkbox"
                                                wire:model="rows.{{ $index }}.send_invitation_with_proofing"
                                                class="h-4 w-4 rounded border-neutral text-primary focus:ring-primary cursor-pointer"
                                            />
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="inline-flex items-center gap-2">
                                            @if ($isEditing)
                                                <button type="button" wire:click="finishEdit({{ $index }})" class="inline-flex items-center justify-center rounded-md border border-primary px-3 py-1.5 text-xs font-semibold text-primary transition-all hover:bg-primary-100">Done</button>
                                            @else
                                                <button type="button" wire:click="startEdit({{ $index }})" class="inline-flex items-center justify-center rounded-md border border-primary px-3 py-1.5 text-xs font-semibold text-primary transition-all hover:bg-primary-100">Edit</button>
                                            @endif
                                            <button type="button" wire:click="removeRow({{ $index }})" class="inline-flex items-center justify-center rounded-md border border-alert px-3 py-1.5 text-xs font-semibold text-alert transition-all hover:bg-alert-100">Remove</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            </div>
        </section>
        </div>

        @if ($showSchoolSelector)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 biu-upload-import-grid">
            <section class="rounded-2xl border border-neutral-400 bg-white p-6 shadow-sm">
                <h4 class="text-lg font-semibold text-gray-900 mb-1">Assign school</h4>
                <p class="text-sm text-neutral mb-4">All invited users are assigned to the school selected here.</p>
                <div class="max-w-xl">
                    <label for="bulk-invite-school" class="mb-2 block text-sm font-semibold text-gray-800">
                        Select School <span class="text-alert">*</span>
                    </label>
                    <div wire:ignore class="w-full">
                        <select id="bulk-invite-school" class="bg-white border border-neutral-400 rounded-lg block w-full p-2.5">
                            <option value="">Select School</option>
                            @foreach ($schools as $school)
                                <option value="{{ $school->id }}" @selected((int) $schoolId === (int) $school->id)>
                                    {{ $school->suburb ? $school->name . ' (' . $school->suburb . ')' : $school->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <p class="mt-1.5 text-xs text-neutral mb-0">Select one school associated to your franchise</p>
                </div>
            </section>
        </div>
        @endif

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
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition-all disabled:opacity-50"
                    style="background:#005890;"
                >
                    <span wire:loading.remove wire:target="submit" class="inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                        </svg>
                        Send invitations
                    </span>
                    <span wire:loading wire:target="submit">Sending…</span>
                </button>
            @else
                <button type="button" disabled class="inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white opacity-50 cursor-not-allowed" style="background:#005890;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                    Send invitations
                </button>
            @endif
        </div>
    </div>

    <style>
        /* Upload spreadsheet = 1/3 of row, Imported users = 2/3 of row
           (Upload spreadsheet is first in markup, Imported users is second). */
        @media (min-width: 1024px) {
            .biu-upload-import-grid {
                grid-template-columns: minmax(0, 1fr) minmax(0, 2fr) !important;
            }
        }
        @media (max-width: 640px) {
            .bulk-invite-page [data-bulk-invite-info] {
                grid-template-columns: 1fr !important;
                padding: 12px !important;
            }
            .bulk-invite-page [data-bulk-invite-info] a {
                justify-self: start;
            }
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

        /* Imported users - Custom DataTables toolbar, table box & centered pagination footer */
        .bulk-invite-page .biu-table-toolbar {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            margin-bottom: 1rem !important;
            width: 100% !important;
            gap: 1rem !important;
            flex-wrap: wrap !important;
        }

        .bulk-invite-page .dataTables_length {
            margin: 0 !important;
            font-size: 0.875rem !important;
            font-weight: 500 !important;
            color: #475569 !important;
        }

        .bulk-invite-page .dataTables_length label {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            margin: 0 !important;
        }

        .bulk-invite-page .dataTables_length select {
            height: 38px !important;
            padding: 0.4rem 0.75rem !important;
            border: 1px solid #D9DDE2 !important;
            border-radius: 8px !important;
            font-size: 0.875rem !important;
            color: #1E293B !important;
            background: #ffffff !important;
            outline: none !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04) !important;
        }

        .bulk-invite-page .dataTables_filter {
            margin: 0 !important;
            font-size: 0.875rem !important;
            font-weight: 500 !important;
            color: #475569 !important;
        }

        .bulk-invite-page .dataTables_filter label {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            margin: 0 !important;
        }

        .bulk-invite-page .dataTables_filter input {
            height: 38px !important;
            padding: 0.4rem 0.85rem !important;
            border: 1px solid #D9DDE2 !important;
            border-radius: 8px !important;
            font-size: 0.875rem !important;
            color: #1E293B !important;
            background: #ffffff !important;
            outline: none !important;
            transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04) !important;
        }

        .bulk-invite-page .dataTables_filter input:focus {
            border-color: #005890 !important;
            box-shadow: 0 0 0 3px rgba(0, 88, 144, 0.15) !important;
        }

        /* Enclose ONLY the table inside a rounded border box */
        .bulk-invite-page .biu-table-wrap {
            width: 100% !important;
            overflow-x: auto !important;
            border: 1px solid #D9DDE2 !important;
            border-radius: 12px !important;
            background: #ffffff !important;
        }

        #biu-imported-users-table {
            width: 100% !important;
            border-collapse: collapse !important;
            margin: 0 !important;
        }

        #biu-imported-users-table thead th {
            background-color: #f0f3f5 !important;
            color: #475569 !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            padding: 0.75rem 1rem !important;
            border-bottom: 1px solid #E2E8F0 !important;
            border-right: 1px solid #E2E8F0 !important;
            white-space: nowrap !important;
            position: relative !important;
        }

        /* Custom sort arrow: replace DataTables' default right-side unicode
           arrows with a small triangle placed BEFORE the header text (left side). */
        #biu-imported-users-table.dataTable thead > tr > th.sorting,
        #biu-imported-users-table.dataTable thead > tr > th.sorting_asc,
        #biu-imported-users-table.dataTable thead > tr > th.sorting_desc {
            padding-left: 1.35rem !important;
            padding-right: 1rem !important;
            cursor: pointer;
        }

        #biu-imported-users-table.dataTable thead .sorting:before,
        #biu-imported-users-table.dataTable thead .sorting:after,
        #biu-imported-users-table.dataTable thead .sorting_asc:after,
        #biu-imported-users-table.dataTable thead .sorting_desc:before,
        #biu-imported-users-table.dataTable thead .sorting_asc_disabled:before,
        #biu-imported-users-table.dataTable thead .sorting_asc_disabled:after,
        #biu-imported-users-table.dataTable thead .sorting_desc_disabled:before,
        #biu-imported-users-table.dataTable thead .sorting_desc_disabled:after {
            content: none !important;
            display: none !important;
        }

        #biu-imported-users-table.dataTable thead > tr > th.sorting:before,
        #biu-imported-users-table.dataTable thead > tr > th.sorting_asc:before,
        #biu-imported-users-table.dataTable thead > tr > th.sorting_desc:before {
            content: "" !important;
            display: block !important;
            position: absolute;
            left: 0.5rem;
            top: 50%;
            width: 0;
            height: 0;
            border-left: 4px solid transparent;
            border-right: 4px solid transparent;
        }

        #biu-imported-users-table.dataTable thead > tr > th.sorting:before {
            margin-top: -5px;
            border-top: 5px solid #94A3B8;
        }

        #biu-imported-users-table.dataTable thead > tr > th.sorting_asc:before {
            margin-top: -3px;
            border-bottom: 5px solid #005890;
        }

        #biu-imported-users-table.dataTable thead > tr > th.sorting_desc:before {
            margin-top: -5px;
            border-top: 5px solid #005890;
        }

        #biu-imported-users-table thead th:last-child {
            border-right: 0 !important;
        }

        #biu-imported-users-table tbody td {
            padding: 0.75rem 1rem !important;
            border-bottom: 1px solid #E2E8F0 !important;
            border-right: 1px solid #E2E8F0 !important;
            vertical-align: middle !important;
            font-size: 0.875rem !important;
            color: #1E293B !important;
        }

        #biu-imported-users-table tbody td:last-child {
            border-right: 0 !important;
        }

        #biu-imported-users-table tbody tr:last-child td {
            border-bottom: 0 !important;
        }

        /* Footer layout: Info text on left, centered single pill pagination container */
        .bulk-invite-page .biu-table-footer {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            position: relative !important;
            margin-top: 1rem !important;
            width: 100% !important;
            min-height: 2.5rem !important;
        }

        .bulk-invite-page .biu-table-footer .dataTables_info {
            position: absolute !important;
            left: 0 !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            margin: 0 !important;
            padding: 0 !important;
            font-size: 0.875rem !important;
            color: #64748B !important;
            float: none !important;
        }

        .bulk-invite-page .dataTables_paginate {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin: 0 auto !important;
            float: none !important;
            background: #ffffff !important;
            border: 1px solid #D1D5DB !important;
            border-radius: 8px !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05) !important;
            overflow: hidden !important;
            padding: 0 !important;
        }

        .bulk-invite-page .dataTables_paginate ul.pagination {
            display: inline-flex !important;
            margin: 0 !important;
            padding: 0 !important;
            list-style: none !important;
            border: 0 !important;
            background: transparent !important;
        }

        .bulk-invite-page .dataTables_paginate ul.pagination .page-item,
        .bulk-invite-page .dataTables_paginate .paginate_button {
            margin: 0 !important;
            padding: 0 !important;
            display: inline-flex !important;
            align-items: stretch !important;
            border: 0 !important;
            background: transparent !important;
        }

        .bulk-invite-page .dataTables_paginate ul.pagination .page-item:not(:last-child) .page-link,
        .bulk-invite-page .dataTables_paginate .paginate_button:not(:last-child),
        .bulk-invite-page .dataTables_paginate > a:not(:last-child),
        .bulk-invite-page .dataTables_paginate > span > a:not(:last-child) {
            border-right: 1px solid #E5E7EB !important;
        }

        .bulk-invite-page .dataTables_paginate .page-link,
        .bulk-invite-page .dataTables_paginate .paginate_button a,
        .bulk-invite-page .dataTables_paginate a {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 0.5rem 0.9rem !important;
            min-width: 2.5rem !important;
            height: 100% !important;
            font-size: 0.875rem !important;
            font-weight: 500 !important;
            color: #005890 !important;
            background: #ffffff !important;
            border: 0 !important;
            border-radius: 0 !important;
            text-decoration: none !important;
            transition: all 0.15s ease !important;
            box-shadow: none !important;
            margin: 0 !important;
            line-height: 1.25 !important;
        }

        .bulk-invite-page .dataTables_paginate ul.pagination .page-item:not(.active):not(.disabled) .page-link:hover,
        .bulk-invite-page .dataTables_paginate .paginate_button:not(.current):not(.disabled):hover,
        .bulk-invite-page .dataTables_paginate a:hover {
            background-color: #F1F5F9 !important;
            color: #00406c !important;
        }

        .bulk-invite-page .dataTables_paginate ul.pagination .page-item.active .page-link,
        .bulk-invite-page .dataTables_paginate .paginate_button.current,
        .bulk-invite-page .dataTables_paginate .paginate_button.current a {
            background-color: #005890 !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            border-radius: 0 !important;
        }

        .bulk-invite-page .dataTables_paginate ul.pagination .page-item.disabled .page-link,
        .bulk-invite-page .dataTables_paginate .paginate_button.disabled,
        .bulk-invite-page .dataTables_paginate .paginate_button.disabled a {
            color: #94A3B8 !important;
            background-color: #ffffff !important;
            cursor: default !important;
            opacity: 0.7 !important;
        }

        @media (max-width: 768px) {
            .bulk-invite-page .biu-table-footer {
                flex-direction: column !important;
                gap: 0.75rem !important;
            }
            .bulk-invite-page .biu-table-footer .dataTables_info {
                position: static !important;
                transform: none !important;
                text-align: center !important;
            }
        }
    </style>
</div>

@push('scripts')
<link rel="stylesheet" href="{{ URL::asset('proofing-assets/plugins/datatables-bs4/css/dataTables.bootstrap4.css') }}">
<script src="{{ URL::asset('proofing-assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('proofing-assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script>
    (function () {
        // Only paginate "Imported users" once there's a table to paginate - and
        // re-init after every Livewire re-render (edit/remove/upload all replace
        // the table's rows), matching the DataTables + Livewire pattern already
        // used on the franchise dashboard.
        var importedUsersRowSignature = null;

        function computeImportedUsersRowSignature(table) {
            var tbody = table.querySelector('tbody');
            if (!tbody) {
                return '';
            }
            return Array.prototype.map.call(tbody.querySelectorAll('tr'), function (tr) {
                return tr.getAttribute('wire:key') || tr.textContent.trim();
            }).join('|');
        }

        function initImportedUsersTable(force) {
            var table = document.getElementById('biu-imported-users-table');
            if (!table || typeof window.jQuery === 'undefined' || !window.jQuery.fn || !window.jQuery.fn.DataTable) {
                return false;
            }

            var alreadyInitialized = window.jQuery.fn.DataTable.isDataTable(table);
            var signature = computeImportedUsersRowSignature(table);

            if (alreadyInitialized && !force && signature === importedUsersRowSignature) {
                // Row data hasn't actually changed - skip the destroy/rebuild so an
                // unrelated Livewire update elsewhere on the page (assigning a
                // school, dragging a file, etc.) doesn't flicker the table.
                return true;
            }

            importedUsersRowSignature = signature;

            var $table = window.jQuery(table);
            if (alreadyInitialized) {
                try {
                    $table.DataTable().destroy();
                } catch (e) {
                    // ignore
                }
            }

            try {
                $table.DataTable({
                    dom: "<'biu-table-toolbar'lf><'biu-table-wrap't><'biu-table-footer'ip>",
                    pageLength: 4,
                    lengthChange: true,
                    lengthMenu: [[4, 10, 25, 50, 100], [4, 10, 25, 50, 100]],
                    paging: true,
                    searching: true,
                    info: true,
                    autoWidth: false,
                    ordering: true,
                    order: [],
                    columnDefs: [
                        { orderable: false, targets: [4, 5] },
                    ],
                    language: {
                        lengthMenu: 'Display _MENU_',
                        info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                        infoEmpty: 'Showing 0 to 0 of 0 entries',
                        search: 'Search:',
                        paginate: {
                            previous: 'Previous',
                            next: 'Next',
                        },
                    },
                });
                return true;
            } catch (e) {
                console.warn('Imported users DataTable init failed', e);
                return false;
            }
        }

        function boot() {
            initImportedUsersTable();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', boot);
        } else {
            boot();
        }
        document.addEventListener('livewire:navigated', boot);
        document.addEventListener('livewire:load', boot);

        if (window.Livewire && typeof window.Livewire.hook === 'function') {
            window.Livewire.hook('morph.updated', initImportedUsersTable);
        } else {
            document.addEventListener('livewire:initialized', function () {
                if (window.Livewire && typeof window.Livewire.hook === 'function') {
                    window.Livewire.hook('morph.updated', initImportedUsersTable);
                }
            });
        }
    })();
</script>
@endpush

