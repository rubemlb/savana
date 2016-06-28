<?php

$expected = ['data_chegada', 'data_partida', 'quartos', 'adultos','num_cama_ext','nome','apelido','email','num_doc','telefone','endereco','pais_origem','nacionalidade','mot_viagem','info'];
$required = ['data_chegada', 'data_partida', 'quartos', 'adultos','nome','apelido','email','num_doc','telefone','endereco'];

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
        'reservasavana@yahoo.com' =>
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
            ->setFrom($from);
		
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
        if ($sent == 1) {
            header('Location: reservas_sucess.html');
            exit;
        }

// handle failures
        $num_failed = count($failures);
        if ($num_failed == 1) {
            $f = 'both';
        } elseif (in_array($email, $failures)) {
            $f = 'email';
        } else {
            $f = 'reg';
        }

// IMPORTANT: log an error before redirecting

        header("Location: error.php?f=$f");
        exit;
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}