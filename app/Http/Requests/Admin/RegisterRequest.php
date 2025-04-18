<?php

namespace App\Http\Requests\Admin;

class RegisterRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' =>  ['required', 'string', 'email', 'max:255', 'unique:users'],
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
            'name' => '称呼',
            'email' => '邮箱',
            'password' => '密码'
        ];
    }
}
