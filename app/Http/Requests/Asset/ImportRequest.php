<?php

namespace App\Http\Requests\Asset;

use Illuminate\Foundation\Http\FormRequest;

class ImportRequest extends FormRequest
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
            "*.asset_tag" => ["required", "unique:assets,asset_tag", "distinct"],
            "*.description" => ["required"],
        ];
    }

    public function attributes()
    {
        return [
            "*.asset_tag" => "Asset Tag",
            "*.description" => "Description",
        ];
    }

    public function messages()
    {
        return [
            "*.exists" => ":Attribute is not registered.",
            ".distinct" => ":Attribute is already been taken.",
            ".required" => ":Attribute is required.",
        ];
    }
}
