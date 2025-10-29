<?php
//TOKEN QUE NOS DA FACEBOOK
$token = 'EAAKqHiB2Q8gBOyN5ZBxMPlslbx5mig9bIxZClwHkbLtWAaIovhSBZAmsMinF5nSeI9z35zgmP1xnpNUOjvNkBiFIYxt08lmJN6BtMPlNP8ngZALV1DDrP2zY7DdQiSEx1WWSbYOFAb2S94HUEjTYj40qbQXKqpSY4NvPWwMQzAv5JiXM1ZAhzQvQ4cgVDVX0SMoZCqkhr9edttBlfIZBplLEFzJ4l0wOzicSl0ZD';
//NUESTRO TELEFONO
$telefono = '523318502809';
//URL A DONDE SE MANDARA EL MENSAJE
$url = 'https://graph.facebook.com/v19.0/303230162872395/messages';

//CONFIGURACION DEL MENSAJE
$mensaje = ''
        . '{'
        . '"messaging_product": "whatsapp", '
        . '"to": "'.$telefono.'", '
    
    
      //  . '"type": "text", '
      // . '"text": '
    
        . '"type": "template", '
        . '"template": '
        . '{'
   
        . '     "name": "hello_world",'
        . '     "language":{ "code": "en_US" } '
        . '} '
    
    
        . '}';
//DECLARAMOS LAS CABECERAS
$header = array("Authorization: Bearer " . $token, "Content-Type: application/json",);
//INICIAMOS EL CURL
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_POSTFIELDS, $mensaje);
curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//OBTENEMOS LA RESPUESTA DEL ENVIO DE INFORMACION
$response = json_decode(curl_exec($curl), true);
//IMPRIMIMOS LA RESPUESTA 
print_r($response);
//OBTENEMOS EL CODIGO DE LA RESPUESTA
$status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
//CERRAMOS EL CURL
curl_close($curl);
?>