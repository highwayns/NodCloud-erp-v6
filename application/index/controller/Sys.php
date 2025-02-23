<?php
namespace app \index \controller ;
use think\Hook;
use app\index\controller\Acl;
use app\index\model\Sys as Syss;
class Sys extends Acl {
    //系统设置模块
    //---------------(^_^)---------------//
    //系统设置视图
    public function main(){
        $this->assign('sys',get_sys());
        return $this->fetch();
    }
    //保存数据
    public function save(){
        $input=input('post.');
        if(is_array($input)){
            foreach ($input as $key=>$input_vo) {
                Syss::where(['name'=>$key])->update(['info'=>$input_vo]);
            }
            Hook::listen('save_sys',$input);//修改系统设置行为
            push_log('システム設定を変更します');//日志
            $resule=['state'=>'success'];
        }else{
            $resule=['state'=>'error','info'=>'入力されたパラメーターが不完全です!'];
        }
        return json($resule);
    }
}