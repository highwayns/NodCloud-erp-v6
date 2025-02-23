<?php
namespace app\index\model;
use	think\Model;
class Serialinfo extends Model{
    //串码详情
    
	//单据类型_读取器
	protected function  getTypeAttr ($val,$data){
        $arr=['1'=>'購買注文','2'=>'販売注文','3'=>'購買返品注文','4'=>'販売返品注文','5'=>'調整伝票-出','6'=>'調整伝票-入','7'=>'その他入庫伝票','8'=>'その他出庫伝票','9'=>'小売注文','10'=>'小売返品注文','11'=>'仕入入庫伝票','12'=>'ポイント交換伝票'];
        $re['name']=$arr[$val];
        $re['nod']=$val;
        return $re;
	}
	
    //单据类型_原始数据
	protected function  getTypenodAttr ($val,$data){
        return $data['type'];//返回原始数据，解决多态关联多维数组问题
	}
	
	//单据类型多态关联
    public function typedata(){
        return $this->morphTo(
            ['typenod','class'],
            [
            	'1'	    =>	'Purchaseclass',//购货单
            	'2'	    =>	'Saleclass',//销货单
            	'3'	    =>	'Repurchaseclass',//购货退货单
            	'4'	    =>	'Resaleclass',//销货退货单
            	'5'	    =>	'Allocationclass',//调拨单
            	'6'	    =>	'Otpurchaseclass',//其他入库单
            	'7'	    =>	'Otsaleclass',//其他出库单
            	'8'	    =>	'Cashierclass',//零售单
            	'9'     =>	'Recashierclass',//零售退货单
            	'10'	=>	'Rpurchaseclass',//采购退货单
            	'11'	=>	'Exchangeclass',//积分兑换单
        	]
        );
    }
}
