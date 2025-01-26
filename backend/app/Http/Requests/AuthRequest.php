<?php

namespace App\Http\Requests;

use App\Services\ResponsesService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AuthRequest extends FormRequest
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
            'image' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
            'name' => 'string|max:255',
            'email' => 'string|email|email:rfc,dns|max:255',
            'password' => 'string|min:8|max:255|regex:/[A-Z]/|regex:/[a-z]/|regex:/[0-9]/|regex:/[@$!%*?&]/',
            'token' => 'string|max:255',
        ];

        if ($this->isMethod('post')) {
            if ($this->routeIs('auth.register')) {
                $rules['name'] = 'required';
                $rules['email'] = 'required|unique:users,email';
                $rules['password'] = 'required|confirmed';
            }

            if ($this->routeIs('auth.login')) {
                $rules['email'] = 'required|exists:users,email';
                $rules['password'] = 'required';
            }

            if ($this->routeIs('auth.verify_email')) {
                $rules['email'] = 'required|exists:users,email';
                $rules['token'] = 'required';
            }

            if ($this->routeIs('auth.forgot_password')) {
                $rules['email'] = 'required|exists:users,email';
            }

            if ($this->routeIs('auth.reset_password')) {
                $rules['email'] = 'required|exists:users,email';
                $rules['token'] = 'required';
                $rules['password'] = 'required|confirmed';
            }

            if ($this->routeIs('auth.send_link_password')) {
                $rules['email'] = 'required|exists:users,email';
            }

            if ($this->routeIs('auth.send_link_verify_email')) {
                $rules['email'] = 'required|exists:users,email';
            }

            if ($this->routeIs('auth.update_password')) {
                $rules['old_password'] = 'required|string|min:8|max:255';
                $rules['new_password'] = 'required|string|min:8|max:255';
                $rules['password_confirmation'] = 'same:new_password';
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
