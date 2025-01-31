<?php

namespace App\Http\Requests;

use App\Services\ResponsesService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PermissionRequest extends FormRequest
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
            'ids.*' => 'exists:permissions,id',
            'name' => 'string|max:255',
            'key_code' => 'string|max:255|unique:permissions,key_code',
            'parent_id' => 'nullable|integer|exists:permissions,id',
            'order' => 'integer',
            'file' => 'file|mimes:xlsx,xls,csv|max:2048',
            'role_ids' => 'array',
            'permission_ids' => 'array',
            'role_ids.*' => 'exists:roles,id',
            'permission_ids.*' => 'exists:permissions,id',
        ];

        if ($this->isMethod('get')) {
        }

        if ($this->isMethod('post')) {
            if ($this->routeIs('permissions.store')) {
                $rules['name'] = 'required';
                $rules['key_code'] = 'required';
                $rules['order'] = 'required';
            }

            if ($this->routeIs('permissions.import_excel')) {
                $rules['file'] = 'required';
            }

            if ($this->routeIs('permissions.assign_permission')) {
                $rules['role_ids'] = 'required';
                $rules['permission_ids'] = 'required';
            }

            if ($this->routeIs('permissions.revoke_permission')) {
                $rules['role_ids'] = 'required';
                $rules['permission_ids'] = 'required';
            }
        }

        if ($this->isMethod('put')) {
            if ($this->routeIs('permissions.update')) {
                $rules['id'] = 'required';
                $rules['name'] = 'required';
                $rules['key_code'] = 'required';
                $rules['order'] = 'required';
            }

            if ($this->routeIs('permissions.restore')) {
                $rules['ids'] = 'required';
            }
        }

        if ($this->isMethod('delete')) {
            if ($this->routeIs('permissions.destroy')) {
                $rules['ids'] = 'required';
            }

            if ($this->routeIs('permissions.delete_completely')) {
                $rules['ids'] = 'required';
            }
        }

        return $rules;
    }

    protected function failedValidation(Validator $validator)
    {
        $response = $this->responsesService->error(422, __('message.validation_failed'), $validator->errors());

        throw new HttpResponseException($response);
    }
}
