<?php

namespace App\Http\Controllers\api\v1\Media;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\Category\CategoryStoreRequest;
use App\Http\Requests\Media\Category\CategoryUpdateRequest;
use App\Models\Media\Category;
use App\Services\Media\CategoryService;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    protected CategoryService $categoryService;
    
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = $this->categoryService->getCategoryCollectionInfo(
            Category::all(),
            Gate::check('isAdmin')
        );

        return AppHelpers::jsonResponse(
            empty($categories) ? 'There are no Categories at the moment' : 'Categories Successfully Retrieved',
            200,
            $categories ?: []
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryStoreRequest $request)
    {
        $category = $this->categoryService->createCategory($request->validated());
        return AppHelpers::jsonResponse('Category Successfully Created', 201, $category);
    }

    /**
     * Display the specified resource.
     */
    public function show(int|string $category)
    {
        $record = AppHelpers::findByKey(Category::class, $category, 'getCategory');

        $resource = $this->categoryService->getCategoryInfo($record, Gate::check('isAdmin'));
        return AppHelpers::jsonResponse('Category Successfully Retrieved', 200, $resource);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryUpdateRequest $request, int|string $category)
    {
        $record = AppHelpers::findByKey(Category::class, $category, 'getCategory');

        $updated = $this->categoryService->updateCategory($record, $request->validated());
        return AppHelpers::jsonResponse('Category Successfully Updated', 200, $updated);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int|string $category)
    {
        $record = AppHelpers::findByKey(Category::class, $category, 'getCategory');

        $this->categoryService->deleteCategory($record);
        return AppHelpers::jsonResponse('Category Successfully Deleted', 200);
    }
}
