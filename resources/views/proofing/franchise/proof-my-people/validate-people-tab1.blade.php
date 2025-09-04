                <fieldset id="ValidateStep1">
                    <div class="row" id="class_questions">
                        <div class="questions col-xs-12 col-sm-12 col-md-8 col-lg-6 col-xl-4 m-auto">
                            <div class="form-group">
                                <label for="folder_name" class="form-control-label">{{ __('Folder Name') }}</label>
                                <input id="folder_name" type="text" class="form-control" name="folder_name" value="{{ $className }}" />
                                <input type="hidden" name="old_folder_name" value="{{ $className }}" />
                            </div>
                        </div>
                    </div>
                    
                    <div class="f1-buttons">
                        <a class="btn btn-previous btn-secondary" id="backPage" href="{{ $backlocation }}">
                            {{ __('Cancel') }}
                        </a>
                        <button id="classNext" type="button" class="btn btn-next btn-primary">Next</button>
                    </div>
                </fieldset>