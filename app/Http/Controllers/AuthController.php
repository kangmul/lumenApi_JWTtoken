<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;
use DateTime;
use Illuminate\Support\Facades\Validator;

use App\Models\User;

class AuthController extends Controller
{

  protected $jwt;

  public function __construct()
  {
    //
  }

  public function testcontroller(Request $request)
  {
    echo 'Menampilkan controller dari method test';
  }

  public function login(Request $request)
  {
    $validator = Validator::make(
      $request->all(),
      [
        'username' => 'required|alpha_num',
        'password' => 'required',
      ],
      [
        'username.required' => 'username tidak boleh kosong',
        'username.alpha_num' => 'username tidak valid',
        'password.required' => 'password tidak boleh kosong',
      ]
    );

    if ($validator->fails()) {
      return response()->json([
        'code' => 201,
        'status' => 'failed',
        'message' => $validator->errors()->getMessages()
      ]);
    }

    $cekuser = User::where('username', $request->username)->first();
    if (!$cekuser) {
      return response()->json([
        'code' => 204,
        'status' => 'failed',
        'message' => 'user tidak terdaftar'
      ]);
    } else {
      if ($cekuser->is_active == 0) {
        return response()->json([
          'code' => 203,
          'status' => 'failed',
          'message' => 'user belum di aktivasi'
        ]);
      } else {
        $passwordinput = hash('sha256', $request->password);
        if ($cekuser->username == $request->username && $cekuser->password == $passwordinput) {
          $detailuser = User::select('users.username', 'master_users.name', 'users.email', 'master_users.npp', 'master_ref_agama.nm_agama', 'master_ref_jenis_kelamin.nm_jk', 'master_ref_pekerjaan.nm_pekerjaan')
            ->join('master_users', function ($join) {
              $join->on('master_users.username', '=', 'users.username')->On('master_users.email', '=', 'users.email');
            })
            ->join('master_ref_agama', function ($join) {
              $join->on('master_ref_agama.kd_agama', '=', 'master_users.kd_agama');
            })
            ->join('master_ref_jenis_kelamin', function ($join) {
              $join->on('master_ref_jenis_kelamin.kd_jk', '=', 'master_users.kd_jns_kelamin');
            })
            ->join('master_ref_pekerjaan', function ($join) {
              $join->on('master_ref_pekerjaan.kd_pekerjaan', '=', 'master_users.pekerjaan');
            })
            ->where('users.username', $request->username)->first();
          $data = $this->generateToken($detailuser->toArray());
          // dd($data);
          return response()->json([
            'code' => 200,
            'status' => 'success',
            'message' => 'berhasil login!',
            'data' => [
              'username' => $cekuser->username,
              'name' => $detailuser->name,
              'token' => $data
            ],
          ]);
        } else {
          return response()->json([
            'code' => 201,
            'status' => 'failed',
            'message' => 'username dan password tidak sesuai'
          ]);
        }
      }
    }
  }

  function generateToken($request)
  {
    $future = new DateTime("+1 minutes");
    $customClaims = [
      "sub" => 'sub',
      "detaildata" => [
        "username" => $request['username'],
        "npp" => $request['npp'],
        "name" => $request['name'],
        "pekerjaan" => $request['nm_pekerjaan']
      ],
    ];

    $payload = JWTFactory::customClaims($customClaims)->make($customClaims);
    $token = JWTAuth::encode($payload);
    $data["token"] = (string)$token;
    $data["expires"] = $future->getTimeStamp();
    return $data;
  }
}
