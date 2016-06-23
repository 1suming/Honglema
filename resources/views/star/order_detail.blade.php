@extends('star.star_layouts')


@section('title', "Task")

@section('body')
@section('page-main')
    <header class="bar bar-nav">
     <a class="button button-link button-nav pull-left back" href="window.history(-1)">
            <span class="icon icon-left"></span>
            返回
        </a>
        <h1 class="title">订单详情</h1>
    </header>

        <div class="content-block"  style="padding: 0px;">
       
          <div class="list-block" style="margin-top:2.5rem;background-color:#FF4500;">
              <div class="item-content">
                    <div class="item-inner">
                        @if($data['order']->status==1)
                            <div class="item-title">正在审核 </div>
                            <div  class="item-after">
                                <a href="#" onclick="$.cancelOrder({{$data['order']->order_id}})" class="button button-dark" style="background-color:white">取消申请</a></div>
                        @endif
                        @if($data['order']->status==2&&$data['task']->status<3)
                            <div class="item-title">审核通过，任务进行中 </div>
                            <div  class="item-after">
  <a href="task_result?order_id={{$data['order']->order_id}}" class="button button-dark" style="background-color:white">提交结果{{$data['order']->order_id}}</a></div>
                        @endif
                        @if($data['order']->status==2&&$data['task']->status==3)
                            <div class="item-title">任务已完成，等待商家评论 </div>
                        @endif
                            @if($data['order']->status==2&&$data['task']->status==4)
                                <div class="item-title">商家已完成评价，任务结束 </div>
                            @endif
                        @if($data['order']->status==0)
                            <div class="item-title">取消申请 </div>
                        @endif
          </div> </div> </div>

         <div class="list-block" style="margin:0 auto">
            <ul>
            <li>
                <div valign="bottom" class="card-header color-white no-border no-padding" style="height:6rem">
                    <img class='card-cover' style="height:100%" src="{{$data['activity']->picture}}" alt="">
                </div>
            </li>
        </ul>

                <ul>
            <li>
                <div class="item-content">
                    <div class="item-inner">
                        <div class="item-title">{{ $data['activity']->title}}</div>
                        <div id="f_merchant_name" class="item-after">${{$data['activity']->total_price}}</div>
                    </div>
                </div>
            </li>
            <li>
                <div class="item-content">
                    <div class="item-inner">
                        <div class="item-title label">活动时间</div>
                        <div class="item-input">
                            <p>{{$data['activity']->time_within}}</p>
                        </div>
                    </div>
                </div>
                <div class="item-content">
                    <div class="item-inner">
                        <div class="item-title label">活动要求</div>
                        <div class="item-input">
                            <p>{{$data['activity']->claim}}</p>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
        </div>                
    </div>
    @include("star.star_footer")
@overwrite
@include('partial/jquery_mobile_page', ["page_id" => "main"])


 

