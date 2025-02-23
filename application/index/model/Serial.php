<?php
namespace app\index\model;
use	think\Model;
class Serial extends Model{
    //串码表
	
	//商品属性关联
    public function goodsinfo(){
        return $this->hasOne('Goods','id','goods');
    }
    
    //仓储属性关联
    public function roominfo(){
        return $this->hasOne('Room','id','room');
    }
	
	//条码类型_读取器
	protected function  getTypeAttr ($val,$data){
        $arr=['0'=>'未販売','1'=>'販売済み','2'=>'在庫なし'];
        $re['name']=$arr[$val];
        $re['nod']=$val;
        return $re;
	}
}
