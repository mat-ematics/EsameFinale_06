<?php

namespace App\Http\Controllers\api\v1;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Global\ConfigUpdateRequest;
use App\Models\Global\Config;
use App\Services\Global\ConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ConfigController extends Controller
{

    protected ConfigService $configService;
    
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $configs = $this->configService->getConfigCollectionInfo(Config::all(), Gate::check('isAdmin'));
        return AppHelpers::jsonResponse('Configs successfully retrieved!', 200, $configs);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string|int $config)
    {
        if (ctype_digit((string) $config)) {
            $configRecord = Config::find((int) $config);
        } else {
            $configRecord = Config::getConfig($config);
        }
        if (!$configRecord) {
            return AppHelpers::jsonResponse('Config Not Found', 404);
        }

        $configResource = $this->configService->getConfigInfo($configRecord, Gate::check('isAdmin'));
        return AppHelpers::jsonResponse('Config Successfully Retrieved', 200, $configResource);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ConfigUpdateRequest $request, int|string $config)
    {
        if (Gate::allows('isAdmin')) {

            $data = $request->validated();
            
            try {
                //Ricerca su ID/Nome o Label
                if (ctype_digit((string) $config)) {
                    $configRecord = Config::find((int) $config);
                } else {
                    $configRecord = Config::getModelByConfig($config);
                    Log::alert($configRecord);
                }

                if (!$configRecord) {
                    throw new HttpException(404, 'Config Not Found');
                }

                $updatedconfig = $this->configService->updateConfig($configRecord, $data);
                return AppHelpers::jsonResponse('Config Successfully Updated', 200, $updatedconfig);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
