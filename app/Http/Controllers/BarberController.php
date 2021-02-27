<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\Barber;
use App\Models\BarberService;
use App\Models\BarberPhoto;
use App\Models\BarberTestimonial;
use App\Models\BarberAvailability;
use App\Models\User;
use App\Models\UserAppointment;
use App\Models\UserFavorite;

class BarberController extends Controller
{
  private $loggedUser;

  public function __construct()
  {
    $this->loggedUser = Auth::user();
  }

  /*
  public function createRandom()
  {
    $array = ['error' => ''];

    for($q=0;$q<15;$q++)
    {
      $names = ['Bonieky', 'Paulo', 'Pedro', 'Amanda', 'Leticia', 'Gabriel', 'Ronaldo'];
      $lastnames = ['Silva', 'Lacerda', 'Diniz', 'Alvaro', 'Sousa', 'Gomes'];

      $servicos = ['Corte', 'Pintura', 'Aparação', 'Enfeite'];
      $servicos2 = ['Cabelo', 'Unha', 'Pelos', 'Sobrancelhas'];

      $depos = [
          'Maecenas ullamcorper mi a justo egestas ultrices quis eget lacus.',
          'Fusce malesuada justo in maximus auctor. In quis enim in.',
          'Aliquam dapibus id dolor non auctor. Morbi vehicula nec ex.',
          'Sed pulvinar, neque sed blandit fermentum, dui mi sollicitudin turpis.',
          'Nam luctus accumsan enim, a finibus sapien rhoncus fermentum. Praesent.'
      ];

      $newBarber = new Barber();
      $newBarber->name = $names[rand(0, count($names)-1)].' '.$lastnames[rand(0, count($lastnames)-1)];
      $newBarber->avatar = rand(1, 4).'.png';
      $newBarber->stars = rand(2, 4).'.'.rand(0, 9);
      $newBarber->latitude = '-23.5'.rand(0,9).'30907';
      $newBarber->longitude = '-46.6'.rand(0,9).'82795'; // Locales from sp brazilian
      $newBarber->save();

      $ns = rand(3, 6);

      for($w=0;$w<4;$w++)
      {
        $newBarberPhoto = new BarberPhoto();
        $newBarberPhoto->id_barber = $newBarber->id;
        $newBarberPhoto->url = rand(1, 5).'.png';
        $newBarberPhoto->save();
      }

      for($w=0;$w<$ns;$w++)
      {
        $newBarberService = new BarberService();
        $newBarberService->id_barber = $newBarber->id;
        $newBarberService->name = $servicos[rand(0, count($servicos)-1)].' de '.$servicos2[rand(0, count($servicos2)-1)];
        $newBarberService->price = rand(1, 99).'.'.rand(0, 100);
        $newBarberService->save();
      }

      for($w=0;$w<3;$w++) {
        $newBarberTestimonial = new BarberTestimonial();
        $newBarberTestimonial->id_barber = $newBarber->id;
        $newBarberTestimonial->name = $names[rand(0, count($names)-1)].' '.$lastnames[rand(0, count($lastnames)-1)];
        $newBarberTestimonial->rate = rand(2, 4).'.'.rand(0, 9);
        $newBarberTestimonial->body = $depos[rand(0, count($depos)-1)];
        $newBarberTestimonial->save();
      }

      for($e=0;$e<4;$e++)
      {
        $rAdd = rand(7, 10);
        $hours = [];
        for($r=0;$r<8;$r++)
        {
          $time = $r + $rAdd;
          if($time < 10)
          {
              $time = '0'.$time;
          }
          $hours[] = $time.':00';
        }
        $newBarberAvail = new BarberAvailability();
        $newBarberAvail->id_barber = $newBarber->id;
        $newBarberAvail->weekday = $e;
        $newBarberAvail->hours = implode(',', $hours);
        $newBarberAvail->save();
      }

    }

    return $array;
  }
  */

  public function searchGeo($address)
  {
    $key = env('MAPS_KEY', null);

    $address = urlencode($address);
    $url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.$address.'&key='.$key;

    // Requisition using curl
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $res = curl_exec($ch);
    // close the connection
    curl_close($ch);

    return json_decode($res, true);
  }

  public function list(Request $request)
  {
    $array = ['error' => ''];

    $lat = $request->input('lat');
    $lng = $request->input('lng');
    $city = $request->input('city');
    $offset = $request->input('offset');

    if(!$offset)
    {
      $offset = 0;
    }

    if(!empty($city))
    {
      $res = $this->searchGeo($city);

      if(count($res['results']) > 0) {
        $lat = $res['results'][0]['geometry']['location']['lat'];
        $lng = $res['results'][0]['geometry']['location']['lng'];
      }
    } elseif(!empty($lat) && !empty($lng))
    {
      $res = $this->searchGeo($lat.','.$lng);

      if(count($res['results']) > 0) {
        $city = $res['results'][0]['formatted_address'];
      }
    } else
    {
      $lat = '-23.5630907';
      $lng = '-46.6682795';
      $city = 'São Paulo';
    }

    $barbers = Barber::select(Barber::raw('*, SQRT(
      POW(69.1 * (latitude - '.$lat.'), 2) +
      POW(69.1 * ('.$lng.' - longitude) * COS(latitude / 57.3), 2)) AS distance'))
      ->havingRaw('distance < ?', [10])
      ->orderBy('distance', 'ASC')
      ->offset($offset)
      ->limit(5)
      ->get();

    foreach($barbers as $bkey => $bvalue) {
      $barbers[$bkey]['avatar'] = url('media/avatars/'.$barbers[$bkey]['avatar']);
    }

    $array['data'] = $barbers;
    $array['loc'] = 'São Paulo';

    return $array;
  }

  public function one($id) {
    $array = ['error' => ''];

    $barber = Barber::find($id);

    if($barber) {
        $barber['avatar'] = url('media/avatars/'.$barber['avatar']);
        $barber['favorited'] = false;
        $barber['photos'] = [];
        $barber['services'] = [];
        $barber['testimonials'] = [];
        $barber['available'] = [];

        // Verificando favorito
        $cFavorite = UserFavorite::where('id_user', $this->loggedUser->id)
            ->where('id_barber', $barber->id)
            ->count();
        if($cFavorite > 0) {
            $barber['favorited'] = true;
        }

        // Pegando as fotos do Barbeiro
        $barber['photos'] = BarberPhoto::select(['id', 'url'])
          ->where('id_barber', $barber->id)
          ->get();
        foreach($barber['photos'] as $bpkey => $bpvalue) {
          $barber['photos'][$bpkey]['url'] = url('media/uploads/'.$barber['photos'][$bpkey]['url']);
        }

        // Pegando os serviços do Barbeiro
        $barber['services'] = BarberService::select(['id', 'name', 'price'])
          ->where('id_barber', $barber->id)
          ->get();

        // Pegando os depoimentos do Barbeiro
        $barber['testimonials'] = BarberTestimonial::select(['id', 'name', 'rate', 'body'])
            ->where('id_barber', $barber->id)
            ->get();

        // Pegando disponibilidade do Barbeiro
        $availability = [];

        // - Pegando a disponibilidade crua
        $avails = BarberAvailability::where('id_barber', $barber->id)->get();
        $availWeekdays = [];
        foreach($avails as $item) {
            $availWeekdays[$item['weekday']] = explode(',', $item['hours']);
        }

        // - Pegar os agendamentos dos próximos 20 dias
        $appointments = [];
        $appQuery = UserAppointment::where('id_barber', $barber->id)
            ->whereBetween('ap_datetime', [
                date('Y-m-d').' 00:00:00',
                date('Y-m-d', strtotime('+20 days')).' 23:59:59'
            ])
            ->get();
        foreach($appQuery as $appItem) {
            $appointments[] = $appItem['ap_datetime'];
        }

        // - Gerar disponibilidade real
        for($q=0;$q<20;$q++) {
          $timeItem = strtotime('+'.$q.' days');
          $weekday = date('w', $timeItem);

          if(in_array($weekday, array_keys($availWeekdays))) {
            $hours = [];

            $dayItem = date('Y-m-d', $timeItem);

            foreach($availWeekdays[$weekday] as $hourItem) {
                $dayFormated = $dayItem.' '.$hourItem.':00';
                if(!in_array($dayFormated, $appointments)) {
                  $hours[] = $hourItem;
                }
            }

            if(count($hours) > 0) {
              $availability[] = [
                'date' => $dayItem,
                'hours' => $hours
              ];
            }
          }
        }

        $barber['available'] = $availability;

        $array['data'] = $barber;
    } else {
      $array['error'] = 'Barbeiro não existe';
      return $array;
    }

    return $array;
    }

    public function setAppointment($id, Request $request) {
        $array = ['error' => ''];

        $service = $request->input('service');
        $year = intval($request->input('year'));
        $month = intval($request->input('month'));
        $day = intval($request->input('day'));
        $hour = intval($request->input('hour'));

        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $day = str_pad($day, 2, '0', STR_PAD_LEFT);
        $hour = str_pad($hour, 2, '0', STR_PAD_LEFT);

        //verificar se o servico do barbeiro existe
        $barberservice = BarberService::where('id', $service)
            ->where('id_barber', $id)
            ->first();
            if(!$barberservice) {
                $array['error'] = 'Serviço inexistente';
                return $array;
            }

        //verificar se a data é real
        $apDate = $year.'-'.$month.'-'.$day.' '.$hour.':00:00';

        if(!strtotime($apDate) > 0) {
            $array['error'] = 'Data invalida!';
            return $array;
        }
        //vefificar se o barbeiro ja possui agendamento nesta data/hora
        $apps = UserAppointment::where('id_barber', $id)
            ->where('ap_datetime', $apDate)
            ->get();
        if(count($apps) > 0) {
            $array['error'] = 'Horário/dia indisponivel!';
            return $array;
        }

        //verificar se o barbeiro atende nesta data
        $weekDay = date('w', strtotime($apDate));
        $avail = BarberAvailability::select()
            ->where('id_barber', $id)
            ->where('weekday', $weekDay)
            ->first();
            if(!$avail) {
                $array['error'] = 'Barbeiro não atende neste dia!';
                return $array;
            }
        //Verificar se o barbeiro atende nesta hora
        $hours = explode(',', $avail['hours']);
        if(!in_array($hour.':00', $hours))
        {
            $array['error'] = 'Barbeiro não atende nesta hora!';
            return $array;
        }
        //fazer o agendamento
        $newApp = new UserAppointment();
        $newApp->id_user = $this->loggedUser->id;
        $newApp->id_barber = $id;
        $newApp->id_service = $service;
        $newApp->ap_datetime = $apDate;
        $newApp->save();

        return $array;
    }

    public function search(Request $request)
    {
        $array = ['error' => '', 'list' => []];

        $q = $request->input('q');
        if($q) {
            $barbers = Barber::select()
              ->where('name', 'LIKE', '%'.$q.'%')
            ->get();

            foreach($barbers as $bKey => $barber) {
              $barbers[$bKey]['avatar'] = url('media/avatars/'.$barbers[$bKey]['avatar']);
            }

            $array['list'] = $barbers;
        } else {
            $array['error'] = 'Digite algo para buscar';
        }

        return $array;
    }

}
