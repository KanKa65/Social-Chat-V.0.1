<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include "baglanti.php";
mysqli_set_charset($link,'utf8mb4'); 
define('GOOGLE_API_KEY', 'AIzaSyBuPCnx9dZ_mWPexbkITmcWkWsyj7OZX4c');//GCM Sunucu Key


require_once 'include/db_handler.php';
require 'libs/Slim/Slim.php';
require_once 'libs/gcm/gcm.php';
require_once 'libs/gcm/push.php';
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();



if(  isset($_POST['txtKullanici']) && isset($_POST['txtfriend'])  )
{
	
	
	
	
	$txtKullanici=$_POST['txtKullanici'];
	$txtfriend=$_POST['txtfriend'];
	
	$sorgu2="select  kullanicilar.gcm_registration_id,kullanicilar.eposta  from kullanicilar  where eposta='$txtKullanici'";
  $sonuc2=mysqli_query($link,$sorgu2);
 $veriler2=mysqli_fetch_assoc($sonuc2);
      $toUser['gcm_registration_id']=$veriler2['gcm_registration_id'];

	  $fromUser= getUser($txtfriend,$txtKullanici);
	    $msg = array();
			    $msg = array();
    $msg['message'] = "Arkadaşlık isteği";
    $msg['resimno'] = $resim_no;
     $msg['resimyol'] = $resimyol;
	  $data = array();
    $data['user'] = $fromUser;
    $data['message'] = $msg;
    //$data['imageUrl'] = $path;
$push = new Push();
    $push->setTitle("Arkadas Ekle");
    $push->setIsBackground(FALSE);
    $push->setFlag(1);
    $push->setData($data);
    $push->setImageUrl("0");
	$push->setGrup("4"); //arkadas ekle
	$push->setSahibi("0");
	
    
	
	$sorgu2="Select * from arkadaslar where (kullanici='$txtKullanici' and friend='$txtfriend') OR (kullanici='$txtfriend' and friend='$txtKullanici')";
	$sonuc2=mysqli_query($link,$sorgu2);
	
	if((mysqli_fetch_assoc($sonuc2) < 1) and ($txtKullanici!=$txtfriend))
	{
	
	
	$sonuc=mysqli_query($link,"INSERT INTO chat_rooms (name) values('Kullanici Odası')");
	       $roomid = mysqli_insert_id($link);
	        
 $sql="insert into arkadaslar(kullanici,friend,room_id,onay,dil) values('$txtKullanici','$txtfriend','$roomid','0','tr')";
 $sql2="insert into arkadaslar(kullanici,friend,room_id,onay,dil) values('$txtfriend','$txtKullanici','$roomid','0','tr')";
 
 
 $sorgu2="Select * from bildirimler where eposta='$eposta' and gondereneposta='$gondereneposta' and grup='4' ";
	$sonuc2=mysqli_query($link,$sorgu2);
	
	if((mysqli_fetch_assoc($sonuc2) < 1) )
	{
 $sqlinsert="insert into bildirimler (eposta,gondereneposta,adi,foto,mesaj,resim_no,resimyol,grup)
 VALUES ('$txtKullanici','$txtfriend','".$fromUser["name"]."','".$fromUser["foto"]."','mesaj','$resimno','$resimyol','4')";
	
	mysqli_query($link,$sqlinsert);
	
	}
mysqli_query($link,$sql); mysqli_query($link,$sql2);
 $registration_ids = array();
	 array_push($registration_ids, $toUser['gcm_registration_id']);
	 sendPushNotification2( $registration_ids,  $push->getPush());
	





echo "1";




	}
	
	else{


	echo "0";}
}
	  function getUser($gonderen,$alici) {
       include "baglanti.php";
mysqli_set_charset($link,'utf8mb4'); 
                
	     $sonuc3=mysqli_query($link,"SELECT foto,kayit_no as user_id, adi as name, eposta as email, gcm_registration_id,tarih as created_at FROM kullanicilar   WHERE eposta = '$gonderen'");
	
       $result = mysqli_fetch_assoc($sonuc3);
        
        
            $user = array();
            $user["user_id"] = $result['user_id'];
			$user["foto"] = $result['foto'];

            $user["name"] =$result['name'];
            $user["email"] = $result['email'];
            $user["gcm_registration_id"] = $result['gcm_registration_id'];
            $user["created_at"] = $result['created_at'];
            
          return $user;
       
    }
	
function sendPushNotification2($registration_ids,$mesaj) {
         
//mysqli_set_charset($link,'utf8mb4'); 

		  $url = 'https://android.googleapis.com/gcm/send';
   
        // $mesaj = array("notification_message" => $mesaj); //gönderdiğimiz mesaj POST 'tan alıyoruz.Androidde okurken notification_message değerini kullanacağız
         $fields = array(
             'registration_ids' => $registration_ids,
             'data' => $mesaj,
         );
		
		//Alttaki Authorization: key= kısmına Google Apis kısmında oluşturduğumuz key'i yazacağız
         $headers = array(
             'Authorization: key=".$GOOGLE_API_KEY."', 
             'Content-Type: application/json'
         );
         // Open connection
         $ch = curl_init();
   
         // Set the url, number of POST vars, POST data
         curl_setopt($ch, CURLOPT_URL, $url);
   
         curl_setopt($ch, CURLOPT_POST, true);
         curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
   
         // Disabling SSL Certificate support temporarly
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
   
         curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
   
         // Execute post
         $result = curl_exec($ch);
         if ($result === FALSE) {
             die('Curl failed: ' . curl_error($ch));
         }
   
         // Close connection
         curl_close($ch);
          $result;
  

}