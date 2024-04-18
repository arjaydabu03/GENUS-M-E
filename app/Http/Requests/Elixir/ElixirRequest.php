<?php

namespace App\Http\Requests\Elixir;

use Illuminate\Foundation\Http\FormRequest;

class ElixirRequest extends FormRequest
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
            "*.mir_id" => "required",
            "*.status" => "required",
            "*.orders.*.order_id" => ["required", "numeric"],
        ];
    }
    public function attributes()
    {
        return [
            "*.mir_id" => "Transaction id",
            "*.status" => "Status",
            "*.orders.*.order_id" => "Order id",
        ];
    }

    public function messages()
    {
        return [
            "*.mir_id.required" => "This :attribute is required.",
            "*.status.required" => "This :attribute is required.",
            "*.orders.*.order_id.required" => "This :attribute is required.",
            "*.orders.*.order_id.numeric" => "This :attribute must be a number.",
        ];
    }
}
