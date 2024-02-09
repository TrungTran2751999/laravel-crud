<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Service\WorkService;
use DB;


class WorkApi extends Controller
{
    public function getAll(){
        return WorkService::getAll();
    }
    public function getById(Request $request){
        $id = $request->input("id");
        return WorkService::getAll($id);
    }
    public function create(Request $request){
        return WorkService::create($request);
    }
    public function update(Request $request){
        return WorkService::update($request);
    }
}
