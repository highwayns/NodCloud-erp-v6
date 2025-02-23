<?php
namespace app\index\model;
use	think\Model;
class Accountinfo extends Model{
    //资金详情表
    
    //时间自动转换
	protected $type=['time'=>'timestamp:Y-m-d'];
    
    //用户属性关联
    public function userinfo(){
        return $this->hasOne('User','id','user');
    }
    
    //金额_读取器
	protected function  getMoneyAttr ($val,$data){
	    return opt_decimal($val);
	}
	
	//积分操作_读取器
	protected function  getSetAttr ($val,$data){
        $arr=['0'=>'資金減少','1'=>'資金増加'];
        $re['name']=$arr[$val];
        $re['nod']=$val;
        return $re;
	}
	//单据类型_读取器
	protected function  getTypeAttr ($val,$data){
        $arr = [
			'1' => '購買核消伝票',  // 购货核销单
			'2' => '売上核消伝票',  // 销货核销单
			'3' => '購買返品核消伝票',  // 购货退货核销单
			'4' => '売上返品核消伝票',  // 销货退货核销单
			'5' => '入金伝票',  // 收款单
			'6' => '出金伝票',  // 付款单
			'7' => 'その他収入伝票',  // 其他收入单
			'8' => 'その他支出伝票',  // 其他支出单
			'9' => '小売売上収入伝票',  // 零售单收款
			'10' => '小売返品伝票',  // 零售退货单
			'11' => '仕入入庫核消伝票',  // 采购入库核销单
			'12' => 'サービス核消伝票',  // 服务核销单
			'13' => '資金振替伝票-出',  // 资金调拨单-出
			'14' => '資金振替伝票-入'   // 资金调拨单-入
		];		
        $re['name']=$arr[$val];
        $re['nod']=$val;
        return $re;
	}
    //来源类型_原始数据
	protected function  getTypenodAttr ($val,$data){
        return $data['type'];//返回原始数据，解决多态关联多维数组问题
	}
	//来源类型多态关联
    public function typedata(){
        return $this->morphTo(
            ['typenod','class'],
            [
            	'1'	    =>	'Purchaseclass',//购货核销单
            	'2'	    =>	'Saleclass',//销货核销单
            	'3'	    =>	'Repurchaseclass',//购货退货核销单
            	'4'	    =>	'Resaleclass',//销货退货核销单
            	'5'	    =>	'Gatherclass',//收款单
            	'6'	    =>	'Paymentclass',//付款单
            	'7'	    =>	'Otgatherclass',//其他收入单
            	'8'	    =>	'Otpaymentclass',//其他支出单
            	'9'     =>	'Cashierclass',//零售单收款
            	'10'	=>	'Recashierclass',//零售退货单
            	'11'	=>	'Rpurchaseclass',//采购入库单
            	'12'	=>	'Itemorderclass',//服务订单
            	'13'	=>	'Eftclass',//资金调拨单-出
            	'14'	=>	'Eftclass',//资金调拨单-入
        	]
        );
    }
}
