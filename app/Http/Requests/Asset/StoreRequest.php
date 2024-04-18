<?php

namespace App\Http\Requests\Asset;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            "asset_tag" => [
                "required",
                "string",
                $this->route()->asset
                    ? "unique:assets,asset_tag," . $this->route()->asset
                    : "unique:assets,asset_tag",
            ],
            "description" => [
                "required",
                "string",
                $this->route()->asset
                    ? "unique:assets,description," . $this->route()->asset
                    : "unique:assets,description",
            ],
        ];
    }
}
