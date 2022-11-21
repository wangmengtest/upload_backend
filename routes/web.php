<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return [
        'code'=> 200,
        'data'=> [],
        'msg' => 'success',
    ];
});

Route::group(['middleware' => ['upload.auth']], function () {
    Route::put('files/upload', 'UploadController@upload');
    Route::get('files/read', 'UploadController@fileRead');
    Route::get('files/exists', 'UploadController@exists');
    Route::get('files/metadata', 'UploadController@metadata');
    Route::post('files/dir', 'UploadController@createDir');
    Route::post('files/delete', 'UploadController@delete');

    //大文件上传
    Route::get('files/uploadedFilesize', 'UploadController@uploadedFilesize');
    Route::get('files/info', 'UploadController@info');
});

Route::get('/download', 'DownloadController@download');