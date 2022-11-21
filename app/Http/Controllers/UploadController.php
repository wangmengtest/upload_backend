<?php

namespace App\Http\Controllers;

use App\Services\FileTypeCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class UploadController extends Controller
{

    protected $bucket;

    private $_checked = FALSE;
    private $_ext;

    const COVER_LIMIT = 3145728; //3M
    const IMG_LIMIT = 10485760; //10M 作品图片
    const ZIP_LIMIT = 314572800; //300M
//    const ZIP_LIMIT = 943718400; //900M
    const COVER_MAX_W = 800;
    const COVER_MAX_H = 600;
    const IMG_MAX_W = 1200;

    public function __construct()
    {
        $username = '';
        $this->middleware(function($request, $next) use(&$username) {
            $username = $request->get('username');
            $this->bucket = config('upload.bucket.' . $username);
            return $next($request);
        });

    }
    /**
     * @param Request $request
     * @return string
     */
    public function upload(Request $request)
    {
        $path = $this->handlePath($request->header('path'));
        $filePath = config('upload.storage').'/'.$path;
        $filePathMsg = explode("/", $filePath);
        array_pop($filePathMsg);
        $fileAbsPath = implode("/", $filePathMsg);
        
        if (!is_dir($fileAbsPath)) {
            mkdir($fileAbsPath, 0777, true);
        }

        file_put_contents(config('upload.storage').'/'.$path, $request->getContent(), FILE_APPEND);
    	return response(['path' => $path, 'success' => true]);        
    }

    public function fileRead(Request $request)
    {
        $path = $request->header('path');
        Log::info('读取数据，'. $path);
        if (!Storage::disk('public')->exists($this->handlePath($path)) || !File::isFile($this->getLocalUrl($path))) {
            return response(['err_code' => 1001, 'success' => false]);
        }
        $content = Storage::disk('public')->get($this->handlePath($path));
        return response()->json(['contents' => $content, 'success' => true]);
    }

    public function exists(Request $request)
    {
        $path = $this->getLocalUrl($request->header('path'));

        $result = file_exists($path);

        if (!$result) {
            return response(['success' => false], 404);
        }

        return response(['success' => true]);
    }


    public function createDir(Request $request)
    {
        $path = $this->handlePath($request->header('path'));
        $result = Storage::disk('public')->makeDirectory($path);
        if (!$result) {
            return response(['success' => false], 404);
        }
        return response(['success' => true]);
    }

    public function delete(Request $request)
    {
        $dir = $request->header('path');

        $localUrl = $this->getLocalUrl($dir);

        $result = '';
        if(is_dir($localUrl)){
            $result = Storage::disk('public')->deleteDirectory($this->bucket . '/' . $dir);
        }else{
            $result = unlink($localUrl);
        }

        if (!$result) {
            return response(['success' => false], 404);
        }
        return response(['success' => true]);
    }


    public function metadata(Request $request)
    {
        //2018-04-02
        $path = $this->getLocalUrl($request->header('path'));
        //$fileInfo['type'] = Storage::disk('public')->mimeType($path);
        //$fileInfo['size'] = Storage::disk('public')->size($path);
        $fileInfo['size'] = file_exists($path) ? filesize($path): 0;
        //$fileInfo['date'] = Storage::disk('public')->lastModified($path);

        /*if(!$this->_checked && $fileInfo['size'] > 15){
            if(! ($fileInfo['fileTypeValid'] = $this->fileTypeCheck($path, $request->input('_type'))) ){
                //unlink($path);
            }

        }*/

        return response(['success' => true, 'data' => $fileInfo]);
    }

    public function uploadedFilesize(Request $request)
    {
        //2018-04-02
        $path = $this->getLocalUrl($request->header('path'));
        $fileInfo['size'] = file_exists($path) ? filesize($path): 0;
        $fileInfo['fileTypeValid'] = TRUE;

        if(!$this->_checked && $fileInfo['size'] > 15){
            if(! ($fileInfo['fileTypeValid'] = $this->fileTypeCheck($path, $request->input('_type'))) ){
                unlink($path);
            }

        }

        return response(['success' => true, 'data' => $fileInfo]);
    }

    private function handlePath($path)
    {
        return $this->bucket . '/' . $path;
    }

    private function getLocalUrl($path)
    {
        $storageUrl = config('upload.storage');
        return $storageUrl . '/' . $this->bucket . '/' . $path;
    }

    /**
     * @param $filepath
     * @param $type
     * @return bool
     */
    protected function fileTypeCheck($filepath, $type)
    {
        if(!in_array($type, ['cover', 'img', 'zip'])){
            return FALSE;
        }

        $this->_checked = TRUE;

        if(!$this->_ext){
            $this->_ext = $this->getFileType($filepath, $type);
        }

        return $this->_ext && in_array($this->_ext, array('jpg', 'png', 'zip', 'rar')) ? TRUE : FALSE;
    }

    /**
     * @param $filepath
     * @param $type
     * @return bool|string
     */
    protected function getFileType($filepath, $type)
    {
        if($type != 'zip'){ // cover/img
            $arr = [1 => 'gif', 2 =>'jpg', '3' => 'png'];
            return isset($arr[exif_imagetype($filepath)]) ? $arr[exif_imagetype($filepath)] : FALSE;
        }else{ //zip
            return FileTypeCheck::getFileType($filepath);
        }

    }

    public function info(Request $request)
    {
        //2018-04-02
        $filename = $request->header('path');
        $path = $this->getLocalUrl('contest/' . $filename);
        $type = $request->input('_type');

        /*if(!$this->_checked){
            if(! ($result['fileTypeValid'] = $this->fileTypeCheck( $path, $type )) ){
                unlink($path);
            }

        }*/

        if(!$this->_ext){
            $this->_ext = $this->getFileType($path, $type);
        }

        if($this->_ext === FALSE){
            unlink($path);
            $result = array('succ' => 0, "data" => array(), "msg" => "不允许上传非法文件。");

        }else{

            if($type == 'cover'){
                $result = $this->handleCover($path, $filename);
            }elseif($type == 'img'){
                $result = $this->handleImg($path, $filename); //作品图片
            }elseif($type == 'zip'){
                $result = $this->handleZip($path, $filename);
            }else{
                $result = array('succ' => 0, "data" => array(), "msg" => "参数错误，请重新上传");
            }

        }



        return response(['success' => true, 'data' => $result]);
    }

    protected function handleCover($filepath, $filename)
    {
        if(filesize($filepath) > self::COVER_LIMIT){
            unlink($filepath);
            return ["succ" => 0, "data" => [], "msg" => '超出作品封面的最大限制，请重新上传小于 3M 的文件'];
        }

        $srcImgInfo = getimagesize($filepath);
        $basename = pathinfo($filepath);
        $saveFile = config('upload.storage'). '/' . $this->bucket . '/contest/' . $basename['filename'] . '_thumbnails.' . $basename['extension'];
        if($srcImgInfo[0] > self::COVER_MAX_W || $srcImgInfo[1] > self::COVER_MAX_H){
            $this->imgzip($filepath, $srcImgInfo, self::COVER_MAX_W, self::COVER_MAX_H, $saveFile);
        }else{
            $this->imgzip($filepath, $srcImgInfo, $srcImgInfo[0], $srcImgInfo[1], $saveFile);
        }

        return ['succ' => 1, 'data' => ["file" => 'contest/' . $filename, 'thumb' => 'contest/' . $basename['filename'] . '_thumbnails.' . $basename['extension']], 'msg' => 'ok'];
    }

    protected function handleImg($filepath, $filename)
    {
        if(filesize($filepath) > self::IMG_LIMIT){
            unlink($filepath);
            return ["succ" => 0, "data" => [], "msg" => '超出作品图片的最大限制，请重新上传小于 10M 的文件'];
        }

        $srcImgInfo = getimagesize($filepath);
        $basename = pathinfo($filepath);
        $saveFile = config('upload.storage'). '/' . $this->bucket . '/contest/' . $basename['filename'] . '_thumbnails.' . $basename['extension'];
        if($srcImgInfo[0] > self::IMG_MAX_W){
            $this->imgzip($filepath, $srcImgInfo, self::IMG_MAX_W, self::IMG_MAX_W * 2, $saveFile);
        }else{
            $this->imgzip($filepath, $srcImgInfo, $srcImgInfo[0], $srcImgInfo[1], $saveFile);
        }

        return ['succ' => 1, 'data' => ["file" => 'contest/' . $filename, 'thumb' => 'contest/' . $basename['filename'] . '_thumbnails.' . $basename['extension']], 'msg' => 'ok'];
    }

    protected function handleZip($filepath, $filename)
    {
        if(filesize($filepath) > self::ZIP_LIMIT){
            unlink($filepath);
            return ["succ" => 0, "data" => [], "msg" => '超出作品压缩包的最大限制, 请重新上传小于 300M 的文件'];
        }

        return ['succ' => 1, 'data' => ["file" => 'contest/' . $filename], 'msg' => 'ok'];
    }

    /**
     * 等比例缩放图片
     *
     * @param $src
     * @param $src_img_info
     * @param $target_w
     * @param $target_h
     * @param $save_filename
     */
    public function imgzip($src, $src_img_info, $target_w, $target_h, $save_filename)
    {
        $imageRealExt = $this->_ext != 'jpg' ? $this->_ext : 'jpeg';

        $func = "imagecreatefrom{$imageRealExt}";
        $src_im = $func($src);
        //原图高／宽
        $src_w = $src_img_info[0];
        $src_h = $src_img_info[1];
        //计算新图宽高
        if($src_w>$src_h){
            $target_h = $target_w/($src_w/$src_h);
        }else{
            $target_w = $target_h*($src_w/$src_h);
        }

        $target_im = imagecreatetruecolor($target_w,$target_h);
        //将图片复制到新建画布中
        imagecopyresized($target_im, $src_im, 0, 0, 0, 0, $target_w,$target_h,$src_w,$src_h);
        imagedestroy($src_im);
        $func_echo = "image{$imageRealExt}";
        $func_echo($target_im, $save_filename);
        imagedestroy($target_im);
    }
}
