<?php
namespace app\index\controller;
use think\Hook;
use app\index\controller\Acl;
use app\index\model\Rpurchaseclass;
use app\index\model\Rpurchaseinfo;
use app\index\model\Rpurchasebill;
use app\index\model\Opurchaseclass;
use app\index\model\Opurchaseinfo;
use app\index\model\Room;
use app\index\model\Roominfo;
use app\index\model\Account;
use app\index\model\Accountinfo;
use app\index\model\Serial;
use app\index\model\Serialinfo;
class Rpurchase extends Acl {
    //采购入库单模块
    //---------------(^_^)---------------//
    //报表视图
    public function form(){
        return $this->fetch();
    }
    //报表列表
    public function form_list(){
        $input=input('post.');
        //数据完整性判断
        if(isset_full($input,'page') && isset_full($input,'limit')){
            $sql=get_sql($input,[
                'name'=>'continue',
                'onumber'=>'continue',
                'number'=>'full_like',
                'start_time'=>'stime',
                'end_time'=>'etime',
                'supplier'=>'full_division_in',
                'type'=>'full_dec_1',
                'warehouse'=>'continue',
                'user'=>'full_division_in',
                'account'=>'full_division_in',
                'data'=>'full_like',
            ],'rpurchaseclass');//构造SQL
            //处理名称搜索
            if(isset_full($input,'name')){
                $goods=get_db_field('goods',['name|py'=>['like','%'.$input['name'].'%']],'id');//取出商品表信息
                if(empty($goods)){
                    sql_assign($sql,'id',[]);//多表查询赋值处理
                }else{
                    $info=get_db_field('rpurchaseinfo',['goods'=>['in',$goods]],'pid');//取出详情表数据
                    sql_assign($sql,'id',$info);//多表查询赋值处理
                }
            }
            //处理订单号搜索
            if(isset_full($input,'onumber')){
                $opurchase=get_db_field('opurchaseclass',['number'=>['like','%'.$input['onumber'].'%'],'type'=>1],'id');//取出采购订单表数据
                sql_assign($sql,'oid',$opurchase);//多表查询赋值处理
            }
            //处理仓库搜索
            if(isset_full($input,'warehouse')){
                $info=get_db_field('rpurchaseinfo',['warehouse'=>['in',explode(",",$input['warehouse'])]],'pid');//取出详情表数据
                sql_assign($sql,'id',$info,'intersect');//多表查询赋值处理
            }
            $sql['merchant']=['in',get_auth_merchant()];//补全授权商户数据
            $sql=auth('rpurchaseclass',$sql);//数据鉴权
            $count = Rpurchaseclass::where ($sql)->count();//获取总条数
            $arr = Rpurchaseclass::with('oidinfo,merchantinfo,supplierinfo,userinfo,accountinfo')->where($sql)->page($input['page'],$input['limit'])->order('id desc')->select();//查询分页数据
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
    //详情
    public function info(){
        $input=input('get.');
        //数据完整性判断
        if(isset_full($input,'id')){
            $class=Rpurchaseclass::where(['id'=>$input['id']])->find();
            $info=Rpurchaseinfo::with('goodsinfo,warehouseinfo')->where(['pid'=>$input['id']])->select();
            $this->assign('class',$class);
            $this->assign('info',$info);
            return $this->fetch('main');
        }else{
            $resule=['state'=>'error','info'=>'入力されたパラメーターが不完全です!'];
        }
        return json($resule);
    }
    //更新信息
    public function set(){
        $input=input('post.');
        if(isset_full($input,'id')){
            //验证采购入库单详情
            if(isset_full($input,'tab')){
                foreach ($input['tab'] as $tab_key=>$tab_vo) {
                    $tab_vali = $this->validate($tab_vo,'Rpurchaseinfo');//详情验证
                    if($tab_vali!==true){
                        return json(['state'=>'error','info'=>'[ 数据表格 ]第'.($tab_key+1).'行'.$tab_vali]);
                        exit;
                    }
                }
            }else{
                return json(['state'=>'error','info'=>'データテーブルを空にすることはできません!']);
                exit;
            }
            //更新操作
            $vali = $this->validate($input,'Rpurchaseclass.update');
            if($vali===true){
                $update_info=Rpurchaseclass::update(syn_sql($input,'rpurchaseclass'));
                Hook::listen('update_rpurchase',$update_info);//采购入库单更新行为
                push_log('倉庫の購入を更新します[ '.$update_info['number'].' ]');//日志
                $resule=['state'=>'success'];
            }else{
                $resule=['state'=>'error','info'=>$vali];
            }
            //更新采购入库单详情
            if($resule['state']=='success'){
                foreach ($input['tab'] as $info_vo) {
                    if(isset_full($info_vo,'attr')){unset($info_vo['attr']);}//删除辅助属性标识
                    Rpurchaseinfo::update(syn_sql($info_vo,'rpurchaseinfo'));
                }
            }
        }else{
            $resule=['state'=>'error','info'=>'入力されたパラメーターが不完全です!'];
        }
        //兼容自动审核[新增操作]
        if($resule['state']=='success'&&empty($input['id'])){
            empty(get_sys(['auto_auditing']))||($this->auditing([$create_info['id']],true));
        }
        return json($resule);
    }
    //审核
    public function auditing($arr=[],$auto=false){
        (empty($arr))&&($arr=input('post.arr'));//兼容多态审核
        if(empty($arr)){
            $resule=['state'=>'error','info'=>'入力されたパラメーターが不完全です!'];
        }else{
            $class_data=[];//初始化CLASS数据
            $info_data=[];//初始化INFO数据
            $oid_data=[];//初始化OID数据
            //数据检验
            foreach ($arr as $arr_vo) {
                $class=Rpurchaseclass::where(['id'=>$arr_vo])->find();
                $info=Rpurchaseinfo::where(['pid'=>$arr_vo])->select();
                //判断操作类型
                if(empty($class['type']['nod'])){
                    //审核操作
                    foreach ($info as $info_key=>$info_vo) {
                        //1.判断入库溢出
                        if(!isset($oid_data[$info_vo['oid']])){
                            $oid_data[$info_vo['oid']]=$info_vo['oidinfo']['readynums'];//OID不存在初始化赋值
                        }
                        $surplusnums=bcsub($info_vo['oidinfo']['nums'],$oid_data[$info_vo['oid']],config('decimal'));//计算差异数[高精度]
                        if($info_vo['nums']>$surplusnums){
                            $auto&&(push_log('購入リストの自動監査購入[ '.$class['number'].' ]失敗,理由:第'.($info_key+1).'線の数は、倉庫の数を超えています!'));//日志
                            return json(['state'=>'error','info'=>'レビュー-エントリリストを購入します[ '.$class['number'].' ]失敗,理由:第'.($info_key+1).'線の数は、倉庫の数を超えています!']);
                            exit;
                        }else{
                            $oid_data[$info_vo['oid']]=$oid_data[$info_vo['oid']]+$info_vo['nums'];
                        }
                        //2.判断串码
                        if(!empty($info_vo['serial'])){
                            $serial_sql=['code'=>['in',explode(',',$info_vo['serial'])],'type'=>['neq',2]];
                            $serial=Serial::where($serial_sql)->find();//查找串码状态为非不在库
                            if(!empty($serial)){
                                $auto&&(push_log('購入リストの自動監査購入[ '.$class['number'].' ]失敗,理由:第'.($info_key+1).'文字列コードのステータスが正しくありません!'));//日志
                                return json(['state'=>'error','info'=>'レビュー-エントリリストを購入します[ '.$class['number'].' ]失敗,理由:第'.($info_key+1).'文字列コードのステータスが正しくありません!']);
                                exit;
                            }
                        }
                    }
                }else{
                    //反审核操作
                    foreach ($info as $info_key=>$info_vo) {
                        if(!empty($info_vo['serial'])){
                            $serial_sql=['code'=>['in',explode(',',$info_vo['serial'])],'type'=>['neq',0]];
                            $serial=Serial::where($serial_sql)->find();//查找串码状态为非未销售
                            if(!empty($serial)){
                                return json(['state'=>'error','info'=>'反レビュー-エントリリストを購入します[ '.$class['number'].' ]第'.($info_key+1).'文字列コードのステータスが正しくありません!']);
                                exit;
                            }
                        }
                    }
                }
                $class_data[$arr_vo]=$class;//转存CLASS数据
                $info_data[$arr_vo]=$info;//转存INFO数据
            }
            //实际操作
            foreach ($arr as $arr_vo) {
                $class=$class_data[$arr_vo];//读取CLASS数据
                $info=$info_data[$arr_vo];//读取INFO数据
                //判断操作类型
                if(empty($class['type']['nod'])){
                    //审核操作
                    foreach ($info as $info_vo) {
                        //获取仓储信息
                        $room_sql=[];
                        $room_sql['warehouse']=$info_vo['warehouse'];
                        $room_sql['goods']=$info_vo['goods'];
                        $room_sql['attr']=$info_vo['attr']['nod'];
                        $room_sql['batch']=$info_vo['batch'];
                        $room=Room::where($room_sql)->find();
                        //设置仓储信息
                        if(empty($room)){
                            //新增仓储数据
                            $room_sql['nums']=$info_vo['nums'];
                            $room=Room::create($room_sql);
                        }else{
                            Room::where(['id'=>$room['id']])->setInc('nums',$info_vo['nums']);//更新仓储数据[+]
                        }
                        //新增仓储详情
                        $roominfo_sql['pid']=$room['id'];
                        $roominfo_sql['type']=11;
                        $roominfo_sql['class']=$arr_vo;
                        $roominfo_sql['info']=$info_vo['id'];
                        $roominfo_sql['nums']=$info_vo['nums'];
                        Roominfo::create($roominfo_sql);
                        Rpurchaseinfo::update(['id'=>$info_vo['id'],'room'=>$room['id']]);//更新INFO数据
                        //操作串码信息
                        if (!empty($info_vo['serial'])){
                            $serial_arr=explode(',',$info_vo['serial']);//分割串码信息
                            foreach ($serial_arr as $serial_arr_vo) {
                                $serial=Serial::where(['code'=>$serial_arr_vo])->find();//获取串码信息
                                //设置串码信息
                                if(empty($serial)){
                                    //新增串码数据
                                    $oldroom=0;//旧仓库ID
                                    $serial=Serial::create (['code'=>$serial_arr_vo,'goods'=>$info_vo['goods'],'room'=>$room['id'],'type'=>0]);
                                }else{
                                    //更新串码数据
                                    $oldroom=$serial['room'];//旧仓储ID
                                    Serial::update(['id'=>$serial['id'],'goods'=>$info_vo['goods'],'room'=>$room['id'],'type'=>0]);
                                }
                                //新增串码详情
                                Serialinfo::create (['pid'=>$serial['id'],'type'=>10,'class'=>$arr_vo,'oldroom'=>$oldroom]);
                            }
                        }
                        Opurchaseinfo::where (['id'=>$info_vo['oid']])->setInc('readynums',$info_vo['nums']);//更新已入库数量[+]
                    }
                    //获取核销状态
                    if($class['money']==$class['actual']){
                        $billtype=2;//已核销
                    }elseif($class['money']==0){
                        $billtype=0;//未核销
                    }else {
                        $billtype=1;//部分核销
                    }
                    //操作核销信息
                    if (!empty($class['money'])){
                        //新增对账单
                        $bill=Rpurchasebill::create(['pid'=>$arr_vo,'account'=>$class['account'],'money'=>$class['money'],'data'=>'システムは自動的に生成されます','user'=>Session('is_user_id'),'time'=>time()]);
                        Account::where (['id'=>$class['account']])->setDec('balance',$class['money']);//操作资金账户[-]
                        Accountinfo::create (['pid'=>$class['account'],'set'=>0,'money'=>$class['money'],'type'=>11,'time'=>time(),'user'=>Session('is_user_id'),'class'=>$arr_vo,'bill'=>$bill['id']]);//新增资金详情
                    }
                    Rpurchaseclass::update(['id'=>$arr_vo,'type'=>1,'auditinguser'=>Session('is_user_id'),'auditingtime'=>time(),'billtype'=>$billtype]);//更新CLASS数据
                    set_summary('rpurchase',$arr_vo,true);//更新统计表
                    push_log(($auto?'自動':'').'調達リストを確認します[ '.$class['number'].' ]');
                }else{
                    //反审核操作
                    foreach ($info as $info_vo){
                        Room::where (['id'=>$info_vo['room']])->setDec('nums',$info_vo['nums']);//更新仓储数据[-]
                        Rpurchaseinfo::update (['id'=>$info_vo['id'],'room'=>0]);//更新INFO数据
                        if(!empty($info_vo['serial'])){
                            $serial=Serial::where(['code'=>['in',explode(',',$info_vo['serial'])]])->select();//获取串码数据
                            foreach ($serial as $serial_vo) {
                                $serialinfo=Serialinfo::where(['pid'=>$serial_vo['id'],'type'=>10,'class'=>$arr_vo])->find();//获取串码详情
                                //设置串码数据
                                Serial::update([
                                    'id'=>$serial_vo['id'],
                                    'room'=>$serialinfo['oldroom'],
                                    'type'=>2
                                ]);
                                Serialinfo::where(['id'=>$serialinfo['id']])->delete();//删除串码详情
                            }
                        }
                        Opurchaseinfo::where (['id'=>$info_vo['oid']])->setDec('readynums',$info_vo['nums']);//更新已入库数量[-]
                    }
                    Roominfo::where(['type'=>11,'class'=>$arr_vo])->delete();//删除仓储详情
                    //操作核销信息
                    if (!empty($class['money'])){
                        $bill=Rpurchasebill::where(['pid'=>$arr_vo])->select();
                        foreach ($bill as $bill_vo){
                            Account::where(['id'=>$bill_vo['account']])->setInc('balance',$bill_vo['money']);//操作资金账户[+]
                        }
                        Accountinfo::destroy (['type'=>11,'class'=>$arr_vo]);//删除资金详情
                        Rpurchasebill::destroy(['pid'=>$arr_vo]);//删除对账单信息
                    }
                    Rpurchaseclass::update(['id'=>$arr_vo,'type'=>0,'money'=>0,'auditinguser'=>0,'auditingtime'=>0,'billtype'=>-1]);//更新CLASS数据
                    set_summary('rpurchase',$arr_vo,false);//更新统计表
                    push_log ('監査対策調達リスト[ '.$class['number'].' ]');
                }
                //更新采购订单入库状态 - 开始
                $opurchaseinfo=Opurchaseinfo::where(['pid'=>$class['oid']])->select()->toArray();//获取采购订单详情数据
                $sums=get_sums($opurchaseinfo,['nums','readynums']);
                //判断入库状态
                if(empty($sums['readynums'])){
                    $storage=0;//未入库
                }elseif($sums['nums']==$sums['readynums']){
                    $storage=2;//全部入库
                }else{
                    $storage=1;//部分入库
                }
                Opurchaseclass::update(['id'=>$class['oid'],'storage'=>$storage]);//更新入库状态
            }
            $resule=['state'=>'success'];
        }
        return $auto?true:json($resule);
    }
    //删除信息
    public function del(){
        $input=input('post.');
        if(isset_full($input,'arr') && is_array($input['arr'])){
            $class=db('rpurchaseclass')->where(['id'=>['in',$input['arr']]])->select()->ToArray();//获取CLASS数据
            $data = searchdata($class,['type'=>['eq',1]]);//查询已审核单据
            //数据检验
            if(empty($data)){
                foreach ($class as $class_vo) {
                    push_log('購入リストを削除します[ '.$class_vo['number'].' ]');//日志
                    Hook::listen('del_rpurchase',$class_vo['id']);//采购入库单删除行为
                }
                Rpurchaseclass::where(['id'=>['in',$input['arr']]])->delete();
                Rpurchaseinfo::where(['pid'=>['in',$input['arr']]])->delete();
                $resule=['state'=>'success'];
            }else{
                $resule=['state'=>'error','info'=>'エントリリストを購入します[ '.$data[0]['number'].' ]レビュー、削除できません!'];
            }
        }else{
            $resule=['state'=>'error','info'=>'入力されたパラメーターが不完全です!'];
        }
        return json($resule);
    }
    //导出报表信息
    public function exports(){
        $input=input('get.');
        if(isset($input['mode'])){
            push_log('調達リストデータをエクスポートします');//日志
            $sql=get_sql($input,[
                'name'=>'continue',
                'onumber'=>'continue',
                'number'=>'full_like',
                'start_time'=>'stime',
                'end_time'=>'etime',
                'supplier'=>'full_division_in',
                'type'=>'full_dec_1',
                'warehouse'=>'continue',
                'user'=>'full_division_in',
                'account'=>'full_division_in',
                'data'=>'full_like',
            ],'rpurchaseclass');//构造SQL
            //处理名称搜索
            if(isset_full($input,'name')){
                $goods=get_db_field('goods',['name|py'=>['like','%'.$input['name'].'%']],'id');//取出商品表信息
                if(empty($goods)){
                    sql_assign($sql,'id',[]);//多表查询赋值处理
                }else{
                    $info=get_db_field('rpurchaseinfo',['goods'=>['in',$goods]],'pid');//取出详情表数据
                    sql_assign($sql,'id',$info);//多表查询赋值处理
                }
            }
            //处理订单号搜索
            if(isset_full($input,'onumber')){
                $opurchase=get_db_field('opurchaseclass',['number'=>['like','%'.$input['onumber'].'%'],'type'=>1],'id');//取出采购订单表数据
                sql_assign($sql,'oid',$opurchase);//多表查询赋值处理
            }
            //处理仓库搜索
            if(isset_full($input,'warehouse')){
                $info=get_db_field('rpurchaseinfo',['warehouse'=>['in',explode(",",$input['warehouse'])]],'pid');//取出详情表数据
                sql_assign($sql,'id',$info,'intersect');//多表查询赋值处理
            }
            $sql['merchant']=['in',get_auth_merchant()];//补全授权商户数据
            $sql=auth('rpurchaseclass',$sql);//数据鉴权
            $arr = Rpurchaseclass::with('oidinfo,merchantinfo,supplierinfo,userinfo,accountinfo')->where($sql)->order('id desc')->select();//查询数据
            //判断报表类型
            if(empty($input['mode'])){
                //简易报表
                $formfield=get_formfield('rpurchase_export','array');//获取字段配置
                //开始构造导出数据
                $excel=[];//初始化导出数据
                //1.填充标题数据
                array_push($excel,['type'=>'title','info'=>'購入リスト']);
                //2.构造表格数据
                $table_cell=[];//初始化表头数据
                //构造表头数据
                foreach ($formfield as $formfield_vo) {
                    $table_cell[$formfield_vo['key']]=$formfield_vo['text'];
                }
                $table_data=[];//初始化表内数据
                //构造表内数据
                foreach ($arr as $arr_vo) {
                    $row_data=[];
                    //循环字段配置
                    foreach ($formfield as $formfield_vo) {
                        $val='nod_initial';//初始化数据
                        //循环匹配数据源
                        foreach (explode('|',$formfield_vo['data']) as $source) {
                            $val=$val=='nod_initial'?$arr_vo[$source]:(isset($val[$source])?$val[$source]:'');
                        }
                        $row_data[$formfield_vo['key']]=$val;//数据赋值
                    }
                    array_push($table_data,$row_data);//加入行数据
                }
                array_push($excel,['type'=>'table','info'=>['cell'=>$table_cell,'data'=>$table_data]]);//填充表内数据
                //3.添加汇总信息
                $sum_arr=get_sums($table_data,['total','actual','money']);
                array_push($excel,['type'=>'node','info'=>[
                    'ドキュメントの総量:'.$sum_arr['total'],
                    '実際の総額:'.$sum_arr['actual'],
                    '実際の支払いの総額:'.$sum_arr['money'],
                ]]);//填充汇总信息
                //4.导出execl
                export_excel('購入リスト',$excel);
            }else{
                //详细报表
                $files=[];//初始化文件列表
                $formfield=get_formfield('rpurchase_exports','array');//获取字段配置
                //配置字段
                $sys=get_sys(['enable_batch','enable_serial']);
                empty($sys['enable_batch'])&&(arrs_key_del($formfield,['key','batch']));
                empty($sys['enable_serial'])&&(arrs_key_del($formfield,['key','serial']));
                //循环CLASS数据
                foreach ($arr as $arr_vo) {
                    $excel=[];//初始化导出数据
                    //1.填充标题数据
                    array_push($excel,['type'=>'title','info'=>'采购入库单']);
                    //2.添加基础字段
                    array_push($excel,['type'=>'node','info'=>[
                        'サプライヤー:'.$arr_vo['supplierinfo']['name'],
                        '',
                        'ドキュメント日:'.$arr_vo['time'],
                        '',
                        'ドキュメント番号:'.$arr_vo['number'],
                    ]]);
                    //3.构造表格数据
                    $info=Rpurchaseinfo::where(['pid'=>$arr_vo['id']])->select();
                    $table_cell=[];//初始化表头数据
                    //构造表头数据
                    foreach ($formfield as $formfield_vo) {
                        $table_cell[$formfield_vo['key']]=$formfield_vo['text'];
                    }
                    $table_data=[];//初始化表内数据
                    //构造表内数据
                    foreach ($info as $info_vo) {
                        $row_data=[];
                        //循环字段配置
                        foreach ($formfield as $formfield_vo) {
                            $val='nod_initial';//初始化数据
                            //循环匹配数据源
                            foreach (explode('|',$formfield_vo['data']) as $source) {
                                $val=$val=='nod_initial'?$info_vo[$source]:(isset($val[$source])?$val[$source]:'');
                            }
                            $row_data[$formfield_vo['key']]=$val;//数据赋值
                        }
                        array_push($table_data,$row_data);//加入行数据
                    }
                    array_push($excel,['type'=>'table','info'=>['cell'=>$table_cell,'data'=>$table_data]]);//填充表内数据
                    //4.添加基础字段
                    array_push($excel,['type'=>'node','info'=>[
                        'ドキュメント量:'.$arr_vo['total'],
                        '',
                        '実際の金額:'.$arr_vo['actual'],
                        '',
                        '実際の支払い額:'.$arr_vo['money'],
                    ]]);
                    //5.添加基础字段
                    array_push($excel,['type'=>'node','info'=>[
                        'シングルハンドの人:'.$arr_vo['userinfo']['name'],
                        '',
                        '決済口座:'.$arr_vo['accountinfo']['name'],
                        '',
                        '備考情報:'.$arr_vo['data'],
                    ]]);
                    $path=export_excel($arr_vo['number'],$excel,false);//生成文件
                    array_push($files,$path);//添加文件路径数据
                }
                file_to_zip('倉庫での購入',$files);//打包输出数据
            }
        }else{
            $resule=['state'=>'error','info'=>'入力されたパラメーターが不完全です!'];
        }
        return json($resule);
    }
    //打印
    public function prints(){
        $input=input('get.');
        if(isset_full($input,'id')){
            $print_name='rpurchase';//模板标识
            $class=Rpurchaseclass::where(['id'=>$input['id']])->find();
            $info=Rpurchaseinfo::where(['pid'=>$input['id']])->select();
            $sys=get_sys(['enable_batch','enable_serial','print_paper']);
            //1.获取字段信息
            $formfield=get_formfield('rpurchase_print','array');//获取字段配置
            //2.配置字段
            empty($sys['enable_batch'])&&(arrs_key_del($formfield,['key','batch']));
            empty($sys['enable_serial'])&&(arrs_key_del($formfield,['key','serial']));
            //3.构造表格数据
            $tab_html=get_print_tab($formfield,$info);
            //4.获取模板代码
            $print=get_print($print_name);
            $print_text=$print[empty($sys['print_paper'])?'paper4':'paper2'];
            //5.赋值数据
            $this->assign('class',$class);
            $this->assign('tab_html',$tab_html);
            $this->assign('print_name',$print_name);
            $this->assign('paper_type',$sys['print_paper']);
            $this->assign('print_text',$print_text);
            return $this->fetch();
        }else{
            $resule=['state'=>'error','info'=>'入力されたパラメーターが不完全です!'];
        }
        return json($resule);
    }
    //---------------(^_^)---------------//
    //核销单
    public function bill(){
        return $this->fetch();
    }
    //核销单列表
    public function bill_list(){
        $input=input('post.');
        //数据完整性判断
        if(isset_full($input,'page') && isset_full($input,'limit')){
            $sql=get_sql($input,[
                'number'=>'full_like',
                'supplier'=>'full_division_in',
                'billtype'=>'full_dec_1',
                'start_time'=>'stime',
                'end_time'=>'etime',
                'account'=>'full_division_in',
                'user'=>'full_division_in',
                'data'=>'full_like',
            ],'rpurchaseclass');//构造SQL
            $whereor=[];//初始化OR条件
            //处理结算账户搜索
            if(isset_full($input,'account')){
                $info=get_db_field('rpurchasebill',['account'=>['in',explode(",",$input['account'])]],'pid');//取出表数据
                sql_assign($whereor,'id',$info);//多表查询赋值处理
            }
            $sql['type']=1;//补充条件
            $sql['merchant']=['in',get_auth_merchant()];//补全授权商户数据
            $sql=auth('rpurchaseclass',$sql);//数据鉴权
            $count = Rpurchaseclass::where($sql)->whereor($whereor)->count();//获取总条数
            $arr = Rpurchaseclass::with('merchantinfo,supplierinfo,userinfo,accountinfo')->where($sql)->whereor($whereor)->page($input['page'],$input['limit'])->order('id desc')->select();//查询分页数据
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
    //获取核销信息
    public function bill_info(){
        $input=input('post.');
        if(isset_full($input,'id')){
            $resule=Rpurchasebill::with('accountinfo,userinfo')->where(['pid'=>$input['id']])->order('id desc')->select();
        }else{
            $resule=['state'=>'error','info'=>'入力されたパラメーターが不完全です!'];
        }
        return json($resule);
    }
    //新增核销信息
    public function add_bill(){
        $input=input('post.');
        if(isset_full($input,'pid') && isset_full($input,'account') && isset_full($input,'money')){
            $class=Rpurchaseclass::where(['id'=>$input['pid']])->find();//获取CLASS数据
            $plus=bcadd($class['money'],$input['money'],config('decimal'));//初始化新金额[高精度]
            if($plus>$class['actual']){
                $resule=['state'=>'error','info'=>'和解額は不快な和解の金額を超えてはなりません!'];
            }else{
                //1.操作CLASS数据
                $billtype=($plus==$class['actual'])?2:1;//获取核销状态
                Rpurchaseclass::where(['id'=>$input['pid']])->update(['billtype'=>$billtype,'money'=>$plus]);//更新CLASS数据
                //2.操作核销数据
                $input['time']=time();
                $input['user']=Session('is_user_id');
                $create_info=Rpurchasebill::create(syn_sql($input,'rpurchasebill'));
                Hook::listen('create_rpurchasebill',$create_info);//采购入库核销单新增行为
                //3.操作资金账户
                Account::where (['id'=>$input['account']])->setDec('balance',$input['money']);//操作资金账户[-]
                Accountinfo::create (['pid'=>$input['account'],'set'=>0,'money'=>$input['money'],'type'=>11,'time'=>time(),'user'=>Session('is_user_id'),'class'=>$class['id'],'bill'=>$create_info['id']]);//新增资金详情
                //4.返回数据
                push_log('倉庫の購入に関する情報を追加する[ '.$class['number'].' ]');//日志
                $bill=Rpurchasebill::with('accountinfo,userinfo')->where(['id'=>$create_info['id']])->find();
                $resule=['state'=>'success','info'=>$bill];
            }
        }else{
            $resule=['state'=>'error','info'=>'入力されたパラメーターが不完全です!'];
        }
        return json($resule);
    }
    //删除核销信息
    public function del_bill(){
        $input=input('post.');
        if(isset_full($input,'id')){
            //1.操作BILL数据
            $bill=Rpurchasebill::where(['id'=>$input['id']])->find();//获取BILL数据
            Rpurchasebill::where(['id'=>$input['id']])->delete();//删除BILL数据
            Hook::listen('del_rpurchasebill',$bill);//采购入库核销单删除行为
            //2.操作资金账户
            Account::where(['id'=>$bill['account']])->setInc('balance',$bill['money']);//操作资金账户[+]
            Accountinfo::where(['type'=>11,'bill'=>$bill['id']])->delete();//删除资金详情
            //3.操作CLASS数据
            $class=Rpurchaseclass::where(['id'=>$bill['pid']])->find();//获取CLASS数据
            $reduce=bcsub($class['money'],$bill['money'],config('decimal'));//初始化新金额[高精度]
            $billtype=($reduce>0)?1:0;//获取核销状态
            Rpurchaseclass::where(['id'=>$bill['pid']])->update(['billtype'=>$billtype,'money'=>$reduce]);//更新CLASS数据
            //4.返回数据
            push_log('仕入れ入庫伝票情報を削除する[ '.$class['number'].' ]');//日志
            $resule=['state'=>'success'];
        }else{
            $resule=['state'=>'error','info'=>'入力されたパラメーターが不完全です!'];
        }
        return json($resule);
    }
    //强制核销
    public function force_bill(){
        $input=input('post.');
        if(isset_full($input,'id')){
            //1.操作CLASS数据
            $class=Rpurchaseclass::where(['id'=>$input['id']])->find();//获取CLASS数据
            Rpurchaseclass::where(['id'=>$input['id']])->update(['billtype'=>3]);//更新CLASS数据
            //2.返回数据
            push_log('仕入れ入庫取り消し伝票を強制的に取り消す[ '.$class['number'].' ]');//日志
            $resule=['state'=>'success'];
        }else{
            $resule=['state'=>'error','info'=>'入力されたパラメーターが不完全です!'];
        }
        return json($resule);
    }
    //批量核销
    public function set_bills(){
        $input=input('post.');
        if(isset_full($input,'arr') && isset_full($input,'account') && isset_full($input,'money')){
            $number_arr=[];//初始化单据号数组
            $tip='仕入れ入庫伝票を一括で取り消す[ '.date('YmdHi',time()).' ]';
            $account=$input['account'];//获取结算账户
            $money=$input['money'];//初始获取总结算金额
            $data=isset_full($input,'data')?$input['data']:$tip;
            $class=Rpurchaseclass::where(['id'=>['in',$input['arr']]])->select();//获取CLASS数据
            foreach ($class as $class_vo){
                //判断总结算金额[高精度]
                if(bccomp($money,0,config('decimal'))==0){
                    break;//跳出循环
                }elseif(!in_array($class_vo['billtype']['nod'],[0,1])){
                    continue;//跳过当前循环
                }else{
                    //1.初始化相关数据
                    $difference=bcsub($class_vo['actual'],$class_vo['money'],config('decimal'));//获取当前单据未结算金额[高精度]
                    $this_money=(bccomp($money,$difference,config('decimal'))==1)?$difference:$money;//获取本次结算金额[高精度]
                    $plus=bcadd($class_vo['money'],$this_money,config('decimal'));//初始化新金额[高精度]
                    //2.操作CLASS数据
                    $billtype=($plus==$class_vo['actual'])?2:1;//获取核销状态
                    Rpurchaseclass::where(['id'=>$class_vo['id']])->update(['billtype'=>$billtype,'money'=>$plus]);//更新CLASS数据
                    //3.操作核销数据
                	$create_bill=Rpurchasebill::create([
                	    'pid'=>$class_vo['id'],
                	    'account'=>$account,
                	    'money'=>$this_money,
                	    'data'=>$data,
                	    'user'=>Session('is_user_id'),
                	    'time'=>time()
                	]);
                	Hook::listen('create_rpurchasebill',$create_bill);//采购入库核销单新增行为
                	//4.操作资金账户
                	Account::where (['id'=>$account])->setDec('balance',$this_money);//操作资金账户[-]
                	Accountinfo::create (['pid'=>$account,'set'=>0,'money'=>$this_money,'type'=>11,'time'=>time(),'user'=>Session('is_user_id'),'class'=>$class_vo['id'],'bill'=>$create_bill['id'],'data'=>$data]);//新增资金详情
                	//5.更新数据
                	array_push($number_arr,$class_vo['number']);
                    $money=bcsub($money,$this_money,config('decimal'));//递减未结算总额[高精度]
                }
            }
            if(!empty($number_arr)){
                push_log($tip.' - 一括取り消しの総金額は[ '.$input['money'].' ] - 実際の取り消し伝票番号は[ '.implode(' | ',$number_arr).' ]');//日志
            }
        	$resule=['state'=>'success','info'=>$number_arr];
        }else{
            $resule=['state'=>'error','info'=>'入力されたパラメーターが不完全です!'];
        }
        return json($resule);
    }
    //导出核销信息
    public function bill_export(){
        $input=input('get.');
        $sql=get_sql($input,[
            'number'=>'full_like',
            'supplier'=>'full_division_in',
            'billtype'=>'full_dec_1',
            'start_time'=>'stime',
            'end_time'=>'etime',
            'account'=>'full_division_in',
            'user'=>'full_division_in',
            'data'=>'full_like',
        ],'rpurchaseclass');//构造SQL
        $whereor=[];//初始化OR条件
        //处理结算账户搜索
        if(isset_full($input,'account')){
            $info=get_db_field('rpurchasebill',['account'=>['in',explode(",",$input['account'])]],'pid');//取出表数据
            sql_assign($whereor,'id',$info);//多表查询赋值处理
        }
        $sql['type']=1;//补充条件
        $sql['merchant']=['in',get_auth_merchant()];//补全授权商户数据
        $sql=auth('rpurchaseclass',$sql);//数据鉴权
        $arr = Rpurchaseclass::with('merchantinfo,supplierinfo,userinfo,accountinfo')->where($sql)->order('id desc')->select();//查询数据
        $formfield=get_formfield('rpurchasebill_export','array');//获取字段配置
        //开始构造导出数据
        $excel=[];//初始化导出数据
        //1.填充标题数据
        array_push($excel,['type'=>'title','info'=>'采购入库核销单信息']);
        //2.构造表格数据
        $table_cell=[];//初始化表头数据
        //构造表头数据
        foreach ($formfield as $formfield_vo) {
            $table_cell[$formfield_vo['key']]=$formfield_vo['text'];
        }
        $table_data=[];//初始化表内数据
        //构造表内数据
        foreach ($arr as $arr_vo) {
            $row_data=[];
            //循环字段配置
            foreach ($formfield as $formfield_vo) {
                $val='nod_initial';//初始化数据
                //循环匹配数据源
                foreach (explode('|',$formfield_vo['data']) as $source) {
                    $val=$val=='nod_initial'?$arr_vo[$source]:(isset($val[$source])?$val[$source]:'');
                }
                $row_data[$formfield_vo['key']]=$val;//数据赋值
            }
            array_push($table_data,$row_data);//加入行数据
        }
        array_push($excel,['type'=>'table','info'=>['cell'=>$table_cell,'data'=>$table_data]]);//填充表内数据
        //3.添加汇总信息
        $sum_arr=get_sums($table_data,['total','actual','money']);
        array_push($excel,['type'=>'node','info'=>[
            'ドキュメントの総量:'.$sum_arr['total'],
            '実際の総額:'.$sum_arr['actual'],
            '実際の支払いの総額:'.$sum_arr['money'],
        ]]);//填充汇总信息
        //4.导出execl
        push_log('仕入れ入庫取り消し伝票情報をエクスポートする');//日志
        export_excel('仕入れ入庫取り消し伝票情報',$excel);
    }
}