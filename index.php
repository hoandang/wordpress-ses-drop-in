<?php
/*
  Plugin Name: SES DropIn
*/

if (!defined('ABSPATH')) {
  exit;
}

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
include_once(ABSPATH . WPINC . '/class-phpmailer.php');

if (!function_exists('wp_mail')) :
function wp_mail($to, $subject, $message, $headers = [], $attachments = [])
{
  $mail = new PHPMailer;
  $mail->setFrom(get_bloginfo('admin_email'));
  $mail->addAddress($to);
  $mail->isHTML(true);
  $mail->Subject = $subject;
  $mail->Body = $message;

  foreach($attachments as $attachment)
  {
    $mail->addAttachment($attachment);
  }

  if(!$mail->preSend())
  {
    throw new Exception($mail->ErrorInfo);
  }

  $rawMessage = $mail->getSentMIMEMessage();

  try
  {
    $args = [
      'version' => 'latest',
      'region' => 'us-west-2'
    ];
    if (getenv('AWS_ACCESS_KEY_ID') && getenv('AWS_SECRET_ACCESS_KEY'))
    {
      $args = array_merge($args, [
        'credentials' => [
          'key' => getenv('AWS_ACCESS_KEY_ID'),
          'secret' => getenv('AWS_SECRET_ACCESS_KEY')
        ]
      ]);
    }

    $client = new SesClient($args);
    return $client->sendRawEmail([
      'RawMessage' => [
        'Data' => $rawMessage
      ]
    ]);
  }
  catch(AwsException $ex)
  {
    throw new Exception($ex->getMessage());
  }
}
endif;