<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use DB;
use Auth;
use Mail;

use App\Users;

class Posts extends Model
{
    //
    protected $table = "posts";
    protected $fillable = [
        "message", "created_at", "updated_at", "users_id", "is_posted"
    ];

    public static function getPendingRequests(){
    	return $query = DB::connection('mysql')
    	->table('posts as a')
    	->select(
    		'a.message as message',
    		'a.created_at as created_at',
    		// DB::raw("DATE_FORMAT('a.created_at', '%m-%d-%y') as created_at"),
    		'b.fullname as fullname',
    		'a.id as id'
    	)
    	->join('users as b', 'a.users_id', '=', 'b.id')
    	->where('a.is_posted', 0)
    	->get();
    }

    public static function sendAnnouncement($data){

    	$user = Auth::User()->id;
    	$saveAnnouncement = DB::connection("mysql")
    	->table('posts')
    	->insert([
    		"message" => $data->announcement,
    		"created_at" => DB::raw("NOW()"),
    		"updated_at" => DB::raw("NOW()"),
    		"users_id" => $user,
    		"is_posted" => 1
    	]);

    	$getStudents = Users::where('roles_id', 3)->where('is_validated', 1)->get();

        $dataMessage = $data->announcement;
    	foreach($getStudents as $out){
    		// return $out['email'];
            $emailTo = $out['email'];
            $name = $out['fullname'];
            $bodyMessage = $dataMessage;

            $data = [
                "name" => $name,
                "bodyMessage" => $bodyMessage
            ];

            Mail::send('sendannouncementemail', $data, function($message) use ($name, $emailTo) {
                $message->to($emailTo, $name)->subject("Announcement");
                $message->from("rhianjane16@gmail.com", "Announcement");
            });
    	}

        return "true";

    }

    public static function requestNotification($data){
        $user = Auth::User()->id;
        return $query = DB::connection('mysql')
        ->table('posts')
        ->insert([
            'message' => $data->message,
            'users_id' => $user,
            'is_posted' => 0,
            'created_at' => DB::raw("NOW()"),
            'updated_at' => DB::raw("NOW()")
        ]);
    }

    public static function tableStudents(){
        return $posts = DB::connection('mysql')
        ->table('posts as a')
        ->select(
            'a.message as message',
            'b.fullname as postedby'
        )
        ->join('users as b', 'a.users_id', '=', 'b.id')
        ->where('a.is_posted', 1)
        ->get();
    }

    public static function sendNotification($data){
        $query = DB::connection('mysql')
        ->table('posts')
        ->where('id', $data->id)
        ->update([
            'is_posted' => 1,
            'updated_at' => DB::raw("NOW()")
        ]);

        $getMessage = DB::connection('mysql')
        ->table('posts')
        ->where('id', $data->id)
        ->get();

        $getStudents = Users::where('roles_id', 3)->where('is_validated', 1)->get();
        $dataMessage = $getMessage[0]->message;
        foreach($getStudents as $out){
            $emailTo = $out['email'];
            $name = $out['fullname'];
            $bodyMessage = $dataMessage;

            $data = [
                "name" => $name,
                "bodyMessage" => $bodyMessage
            ];

            Mail::send('sendannouncementemail', $data, function($message) use ($name, $emailTo) {
                $message->to($emailTo, $name)->subject("Announcement");
                $message->from("rhianjane16@gmail.com", "Announcement");
            });
        }

        return "true";

    }
}
