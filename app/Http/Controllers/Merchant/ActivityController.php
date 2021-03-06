<?php
/**
 * 
 * author: 田旭耕
 * 
 */
namespace App\Http\Controllers\Merchant;

use App\Http\Requests;
use Illuminate\Contracts\Http\Request;
use Illuminate\Support\Facades\Redirect;
use EasyWeChat\Foundation\Application;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityCommodityList;
use App\Models\Task;
use App\Models\TaskResult;
use App\Models\TaskPicture;
use App\Models\Star;
use App\Models\StarPicture;
use App\Models\Order;
use App\Models\PriceLevel;
use App\Models\Commodity;
use App\Models\Merchant;
use App\Models\Complaint;

class ActivityController extends RootController{
    public function __construct(){
        parent::__construct();
    }
    public function index(){
        $merchant_id = $_SESSION['merchant_id'];
        $activity = Activity::where('merchant_id',$merchant_id)->get();
        return view('merchant.activityOrder',['list'=>$activity]);
        //return view('merchant.index');
       
    }

    //发布活动
    public function addActivity(){
        $options = config('wechat');
        $app = new Application($options);
        $js = $app->js;
        $priceLevel = PriceLevel::all();
        $merchant = Merchant::where('merchant_id',$_SESSION['merchant_id'])->first();
        return view('merchant.add_activity',['priceLevel'=>$priceLevel,'js'=>$js,'merchant'=>$merchant]);
    }

    //保存发布的活动
    public function saveActivity(){
        // echo "<pre>";
        // var_dump($_POST);die;
        $commodity_names = $_POST['commodity_name'];
        $commodity_urls = $_POST['commodity_url'];
        $commodity_introduction = $_POST['commodity_introduction'];
        unset($_POST['_token'],$_POST['commodity_name'],$_POST['commodity_url'],$_POST['imgs'],$_POST['commodity_introduction']);
        //保存Activity
        $activity = new Activity();
        foreach ($_POST as $key => $value) {
           $activity[$key] = $value; 
        }
        $activity['merchant_id'] = $_SESSION['merchant_id'];
        $activity['activity_status'] = 0;
        $activity['confirm_num'] = 0;
        $activity->save();
        $activity_id = $activity['activity_id'];
        // //保存Task
        // $task = new Task();
        // $task['activity_id'] = $activity_id;
        // $task['status'] = 0;
        // $task->save();

        //保存商品
        for($i = 0;$i < count($commodity_names); $i ++){
            if($commodity_names[$i]==''){
                continue;
            }
            //保存commodity
            $commodity = new Commodity();
            $commodity['merchant_id'] = $_SESSION['merchant_id'];
            $commodity['name'] = $commodity_names[$i];
            $commodity['url'] = $commodity_urls[$i];
            $commodity['introduction'] = $commodity_introduction[$i];
            $commodity->save();

            //保存commodity和activity的关联表ActivityCommodityList
            $activityCommodityList = new ActivityCommodityList();
            $activityCommodityList['activity_id'] = $activity_id;
            $activityCommodityList['commodity_id'] = $commodity['commodity_id'];
            $activityCommodityList->save();
        }
        Redirect::to('/Merchant/activityOrder')->send();

    }


    public function modify(){
        return view('merchant.personalData');
    }

    public function activityDetail($id){
        /*
            根据数据库中该活动的状态字段确定前台页面的展示
            获取$detail['status']
            1表示未开始
            2表示正在进行
            3表示已结束
        */
        $activityDetail = Activity::where('activity_id',$id)->first();

        //获取所有已经确定的task
        $data['doing_order'] = Order::where('activity_id',$id)->where('status',2)->get();
        //获取所有等待确定的网红
        $data['waiting_order'] = Order::where('activity_id',$id)->where('status',1)->get();

        //获取该活动下的所有commodity_id
        $data['commodity_ids'] = ActivityCommodityList::where('activity_id',$activityDetail['activity_id'])->get();

        return view('merchant.activity_detail',['detail'=>$activityDetail,'data'=>$data]);
    }

    public function setOrder(){
        $star_id = intval($_POST['star_id']);
        $activity_id = intval($_POST['activity_id']);
        $status = intval($_POST['order_status']);
        $add_num = intval($_POST['task_num']);
        $order = Order::where('status',1)->where('star_id',$star_id)->where('activity_id',$activity_id)->first();
        if(!$order){
            echo json_encode(array('error'=>1,'msg'=>'该网红已取消订单'));die;
        }
        $order['status'] = $status;
        
        if($status == 2){
            $task = new Task();
            $task['status'] = 1;
            $task['activity_id'] = $activity_id;
            $task['show_num'] = $add_num;
            $task->save();
            $order['task_id'] = $task->task_id;
            $activity = Activity::where('activity_id',$activity_id)->first();
            if($activity['confirm_num'] < $activity['task_num']){
                $activity['confirm_num'] = $activity['confirm_num'] + $add_num;
            }
            $activity->save();
        }
        $order->save();
        echo json_encode(array('error'=>0,'msg'=>'合作成功'));die;
    }

    //物流详情页
    public function taskDetail($task_id){
        $task = Task::where('task_id',$task_id)->first();
        //$task_Pic = TaskPicture::where('task_id',$task_id)->get();
        $task_result = TaskResult::where('task_id',$task_id)->get();

        $order = Order::where('task_id',$task_id)->first();
        $star = Star::where('star_id',$order['star_id'])->first();
        $activity = Activity::where('activity_id',$order['activity_id'])->first();
        return view('merchant.task_detail',['task'=>$task,'task_result'=>$task_result,'star'=>$star,'activity'=>$activity]);
    }

    //保存物流信息
    public function saveLogistic(){
         // var_dump($_POST);die;
        $task_id = $_POST['task_id'];
        $task = Task::where('task_id',$task_id)->first();
        $task['is_shipping'] = intval($_POST['is_shipping']);
        if($task['is_shipping']==1){
            $task['express_company'] = $_POST['company'];
            $task['express_num'] = $_POST['num'];
        }
        $task['status'] = 2;

        $task->save();
    }

    // //跳转到评论页
    // public function comment($task_id){
    //     $task_Pic = TaskPicture::where('task_id',$task_id)->get();
    //     $task = Task::where('task_id',$task_id)->first();
    //     return view('merchant.comment',['taskPics'=>$task_Pic,'task'=>$task]);
    // }

    //保存评论
    public function saveComment(){
        //var_dump($_POST['task_id']);die;
        $task_id = intval($_POST['task_id']);
        $comment = $_POST['comment'];
        $evaluation_level = intval($_POST['evaluation_level']);
        $task = Task::where('task_id',$task_id)->first();
        $task['evaluation'] = $comment;
        $task['evaluation_level'] = $evaluation_level;
        $task['status'] = 4;
        $task->save();

        //判断activity的状态是否改变
        $activity = Activity::where('activity_id',$task['activity_id'])->first();
        if($activity['task_num']==$activity['confirm_num']){
            //报名人数是否已满
            $end_task = Task::where('activity_id',$activity['activity_id'])->where('status',4)->get();
            $num = count($end_task); //已结束的task
            if($activity['confirm_num'] == $num){
                //全部的task都结束，则activity结束
                $activity['activity_status'] = 2;
                $activity->save(); 
            }

        }
    }

    //跳转到直播详情页
    public function showDetail($task_result_id){
        // var_dump($task_result_id);die;
        $task_Pics = TaskPicture::where('task_result_id',$task_result_id)->get();
        $task_result = TaskResult::where('task_result_id',$task_result_id)->first();
        return view('merchant.task_result',['taskPics'=>$task_Pics,'task_result'=>$task_result]);
    }

    //跳转到投诉页
    public function complaint($task_id){
        $order = Order::where('task_id',$task_id)->first();
        return view('merchant.complaint',['task_id'=>$task_id,'star_id'=>$order['star_id']]);
    }

    //保存投诉内容
    public function saveComplaint(){
        $complaint = new Complaint();
        $complaint['star_id'] = intval($_POST['star_id']);
        $complaint['task_id'] = intval($_POST['task_id']);
        $complaint['merchant_id'] = $_SESSION['merchant_id'];
        $complaint['content'] = $_POST['content'];
        $complaint['status'] = 0;
        $complaint['type'] = 0;
        $complaint->save();
        if($complaint['complaint_id']){
            echo json_encode(array('error'=>0,'msg'=>'投诉成功'));die;
        }else{
            echo json_encode(array('error'=>1,'msg'=>'投诉失败'));die;
        }

    }

    //查看网红详情
    public function starDetail($star_id,$activity_id){
        $star = Star::where('star_id',$star_id)->first();
        $starPic = StarPicture::where('uid',$star_id)->get();

        //计算网红评分
        $orders = Order::where('star_id',$star_id)->where('status',2)->get();
        $sum = 0;
        $num = 0;
        foreach ($orders as $key => $value) {
           $task = Task::where('task_id',$value['task_id'])->where('evaluation_level', '>', 0)->first();
           if($task){
              $sum += $task['evaluation_level'];
              $num ++;
           }
        }
        if($num != 0){
            $average_evaluation = round($sum / $num,1);
            $average_evaluation = $average_evaluation / 5 * 100;
            $average_evaluation = $average_evaluation.'分';
        }else{
            $average_evaluation = '暂无评价';
        }

        $activity = Activity::where('activity_id',$activity_id)->first();
        $this_order = Order::where('star_id',$star_id)->where('activity_id',$activity_id)->first();

        return view('merchant.star_detail',['this_order'=>$this_order,'star'=>$star,'starPic'=>$starPic,'activity'=>$activity,'average_evaluation'=>$average_evaluation]);

    }

}