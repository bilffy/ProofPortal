<?php

namespace App\Http\Controllers\Proofing;

use App\Http\Controllers\Controller;
use App\Services\Proofing\SqlServerReportingServices;
use App\Services\Proofing\StatusService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
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
        $allowedSorts = ['id', 'name', 'description'];
        $sort = in_array($request->input('sort'), $allowedSorts, true) ? $request->input('sort') : 'id';
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';

        $query = Report::with('report_roles')
            ->whereHas('report_roles', function ($query) {
                $query->whereIn('role_id', Auth::user()->roles->pluck('id'));
            })
            ->where('is_deleted', 0)
            ->select('id', 'name', 'description', 'query');

        if ($request->has('record_filter')) {
            $query = $this->applyFilters($query, $request->input('record_filter'));
        }

        $reports = $query->orderBy($sort, $direction)->paginate(10);
        $user = Auth::user();

        return view('proofing.reports.index', [
            'reports' => $reports,
            'sort' => $sort,
            'direction' => $direction,
            'user' => new UserResource($user),
        ]);
    }

    public function run($reportName = null, $tsJobId = null, $tsFolderId = null)
    {
        $user = Auth::user();
        if (is_null($reportName)) {
            session()->flash('error', __('Sorry, please select a Report and try again.'));
            return redirect()->route('reports');
        }

        try {
            if (!is_null($tsJobId) && $tsJobId !== '') {
                $tsJobId = (int) Crypt::decryptString($tsJobId);
            }
            if (!is_null($tsFolderId) && $tsFolderId !== '') {
                $tsFolderId = (int) Crypt::decryptString($tsFolderId);
            }
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            session()->flash('error', __('Invalid report parameters. Please try again.'));
            return redirect()->route('reports');
        }

        $currentSeasonID = Session::has('selectedSeason') ? Session::get('selectedSeason')->ts_season_id : '';
        $seasonList = Season::orderBy('code', 'asc')->pluck('code', 'ts_season_id')->toArray();

        $report = Report::where('query', $reportName)->first();
        if (empty($report)) {
            session()->flash('error', __('Sorry, please select a Report and try again.'));
            return redirect()->route('reports');
        }

        $passedParamValues = [];
        if (!is_null($tsJobId)) {
            $passedParamValues[] = $tsJobId;
        }
        if (!is_null($tsFolderId)) {
            $passedParamValues[] = $tsFolderId;
        }

        $passedParamCount = count($passedParamValues);

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

                $paramsToBePassedThrough = array_slice($passedParamValues, 0, $numOfArguments);
                $paramQuery = call_user_func_array([Report::class, $paramQueryName], $paramsToBePassedThrough);

                $parameter['query'] = $paramQuery;
                $reportParams[] = $parameter;
            }
        }

        $requiredParamCount = count($reportParams);

        while ($passedParamCount < $requiredParamCount) {
            $nextParam = $reportParams[$passedParamCount];

            if ($nextParam['query']->isEmpty()) {
                $emptyMessage = $this->emptyParamMessage($nextParam['queryName'] ?? '');

                if ($emptyMessage !== null) {
                    return view('proofing.reports.run_params', [
                        'currentSeasonID' => $currentSeasonID,
                        'seasonList' => $seasonList,
                        'report' => $report,
                        'reportName' => $report->name,
                        'reportDescription' => $report->description,
                        'user' => new UserResource($user),
                        'passedParamCount' => $passedParamCount,
                        'passedParamValues' => $passedParamValues,
                        'reportParams' => $reportParams,
                        'requiredParamCount' => $requiredParamCount,
                        'emptyMessage' => $emptyMessage,
                    ]);
                }

                $passedParamCount++;
                continue;
            }

            return view('proofing.reports.run_params', [
                'currentSeasonID' => $currentSeasonID,
                'seasonList' => $seasonList,
                'report' => $report,
                'reportName' => $report->name,
                'reportDescription' => $report->description,
                'user' => new UserResource($user),
                'passedParamCount' => $passedParamCount,
                'passedParamValues' => $passedParamValues,
                'reportParams' => $reportParams,
                'requiredParamCount' => $requiredParamCount,
                'emptyMessage' => null,
            ]);
        }

        $reportParamPayload = SqlServerReportingServices::buildSsrsDownloadParams(
            $report->query,
            $user->email,
            $passedParamValues,
            $reportParams,
            $report->name
        );

        $data = [
            'currentSeasonID' => $currentSeasonID,
            'seasonList' => $seasonList,
            'report' => $report,
            'reportName' => $report->name,
            'reportDescription' => $report->description,
            'user' => new UserResource($user),
            'passedParamCount' => $passedParamCount,
            'passedParamValues' => $passedParamValues,
            'reportParams' => $reportParams,
            'requiredParamCount' => $requiredParamCount,
            'resultCount' => Report::countResults($report->query, $passedParamValues),
            'ssrsReportName' => $report->query,
            'ssrsParams' => $reportParamPayload['ssrsParams'],
            'sqlParams' => $reportParamPayload['sqlParams'],
            'downloadNameBuilder' => $reportParamPayload['downloadName'],
            'ssrsParamsEncrypt' => Crypt::encryptString(json_encode([
                'ssrsParams' => $reportParamPayload['ssrsParams'],
                'reportQuery' => $report->query,
            ])),
        ];

        return view('proofing.reports.run', $data);
    }

    private function emptyParamMessage(string $queryName): ?string
    {
        return match ($queryName) {
            'mySchoolsIds', 'mySchools' => __('No Jobs have been synced for proofing yet.'),
            default => null,
        };
    }

    public function downloadReport(Request $request)
    {
        $format = $request->input('format', 'csv');
        $payload = json_decode(Crypt::decryptString($request->input('params')), true);
        $ssrsParams = $payload['ssrsParams'] ?? [];
        $ssrsReportName = $payload['reportQuery'] ?? null;
        $downloadName = $request->input('reportName');

        if (empty($ssrsReportName)) {
            abort(403, 'Invalid report request.');
        }

        $authorizedReport = Report::where('query', $ssrsReportName)
            ->whereHas('report_roles', function ($query) {
                $query->whereIn('role_id', Auth::user()->roles->pluck('id'));
            })
            ->where('is_deleted', 0)
            ->first();

        if (!$authorizedReport) {
            abort(403, 'You are not authorized to download this report.');
        }

        $result = SqlServerReportingServices::downloadFromReportServer(
            $ssrsReportName,
            $format,
            $ssrsParams
        );

        if (!$result['success']) {
            Log::error('SSRS report download failed', [
                'report' => $ssrsReportName,
                'format' => $format,
                'params' => $result['params'] ?? $ssrsParams,
                'url' => $result['url'],
                'error' => $result['error'],
            ]);

            return back()->with('error', 'Report download failed. Please try again.');
        }

        Log::info('SSRS report download succeeded', [
            'report' => $ssrsReportName,
            'format' => $format,
            'params' => $result['params'] ?? $ssrsParams,
            'url' => $result['url'],
            'bytes' => strlen($result['body'] ?? ''),
        ]);

        $filename = $downloadName . '.' . $result['extension'];

        return response($result['body'], 200)
            ->header('Content-Type', $result['contentType'])
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
