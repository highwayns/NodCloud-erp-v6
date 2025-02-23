<?php
namespace app\index\model;
use	think\Model;
class Opurchaseclass extends Model{
    //采购订单
    protected $type = [
        'time'=>'timestamp:Y-m-d',
        'auditingtime'=>'timestamp:Y-m-d H:i:s',
        'more'    =>  'json'
    ];
    
    //商户属性关联
    public function merchantinfo(){
        return $this->hasOne('Merchant','id','merchant');
    }
	
	//制单人属性关联
    public function userinfo(){
        return $this->hasOne('User','id','user');
    }
	
	//审核人属性关联
    public function auditinguserinfo(){
        return $this->hasOne('User','id','auditinguser');
    }
    
	//审核状态_读取器
	protected function  getTypeAttr ($val,$data){
        $arr=['0'=>'未承認','1'=>'承認済み'];
        $re['name']=$arr[$val];
        $re['nod']=$val;
        return $re;
	}
	
	//入库状态_读取器
	protected function  getStorageAttr ($val,$data){
        $arr=['0'=>'モデル','1'=>'倉庫への部分的なエントリ','2'=>'倉庫に入った'];
        $re['name']=$arr[$val];
        $re['nod']=$val;
        return $re;
	}
}
