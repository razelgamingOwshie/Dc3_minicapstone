<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller; use App\Models\User; use Illuminate\Http\Request; use Illuminate\Support\Facades\Hash; use Illuminate\Validation\ValidationException;
class AuthController extends Controller {
 public function register(Request $r){$d=$r->validate(['name'=>'required|string|max:120','email'=>'required|email|max:255|unique:users,email','password'=>'required|string|min:8|confirmed']);$u=User::create(['name'=>$d['name'],'email'=>strtolower($d['email']),'password'=>Hash::make($d['password']),'role'=>'pet_owner']);return response()->json(['user'=>$u,'token'=>$u->createToken('web-client')->plainTextToken],201);}
 public function login(Request $r){$d=$r->validate(['email'=>'required|email','password'=>'required|string']);$u=User::where('email',strtolower($d['email']))->first();if(!$u||!Hash::check($d['password'],$u->password))throw ValidationException::withMessages(['email'=>['The provided credentials are incorrect.']]);return response()->json(['user'=>$u,'token'=>$u->createToken('web-client')->plainTextToken]);}
 public function user(Request $r){return response()->json(['user'=>$r->user()]);}
 public function logout(Request $r){$r->user()->currentAccessToken()?->delete();return response()->json(['message'=>'Logged out.']);}
}
