<?php
namespace app \index \controller ;
use think\Hook;
use app\index\controller\Acl;
use app\index\controller\Formfield;
class Backup extends Acl {
    //数据备份模块
    //---------------(^_^)---------------//
    //数据备份视图
    public function main(){
        return $this->fetch();
    }
    //数据备份列表
    public function backup_list(){
        $input=input('post.');
        //数据完整性判断
        if(isset_full($input,'page') && isset_full($input,'limit')){
            $dbinfo=config('database');
    		$backup=new \org\baksql ($dbinfo['hostname'],$dbinfo['username'],$dbinfo['password'],$dbinfo['database']);
    		$list=$backup->get_filelist();
            $count = count($list);//获取总条数
            $arr =array_slice($list,$input['limit']*($input['page']-1),$input['limit']);//匹配分页数据
            $resule=[
                'code'=>0,
                'msg'=>'取得成功',
                'count'=>$count,
                'data'=>$arr
            ];//返回数据
        }else{
            $resule=['state'=>'error','info'=>'入力されたパラメーターが不完全です!'];
        }
        return json($resule);
    }
    //备份数据
    public function new_backup(){
        $dbinfo=config('database');
		$backup=new \org\baksql($dbinfo['hostname'],$dbinfo['username'],$dbinfo['password'],$dbinfo['database']);
		$backup->backup();
		push_log ('システムデータのバックアップを作成する');
		return json (['state'=>'success']);
    }
    //恢复备份
    public function restore(){
        $input=input('post.');
        if(isset_full($input,'name')){
            $dbinfo=config('database');
    		$backup=new \org\baksql($dbinfo['hostname'],$dbinfo['username'],$dbinfo['password'],$dbinfo['database']);
    		$backup->restore($input['name']);
    		push_log('データバックアップを復元する[ '.$input['name'].' ]');//日志信息
            $resule=['state'=>'success'];
        }else{
            $resule=['state'=>'error','info'=>'入力されたパラメーターが不完全です!'];
        }
        return json($resule);
    }
    //データバックアップを削除する
    public function del_backup(){
        $input=input('post.');
        if(isset_full($input,'arr') && is_array($input['arr'])){
            $info=$input['arr'];
            $path=ROOT_PATH.'skin'.DS.'backup'.DS;
            foreach ($info as $info_vo) {
                //防止恶意请求
                if(strpos($info_vo,'/')===false && strpos($info_vo,'..')===false){
                    unlink($path.$info_vo);
                    push_log('データバックアップを削除する[ '.$info_vo.' ]');//日志信息
                }else{
                    return json(['state'=>'error','info'=>'パラメーターエラー!']);
                }
            }
            $resule=['state'=>'success'];
        }else{
            $resule=['state'=>'error','info'=>'入力されたパラメーターが不完全です!'];
        }
        return json($resule);
    }
}