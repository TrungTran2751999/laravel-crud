<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\WorkApi;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
//============================API==============================
Route::prefix("/api")->group(function(){
    // ==============USER==============
    Route::prefix("/user")->group(function(){
        Route::get("/client",[UserApi::class,'getMembers']);

        Route::get("/",[UserApi::class,'getAll'])->middleware("role:ADMIN");
        Route::post("/",[UserApi::class,'create'])->middleware("role:ADMIN");
        Route::post("/update",[UserApi::class,'update'])->middleware("role:ADMIN");
        Route::get("/detail/{id}/{guid}",[UserApi::class,'getById']);

        Route::post("/login",[UserApi::class,'login']);
        Route::get("/logout",[UserApi::class,'logout']);
        Route::post('/change-pass', [UserApi::class,'changePass'])->middleware("role:ADMIN;PARTNER");
        Route::get("/check-role",[UserApi::class,'checkRole']);
        
    });
    Route::prefix("/work")->group(function(){
        Route::get("/",[WorkApi::class,'getAll']);
        Route::get("/detail",[WorkApi::class,'getById']);
        Route::post("/",[WorkApi::class,'create']);
        Route::post("/update",[WorkApi::class,'update']);
        Route::get("/delete",[WorkApi::class,'delete']);
    });
});

//==========================ROUTE===============================