<?php

namespace App\Http\Controllers\Proofing;

use App\Http\Controllers\Controller;
use App\Services\Proofing\SqlServerReportingServices;
use App\Services\Proofing\StatusService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use App\Models\Season;
use App\Models\Report;
use ReflectionMethod;
use Auth;


class ReportController extends Controller
{

    protected $statusService;

    public function __construct(StatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    public function applyFilters($query, array $filters)
    {
        // Apply filters if they exist
        if (isset($filters['id'])) {
            $query->where('id', $filters['id']);
        }

        if (isset($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (isset($filters['description'])) {
            $query->where('description', 'like', '%' . $filters['description'] . '%');
        }

        return $query;
    }

    public function index(Request $request)
    {
        // Default sorting options
        $sort = $request->input('sort', 'id'); // Default sort column
        $direction = $request->input('direction', 'asc'); // Default sort direction

        // Fetch reports with sorting applied
        $query = Report::with('report_roles')
            ->whereHas('report_roles', function ($query) {
                $query->whereIn('role_id', Auth::user()->roles->pluck('id'));
            })
            ->select('id', 'name', 'description', 'query');
    
        // Apply record filters if any
        if ($request->has('record_filter')) {
            $query = $this->applyFilters($query, $request->input('record_filter'));
        }
    
        // Apply sorting
        $query = $query->orderBy($sort, $direction);
    
        // Paginate the results
        $reports = $query->paginate(10);
        $user = Auth::user();
    
        // Pass current sorting parameters to the view
        return view('proofing.reports.index', [
            'reports' => $reports,
            'sort' => $sort,
            'direction' => $direction,
            'user' => new UserResource($user)
        ]);
    }

    public function run($reportName = null, $tsJobId = null, $tsFolderId = null)
    {
        
        $user = Auth::user();
        if (is_null($reportName)) {
            session()->flash('error', __('Sorry, please select a Report and try again.'));
            return redirect()->route('reports');
        }
    
        // Fetch seasons
        // $seasons = Season::all();  // Assuming a Season model exists
        $currentSeasonID = Session::has('selectedSeason') ? Session::get('selectedSeason')->ts_season_id : '';  // Custom method in Season model
        $seasonList = Season::orderBy('code', 'asc')->pluck('code', 'ts_season_id')->toArray();  // Custom method in Season model

        // Share variables with the view
        $data = [
            // 'seasons' => $seasons,
            'currentSeasonID' => $currentSeasonID,
            'seasonList' => $seasonList,
        ];
    
        // Find the report
        $report = Report::where('query', $reportName)->first();
        if (empty($report)) {
            session()->flash('error', __('Sorry, please select a Report and try again.'));
            return redirect()->route('reports');
        }
    
        // Additional data to pass to the view
        $data['report'] = $report;
        $data['reportName'] = $report->name;
        $data['reportDescription'] = $report->description;
        $data['user'] = new UserResource($user);

        // Parameters passed in URL
        $passedParamValues = request()->route()->parameters();
        array_shift($passedParamValues);  // Shift out $reportName
        $passedParamCount = count($passedParamValues);
    
        $data['passedParamCount'] = $passedParamCount;
        $data['passedParamValues'] = $passedParamValues;
       
        // Prepare report parameters
        $reportParams = [];
        if (!empty($report->params)) {
            $parameters = json_decode($report->params, true);

            foreach ($parameters as $parameter) {
                $paramQueryName = $parameter['queryName'];
    
                try {
                    $numOfArguments = (new ReflectionMethod(Report::class, $paramQueryName))->getNumberOfParameters();
                } catch (\ReflectionException $e) {
                    $numOfArguments = 0;
                }
             
                // Work out if parameters need to be passed into the method
                $paramsToBePassedThrough = array_slice($passedParamValues, 0, $numOfArguments);
            
                $paramQuery = call_user_func_array([Report::class, $paramQueryName], $paramsToBePassedThrough);
    
                $parameter['query'] = $paramQuery;
                $reportParams[] = $parameter;
            }
        }
    
        $data['reportParams'] = $reportParams;
        $requiredParamCount = count($reportParams);
        $data['requiredParamCount'] = $requiredParamCount;
   
        // If not enough parameters, show parameter capture view
        if ($passedParamCount < $requiredParamCount) {
            return view('proofing.reports.run_params', $data);
        }

        // Run the report
        $reportQuery = call_user_func_array([Report::class, $reportName], $passedParamValues);
         
        $data['reportQuery'] = $reportQuery;
        $data['resultCount'] = count($reportQuery);
        $data['format'] = null;
        $data['browserMode'] = null;

        // Handle form submission for report generation (e.g., CSV, PDF)
        if (request()->isMethod('post')) {
            $allowedFormats = ['csv', 'xlsx', 'pdf', 'xls'];
            $allowedBrowserModes = ['view', 'download'];
    
            $requestData = request()->all();
            if (isset($requestData['format']) && isset($requestData['browserMode']) && isset($requestData['downloadName'])) {
    
                $format = strtolower($requestData['format']);
                $browserMode = strtolower($requestData['browserMode']);
    
                if (in_array($browserMode, $allowedBrowserModes)) {
                    $data['browserMode'] = $browserMode;
                }
    
                if (in_array($format, $allowedFormats)) {
                    $data['format'] = $format;
    
                    // Render the view content
                    $view = view('reports.run', $data)->render();
                    $content = trim($view);
    
                    // If no error in content, serve the report file
                    if (substr($content, 0, 5) !== 'error') {
                        $reportBinaryLocation = session()->get("MyReports." . auth()->user()->id);
                        $reportBinaryData = file_get_contents($reportBinaryLocation);
                        unlink($reportBinaryLocation);
    
                        if ($browserMode == 'download') {
                            return response($reportBinaryData)
                                ->header('Content-Type', mime_content_type($reportBinaryLocation))
                                ->header('Content-Disposition', 'attachment; filename=' . $requestData['downloadName']);
                        }
    
                        return response($reportBinaryData)->header('Content-Type', mime_content_type($reportBinaryLocation));
                    } else {
                        $errorValues = explode(":", $content);
                        $msg = __("Code {0} - {1}", $errorValues[1], $errorValues[2]);
                        session()->flash('error', __('Error Creating Report ({0}).', $msg));
                        return redirect()->back();
                    }
                }
            }
        }
    
        return view('proofing.reports.run', $data);
    }

    public function downloadReport(Request $request)
    {
        $format = $request->input('format');  // This is the format (e.g., 'csv', 'pdf')
        $ssrsParams = json_decode(Crypt::decryptString($request->input('params')), true);
        $reportName = $request->input('reportName');
        $reportExtension = $request->input('reportExtension');
    
        // Generate the SSRS report URL
        $ssrsUrl = SqlServerReportingServices::makeSsrsUrl([
            'ssrsServer' => config('settings.ssrs_server'),
            'ssrsFolder' => config('settings.ssrs_folder'),
            'ssrsReport' => $request->input('report'),
            'format' => $format, 
            'params' => $ssrsParams,
        ]);

        // // SSRS Authentication
        $ssrsUsername = config('settings.ssrs_username');
        $ssrsPassword = config('settings.ssrs_password');

        // Use Http client to download the report with basic authentication
        $response = Http::withBasicAuth($ssrsUsername, $ssrsPassword)->get($ssrsUrl);

    
        // Check if the request was successful
        if ($response->successful()) {
            // Return the report as a stream download
            return response($response->body(), 200)
                    ->header('Content-Type', $response->header('Content-Type'))
                    ->header('Content-Disposition', 'attachment; filename="' .$reportName. '.'.$reportExtension.'"');
        } else {
            // Handle any errors
            return back()->with('error', 'Failed to download report. Please try again.');
        }
    }

}
