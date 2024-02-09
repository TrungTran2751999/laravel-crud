<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Util\UtilService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\User;
use App\Models\Image;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Carbon\Carbon;
use DB;

class UserService extends Controller
{
    public static function getAll(){
        $user = User::leftJoin("image", 'user.imageId', '=', 'image.id')
                    ->select("user.id","user.guid","user.userName","user.name","user.email","user.roleId", "image.path as image")
                    ->where("userName","<>","adminMaster")
                    ->orderByDesc("updatedAt")
                    ->get();
        return response($user,200);
    }
    public static function getMemberTeam(){
        $user = User::leftJoin("image", 'user.imageId', '=', 'image.id')
                    ->select("user.id","user.name", "user.position", "user.coporation","image.path as image")
                    ->where("userName","<>","adminMaster")
                    ->where("user.roleId",1)
                    ->orWhere("user.roleId",2)
                    ->orderByDesc("updatedAt")
                    ->get();
        return response($user,200);
    }

    public static function getAllClient(){
        $user = User::leftJoin("image", 'user.imageId', '=', 'image.id')
                    ->select("user.id","user.name", "user.position", "user.coporation","image.path as image")
                    ->where("userName","<>","adminMaster")
                    ->orderByDesc("updatedAt")
                    ->get();
        return response($user,200);
    }

    public static function getById($id, $guid){
        $user = User::leftJoin("image", 'user.imageId', '=', 'image.id')
                    ->select("user.id","user.guid as guid","user.userName","user.name","user.email","user.roleId", "user.position", "user.coporation", "image.path as image")
                    ->where("user.id",$id)
                    ->first();
        if(!$user || $user->guid!=$guid) return response("User không tồn tại",400);
        
        return response($user,200);
    }

    public static function login($request){
        $userName = $request->input("userName");
        $password = $request->input("password");
        $user = User::where("userName",$userName)
                    ->first();
        
        if(!$user || !Hash::check($password,$user->password)) return response("Tên đăng nhập hoặc mật khẩu không tồn tại",400);
        $arrUser = [
            "id"=>$user->id,
            "userName"=>$userName,
            "password"=>$password,
        ];
        $token = JWTAuth::attempt($arrUser);
        
        $response = new Response();
        $response->cookie("token",$token, 3600*24*365);
        $response->cookie(cookie("name",$user->name, 3600*24*365)->withHttpOnly(false));
        $response->withCookie(cookie("id",$user->id, 3600*24*365)->withHttpOnly(false));
        $response->withCookie(cookie("guid",$user->guid, 3600*24*365)->withHttpOnly(false));
        return $response;
    }

    public static function logout(){

        $response = new Response();
        $response->withCookie(Cookie::forget('token'));
        $response->withCookie(Cookie::forget('name'));
        $response->withCookie(Cookie::forget('id'));
        $response->withCookie(Cookie::forget('guid'));
        return $response;
    }

    public static function create(Request $request){
        $validate = $request->validate([
            "id"=>"required",
            "guid"=>"required",
            "userName"=>"required",
            "position"=>"required",
            "coporation"=>"required",
            "name"=>"required",
            "roleId"=>"required",
            "updatedBy"=>"required",
            "createdBy"=>"required"
        ]);
        //check role
        $checkRole = User::where("id", $request->input("id"))
                            ->where("guid", $request->input("guid"))
                            ->first();

        if(!$checkRole || $checkRole->roleId != 1) return response("Bạn không có quyền tạo user",400);

        $name = $request->input("userName");
        $checkUser = User::where("userName",$name)->first();

        if($checkUser) return response("Username đã tồn tại", 400);

        $user = new User();
        $user->guid = Str::uuid()->toString();
        $user->userName = $request->input("userName");
        $user->name = $request->input("name");
        $user->email = $request->input("email");
        $user->password = Hash::make($request->input("password"));
        if($request->file("email")) $user->email = $request->input("email");
        $user->roleId = $request->input("roleId");
        if($request->file("avartar")) $user->imageId = UtilService::upload($request->file("avartar"));
        $user->position = $request->input("position");
        $user->coporation = $request->input("coporation");
        $user->createdAt = Carbon::now('Asia/Ho_Chi_Minh');
        $user->updatedAt = Carbon::now('Asia/Ho_Chi_Minh');
        $user->createdBy = $request->input("createdBy");
        $user->updatedBy = $request->input("updatedBy");

        $idMax = User::max("id");
        $user->id = $idMax+1;
        $user->save();
        return response($user,200);
    }

    public static function update($request){
        try{
            DB::beginTransaction();
            $validate = $request->validate([
                "id"=>"required",
                "guid"=>"required",
                "userName"=>"required",
                "name"=>"required",
                "roleId"=>"required",
                "updatedBy"=>"required"
            ]);
            $checkUserExist = User::where("userName",$request->input("userName"))
                                      ->where("id", "<>", $request->input("id"))
                                      ->get();
            
            if(!$checkUserExist->isEmpty()){
                return response("Tên user đã tồn tại",400);
            }
            $user = User::where("guid",$request->input("guid"))
                                    ->where("id", $request->input("id"))
                                    ->first();
            if(!$user){
                return response("User không tồn tại",400);
            }
            
            $avartar = $request->file("avartar");

            $user->userName = $request->input("userName");
            $user->name = $request->input("name");
            $user->email = $request->input("email");
            $user->roleId = $request->input("roleId");
            $user->position = $request->input("position");
            $user->coporation = $request->input("coporation");
            $user->updatedBy = $request->input("updatedBy");
            $user->updatedAt = Carbon::now('Asia/Ho_Chi_Minh');
            if($request->input("password")) $user->password = Hash::make($request->input("password"));
            if($avartar) {
                $imageId = $user->imageId;
                if($imageId!=null){
                    $image = Image::where("id", $imageId)->first();
                    UtilService::updateAvartar($avartar, $image->path);
                }else{
                    $user->imageId = UtilService::upload($avartar);
                }
            }
            $user->save();
            DB::commit();
        }catch(Exeption $e){
            return response("Lỗi server",500);
            DB::rollback();
        }
    }

    public static function changePass($request){
        $validate = $request->validate([
            "id"=>"required",
            "password"=>"required",
            "guid"=>"required",
        ]);

        $user = User::where("guid",$request->input("guid"))
                                ->where("id", $request->input("id"))
                                ->first();
        $passwordFromDB = $user->password;
        $oldPassword = $request->input("oldPassword");
        if(!$user){
            return response("User không tồn tại",400);
        }
        $user->password = Hash::make($request->input("password"));
        $user->save();
        return "OK";
    }

    public static function checkRole($id, $guid){
        $roleId = User::where("id", $id)
                    ->where("guid", $guid)
                    ->select("roleId")
                    ->first();
        if(!$roleId) return response("User không tồn tại", 400);
        return $roleId;
    }
}
