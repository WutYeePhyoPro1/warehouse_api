<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {

        $userId = $this->route('user');
        return [
            'title' => 'required|in:Mr,Mrs',
            'name' => 'required|string|max:255',
            'emp_id' => [
                'required',
                'string',
                'max:255',
                // Rule::unique('users', 'employee_number')->ignore($userId)
            ],
            'password' => $userId ? 'nullable|string|min:8|confirmed' : 'required|string|min:8|confirmed',
            'password_confirmation' => $userId ? 'nullable' : 'required',
            'branch_id' => 'required|exists:branches,id',
            'role_id' => ['required', 'exists:roles,id'],
            'status' => ['required', 'in:1,0'],
        ];
    }


    public function messages()
    {
        return [
            // 'name.required' => 'The name is required.',
            // 'employee_number' => 'The employee number is required',
            // 'password.confirmed' => 'The password and confirmation password do not match.',
        ];
    }
}
