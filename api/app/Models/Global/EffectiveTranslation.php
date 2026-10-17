<?php

namespace App\Models\Global;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class EffectiveTranslation extends Model
{
    protected $table = 'effective_translation';

    //NO CHIAVE PRIMARIA O TIMESTAMPS
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $guarded = ['*'];

    /* --------- RELAZIONI ---------*/

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    /* ---- FORZA READ-ONLY ---- */

    public function save(array $options = [])
    {
        throw new LogicException('effective_translations is a read-only view.');
    }

    public function delete()
    {
        throw new LogicException('effective_translations is a read-only view.');
    }

    /* --------- METODI STATICI ---------*/

    /**
     * Takes in a query on the Effective Translation and scopes it by the Language ID
     */
    public static function scopeForLanguage($query, int $languageId)
    {
        return $query->where('language_id', $languageId);
    }
}
