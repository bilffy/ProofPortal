<div class="row">
    <div class="col-12 m-auto">
        <div class="card border-danger">
            <div class="card-header">
                <legend class="text-danger">Danger Zone</legend>
            </div>
            <div class="card-body">
                <!-- Archive Job Section -->
                <div class="row">
                    <div class="col-9 m-auto">
                        <p class="lead mb-0"><strong>Archive this Job</strong></p>
                        @php
                            $purgeMonths = '12';
                        @endphp
                        <ul>
                            <li>The will effectively mark the Proofing process as complete.</li>
                            <li>The Job will be removed from your Dashboard list of active Jobs.</li>
                            <li>Photo Coordinators and Teachers will no longer be able to make changes.</li>
                            <li>The Job will be automatically deleted after our data retention policy of <strong>{{ $purgeMonths }} months</strong>.</li>
                            <li>Please download all reports for your records before Archiving.</li>
                        </ul>
                    </div>
                    <div class="col-3 m-auto">
                        {{-- @if (auth()->user()->hasRole('franchise')) --}}
                            @php
                                $confirmMsg = __('Are you sure you want to archive :school?', ['school' => $selectedJob->ts_jobname]);
                            @endphp
                            <form method="POST" action="{{ route('dashboard.archive') }}">
                                @csrf
                                <input type='hidden' value='{{$jobIdHash}}' name='job'>
                                <button type="submit" class="btn btn-danger float-right pl-4 pr-4"
                                        onclick="return confirm('{{ $confirmMsg }}')">
                                    Archive this Job
                                </button>
                            </form>
                        {{-- @endif --}}
                    </div>
                </div>

                <hr>

                <!-- Delete Job Section -->
                <div class="row">
                    <div class="col-9 m-auto">
                        <p class="lead mb-0"><strong>Delete this Job</strong></p>
                        <ul>
                            <li>Once you delete a Job, there is no going back. Please be certain.</li>
                            <li>All changes made by Photo Coordinators and Teachers will be lost.</li>
                            <li>After deletion, you can add this Job back via the Dashboard but you will need to re-invite Photo Coordinators and Teachers.</li>
                            <li>Please download all reports for your records before Deleting.</li>
                        </ul>
                    </div>
                    <div class="col-3 m-auto">
                        <div class="row">
                            <div class="col-12">
                                {{-- @if (auth()->user()->hasRole('franchise')) --}}
                                    @php
                                        $confirmMsg = __('Are you sure you want to delete :school? This action is irreversible!', ['school' => $selectedJob->ts_jobname]);
                                    @endphp
                                    <form method="POST" action="{{ URL::signedRoute('dashboard.deleteJob', ['hash' => $hash]) }}">
                                        @csrf
                                        <input type='hidden' value='{{$jobIdHash}}' name='job'>
                                        <button type="submit" id="math-question-button-delete" class="btn btn-danger float-right pl-4 pr-4 disabled"
                                                onclick="return confirm('{{ $confirmMsg }}')">
                                            Delete this Job
                                        </button>
                                    </form>
                                {{-- @endif --}}
                            </div>

                            <!-- Math Question Section -->
                            <div class="col-12 mt-2">
                                @php
                                    $figureA = mt_rand(1, 9);
                                    $figureB = mt_rand(1, 9);
                                    $figureC = $figureA + $figureB;
                                @endphp
                                <div class="form-group">
                                    <label for="math-question-field" class="sr-only">Math Question</label>
                                    <input type="text"
                                           class="form-control"
                                           id="math-question-field"
                                           name="math-question-field"
                                           placeholder="Please type the answer to ({{ $figureA }} + {{ $figureB }})"
                                           data-a="{{ $figureA }}"
                                           data-b="{{ $figureB }}"
                                           data-c="{{ $figureC }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>