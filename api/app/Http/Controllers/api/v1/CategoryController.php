<?php

namespace App\Http\Controllers\api\v1;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Category\CategoryStoreRequest;
use App\Http\Requests\Category\CategoryUpdateRequest;
use App\Models\Media\Category;
use App\Services\Media\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        $categories = $this->categoryService->getCategoryCollectionInfo(Category::all(), Gate::check('isAdmin'));
        if (empty($categories))
        {
            $categories = 'There are no Categories Present at the moment';
        }
        return AppHelpers::jsonResponse('Categories Successfully Retrieved', 200, $categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryStoreRequest $request)
    {
        if (Gate::allows('isAdmin')) {
            try {
                $category = $this->categoryService->createCategory($request->validated());
                return AppHelpers::jsonResponse('Category Successfully Created', 201, $category);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }

    /**
     * Display the specified resource.
     */
    public function show(int|string $category)
    {
        if (ctype_digit((string) $category)) {
            $categoryRecord = Category::find((int) $category);
        } else {
            $categoryRecord = Category::getCategory($category);
        }
        if (!$categoryRecord) {
            return AppHelpers::jsonResponse('Category Not Found', 404);
        }

        $categoryResource = $this->categoryService->getCategoryInfo($categoryRecord, Gate::check('isAdmin'));
        return AppHelpers::jsonResponse('Category Successfully Retrieved', 200, $categoryResource);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryUpdateRequest $request, int|string $category)
    {
        if (Gate::allows('isAdmin')) {

            $data = $request->validated();
            
            try {
                //Ricerca su ID/Nome o Label
                if (ctype_digit((string) $category)) {
                    $categoryRecord = Category::find((int) $category);
                } else {
                    $categoryRecord = Category::getCategory($category);
                }

                if (!$categoryRecord) {
                    throw new HttpException(404, 'Category Not Found');
                }

                $updatedCategory = $this->categoryService->updateCategory($categoryRecord, $data);
                return AppHelpers::jsonResponse('Category Successfully Updated', 200, $updatedCategory);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int|string $category)
    {
        if (Gate::allows('isAdmin')) {
            try {
                 //Ricerca su ID/Nome o Label
                if (ctype_digit((string) $category)) {
                    $categoryRecord = Category::find((int) $category);
                } else {
                    $categoryRecord = Category::getCategory($category);
                }
                
                if (!$categoryRecord) {
                    throw new HttpException(404, 'Category Not Found');
                }

                $this->categoryService->deleteCategory($categoryRecord);
                return AppHelpers::jsonResponse('Category Successfully Deleted', 204);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }
}
