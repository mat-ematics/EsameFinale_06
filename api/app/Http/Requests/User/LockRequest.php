<?php

namespace App\Http\Requests\User;

use App\Helpers\AppHelpers;
use Illuminate\Foundation\Http\FormRequest;

class LockRequest extends SuspendRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return AppHelpers::updateRequestRules(parent::rules());
    }
}
