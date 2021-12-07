<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User_model as user_m;
use App\Main_service_model as main_service_m;
use App\Sub_service_model as sub_service_m;
use App\Slider_model as slider_m;
use App\Buy_service_model as buy_service_m;
use App\Coupon_model as coupon_m;
use App\Coupon_purchase_model as coupon_purchase_m;
use App\Coupon_redeem_model as coupon_redeem_m;
use App\Membership_model as membership_m;
use App\Membership_purchase_model as purchase_membership_m;
use App\Notification_model as notification_m;
use App\Referral_code_model as referral_code_m;
use Crypt;
use Storage;
Use Carbon\Carbon;

class User extends Controller
{
    public function user_register(Request $request)
    {
        $user_lattitude = $user_longitude = '';

        $login_type = $request->login_type;
        $social_id = $request->social_id;
        $user_lattitude = $request->user_lattitude;
        $user_longitude = $request->user_longitude;
        $first_name = $request->first_name;
        $last_name = $request->last_name;
        $phone_no = $request->phone_no;
        $email_id = $request->email_id;
        $referral_code = $request->referral_code;
        $password = $request->password;
        $device_token = $request->device_token;
        $device_type = $request->device_type;
        $profile_pic = $request->profile_pic;

        if($login_type == ""){
            return response()->json(['status' => false,'statusCode' => 0, 'message'=>'Please Enter login type.'], 200);
        }

        /*if($user_lattitude == ""){
            return response()->json(['status' => false,'statusCode' => 0, 'message'=>'Please Enter user Lattitude.'], 200);
        }

        if($user_longitude == ""){
            return response()->json(['status' => false,'statusCode' => 0, 'message'=>'Please Enter user Longitude.'], 200);
        } */

        # check login type social_login or normal login
        if($login_type != 'social_login'){
            
            # normal login process
            if($first_name == ""){
                return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please Enter First Name.'], 200);
            }

            if($last_name == ""){
                return response()->json(['status' => false,'statusCode' => 0, 'message'=>'Please Enter Last Name.'], 200);
            }

            // if($referral_code == ""){
            //     return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please Enter Refer code.'], 200);
            // } 
    
            if($phone_no == ""){
                return response()->json(['status' => false,'statusCode' => 0, 'message'=>'Please Enter Phone Number.'], 200);
            }
    
            if($email_id == ""){
                return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please Enter Email Id.'], 200);
            }
            
            if($password == ""){
                return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please Enter Password.'], 200);
            }
            
            # check email exist or not
            $useremail_exist = user_m::where(['email_id'=>$email_id, 'is_deleted' => 0])->first();
            
            if(is_null($useremail_exist)){

                # check phone number exist or not
                $userphone_exist = user_m::where(['phone_no'=>$phone_no, 'is_deleted' => 0])->first();

                if(is_null($userphone_exist)){
                    $user_password = Crypt::encrypt($password);
                    $unique_token = $this->getSecureKey();
                    $unique_id = $this->getUniqueId();
                    $referral_code_generate = $this->getReferralCode();
    
                    $insert_data = [
                                'first_name' => $first_name,
                                'last_name' => $last_name,
                                'phone_no' => $phone_no,
                                'email_id' => $email_id,
                                'password' => $user_password,
                                'referral_code' => $referral_code_generate,
                                'unique_id' => $unique_id,
                                'user_lattitude'=> $user_lattitude,
                                'user_longitude'=> $user_longitude,
                                'unique_token' => $unique_token,
                                'login_type'  => $request->login_type,                  
                                'social_id'  => $request->social_id,    
                                'device_type' => $device_type,                     
                                'device_token' => $device_token,              
                    ];
                    
                    # add profile picture
                    if($request->hasFile('profile_pic')){
                        
                        $newImageName = 'user_profiles_'.time().'_'.str_random(10).'.'.$request->profile_pic->getClientOriginalExtension();
                        $request->profile_pic->storeAs('public/image/user_profiles/',$newImageName);
        
                        $insert_data['profile_pic'] = $newImageName;
                    }
                    $insertid = user_m::create($insert_data);
                    
                    if($insertid){

                        $get_user_detail = user_m::where(['user_id' => $insertid->user_id,'is_deleted'=>0])->first();
                        if(!is_null($get_user_detail)){
                           

                            # get user data 
                            $data = $this->getUserData($get_user_detail->user_id);

                            # insert referral code data 
                            if(!is_null($referral_code)){
                                $referral_code_detail = user_m::where(['referral_code' => $referral_code,'is_deleted'=>0])->first();
                                if(!is_null($referral_code_detail)){
                                    $insert_referral_code = ['register_user_id'=>$insertid->user_id, 'referral_code_user_id'=> $referral_code_detail->user_id,'referral_code'=>$referral_code];
                                    
                                    $store_referral = referral_code_m::create($insert_referral_code);

                                    $data['referral_user_id'] = $referral_code_detail->user_id;
                                    $data['referral_user_code'] = $referral_code;

                                }else{
                                    return response()->json(['status' => false,'statusCode' => 5,'message'=>'Referral code not exist.'], 200);
                                }
                            }


                            return response()->json(['status' => true,'statusCode' => 3,'message'=>"User Sign up Successfully.", 'data' => $data], 200);
                        }else{
                            return response()->json(['status' => false,'statusCode' => 5,'message'=>"Oops! Something wents Wrong."], 200);
                        }
                    }else{
                        return response()->json(['status' => false,'statusCode' => 4,'message'=>"User can't Sign up."], 200);
                    }
                }else{
                    return response()->json(['status' => false,'statusCode' => 2,'message'=>'Phone Number already exits.'], 200);
                }
            }else{
                return response()->json(['status' => false,'statusCode' => 1,'message'=>'Email already exits.'], 200);
            }
        }else{
            # social login process
            if($social_id == ""){
                return response()->json(['status' => false,'statusCode' => 0, 'message'=>'Please Enter social id.'], 200);
            }

            $usersocialid_exist = user_m::where(['social_id' => $social_id,'is_deleted'=>0])->first();

            # check social login exist or not
            if(is_null($usersocialid_exist)){
                $user_password = Crypt::encrypt($password);
                $unique_token = $this->getSecureKey();
                $unique_id = $this->getUniqueId();
                $referral_code_generate = $this->getReferralCode();

                # register process
                $insert_data = [
                            'first_name' => $first_name,
                            'last_name' => $last_name,
                            'phone_no' => $phone_no,
                            'email_id' => $email_id,
                            'password' => $user_password,
                            'referral_code' => $referral_code_generate,
                            'unique_id' => $unique_id,
                            'profile_pic' => $profile_pic,
                            'user_lattitude'=> $user_lattitude,
                            'user_longitude'=> $user_longitude,
                            'unique_token' => $unique_token,
                            'login_type'  => $request->login_type,                  
                            'social_id'  => $request->social_id,    
                            'device_type' => $device_type,                     
                            'device_token' => $device_token,              
                ];
                $insertid = user_m::create($insert_data);

                if($insertid){
                    # get register user data 
                    $get_user_detail = user_m::where(['user_id' =>$insertid->user_id,'is_deleted'=>0])->first();

                    if(!is_null($get_user_detail)){
                        
                        # get user data using comman function
                        $data = $this->getUserData($get_user_detail->user_id);
                                           
                        return response()->json(['status' => true,'statusCode' => 3,'message'=>"User Sign up Successfully.", 'data' => $data], 200);
                    }else{
                        return response()->json(['status' => false,'statusCode' => 5,'message'=>"Oops! Something wents Wrong."], 200);
                    }
                }else{
                    return response()->json(['status' => false,'statusCode' => 4,'message'=>"User can't Sign up."], 200);
                }
            }else{
                # update user token process 
                $unique_token = $this->getSecureKey();
                $edit_user =[
                    'device_type' => $device_type,                     
                    'device_token' => $device_token,
                    'unique_token' => $unique_token
                ];
                $usersocialid_exist_updatetoken = user_m::where(['social_id' =>$social_id,'is_deleted'=>0])->update($edit_user);
                if($usersocialid_exist_updatetoken == 1){

                    # get already login user data by social id
                    $data = $this->getUserData($usersocialid_exist->user_id);
                    $data['userphone_exist'] = ($usersocialid_exist->phone_no == null) ? 'false' : 'true';

                    return response()->json(['status' => true,'statusCode' => 3,'message'=>"User login Successfully.", 'data' => $data], 200);
                }else{
                    return response()->json(['status' => false,'statusCode' => 5,'message'=>"Oops! Something wents Wrong."], 200);
                }
            }
        }
    }

    public function user_login(Request $request)
    {
       //dd('hello');
       $phone_no = $request->phone_no;
       $password = $request->password;
       $device_token = $request->device_token;
       $device_type = $request->device_type;

       if($phone_no == ""){
           return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please enter phone number.'], 200);
       }
       if($password == ""){
           return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please enter password.'], 200);
       }

       $get_user = user_m::where(['phone_no'=>$phone_no, 'is_deleted' => 0])->first();

        # check user phone number exsit or not 
        if(!is_null($get_user)){

            # check user type
            $get_vender = user_m::where(['user_type'=> 'normal_user', 'is_deleted' => 0])->first();
            if(!is_null($get_vender)){


               $get_password = Crypt::decrypt($get_user->password);
               # match password after descypt
               if($get_password == $password){
                    $unique_token = $this->getSecureKey();
                       $edit_user =[
                           'device_type' => $device_type,                     
                           'device_token' => $device_token,
                           'unique_token' => $unique_token
                       ];

                       $update_user = user_m::where(['user_id' => $get_user->user_id,'is_deleted' => 0])->update($edit_user);
                      
                       $get_user_detail = user_m::where(['user_id' => $get_user->user_id,'is_deleted' => 0])->first();
                     
                       $data = $this->getUserData($get_user_detail->user_id);
                       
                    return response()->json(['status' => true,'statusCode' => 3, 'message'=>'User login Successfully.', 'data' => $data], 200);
                }else{
                   return response()->json(['status' => false,'statusCode' => 2,'message'=>'Password you entered is incorrect.'], 200);
                }
            }else{
               return response()->json(['status' => false,'statusCode' => 1,'message'=>'User not Exist.'], 200);
            }
        }else{
           return response()->json(['status' => false,'statusCode' => 1,'message'=>'Data not Found.'], 200);
        }
    }
    
    public function get_slider(Request $request)
    {
        $user_id = $request->header('UserId');

        # check user exist or not
        $user_exist = user_m::where(['user_id'=>$user_id, 'is_deleted' => 0])->first();
        if(!is_null($user_exist)){

            // $get_slider = slider_m::where('visible_status','is_active')->get();
            $get_slider = slider_m::where(['visible_status'=>'is_active','is_deleted'=> 0])->get();

            if($get_slider->count() >0){
                $result = [];
                foreach($get_slider as $key_data => $get_slider_detail){
    
                    $data['slider_id'] = $get_slider_detail->slider_id;
                    $data['slider_title'] = ($get_slider_detail->slider_title == null) ? '' : $get_slider_detail->slider_title;
                    $data['slider_description'] = ($get_slider_detail->slider_description == null) ? '' : $get_slider_detail->slider_description;
                    $data['slider_price'] = ($get_slider_detail->slider_price == null) ? '' : $get_slider_detail->slider_price;
                    if($get_slider_detail->slider_image != null){
                        $data['slider_image'] = asset('public/storage/image/slider')."/".$get_slider_detail->slider_image;
                    }else{
                        $data['slider_image'] = "";
                    }
                    $data['notification_badge'] = ($user_exist->badge_notification == null) ? 0 : $user_exist->badge_notification;

                    $result[] = $data;
                }
                return response()->json(['status' => true,'statusCode' => 3,'message'=>"Data get successfully.", 'data' => $result], 200);
            }else{
                return response()->json(['status' => false,'statusCode' => 1,'message'=>"Data not found."], 200);
            } 
        }else{
            return response()->json(['status' => false,'statusCode' => 1,'message'=>'User Not Exist.'], 200);
        } 
    }

    public function get_main_services(Request $request)
    {
        $get_services = main_service_m::where('is_deleted',0)->get();

        if($get_services->count() >0){
             $result = [];
            foreach($get_services as $key_data => $get_main_service_detail){
 
                $data['main_services_id'] = $get_main_service_detail->main_services_id;
                $data['main_services_title'] = ($get_main_service_detail->main_services_title == null) ? '' : $get_main_service_detail->main_services_title;
                $data['main_services_description'] = ($get_main_service_detail->main_services_description == null) ? '' : $get_main_service_detail->main_services_description;
                
                if($get_main_service_detail->main_services_image != null){
                    $data['main_services_image'] = asset('public/storage/image/main_services')."/".$get_main_service_detail->main_services_image;
                }else{
                    $data['main_services_image'] = "";
                }

                $result[] = $data;
            }
            return response()->json(['status' => true,'statusCode' => 3,'message'=>"Main services get successfully.", 'data' => $result], 200);
        }else{
            return response()->json(['status' => false,'statusCode' => 1,'message'=>"Data not found."], 200);
        }  
    }
    
    public function get_sub_services(Request $request)
    {
        $user_id = $request->header('UserId');
        $main_service_id = $request->main_service_id;
        
        if($main_service_id == ""){
            return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please enter main services id.'], 200);
        }

        $user_exist = user_m::where(['user_id'=>$user_id, 'is_deleted' => 0])->first();

        # check user exist or not
        if(!is_null($user_exist)){

            # check main services is exist or not
            $get_main_services = main_service_m::where(['main_services_id' => $main_service_id,'is_deleted' => 0])->first();
            if(!is_null($get_main_services)){

                # check sub services is exist or not find main services id through
                $get_services = sub_service_m::where(['main_service_id' => $main_service_id,'is_deleted' => 0])->get();
                if(!is_null($get_services)){
                    $result = [];
                    foreach($get_services as $key_data => $get_subservice_detail){

                        # check services buy or not
                        $buy_service = buy_service_m::where(['sub_services_id' => $get_subservice_detail->sub_service_id, 'user_id' => $user_id,'redeem_status'=>'false'])->first();
                    
                        $data['sub_service_id'] = $get_subservice_detail->sub_service_id;
                        $data['main_service_id'] = $get_subservice_detail->main_service_id;
                        $data['main_service_title'] = $get_main_services->main_services_title;
                        $data['sub_service_title'] = ($get_subservice_detail->sub_service_title == null) ? '' : $get_subservice_detail->sub_service_title;
                        $data['sub_service_description'] = ($get_subservice_detail->sub_service_description == null) ? '' : $get_subservice_detail->sub_service_description;
                        $data['sub_service_price'] = ($get_subservice_detail->sub_service_price == null) ? '' : $get_subservice_detail->sub_service_price;
                        
                        if($get_subservice_detail->sub_service_image != null){
                            $data['sub_service_image'] = asset('public/storage/image/sub_services')."/".$get_subservice_detail->sub_service_image;
                        }else{
                            $data['sub_service_image'] = "";
                        }

                        # buy services data
                        $data['buy_service_status'] = ($buy_service == null) ? 'is_not_purchase' : 'is_purchase';
                    
                        if(!is_null($buy_service)){
                            $data['buy_services_id'] = $buy_service->buy_services_id;
                            $data['qr_code_data'] = ['app_name'=> 'salon' ,'purchase_type'=> 'sub_service_status','user_unique_id' => $user_exist->unique_id, 'purchase_id' =>$buy_service->buy_services_id];
                            // $data['qr_code'] = ($buy_service->qr_code == null) ? '' : $buy_service->qr_code;
                            $data['service_buy_date'] = ($buy_service->buy_service_date == null) ? '' : $buy_service->buy_service_date;
                            $data['service_end_date'] = ($buy_service->buy_service_end_date == null) ? '' : $buy_service->buy_service_end_date;
                            $data['service_buy_place'] = ($buy_service->buy_service_place == null) ? '' : $buy_service->buy_service_place;
                        }else{
                            $data['buy_services_id'] = '';
                            $data['qr_code_data'] =  '' ;
                            $data['service_buy_date'] = '' ;
                            $data['service_end_date'] = '' ;
                            $data['service_buy_place'] = '' ;
                        }
                        
                        $result[] = $data;
                    }
                    return response()->json(['status' => true,'statusCode' => 3,'message'=>"Sub services get successfully.", 'data' => $result], 200);
                }else{
                    return response()->json(['status' => false,'statusCode' => 1,'message'=>"Data not found."], 200);
                } 
            }else{
                return response()->json(['status' => false,'statusCode' => 2,'message'=>"Main service not found."], 200);
            }  
        }else{
            return response()->json(['status' => false,'statusCode' => 1,'message'=>'User Not Exist.'], 200);
        }
    }

    public function check_mobile_no(Request $request)
    {
        $phone_no = $request->phone_no;
       
        if($phone_no == ""){
            return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please Enter Phone number.'], 200);
        }
       
        $get_user = user_m::where(['phone_no'=>$phone_no, 'is_deleted' => 0,'login_type' => 'normal_login'])->first();

        if(!is_null($get_user)){
            return response()->json(['status' => true,'statusCode' => 3,'message'=>"Data get Successfully."], 200);  
        }else{
            return response()->json(['status' => false,'statusCode' => 2,'message'=>'User not Found'], 200);
        }
    }

    public function create_new_password(Request $request)
    {
        $phone_no = $request->phone_no;
        $new_password = $request->new_password;
        $confirm_new_password = $request->confirm_new_password;
        
        if($phone_no == ""){
            return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please Enter  Phone number.'], 200);
         }
        if($new_password == ""){
           return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please Enter new password.'], 200);
        }
        if($confirm_new_password == ""){
            return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please Enter confirm new password.'], 200);
        }

        $get_user = user_m::where(['phone_no'=>$phone_no, 'is_deleted' => 0])->first();

        # check user exist or not
        if(!is_null($get_user)){
            
            # match password 
            $new_password = $request->new_password;
            if($confirm_new_password == $new_password){

               $unique_token = $this->getSecureKey();
               $update = user_m::where('user_id',$get_user->user_id)
                        ->update(['password' => Crypt::encrypt($new_password),'unique_token' => $unique_token]);

                # change password status check
                if($update == 1){
                    $get_user_detail = user_m::where(['user_id' => $get_user->user_id, 'is_deleted' => 0])->first();

                    if(!is_null($get_user_detail)){
                       
                         # get user data 
                         $data = $this->getUserData($get_user_detail->user_id);
                      
                        return response()->json(['status' => true,'statusCode' => 3,'message'=>"New password Created Successfully.", 'data' => $data], 200);  
                    }else{
                       return response()->json(['status' => false,'statusCode' => 4,'message'=>'Data not Found'], 200);
                    }
                }else{
                   return response()->json(['status' => false,'statusCode' => 5,'message'=>"Oops!Something wents Wrong."], 200);
                }
            }else{
               return response()->json(['status' => false,'statusCode' => 2,'message'=>"Confirm Password not Match."], 200);
            }
        }else{
           return response()->json(['status' => false,'statusCode' => 1,'message'=>'User Not Exist.'], 200);
        }
    }

    public function change_password(Request $request)
    {
        $user_id = $request->header('UserId');
        $old_password = $request->old_password;
        $new_password = $request->new_password;
        $confirm_new_password = $request->confirm_new_password;

        if($old_password == ""){
           return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please Enter current password.'], 200);
        }
        if($new_password == ""){
           return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please Enter new password.'], 200);
        }
        if($confirm_new_password == ""){
            return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please Enter confirm new password.'], 200);
        }

        $get_user = user_m::where(['user_id'=>$user_id, 'is_deleted' => 0])->first();

         # check user exist or not
        if(!is_null($get_user)){

            # match old and new password
           $user_password = Crypt::decrypt($get_user->password);
           if($user_password == $old_password){
                
                # match new password with confirm password
                $new_password = $request->new_password;
                if($confirm_new_password == $new_password){
                   
                    # update password
                    $unique_token = $this->getSecureKey();
                    $update = user_m::where(['user_id' => $user_id, 'is_deleted' => 0])
                            ->update(['password' => Crypt::encrypt($new_password),'unique_token' => $unique_token]);

                    if($update == 1){
                        $get_user_detail = user_m::where(['user_id' => $user_id, 'is_deleted' => 0])->first();

                        if(!is_null($get_user_detail)){
                        
                            # get user data 
                            $data = $this->getUserData($get_user_detail->user_id);

                            return response()->json(['status' => true,'statusCode' => 3,'message'=>"Password updated Successfully.", 'data' => $data], 200);  
                        }else{
                        return response()->json(['status' => false,'statusCode' => 4,'message'=>'Data not Found'], 200);
                        }
                    }else{
                    return response()->json(['status' => false,'statusCode' => 5,'message'=>"Oops!Something wents Wrong."], 200);
                    }
                }else{
                    return response()->json(['status' => false,'statusCode' => 2,'message'=>"Confirm Password not Match."], 200);
                 }
            }else{
               return response()->json(['status' => false,'statusCode' => 2,'message'=>"Current Password is Wrong."], 200);
            }
        }else{
           return response()->json(['status' => false,'statusCode' => 1,'message'=>'User Not Exist.'], 200);
        }
    }

    public function change_name(Request $request)
    {
        $user_id = $request->header('UserId');
        $first_name = $request->first_name;
        $last_name = $request->last_name;

        if($first_name == ""){
           return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please Enter First name.'], 200);
        }
        if($last_name == ""){
           return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please Enter Last name.'], 200);
        }

        $get_user = user_m::where(['user_id'=>$user_id, 'is_deleted' => 0])->first();

        # check user exist or not
        if(!is_null($get_user)){

            # change name
            $update = user_m::where(['user_id' => $user_id, 'is_deleted' => 0])->update(['first_name' => $first_name, 'last_name' => $last_name]);
            if($update == 1){
                $get_user_detail = user_m::where(['user_id' => $user_id, 'is_deleted' => 0])->first();

                if(!is_null($get_user_detail)){
                    
                     # get user data 
                     $data = $this->getUserData($get_user_detail->user_id);
                    
                    return response()->json(['status' => true,'statusCode' => 3,'message'=>"Name updated Successfully.", 'data' => $data], 200);  
                }else{
                    return response()->json(['status' => false,'statusCode' => 4,'message'=>'Data not Found'], 200);
                }
            }else{
                return response()->json(['status' => false,'statusCode' => 5,'message'=>"Oops!Something wents Wrong."], 200);
            }
        }else{
           return response()->json(['status' => false,'statusCode' => 1,'message'=>'User Not Exist.'], 200);
        }
    }

    public function change_phone_no(Request $request)
    {
        $user_id = $request->header('UserId');
        $phone_no = $request->phone_no;

        if($phone_no == ""){
           return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please Enter Phone number.'], 200);
        }
      
        $get_user = user_m::where(['user_id'=>$user_id, 'is_deleted' => 0])->first();

        # check user exist or not
        if(!is_null($get_user)){

            # change phone number
            $update = user_m::where(['user_id' => $user_id, 'is_deleted' => 0])->update(['phone_no' => $phone_no]);
            if($update == 1){
                $get_user_detail = user_m::where(['user_id' => $user_id, 'is_deleted' => 0])->first();

                if(!is_null($get_user_detail)){
                    
                     # get user data 
                     $data = $this->getUserData($get_user_detail->user_id);
                    
                    return response()->json(['status' => true,'statusCode' => 3,'message'=>"Phone number updated Successfully.", 'data' => $data], 200);  
                }else{
                    return response()->json(['status' => false,'statusCode' => 4,'message'=>'Data not Found'], 200);
                }
            }else{
                return response()->json(['status' => false,'statusCode' => 5,'message'=>"Oops!Something wents Wrong."], 200);
            }
        }else{
           return response()->json(['status' => false,'statusCode' => 1,'message'=>'User Not Exist.'], 200);
        }
    }

    public function get_coupons(Request $request)
    {
        $main_services_id = $request->main_services_id;

        $get_coupons = coupon_m::where('is_deleted',0)->get();
        // print_r($get_coupons);die;
        if($get_coupons->count() >0){

            $query = coupon_m::query();
            if(!is_null($main_services_id)){
                $query->where('main_services_id', $main_services_id);
            }
            $query->where('is_deleted', 0);
            $check_coupun_services = $query->get();

            if(!is_null($check_coupun_services)){
            
                $result = [];
                foreach($check_coupun_services as $key_data => $coupon_value){

                    $query_all = main_service_m::query();
                    $query_all->where(['is_deleted'=> 0,'main_services_id' => $coupon_value->main_services_id]);
                    $check_services = $query_all->first();
                    //dd($check_services->getQueryLog());
    
                    $data['coupon_id'] = $coupon_value->coupon_id;
                    $data['main_services_id'] = $coupon_value->main_services_id;
                    $data['main_services_title'] = $check_services->main_services_title;
                    $data['coupon_price'] = ($coupon_value->coupon_price == null) ? '' : $coupon_value->coupon_price;
                    $data['coupon_title'] = ($coupon_value->coupon_title == null) ? '' : $coupon_value->coupon_title;
                    $data['coupon_description'] = ($coupon_value->coupon_description == null) ? '' : $coupon_value->coupon_description;
                    
                    $cp_validity = ($coupon_value->coupon_validity_time == null) ? '' : $coupon_value->coupon_validity_time / 30 ;
                    // $cp_validity = null;
                    if($cp_validity <= 1){
                        $data['coupon_validity_time'] = $cp_validity .' ' .'month';;
                    }else{
                        $data['coupon_validity_time'] = $cp_validity .' ' .'months';;
                    }
                    $data['coupon_session'] = ($coupon_value->coupon_session == null) ? '' : $coupon_value->coupon_session;
                    
                    $result[] = $data;
                } 
            }
            return response()->json(['status' => true,'statusCode' => 3,'message'=>"Coupon list get successfully.", 'data' => $result], 200);
        }
        else{
            return response()->json(['status' => false,'statusCode' => 1,'message'=>"Data not found."], 200);
        }  
    }

    public function user_purchase_coupon(Request $request)     // my coupon
    {
        $user_id = $request->header('UserId');
        $main_services_id = $request->main_services_id;

        # check user exist or not
        $get_user = user_m::where(['user_id'=>$user_id, 'is_deleted' => 0])->first();
        if(!is_null($get_user)){

            $purchase_coupon_data = coupon_purchase_m::where(['user_id' => $user_id, 'is_deleted' => 0])->get();
            if(!is_null($purchase_coupon_data)){

                $query_all = main_service_m::query();
                if(!is_null($main_services_id)){
                    $query_all->where('main_services_id', $main_services_id);
                }
                $query_all->where('is_deleted', 0);
                $check_services = $query_all->first();

                if(!is_null($check_services)){
                   
                    $result = [];
                    foreach($purchase_coupon_data as $key_data => $coupon_value){
                        
                        $query = coupon_m::query();
                        if(!is_null($main_services_id)){
                            $query->where('main_services_id', $main_services_id);
                        }
                        $query->where('coupon_id', $coupon_value->coupon_id);
                        $query->where('is_deleted', 0);
                        $check_coupun_services = $query->first();

                        // dd(!is_null($check_coupun_services));
                        if(!is_null($check_coupun_services)){

                            # coupon purchase data
                            $data['coupon_purchase_id'] = $coupon_value->coupon_purchase_id;
                            $data['coupon_id'] = $coupon_value->coupon_id;
                            $data['user_id'] = $coupon_value->user_id;
                            $data['coupon_purchase_date'] = ($coupon_value->coupon_purchase_date == null) ? '' : $coupon_value->coupon_purchase_date;
                            $data['coupon_end_date'] = ($coupon_value->coupon_end_date == null) ? '' : $coupon_value->coupon_end_date;
                            // $data['qr_code'] = ($coupon_value->qr_code == null) ? '' : $coupon_value->qr_code;
                            $data['qr_code_data'] = ['app_name'=> 'salon','purchase_type'=> 'coupon_status','user_unique_id' => $get_user->unique_id, 'purchase_id' =>$coupon_value->coupon_purchase_id];
                            $data['representative_name'] = ($coupon_value->representative_name == null) ? '' : $coupon_value->representative_name;
                            $data['coupon_place_address'] = ($coupon_value->coupon_place_address == null) ? '' : $coupon_value->coupon_place_address;
                            $data['coupon_purchase_lattitude'] = ($coupon_value->coupon_purchase_lattitude == null) ? '' : $coupon_value->coupon_purchase_lattitude;
                            $data['coupon_purchase_longitude'] = ($coupon_value->coupon_purchase_longitude == null) ? '' : $coupon_value->coupon_purchase_longitude;

                            # services data
                            $get_services = main_service_m::where(['main_services_id' => $main_services_id ,'is_deleted' => 0])->first();
                            if(!is_null($get_services)){
                                $data['main_services_id'] = $get_services->main_services_id;
                                $data['main_services_title'] = $get_services->main_services_title;
                            }else{
                                $data['main_services_id'] = '';
                                $data['main_services_title'] = '';
                            }

                            # coupon data 
                            $data['coupon_price'] = ($check_coupun_services->coupon_price == null) ? '' : $check_coupun_services->coupon_price;
                            $data['coupon_title'] = ($check_coupun_services->coupon_title == null) ? '' : $check_coupun_services->coupon_title;
                           
                            # coupon redeem data
                            $get_redeem_coupon = coupon_redeem_m::where(
                                        ['coupon_id' => $coupon_value->coupon_id,
                                        'user_id' => $user_id,
                                        'purchase_coupon_id' => $coupon_value->coupon_purchase_id]
                                        );
                    
                            $get_session = $get_redeem_coupon->count();
                            $get_session_data = $get_redeem_coupon->orderBy('coupon_redeem_id','DESC')->first();

                            // dd($check_coupun_services->coupon_session);
                           $data['remaining_coupon_session'] = $check_coupun_services->coupon_session - $get_session;
                                                   
                            if(!is_null($get_session_data)){
                                $data['coupon_redeem_id'] = ($get_session_data->coupon_redeem_id == null) ? '' : $get_session_data->coupon_redeem_id;
                                $data['coupon_redeem_last_date'] = ($get_session_data->redeem_last_date == null) ? '' : $get_session_data->redeem_last_date;
                                $data['coupon_redeem_place'] = ($get_session_data->redeem_last_time_place == null) ? '' : $get_session_data->redeem_last_time_place;
                            }else{
                                $data['coupon_redeem_id'] = '';
                                $data['coupon_redeem_last_date'] = '';
                                $data['coupon_redeem_place'] = '' ;
                            }

                            $result[] = $data;
                            // return response()->json(['status' => true,'statusCode' => 3,'message'=>"Data get successfully.", 'data' => $result ], 200);
                        }
                    }
                    return response()->json(['status' => true,'statusCode' => 3,'message'=>"Data get successfully.", 'data' => $result ], 200);
                }else{
                    return response()->json(['status' => false,'statusCode' => 1,'message'=>"Service not found."], 200);
                } 
            }else{
                return response()->json(['status' => false,'statusCode' => 1,'message'=>"No any coupon purchased."], 200);
            }
        }else{
            return response()->json(['status' => false,'statusCode' => 1,'message'=>'User Not Exist.'], 200);
        }
    }

    public function change_profile_pic(Request $request)
    {
        $user_id = $request->header('UserId');
        $profile_pic = $request->profile_pic;

        // if($profile_pic == ""){
        //     return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please chooes profile.'], 200);
        //  }
      
        $user_exist = user_m::where(['user_id'=>$user_id, 'is_deleted' => 0])->first();

        # check user exist or not
        if(!is_null($user_exist)){

            # change profile picture
            $update_data = [];
            if($request->hasFile('profile_pic')){
                if($user_exist->profile_pic != ""){
                   
                    $path = 'public/storage/image/user_profiles/';
                    $image_name = $path.$user_exist->profile_pic;

                    if (file_exists($image_name)) {
                            @unlink($image_name);
                    }
                    
                }
                $newImageName = 'user_profiles_'.time().'_'.str_random(10).'.'.$request->profile_pic->getClientOriginalExtension();
                $request->profile_pic->storeAs('public/image/user_profiles/',$newImageName);

                $update_data['profile_pic'] = $newImageName;
            }
        

            $update = user_m::where(['user_id' => $user_id, 'is_deleted' => 0])->update($update_data);
            if($update == 1){

                $get_user_detail = user_m::where(['user_id' => $user_id, 'is_deleted' => 0])->first();
                if(!is_null($get_user_detail)){
                    
                    # get user data 
                    $data = $this->getUserData($get_user_detail->user_id);

                    return response()->json(['status' => true,'statusCode' => 3,'message'=>"Profile updated Successfully.", 'data' => $data], 200);  
                }else{
                    return response()->json(['status' => false,'statusCode' => 4,'message'=>'Data not Found'], 200);
                }
            }else{
                return response()->json(['status' => false,'statusCode' => 5,'message'=>"Oops!Something wents Wrong."], 200);
            }
        }else{
           return response()->json(['status' => false,'statusCode' => 1,'message'=>'User Not Exist.'], 200);
        }
    }

    public function get_membership(Request $request)
    {
        $user_id = $request->header('UserId');
        $get_membership = membership_m::where('is_deleted',0)->get();

        $get_user = user_m::where(['user_id'=>$user_id, 'is_deleted' => 0])->first();

        # check user phone number exsit or not 
        if(!is_null($get_user)){

            if($get_membership->count() >0){
                $result = [];
                foreach($get_membership as $key_data => $get_membership_value){
    
                    $data['membership_id'] = $get_membership_value->membership_id;
                    $data['membership_price'] = ($get_membership_value->membership_price == null) ? '' : $get_membership_value->membership_price;
                    $data['membership_title'] = ($get_membership_value->membership_title == null) ? '' : $get_membership_value->membership_title;
                    $data['membership_description'] = ($get_membership_value->membership_description == null) ? '' : $get_membership_value->membership_description;
                    $mb_time_period = $get_membership_value->member_time_period / 30;
                    if($mb_time_period <= 1){
                        $data['member_time_period'] = $mb_time_period .' ' .'month';
                    }else{
                        $data['member_time_period'] = $mb_time_period .' ' .'months';
                    }
                    
                    # check membership buy or not
                    $buy_membership = purchase_membership_m::
                                    where(['membership_id' => $get_membership_value->membership_id,'user_id' => $user_id])->first();

                    $data['buy_membership_status'] = ($buy_membership == null) ? 'is_not_purchase' : 'is_purchase';

                    if(!is_null($buy_membership)){
                        $data['membership_purchase_id'] = $buy_membership->membership_purchase_id;
                        $data['qr_code_data'] = ['app_name'=> 'salon', 'purchase_type'=> 'membership_status','user_unique_id' => $get_user->unique_id, 'purchase_id' =>$buy_membership->membership_purchase_id];
                        // $data['qr_code'] = ($buy_membership->qr_code == null) ? '' : $buy_membership->qr_code;
                        $data['membership_buy_date'] = ($buy_membership->membership_buy_date == null) ? '' : $buy_membership->membership_buy_date;
                        $data['membership_end_date'] = ($buy_membership->membership_end_date == null) ? '' : $buy_membership->membership_end_date;
                        $data['membership_purchase_place'] = ($buy_membership->membership_purchase_place == null) ? '' : $buy_membership->membership_purchase_place;
                        $data['membership_purchase_lattitude'] = ($buy_membership->membership_purchase_lattitude == null) ? '' : $buy_membership->membership_purchase_lattitude;
                        $data['membership_purchase_longitude'] = ($buy_membership->membership_purchase_longitude == null) ? '' : $buy_membership->membership_purchase_longitude;
                        $data['representative_name'] = ($buy_membership->representative_name == null) ? '' : $buy_membership->representative_name;
                    }else{
                        $data['membership_purchase_id'] = '';
                        // $data['qr_code'] =  '' ;
                        $data['qr_code_data'] = '' ;
                        $data['membership_buy_date'] = '' ;
                        $data['membership_end_date'] = '' ;
                        $data['membership_purchase_place'] = '' ;
                        $data['membership_purchase_lattitude'] = '' ;
                        $data['membership_purchase_longitude'] = '' ;
                        $data['representative_name'] = '' ;
                    }
                   
                    $result[] = $data;
                }
                return response()->json(['status' => true,'statusCode' => 3,'message'=>"Main services get successfully.", 'data' => $result], 200);
            }else{
                return response()->json(['status' => false,'statusCode' => 2,'message'=>"Data not found."], 200);
            } 
        }else{
            return response()->json(['status' => false,'statusCode' => 1,'message'=>'User not found.'], 200);
        } 
    }

    public function membership_history(Request $request)
    {
        // $user_id = $request->header('UserId');
        $membership_id = $request->membership_id;
        $user_id = $request->user_id;

        if($user_id == ""){
            return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please enter user id.'], 200);
        }
        if($membership_id == ""){
            return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please enter membership id.'], 200);
        }
      
        # check user phone number exsit or not 
        $get_user = user_m::where(['user_id'=>$user_id, 'is_deleted' => 0])->first();
        if(!is_null($get_user)){

            $buy_membership = purchase_membership_m::
                            where(['membership_id' => $membership_id,'user_id' => $user_id])->first();
            
            if(!is_null($buy_membership)){

                $data['user_id'] = $get_user->user_id;
                $data['membership_id'] = $buy_membership->membership_id;
                $data['membership_purchase_id'] = $buy_membership->membership_purchase_id;
                // $data['total_session'] = ($buy_membership->total_session == null) ? '' : $buy_membership->total_session;
                $data['membership_buy_date'] = ($buy_membership->membership_buy_date == null) ? '' : $buy_membership->membership_buy_date;
                $data['membership_end_date'] = ($buy_membership->membership_end_date == null) ? '' : $buy_membership->membership_end_date;
                $data['membership_purchase_place'] = ($buy_membership->membership_purchase_place == null) ? '' : $buy_membership->membership_purchase_place;
                $data['membership_purchase_lattitude'] = ($buy_membership->membership_purchase_lattitude == null) ? '' : $buy_membership->membership_purchase_lattitude;
                $data['membership_purchase_longitude'] = ($buy_membership->membership_purchase_longitude == null) ? '' : $buy_membership->membership_purchase_longitude;
        
                return response()->json(['status' => true,'statusCode' => 3,'message'=>"Membership Detail get successfully.", 'data' => $data], 200);
            }else{
                return response()->json(['status' => false,'statusCode' => 1,'message'=>"Membership not purchased."], 200);
            } 
        }else{
            return response()->json(['status' => false,'statusCode' => 1,'message'=>'User not found.'], 200);
        } 

    }

    public function coupon_history(Request $request)
    {
        // $login_user_id = $request->header('UserId');
        $user_id = $request->user_id;
        $coupon_purchase_id = $request->coupon_purchase_id;

        if($user_id == ""){
            return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please enter user id.'], 200);
        }
        if($coupon_purchase_id == ""){
            return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please enter coupon purchase id.'], 200);
        }

        # check user exsit or not 
        $get_user = user_m::where(['user_id'=>$user_id, 'is_deleted' => 0])->first();
        if(!is_null($get_user)){

            $buy_coupon = coupon_purchase_m::
                        where(['coupon_purchase_id' => $coupon_purchase_id,'user_id' => $user_id])->first();
            
            if(!is_null($buy_coupon)){

                $data['user_id'] = $get_user->user_id;
                $data['coupon_id'] = $buy_coupon->coupon_id;
                $data['coupon_purchase_id'] = $buy_coupon->coupon_purchase_id;

                # coupon redeem data
                $get_redeem_coupon = coupon_redeem_m::where(
                                    ['coupon_id' => $buy_coupon->coupon_id,
                                    'user_id' => $user_id,
                                    'purchase_coupon_id' => $buy_coupon->coupon_purchase_id]);

                $get_reedem_session_data = $get_redeem_coupon->orderBy('coupon_redeem_id','DESC')->get();
                // $get_session = $get_redeem_coupon->count();
                
                $result = []; 
                foreach($get_reedem_session_data as $get_session_data){
                    
                    if(!is_null($get_session_data)){
                        $data['coupon_redeem_id'] = ($get_session_data->coupon_redeem_id == null) ? '' : $get_session_data->coupon_redeem_id;
                        $data['redeem_last_date'] = ($get_session_data->redeem_last_date == null) ? '' : $get_session_data->redeem_last_date;
                        $data['redeem_last_time_place'] = ($get_session_data->redeem_last_time_place == null) ? '' : $get_session_data->redeem_last_time_place;
                        $data['redeem_place_lattitude'] = ($get_session_data->redeem_place_lattitude == null) ? '' : $get_session_data->redeem_place_lattitude;
                        $data['redeem_place_longitude'] = ($get_session_data->redeem_place_longitude == null) ? '' : $get_session_data->redeem_place_longitude;
                    }else{

                        $data['coupon_redeem_id'] = '';
                        $data['redeem_last_date'] = '';
                        $data['redeem_last_time_place'] = '';
                        $data['redeem_place_lattitude'] = '';
                        $data['redeem_place_longitude'] = '';
                    }
                    $result[] = $data;
                }

                return response()->json(['status' => true,'statusCode' => 3,'message'=>"Coupon Detail get successfully.", 'data' => $result], 200);
            }else{
                return response()->json(['status' => false,'statusCode' => 1,'message'=>"Coupon not purchased."], 200);
            } 
        }else{
            return response()->json(['status' => false,'statusCode' => 1,'message'=>'User not found.'], 200);
        } 
    }
   
    public function buy_service(Request $request)
    {
        $representative_name = $transaction_id = $total_session = '';
        $purchase_place = $purchase_lattitude = $purchase_longitude = '';

        $user_id = $request->header('UserId');
        $purchase_type_id = $request->purchase_type_id;
    	$purchase_type	 = $request->purchase_type;
    	$purchase_image	 = $request->purchase_image;
    	$payment_type	 = $request->payment_type;
    	$purchase_place	 = $request->purchase_place;
    	$purchase_lattitude	 = $request->purchase_lattitude;
    	$purchase_longitude	 = $request->purchase_longitude;
    	$payment_amount	 = $request->payment_amount;
    	$purchase_date	 = $request->purchase_date;
    	$total_session	 = $request->total_session;
    	$transaction_id	 = $request->transaction_id;
    	$representative_name	 = $request->representative_name;

        $payment_status = "false";
        if(!is_null($transaction_id)){
            $payment_status = "true";
        }

        if($purchase_type_id == "" ){
            return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please enter service purchase id or membership purchase id or coupon purchase id.'], 200);
            }
            if($purchase_type == "" ){
                return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please enter purchase type.'], 200);
            }
            if($purchase_image == ""){
            return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please chooes buy image.'], 200);
            }
            if($payment_type == "" ){
                return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please enter payment type.'], 200);
            }
            // if($purchase_place == "" ){
            //     return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please enter buy place.'], 200);
            // }
            // if($purchase_lattitude == "" ){
            //     return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please enter buy place lattitude.'], 200);
            // }
            // if($purchase_longitude == "" ){
            //     return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please enter buy place longitude.'], 200);
            // }
            if($payment_amount == "" ){
                return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please enter pay amount.'], 200);
            }
            if($purchase_date == "" ){
                return response()->json(['status' => false,'statusCode' => 0,'message'=>'Please enter buy date.'], 200);
        }

        $get_user = user_m::where(['user_id'=>$user_id, 'is_deleted' => 0])->first();

        # check user exist or not
        if(!is_null($get_user)){

            if($purchase_type == "coupon_status"){
                        
                $coupun_data = coupon_m::where(['coupon_id' => $purchase_type_id,'is_deleted'=>0])->first();
                if(!is_null($coupun_data)){

                    $coupon_validity_time = (int)$coupun_data->coupon_validity_time . 'days';
                    $end_date =  date('Y-m-d H:i:s', strtotime($purchase_date. $coupon_validity_time));
                    
                    $newImageName = '';
                    if($request->hasFile('purchase_image')){
                        $newImageName = 'coupon_purchase_'.time().'_'.str_random(10).'.'.$request->purchase_image->getClientOriginalExtension();
                        $request->purchase_image->storeAs('public/image/coupon_purchase/',$newImageName);
                    }
                    
                    $insert_data = [
                        'user_id' => $user_id,
                        'coupon_id' => $purchase_type_id,
                        'user_coupon_image' => $newImageName,
                        'payment_type' => $payment_type,
                        'representative_name' => $representative_name,
                        'total_session' => $total_session,
                        'payment_amount' => $payment_amount,
                        'coupon_purchase_date' => $purchase_date,
                        'coupon_end_date' => $end_date,
                        'transaction_id'=> $transaction_id,
                        'payment_status'=> $payment_status,
                        'coupon_place_address' => $purchase_place,
                        'coupon_purchase_lattitude'  => $purchase_lattitude,                  
                        'coupon_purchase_longitude'  => $purchase_longitude,    
                    ];

                    $purchase_coupon = coupon_purchase_m::create($insert_data);
                    if(!is_null($purchase_coupon)){

                        # user data
                        $data['user_id'] = $get_user->user_id;
                        $data['user_name'] = ($get_user->first_name == null) ? '' : $get_user->first_name . $get_user->last_name;
                        $data['purchase_type'] = 'coupon_status';
                    
                        # coupon purchase data
                        $data['purchase_id'] = $purchase_coupon->coupon_purchase_id;     // coupon purchase  id
                        $data['purchase_date'] = ($purchase_coupon->coupon_purchase_date == null) ? '' : $purchase_coupon->coupon_purchase_date;
                        $data['purchase_end_date'] = ($purchase_coupon->coupon_end_date == null) ? '' : $purchase_coupon->coupon_end_date;
                        $data['purchase_place'] = ($purchase_coupon->coupon_place_address == null) ? '' : $purchase_coupon->coupon_place_address;
                        $data['purchase_lattitude'] = ($purchase_coupon->coupon_purchase_lattitude == null) ? '' : $purchase_coupon->coupon_purchase_lattitude;
                        $data['purchase_longitude'] = ($purchase_coupon->coupon_purchase_longitude == null) ? '' : $purchase_coupon->coupon_purchase_longitude;
                        $data['representative_name'] = ($purchase_coupon->representative_name == null) ? '' : $purchase_coupon->representative_name;
                        $data['payment_type'] = ($purchase_coupon->payment_type == null) ? '' : $purchase_coupon->payment_type;
                        $data['transaction_id'] = ($purchase_coupon->transaction_id == null) ? '' : $purchase_coupon->transaction_id;
                        $data['payment_amount'] = ($purchase_coupon->payment_amount == null) ? '' : $purchase_coupon->payment_amount;
                        $data['total_session'] = ($purchase_coupon->total_session == null) ? '' : $purchase_coupon->total_session;
                        $data['payment_status'] = ($purchase_coupon->payment_status == null) ? '' : $purchase_coupon->payment_status;

                        if($purchase_coupon->user_coupon_image != null){
                            $data['purchase_image'] = asset('public/storage/image/coupon_purchase')."/".$purchase_coupon->user_coupon_image;
                        }else{
                            $data['purchase_image'] = "";
                        }

                        # coupon data
                        $data['purchase_title'] = ($coupun_data->coupon_title == null) ? '' : $coupun_data->coupon_title;
                   
                        return response()->json(['status' => true,'statusCode' => 3,'message'=>"Coupon purchase successfully.", 'data' => $data ], 200);
                    }else{
                        return response()->json(['status' => false,'statusCode' => 2,'message'=>'Oops!Something wents Wrong.'], 200);
                    }
                }else{
                    return response()->json(['status' => false,'statusCode' => 1,'message'=>'Coupon not found.'], 200);
                }
            }elseif($purchase_type == "membership_status"){
                      
                $membership_data = membership_m::where(['membership_id' => $purchase_type_id,'is_deleted'=>0])->first();
                if(!is_null($membership_data)){
                    $member_time_period = (int)$membership_data->member_time_period . 'days';
                    $end_date =  date('Y-m-d H:i:s', strtotime($purchase_date. $member_time_period));
                    // $date = date_create($purchase_date);
                    // $a = date_add($date, date_interval_create_from_date_string($member_time_period));
                    // echo date_format($a, "Y-m-d H:i:s");die;

                    $newImageName = '';
                    if($request->hasFile('purchase_image')){
                        $newImageName = 'membership_purchase_'.time().'_'.str_random(10).'.'.$request->purchase_image->getClientOriginalExtension();
                        $request->purchase_image->storeAs('public/image/membership_purchase/',$newImageName);
                    }
                  
                    $insert_data = [
                        'user_id' => $user_id,
                        'membership_id' => $purchase_type_id,
                        'membership_purchase_image' => $newImageName,
                        'payment_type' => $payment_type,
                        'representative_name' => $representative_name,
                        'payment_amount' => $payment_amount,
                        'total_session' => $total_session,
                        'membership_buy_date' => $purchase_date,
                        'membership_end_date' => $end_date,
                        'transaction_id'=> $transaction_id,
                        'payment_status'=> $payment_status,
                        'membership_purchase_place' => $purchase_place,
                        'membership_purchase_lattitude'  => $purchase_lattitude,                  
                        'membership_purchase_longitude'  => $purchase_longitude,    
                    ];

                    $purchase_membership = purchase_membership_m::create($insert_data);
                    if(!is_null($purchase_membership)){

                        # user data
                        $data['user_id'] = $get_user->user_id;
                        $data['user_name'] = ($get_user->first_name == null) ? '' : $get_user->first_name . $get_user->last_name;
                        $data['purchase_type'] = 'membership_status';

                        # membership purchase data
                        $data['purchase_id'] = $purchase_membership->membership_purchase_id;   // purchase id
                        $data['purchase_date'] = ($purchase_membership->membership_buy_date == null) ? '' : $purchase_membership->membership_buy_date;
                        $data['purchase_end_date'] = ($purchase_membership->membership_end_date == null) ? '' : $purchase_membership->membership_end_date;
                        $data['purchase_place'] = ($purchase_membership->membership_purchase_place == null) ? '' : $purchase_membership->membership_purchase_place;
                        $data['purchase_lattitude'] = ($purchase_membership->membership_purchase_lattitude == null) ? '' : $purchase_membership->membership_purchase_lattitude;
                        $data['purchase_longitude'] = ($purchase_membership->membership_purchase_longitude == null) ? '' : $purchase_membership->membership_purchase_longitude;
                        $data['representative_name'] = ($purchase_membership->representative_name == null) ? '' : $purchase_membership->representative_name;
                        $data['payment_type'] = ($purchase_membership->payment_type == null) ? '' : $purchase_membership->payment_type;
                        $data['transaction_id'] = ($purchase_membership->transaction_id == null) ? '' : $purchase_membership->transaction_id;
                        $data['payment_amount'] = ($purchase_membership->payment_amount == null) ? '' : $purchase_membership->payment_amount;
                        $data['total_session'] = ($purchase_membership->total_session == null) ? '' : $purchase_membership->total_session;
                        $data['payment_status'] = ($purchase_membership->payment_status == null) ? '' : $purchase_membership->payment_status;

                        if($purchase_membership->membership_purchase_image != null){
                            $data['purchase_image'] = asset('public/storage/image/membership_purchase')."/".$purchase_membership->membership_purchase_image;
                        }else{
                            $data['purchase_image'] = "";
                        }

                        # membership data
                        $data['purchase_title'] = ($membership_data->membership_title == null) ? '' : $membership_data->membership_title;

                        return response()->json(['status' => true,'statusCode' => 3,'message'=>"Membership purchase successfully.", 'data' => $data ], 200);
                    }else{
                        return response()->json(['status' => false,'statusCode' => 2,'message'=>'Oops!Something wents Wrong.'], 200);
                    }
                }else{
                    return response()->json(['status' => false,'statusCode' => 1,'message'=>'Membership not found.'], 200);
                }
            }elseif($purchase_type == "sub_service_status"){
                
                $sub_service_data = sub_service_m::where(['sub_service_id' => $purchase_type_id,'is_deleted'=> 0])->first();
                if(!is_null($sub_service_data)){

                    $newImageName = '';
                    if($request->hasFile('purchase_image')){
                        $newImageName = 'service_purchase_'.time().'_'.str_random(10).'.'.$request->purchase_image->getClientOriginalExtension();
                        $request->purchase_image->storeAs('public/image/service_purchase/',$newImageName);
                    }
                    
                    $end_date = date('Y-m-d H:i:s', strtotime('+1 month', strtotime($purchase_date)));
                    $insert_data = [
                        'user_id' => $user_id,
                        'sub_services_id' => $purchase_type_id,
                        'buy_services_image' => $newImageName,
                        'payment_type' => $payment_type,
                        'representative_name' => $representative_name,
                        'payment_amount' => $payment_amount,
                        'buy_service_date' => $purchase_date,
                        'buy_service_end_date' => $end_date,
                        'transaction_id'=> $transaction_id,
                        'payment_status'=> $payment_status,
                        'buy_service_place' => $purchase_place,
                        'buy_service_lattitude'  => $purchase_lattitude,                  
                        'buy_service_longitude'  => $purchase_longitude,    
                    ];

                    $buy_services = buy_service_m::create($insert_data);
                    if(!is_null($buy_services)){

                        # user data
                        $data['user_id'] = $get_user->user_id;
                        $data['user_name'] = ($get_user->first_name == null) ? '' : $get_user->first_name . $get_user->last_name;
                        $data['purchase_type'] = 'sub_service_status';
                        
                        # services purchase data
                        $data['purchase_id'] = $buy_services->buy_services_id;   // purchase id
                        $data['purchase_date'] = ($buy_services->buy_service_date == null) ? '' : $buy_services->buy_service_date;
                        $data['purchase_end_date'] = ($buy_services->buy_service_end_date == null) ? '' : $buy_services->buy_service_end_date;
                        $data['purchase_place'] = ($buy_services->buy_service_place == null) ? '' : $buy_services->buy_service_place;
                        $data['purchase_lattitude'] = ($buy_services->buy_service_lattitude == null) ? '' : $buy_services->buy_service_lattitude;
                        $data['purchase_longitude'] = ($buy_services->buy_service_longitude == null) ? '' : $buy_services->buy_service_longitude;
                        $data['representative_name'] = ($buy_services->representative_name == null) ? '' : $buy_services->representative_name;
                        $data['payment_type'] = ($buy_services->payment_type == null) ? '' : $buy_services->payment_type;
                        $data['transaction_id'] = ($buy_services->transaction_id == null) ? '' : $buy_services->transaction_id;
                        $data['payment_amount'] = ($buy_services->payment_amount == null) ? '' : $buy_services->payment_amount;
                        $data['payment_status'] = ($buy_services->payment_status == null) ? '' : $buy_services->payment_status;
                        $data['total_session'] = '';
                    
                        if($buy_services->buy_services_image != null){
                            $data['purchase_image'] = asset('public/storage/image/service_purchase')."/".$buy_services->buy_services_image;
                        }else{
                            $data['purchase_image'] = "";
                        }

                        # sub service data
                        $data['purchase_title'] = $sub_service_data->sub_service_title == null ? '' : $sub_service_data->sub_service_title ; 
                        
                        return response()->json(['status' => true,'statusCode' => 3,'message'=>"Services buy successfully.", 'data' => $data ], 200);
                    }else{
                        return response()->json(['status' => false,'statusCode' => 2,'message'=>'Oops!Something wents Wrong.'], 200);
                    }
                }else{
                    return response()->json(['status' => false,'statusCode' => 2,'message'=>'Sub service not found.'], 200);
                }
            }else{
                return response()->json(['status' => false,'statusCode' => 2,'message'=>'Data not found.'], 200);
            }
        }else{
            return response()->json(['status' => false,'statusCode' => 1,'message'=>'User Not Exist.'], 200);
        }
    }

    public function view_notification(Request $request)
    {
        $user_id = $request->header('UserId');
        $get_user = user_m::where(['user_id'=>$user_id, 'is_deleted' => 0])->first();

         # check user exist or not
        if(!is_null($get_user)){

            $query = notification_m::query();
            $query->where(['user_id' => $user_id,'is_deleted' => 0]);
            $get_notification = $query->orderBy('notification_id','DESC')->get();
            
            if(!is_null($get_notification)){

                $get_user = user_m::where(['user_id'=>$user_id, 'is_deleted' => 0])->update(['badge_notification'=>0]);

                $result = [];
                foreach($get_notification as $key_data => $notification_value){
                
                    $data['user_id'] = $notification_value->user_id;
                    $data['notification_id'] = $notification_value->notification_id;
                    $data['notification_type'] = $notification_value->notification_type;
                    $data['notification_message'] = $notification_value->notification_message;
                    $data['notification_date'] = $notification_value->notification_date;

                    $result[] = $data;
                }
                return response()->json(['status' => false,'statusCode' => 3,'message'=>'Notification get Successfully.' ,'data' => $result], 200);
            }else{
                return response()->json(['status' => false,'statusCode' => 2,'message'=>'No any notification.'], 200);
            }
        }else{
            return response()->json(['status' => false,'statusCode' => 1,'message'=>'User Not Exist.'], 200);
        }
    }
    
    # get user data 
    public function getUserData($id)
	{
		$get_user_detail = user_m::find($id);

        $data['Token'] = $get_user_detail->unique_token;
        $data['user_id'] = $get_user_detail->user_id;
        $data['first_name'] = ($get_user_detail->first_name == null) ? '' : $get_user_detail->first_name;
        $data['last_name'] = ($get_user_detail->last_name == null) ? '' : $get_user_detail->last_name;
        $data['phone_no'] = ($get_user_detail->phone_no == null) ? '' : $get_user_detail->phone_no;
        $data['email_id'] = ($get_user_detail->email_id == null) ? '' : $get_user_detail->email_id;
       
        if($get_user_detail->profile_pic != null){
            // $url = asset('public/storage/image/user_profiles')."/".$get_user_detail->profile_pic;
            // $path = Storage::disk('public')->exists('/image/user_profiles/' .$get_user_detail->profile_pic);
            if(filter_var($get_user_detail->profile_pic, FILTER_VALIDATE_URL) !== false)
            {
                $data['profile_pic'] = $get_user_detail->profile_pic;
            }else{
                $data['profile_pic'] = asset('public/storage/image/user_profiles')."/".$get_user_detail->profile_pic;
            }
        }else{
            $data['profile_pic'] = "";
        }
        // $data['profile_pic'] = ($get_user_detail->profile_pic == null) ? '' : $get_user_detail->profile_pic;
        $data['unique_id'] = ($get_user_detail->unique_id == null) ? '' : $get_user_detail->unique_id;
        $data['referral_code'] = ($get_user_detail->referral_code == null) ? '' : $get_user_detail->referral_code;
        $data['user_lattitude'] = ($get_user_detail->user_lattitude == null) ? '' : $get_user_detail->user_lattitude;
        $data['user_longitude'] = ($get_user_detail->user_longitude == null) ? '' : $get_user_detail->user_longitude;
        $data['login_type'] = ($get_user_detail->login_type == null) ? '' : $get_user_detail->login_type;
        $data['social_id'] = ($get_user_detail->social_id == null) ? '' : $get_user_detail->social_id;
        $data['device_type'] = ($get_user_detail->device_type == null) ? '' : $get_user_detail->device_type;
        $data['device_token'] = ($get_user_detail->device_token == null) ? '' : $get_user_detail->device_token;
			
		// $getuserID = User::where('user_id', '=', $get_user_detail->user_id)->first();
		return $data;
	}

    # generate user unique id
    public function getUniqueId()
    {
        $stamp = time();
        $secure_key = '';
        for ($i = 0; $i < strlen($stamp); $i++) {
            $key =  substr($stamp, $i, 1);
            $secure_key .= (rand(0, 1) == 0 ? $key : (rand(0, 1) == 1 ? strtoupper($key) : rand(0, 9)));
        }
        return  $secure_key ;
    }

    # gerate referral code
    public function getReferralCode()
    {
        $string = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $stamp = time();
        $secure_key = $post = '';
      
        for ($p = 0; $p <= 10; $p++) {
            $post .= substr($string, rand(0, strlen($string) - 1), 1);
        }
        return $post;
    }

    # generate user token
    public function getSecureKey() {
        $string = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $stamp = time();
        $secure_key = $pre = $post = '';
        for ($p = 0; $p <= 10; $p++) {
            $pre .= substr($string, rand(0, strlen($string) - 1), 1);
        }

        for ($i = 0; $i < strlen($stamp); $i++) {
            $key = substr($string, substr($stamp, $i, 1), 1);
            $secure_key .= (rand(0, 1) == 0 ? $key : (rand(0, 1) == 1 ? strtoupper($key) : rand(0, 9)));
        }

        for ($p = 0; $p <= 10; $p++) {
            $post .= substr($string, rand(0, strlen($string) - 1), 1);
        }
        return $pre . '-' . $secure_key . $post;
    }
}
