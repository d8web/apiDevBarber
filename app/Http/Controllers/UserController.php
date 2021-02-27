<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;

use App\Models\UserAppointment;
use App\Models\UserFavorite;
use App\Models\Barber;
use App\Models\BarberService;
use App\Models\User;

class UserController extends Controller
{
  private $loggedUser;

  public function __construct()
  {
    $this->loggedUser = Auth::user();
  }

  public function read()
  {
    $array = ['error' => ''];

    $info = $this->loggedUser;
    $info['avatar'] = url('media/avatars/'.$info['avatar']);
    $array['data'] = $info;

    return $array;
  }

  public function toggleFavorite(Request $request)
  {
    $array = ['error' => ''];

    $id_barber = $request->input('id_barber');
    $barber = Barber::find($id_barber);

    if($barber) {
      $fav = UserFavorite::select()
        ->where('id_user', $this->loggedUser->id)
        ->where('id_barber', $id_barber)
      ->first();

      if($fav) {
        // Remove
        $fav->delete();
        $array['have'] = false;

      } else {
        // Add Barber favorite
        $newFav = new UserFavorite();
        $newFav->id_user = $this->loggedUser->id;
        $newFav->id_barber = $id_barber;
        $newFav->save();

        $array['have'] = true;
      }
    } else {
      $array['error'] = 'Barber not found!';
    }

    return $array;
  }

  public function getFavorites()
  {
    $array = ['error' => '', 'list' => []];

    $favs = UserFavorite::where('id_user', $this->loggedUser->id)->get();
    if($favs) {
        foreach($favs as $fav) {
            // Get barber by id, rest id came by userfavorites field id_barber
            $barber = Barber::find($fav['id_barber']);
            $barber['avatar'] = url('media/avatars/'.$barber['avatar']);
            $array['list'][] = $barber;
        }
    }

    return $array;
  }

  public function getAppointments()
  {
    $array = ['error' => '', 'list' => []];

    $apps = UserAppointment::where('id_user', $this->loggedUser->id)
        ->orderBy('ap_datetime', 'DESC')
        ->get();

    if($apps) {
        foreach($apps as $app)
        {
            // Barber info
            $barber = Barber::find($app['id_barber']);
            $barber['avatar'] = url('media/avatars/'.$barber['avatar']);

            // Services info
            $service = BarberService::find($app['id_service']);
            $array['list'][] = [
                'id' => $app['id'],
                'datetime' => $app['ap_datetime'],
                'barber' => $barber,
                'services' => $service
            ];
        }
    } else {
        $array['error'] = 'Nada para mostrar!';
    }

    return $array;
  }

  public function update(Request $request)
  {
      $array = ['error' => ''];

      $rules = [
          'name' => 'min:2',
          'email' => 'email|unique:users',
          'password' => 'same:password_confirm',
          'password_confirm' => 'same:password'
      ];

      $validator = Validator::make($request->all(), $rules);
      if($validator->fails()) {
          $array['error'] = $validator->errors()->first();
          return $array;
      }

      $name = $request->input('name');
      $email = $request->input('email');
      $password = $request->input('password');
      $password_confirm = $request->input('password_confirm');

      $user = User::find($this->loggedUser->id);

      if($name) {
          $user->name = $name;
      }

      if($email) {
          $user->email = $email;
      }

      if($password) {
          $user->password = password_hash($password, PASSWORD_DEFAULT);
      }

      $user->save();

      return $array;
  }

  public function updateAvatar(Request $request)
  {
      $array = ['error' => ''];

      $rules = [
          'avatar' => 'required|image|mimes:png,jpg,jpeg'
      ];

      $validator = Validator::make($request->all(), $rules);
      if($validator->fails()) {
          $array['error'] = $validator->errors()->messages();
          return $array;
      }

      $avatar = $request->file('avatar');

      $dest = public_path('/media/avatars');
      $avatarName = md5(time().rand(0,999)).'.jpg';

      $img = Image::make($avatar->getRealPath());
      $img->fit(300, 300)->save($dest.'/'.$avatarName);

      $user = User::find($this->loggedUser->id);
      $user->avatar = $avatarName;
      $user->save();

      return $array;
  }

}
