<?php

namespace App\Http\Requests;

use App\Services\ResponsesService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CategoryRequest extends FormRequest
{
    protected $responsesService, $manageFilesService;

    public function __construct(ResponsesService $responsesService)
    {
        $this->responsesService = $responsesService;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'page' => 'integer',
            'limit' => 'integer',
            'ids' => 'array',
            'ids.*' => 'exists:categories,id',
            'name' => 'string|max:255',
            'url' => 'nullable|string|max:255',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'order' => 'integer',
            'icon' => 'nullable|string|max:255',
            'file' => 'file|mimes:xlsx,xls,csv|max:2048',
        ];

        if ($this->isMethod('get')) {
        }

        if ($this->isMethod('post')) {
            if ($this->routeIs('categories.store')) {
                $rules['name'] = 'required';
                $rules['order'] = 'required';
            }

            if ($this->routeIs('categories.import_excel')) {
                $rules['file'] = 'required';
            }
        }

        if ($this->isMethod('put')) {
            if ($this->routeIs('categories.update')) {
                $rules['id'] = 'required';
                $rules['name'] = 'required';
                $rules['order'] = 'required';
            }

            if ($this->routeIs('categories.restore')) {
                $rules['ids'] = 'required';
            }
        }

        if ($this->isMethod('delete')) {
            if ($this->routeIs('categories.destroy')) {
                $rules['ids'] = 'required';
            }
            
            if ($this->routeIs('categories.delete_completely')) {
                $rules['ids'] = 'required';
            }
        }

        return $rules;
    }

    protected function failedValidation(Validator $validator)
    {
        $response = $this->responsesService->error(422, __('messages.validation_failed'), $validator->errors());

        throw new HttpResponseException($response);
    }
}
