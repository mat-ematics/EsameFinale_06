<?php

namespace App\Services\Media;

use App\Helpers\AppHelpers;
use App\Http\Resources\Category\CategoryCollection;
use App\Http\Resources\Category\CategoryResource;
use App\Models\Media\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategoryService {

    /**
     * Ritorna le informazioni associate alla categoria passata
     */
    public function getCategoryInfo(Category $category, bool $isAdmin = false) : CategoryResource
    {
        return new CategoryResource($category, $isAdmin);
    }

    /**
     * Ritorna le informazioni della collection di categorie passate
     */
    public function getCategoryCollectionInfo(Collection|array $categories, bool $isAdmin = false) : array
    {
        return (new CategoryCollection($categories, $isAdmin))->toArray();
    }

    /**
     * Crea una nuova categoria e ne ritorna il Model.
     * Attenzione: i dati non sono validati all'interno della funzione
     */
    public function createCategory(array $data) : Category
    {
        return DB::transaction(function () use ($data) {
            
            //Creazione Categoria

            $newCategory = Category::create([
                'name' => $data['name'],
                'label' => $data['label'],
                'description' => $data['description'] ?? null,
            ]);

            return $newCategory;
        });
    }

     /**
     * Aggiorna le informazioni della categoria passata
     */
    public function updateCategory(Category $category, array $data) : CategoryResource
    {
        $data = array_filter($data);

        //Filtra i valori nulli
        $updateData = array_filter(Arr::only($data, ['name', 'label']));

        
        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description']; // puo' essere null - ignorato se non passato
        }

        //Aggiorna Username e/o password se presenti
        if (!empty($updateData)) {
            $category->update($updateData);
        }

        return $this->getCategoryInfo($category, true);
    }

    /**
     * Elimina la Categoria Passata
     */
    public function deleteCategory(Category $category)
    {
        return $category->deleteOrFail();
    }
}