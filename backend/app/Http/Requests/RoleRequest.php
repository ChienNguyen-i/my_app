<?php

namespace App\Http\Requests;

use App\Services\ResponsesService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RoleRequest extends FormRequest
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
            'ids.*' => 'exists:roles,id',
            'name' => 'string|max:255',
            'file' => 'file|mimes:xlsx,xls,csv|max:2048',
            'user_ids' => 'array',
            'role_ids' => 'array',
            'user_ids.*' => 'exists:users,id',
            'role_ids.*' => 'exists:roles,id',
        ];

        if ($this->isMethod('get')) {
        }

        if ($this->isMethod('post')) {
            if ($this->routeIs('roles.store')) {
                $rules['name'] = 'required';
            }

            if ($this->routeIs('roles.import_excel')) {
                $rules['file'] = 'required';
            }

            if ($this->routeIs('roles.assign_role')) {
                $rules['user_ids'] = 'required';
                $rules['role_ids'] = 'required';
            }

            if ($this->routeIs('roles.revoke_role')) {
                $rules['user_ids'] = 'required';
                $rules['role_ids'] = 'required';
            }
        }

        if ($this->isMethod('put')) {
            if ($this->routeIs('roles.update')) {
                $rules['id'] = 'required';
                $rules['name'] = 'required';
            }

            if ($this->routeIs('roles.restore')) {
                $rules['ids'] = 'required';
            }
        }

        if ($this->isMethod('delete')) {
            if ($this->routeIs('roles.destroy')) {
                $rules['ids'] = 'required';
            }

            if ($this->routeIs('roles.delete_completely')) {
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
