<?php

namespace App\Http\Requests\Admin;

class LoginRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'email' =>  ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function messages()
    {
        return [];
    }

    public function attributes()
    {
        return [
            'email' => '邮箱',
            'password' => '密码'
        ];
    }
}
