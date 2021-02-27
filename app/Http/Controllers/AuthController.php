<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use App\Models\User;

class AuthController extends Controller
{
  public function unauthorized()
  {
    return response()->json(['error' => 'Não autorizado'], 401);
  }

  public function create(Request $request)
  {
    $array = ['error' => ''];

    $validator = Validator::make($request->all(), [
      'name' => 'required|string',
      'email' => 'required|email|unique:users,email',
      'password' => 'required|string'
    ]);

    if(!$validator->fails())
    {
      $name = $request->input('name');
      $email = $request->input('email');
      $password = $request->input('password');

      $user = new User();
      $user->name = $name;
      $user->email = $email;
      $user->password = password_hash($password, PASSWORD_DEFAULT);
      $user->save();

      $token = Auth::attempt(['email' => $email, 'password' => $password]);
      if(!$token) {
        $array['error'] = 'Ocorreu um erro!';
      }

      $info = Auth::user();
      $info['avatar'] = url('media/avatars/'.$info['avatar']); // Mount The url photo complete using url laravel

      $array['data'] = $info;
      $array['token'] = $token;

    } else {
      $array['error'] = $validator->errors()->first();
    }

    return $array;
  }

  public function login(Request $request)
  {
    $array = ['error' => ''];

    $validator = Validator::make($request->all(), [
      'email' => 'required|string|min:5|max:200',
      'password' => 'required|string|min:3|max:200'
    ]);

    if(!$validator->fails())
    {
      $email = $request->input('email');
      $password = $request->input('password');

      $token = Auth::attempt(['email' => $email, 'password' => $password]);
      if(!$token) {
        $array['error'] = 'Email e/ou senha incorretos!';
      } else {
        $info = Auth::user();
        $info['avatar'] = url('media/avatars/'.$info['avatar']);

        $array['data'] = $info;
        $array['token'] = $token;
      }

    } else {
      $array['error'] = $validator->errors()->first();
    }

    return $array;
  }

  public function logout()
  {
    Auth::logout();
    return ['error' => ''];
  }

  public function refresh()
  {
    $array = ['error' => ''];
    $token = Auth::refresh(); // Update Token

    $info = Auth::user(); // Get info from user, simple array one, not map.
    $info['avatar'] = url('media/avatars/'.$info['avatar']); // The correct url from photo user

    $array['data'] = $info;
    $array['token'] = $token;

    return $array;
  }

}
