<?php

namespace App\Http\Controllers\Util;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Naopon\LaravelGoogleDrive\Facades\GoogleDrive;
use Storage;
use Cloudder;
use App\Models\Image;
use Illuminate\Support\Str;
class UtilService extends Controller
{
    public static function upload($file){
        $path = Storage::disk('google')->put("/",$file, ['visibility' => 'public']);
        $result = Storage::disk('google')->getMetadata($path);
        $image = new Image();
        $idMax = Image::max("id")+1;
        $image->id =  $idMax;
        $image->fileName = $result["name"];
        $image->path = $result["path"];
        $image->save();
        return $idMax;
    }
    public static function updateAvartar($fileName, $fileId){
        $newFileId = Storage::disk('google')->putFileAs("/", $fileName, $fileId, ['visibility' => 'public']);
        return response("OK",200);
    }
    public static function getAll(){
        $path = Storage::disk('google')->listContents("/", false);
        return $path;
    }
}
