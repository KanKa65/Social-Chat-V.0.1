<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include "baglanti.php";
mysqli_set_charset($link,'utf8mb4'); 
define('GOOGLE_API_KEY', 'AIzaSyBuPCnx9dZ_mWPexbddkITmcWkWsyj7OZX4c');//GCM  Key


require_once 'include/db_handler.php';
require 'libs/Slim/Slim.php';
require_once 'libs/gcm/gcm.php';
require_once 'libs/gcm/push.php';
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();



if(isset($_POST['txtMessage']) &&  isset($_POST['txtresim_no']) && isset($_POST['txteposta']) && isset($_POST['txtDil']) && isset($_POST['txtresimyol']) && isset($_POST['txtresimbaslik']) )
{
	
	     $resim_no=$_POST['txtresim_no'];
		 $gonderen=$_POST['txteposta'];
		 $dil=$_POST['txtDil'];
		  $resimyol=$_POST['txtresimyol'];
		 $resimbaslik=$_POST['txtresimbaslik'];

	 $oy="0";
	 $yorum=$_POST['txtMessage'];

		  $fromUser= getUser($gonderen);

	  




	  $sorgu="insert into olaylar (resim_no,kullanici,oy,yorum,dil) VALUES ('$resim_no','$gonderen','$oy','$yorum','$dil') ";
	
	 $sonuc=mysqli_query($link, $sorgu);
	if ($sonuc > 0 )
	{
				 mysqli_query($link,"
    UPDATE resimler 
    SET yorumsayisi = yorumsayisi + 1
    WHERE resim_no = '$resim_no'
");


 $sorgu2="select  kullanicilar.gcm_registration_id,kullanicilar.eposta  from resimler JOIN kullanicilar ON kullanicilar.eposta=resimler.kullanici where resim_no='$resim_no'";
  $sonuc2=mysqli_query($link,$sorgu2);
 $veriler2=mysqli_fetch_assoc($sonuc2);
          $result = array();
	  $registration_ids = array();

	 $toUser['gcm_registration_id']=$veriler2['gcm_registration_id'];
	  	  array_push($registration_ids, $toUser['gcm_registration_id']);
	 	
		$resimsahibi=$veriler2['eposta'];

	     $msg = array();
    $msg['message'] = $yorum;
    $msg['resimno'] = $resim_no;
     $msg['resimyol'] = $resimyol;
	 $msq['resimsahibi']=$resimsahibi;

	  $data = array();
    $data['user'] = $fromUser;
    $data['message'] = $msg;
    //$data['imageUrl'] = $path;
$push = new Push();
    $push->setTitle("Yorum");
    $push->setIsBackground(FALSE);
    $push->setFlag(1);
    $push->setData($data);
    $push->setImageUrl("0");
	$push->setGrup("3");
	$push->setSahibi("0");   
	
	  
	  
	 
	
	  $sorgu="insert into olaylar (resim_no,kullanici,oy,yorum,dil,okundumu) VALUES ('$resim_no','$gonderen','$oy','$yorum','$dil','0') ";
	$bildirimcilerSql="select DISTINCT kullanici from olaylar where resim_no='$resim_no' and yorum!='' and sessiz='0' ";
		$bildirimcilerSqls=mysqli_query($link,$bildirimcilerSql);
	
	 
	 while ($veriler=mysqli_fetch_array($bildirimcilerSqls))
	 {
	     $sonuc3=mysqli_query($link,"SELECT kayit_no as user_id, adi as name, eposta, gcm_registration_id,tarih as created_at FROM kullanicilar   WHERE eposta = '".$veriler['kullanici']."'");
	
       $result = mysqli_fetch_assoc($sonuc3);
          if($result['eposta']!=$gonderen){
	  $sqlinsert="insert into bildirimler (eposta,gondereneposta,adi,foto,mesaj,resim_no,resimyol,grup)
 VALUES ('".$result['eposta']."','$gonderen','".$fromUser["name"]."','".$fromUser["foto"]."','Yorum yapıldı','$resim_no','$resimyol','3')";
	
	mysqli_query($link,$sqlinsert);
	  	  array_push($registration_ids, $result['gcm_registration_id']);

	  }  
		
	 }
		  sendPushNotification2( $registration_ids,  $push->getPush());

	
	 
		echo "success_login";
		exit;
	}else {
		
		echo "Wrong Username and Password";
	    exit;
	}
	
}	
	  function getUser($gonderen) {
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
  

	 
	
	

	

?>
