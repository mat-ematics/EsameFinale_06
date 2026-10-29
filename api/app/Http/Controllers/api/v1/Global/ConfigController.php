<?php

namespace App\Http\Controllers\api\v1\Global;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Global\Config\ConfigUpdateRequest;
use App\Models\Global\Config;
use App\Services\Global\ConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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
        return AppHelpers::jsonResponse(
            empty($configs) ? 'Configs successfully retrieved!' : "There are no Configs present at the moment",
            200, 
            $configs ?: []
        );
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
        $record = AppHelpers::findByKey(Config::class, $config, 'getConfig');

        $configResource = $this->configService->getConfigInfo($record, Gate::check('isAdmin'));
        return AppHelpers::jsonResponse('Config Successfully Retrieved', 200, $configResource);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ConfigUpdateRequest $request, int|string $config)
    {
        $record = AppHelpers::findByKey(Config::class, $config, 'getConfig');

        $updatedConfig = $this->configService->updateConfig($record, $request->validated());
        return AppHelpers::jsonResponse('Config Successfully Updated', 200, $updatedConfig);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
