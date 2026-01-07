
<div class="modal fade" id="indexFilterHelp" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
     aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Using Search Filters</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                Text Search Filters
                            </div>
                            <div class="card-body">

                                <table class="table table-bordered">
                                    <thead>
                                    <tr>
                                        <th>Operator</th>
                                        <th>Example</th>
                                        <th width="60%">Description</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <th scope="row">OR</th>
                                        <td><input class="form-control" value="OR sydney melbourne"> </input></td>
                                        <td>
                                            Returns records that contain either "Sydney" OR "Melbourne".
                                            Because this is the default operator, the OR is not strictly necessary.
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">AND</th>
                                        <td><input class="form-control" value="AND sydney melbourne"> </input></td>
                                        <td>
                                            Returns records that contain both "Sydney" AND "Melbourne".
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">EXACT</th>
                                        <td><input class="form-control" value="EXACT i love sydney"> </input></td>
                                        <td>
                                            Returns records that is the exact phrase "I Love Sydney".
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">CONTAINS</th>
                                        <td><input class="form-control" value="CONTAINS i love sydney"> </input></td>
                                        <td>
                                            Returns records that contains the phrase "I Love Sydney".
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">STARTS</th>
                                        <td><input class="form-control" value="NULL syd"> </input></td>
                                        <td>
                                            Returns records that START with "Syd".
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">ENDS</th>
                                        <td><input class="form-control" value="ENDS ney"> </input></td>
                                        <td>
                                            Returns records that ENDS with "ney".
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">NOT</th>
                                        <td><input class="form-control" value="NOT sydney brisbane"> </input></td>
                                        <td>
                                            Returns records that do not contain "Sydney" AND "Brisbane".
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">NULL</th>
                                        <td><input class="form-control" value="NULL"> </input></td>
                                        <td>
                                            Returns records that contain NULL or EMPTY values.
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">EMPTY</th>
                                        <td><input class="form-control" value="EMPTY"> </input></td>
                                        <td>
                                            Returns records that contain NULL or EMPTY values.
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                Number Search Filters
                            </div>
                            <div class="card-body">

                                <table class="table table-bordered">
                                    <thead>
                                    <tr>
                                        <th>Operator</th>
                                        <th>Example</th>
                                        <th width="60%">Description</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <th scope="row">IN</th>
                                        <td><input class="form-control" value="IN 3 7 9"> </input></td>
                                        <td>
                                            Returns records that are only 3, 7 and 9.
                                            Because this is the default operator, the IN is not strictly necessary.
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">BETWEEN</th>
                                        <td><input class="form-control" value="BETWEEN 5 9"> </input></td>
                                        <td>
                                            Returns records inside the range 5-9 (inclusive of 5 and 9).
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">OUTSIDE</th>
                                        <td><input class="form-control" value="OUTSIDE 5 9"> </input></td>
                                        <td>
                                            Returns records outside the range 5-9 (exclusive of 5 and 9).
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Math Operators</th>
                                        <td><input class="form-control" value=">=7"> </input><br>
                                            <input class="form-control" value=">7"> </input><br>
                                            <input class="form-control" value="<=7"> </input><br>
                                            <input class="form-control" value="<7"> </input><br>
                                            <input class="form-control" value="!=7 12 18"> </input><br></td>
                                        <td>
                                            Greater than or equal to 7<br>
                                            Greater than 7<br>
                                            Less than or equal to 7<br>
                                            Less than 7<br>
                                            Not equal to 7, 12 or 18<br>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>

                {{-- <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                Date and Time Filters
                            </div>
                            <div class="card-body">

                                <table class="table table-bordered">
                                    <thead>
                                    <tr>
                                        <th>Operator</th>
                                        <th>Example</th>
                                        <th width="60%">Description</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <th scope="row">AND</th>
                                        <td><input class="form-control" value="AND sydney melbourne"> </input></td>
                                        <td>
                                            Returns records that contain both "Sydney" and "Melbourne".
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">OR</th>
                                        <td><input class="form-control" value="OR sydney melbourne"> </input></td>
                                        <td>
                                            Returns records that contain either "Sydney" or "Melbourne".
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                </div> --}}

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>







