<?php
namespace app \index \controller ;
use think\Request;
use app\index\controller\Acl;
class kindeditor extends Acl {
    //kindeditor编辑器上传模块
    //---------------(^_^)---------------//
    //主模块
    public function main(Request $request) {
        $input = input('get.');
        if (isset_full($input,'dir')) {
            $file=$request->file('imgFile');//获取表单上传文件
            if (empty($file)){
                $resule=['state'=>'error','info'=>'渡されたデータが不完全です!'];
            }else{
                if ($input['dir'] == 'image') {
                    //图像
                    $nod=$file->validate (['size'=>3000000,'ext'=>'png,gif,jpg,jpeg,bmp'])->rule ('uniqid')->move (ROOT_PATH .'skin'.DS .'upload'.DS .'goods'.DS .'details');
                    if ($nod){
                        $file_name=$nod->getSaveName ();
                        $file_path='/skin/upload/goods/details/'.$file_name;
                        $resule=['error'=>0,'url'=>$file_path];
                    }else {
                        $resule=['error'=>1,'message'=>$file->getError()];//返回错误信息
                    }
                }elseif ($input['dir'] == 'flash') {
                    //flash
                    $nod=$file->validate (['size'=>4000000,'ext'=>'swf'])->rule ('uniqid')->move (ROOT_PATH .'skin'.DS .'upload'.DS .'goods'.DS .'flash');
                    if ($nod){
                        $file_name=$nod->getSaveName ();
                        $file_path='/skin/upload/goods/flash/'.$file_name;
                        $resule=['error'=>0,'url'=>$file_path];
                    }else {
                        $resule=['error'=>1,'message'=>$file->getError()];//返回错误信息
                    }
                }elseif ($input['dir'] == 'media') {
                    //多媒体
                    $nod=$file->validate (['size'=>22000000,'ext'=>'mp4,rmvb,avi,3gp,mp3,wav'])->rule ('uniqid')->move (ROOT_PATH .'skin'.DS .'upload'.DS .'goods'.DS .'media');
                    if ($nod){
                        $file_name=$nod->getSaveName ();
                        $file_path='/skin/upload/goods/media/'.$file_name;
                        $resule=['error'=>0,'url'=>$file_path];
                    }else {
                        $resule=['error'=>1,'message'=>$file->getError()];//返回错误信息
                    }
                }elseif ($input['dir'] == 'file') {
                    //文件
                    $nod=$file->validate (['size'=>4000000,'ext'=>'doc,docx,xls,xlsx,rar,zip,7z'])->rule ('uniqid')->move (ROOT_PATH .'skin'.DS .'upload'.DS .'goods'.DS .'file');
                    if ($nod){
                        $file_name=$nod->getSaveName ();
                        $file_path='/skin/upload/goods/file/'.$file_name;
                        $resule=['error'=>0,'url'=>$file_path];
                    }else {
                        $resule=['error'=>1,'message'=>$file->getError()];//返回错误信息
                    }
                }else{
                    //文件
                    $resule=['state'=>'error','info'=>'不特定のデータ型!'];//返回错误信息
                }
            }
        }else{
            $resule=['state'=>'error','info'=>'入力されたパラメーターが不完全です!'];
        }
        return json($resule);
    }
}