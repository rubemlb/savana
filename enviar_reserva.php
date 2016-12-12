<?php
ini_set('max_execution_time', 300); // 5 minutes

    $captcha;
    if(isset($_POST['g-recaptcha-response'])){
      $captcha=$_POST['g-recaptcha-response'];
    }
    if(!$captcha){
      echo "<script type='text/javascript'>
                alert('Por favor confirme o Captcha!');
                location='booking.html';
            </script>";
      exit;
    }
    //d-reca
    $secretKey = "6LdFUA0UAAAAAPtnM2Zi96FiiuXU3jJZFf85G8Bk";
    $ip = $_SERVER['REMOTE_ADDR'];
    $response=file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".$secretKey."&response=".$captcha."&remoteip=".$ip);
    $responseKeys = json_decode($response,true);
    if(intval($responseKeys["success"]) !== 1) {
      echo "<script type='text/javascript'>
                alert('Captcha invalido!');
                location='booking.html';
            </script>";
      exit;
    } else {
        if((include "/data/customers/projetos.prime.cv/httpdocs/bookingodoo_teste/reservaodoo.php") == 0){
        
            $expected = ['name', 'surname', 'telephone', 'email', 'address', 'doc_num', 'origin_country', 'nationality', 'travel_motive', 'arrival_date', 'departure_date', 'num_rooms', 'number_extra_bed', 'num_adults', 'num_children', 'info', 'room_type', 'site'];
            $required = ['arrival_date', 'departure_date', 'num_rooms','num_adults','name','surname','email','doc_num', 'telephone', 'address'];

            // check $_POST array
            foreach ($_POST as $key => $value) {
                if (in_array($key, $expected)) {
                    if (!is_array($value)) {
                        $value = trim($value);
                    }
                    if (empty($value) && in_array($key, $required)) {
                        $$key = '';
                        $missing[] = $key;
                    } else {
                        $$key = $value;
                    }
                }
            }

            // check email address
            if (!in_array($email, $missing)) {
                $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
                if (!$email) {
                    $errors['email'] = 'Please use a valid email address';
                }
            }


            // process only if there are no errors or missing fields
            if (!$errors && !$missing) {
                require_once 'config.php';

                // set up replacements for decorator plugin
            $replacements = [
                'rubem.barros@primeconsulting.org' =>
                    ['#subject#' => 'Reservations - Savana',
                    '#greeting#' => "You received a reservation request from $nome. See details below:"],
                 $email =>
                    ['#subject#' => 'Reservations - Savana',
                    '#greeting#' => "You received a reservation request from $nome. See details below:"]
            ];

                try {
                    // create a transport
                    $transport = Swift_SmtpTransport::newInstance($smtp_server, 465, 'ssl')
                        ->setUsername($username)
                        ->setPassword($password);
                    $mailer = Swift_Mailer::newInstance($transport);

                    // register the decorator and replacements
                    $decorator = new Swift_Plugins_DecoratorPlugin($replacements);
                    $mailer->registerPlugin($decorator);

                    // initialize the message
                   $message = Swift_Message::newInstance()
                       ->setSubject('#subject#')
                       ->setReplyTo(array($email,$username))
                       ->setFrom($email);

                    $image_ilha = $message->embed(Swift_Image::fromPath('img/fogo.png'));
                    $image_logo = $message->embed(Swift_Image::fromPath('img/logo.png'));
                    // create the first part of the HTML output
                    $html = <<<EOT
<html lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<title>Hotel Savana</title>
</head>
<body bgcolor="#EBEBEB" link="#B64926" vlink="#FFB03B">
<table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#EBEBEB">
<tr>
<td>
<table width="600" align="center" border="0" cellpadding="0" cellspacing="0" bgcolor="#FFFFFF">
<tr><td style='text-align:center; background-color: grey'><img src='$image_logo'></td></tr>
<tr>
<td style="padding-top: 0.5em">
<h1 style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', Verdana, sans-serif; color: #0E618C; text-align:
center">Reservations - Hotel Savana</h1>
</td>
</tr>
<tr>
<td style="font-family: 'Lucida Grande', 'Lucida Sans Unicode', Verdana, sans-serif; color: #1B1B1B; font-size: 14px; padding: 1em">
<p>#greeting#</p>
<ul style="list-style-type: none">
EOT;

                    // initialize variable for plain text version
                    $text = '';

                    // add each form element to the HTML and plain text content
                    foreach ($expected as $item) {
                        if (isset($$item)) {
                            $value = $$item;
                            $label = ucwords(str_replace('_', ' ', $item));
                            $html .= "<li>> $label: ";
                            if (is_array($value)) {
                                $value = implode(', ', $value);
                            }
                            $html .= "$value</li>";
                            $text .= "$label: $value\r\n";
                        }
                    }

                    // complete the HTML content
                    $html .= '</ul></td></tr>';
                    $html .="<tr><td style='text-align:center'><img src='$image_ilha'></td></tr>";
                    $html .= '</table></td></tr></table></body></html>';

                    // set the HTML body and add the plain text version
                    $message->setBody($html, 'text/html')
                        ->addPart($text, 'text/plain');

                    // initialize variables to track the emails
                    $sent = 0;
                    $failures = [];

                    // send the messages
                    foreach ($replacements as $recipient => $values) {
                        $message->setTo($recipient);
                        $sent += $mailer->send($message, $failures);
                    }

                    // if the message have been sent, redirect to relevant page
                    if ($sent == 2) {
                        /*header('Location: booking_success.html');*/
                        echo "<script type='text/javascript'>
                               alert('O seu pedido de reserva foi efectuado com sucesso!');
                               location='booking_sucess.html';
                          </script>";
                        exit;
                    }

                    // handle failures
                    $num_failed = count($failures);
                    if ($num_failed == 2) {
                        $f = 'both';
                    } elseif (in_array($email, $failures)) {
                        $f = 'email';
                    } else {
                        $f = 'reg';
                    }

                    // IMPORTANT: log an error before redirecting

                    header("Location: booking.html");
                    exit;
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
            }
        }else{
            echo "<script type='text/javascript'>
                       alert('A sua reserva não pude ser feita!');
                       location='error.php';
                  </script>";
        }
    }
?>