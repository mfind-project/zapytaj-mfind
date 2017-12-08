<?php

ini_set('display_errors', 0);
error_reporting(E_ALL); // na serwerze produkcyjnym zbieramy bledy

/*
ini_set('display_errors', 1);
*/

// Load configuration as an array. Use the actual location of your configuration file
$config = parse_ini_file('config.ini');

// ------------------------------------------------------
// Emails counter - mysql
// ------------------------------------------------------

function db_connect($config)
{
  // Define connection as a static variable, to avoid connecting more than once
  static $connection;

  // Try and connect to the database, if a connection has not been established yet
  if (!isset($connection)) {
    $connection = mysqli_connect($config['connection'], $config['username'], $config['password'], $config['dbname']);
  }

  // If connection was not successful, handle the error
  if ($connection === false) {
    // Handle error - notify administrator, log to a file, show an error screen, etc.
    return mysqli_connect_error();
  }
  return $connection;
}

function db_query($query, $config)
{
  // Connect to the database
  $connection = db_connect($config);

  // Query the database
  $result = mysqli_query($connection, $query);

  return $result;
}

$emails_count = 0;
$result = db_query("SELECT COUNT(email) as count FROM emails", $config);
$emails_count = mysqli_fetch_assoc($result);
$emails_count = $emails_count['count'] + 600;

// ------------------------------------------------------
// Form
// ------------------------------------------------------

$message = '';
$email = '';
$reminder_acceptance = 0;
$oc_end_date = '';
$oc_start_date = '';
$phone = '';
$terms_acceptance = 0;
$newsletter_acceptance = 0;

$form_source_id = 9; // form source is 9 for Formularz zapytaj.mfind.pl

$error_message = false;
$error_email = false;
$error_terms = false;
$success = false;

$file_encoded = '';
$file_type = '';
$file_name = '';

if (isset($_POST['message'])) {
  $count_words = str_word_count($_POST['message']);
  $message = nl2br(htmlspecialchars($_POST['message']));
  if ($count_words < 3) {
    $error_message = true;
  }
}

if (isset($_POST['email'])) {
  $email = $_POST['email'];
  $email = filter_var($email, FILTER_SANITIZE_EMAIL);
  if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
  } else {
    $error_email = true;
  }
}

if (isset($_POST['reminder_acceptance'])) {
  $reminder_acceptance = 1;

  if (isset($_POST['oc_end_date'])) {
    $oc_end_date = $_POST['oc_end_date'];
    $oc_end_date = str_replace('/', '-', $oc_end_date);
    $oc_start_date = date('Y-m-d', strtotime('+ 1 day', strtotime($oc_end_date)));
  }
  if (isset($_POST['phone'])) {
    $phone = $_POST['phone'];
  }
}

if (isset($_POST['terms_acceptance'])) {
  $terms_acceptance = 1;
}

if (isset($_POST['email']) && !isset($_POST['terms_acceptance'])) {
  $error_terms = true;
}

if (isset($_POST['newsletter_acceptance'])) {
  $newsletter_acceptance = 1;
}

if (isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
  $file_encoded = chunk_split(base64_encode(file_get_contents($_FILES['file']['tmp_name'])));
  $file_type = $_FILES['file']['type'];
  $file_name = $_FILES['file']['name'];
}

//if no errors, then send the data to crm, db, send email to pomoc@mfind.pl and confirmation to the user
if ($error_message == false && $error_email == false && $error_terms == false && isset($_POST['email'])) {

  //send json data do SalesAdapter
  // require_once('config.php');
  require_once('MfindSalesAdapterAPI.php');

  //default response is SUCCESS
  $response = array('success' => true);

  //The JSON data.
  $salesAdapter = new MfindSalesAdapterAPI(
    ['Authorization' => 'Basic ' . base64_encode($config['sales_adapter_user'] . ':' . $config['sales_adapter_password'])],
    $config['sales_adapter_url'],
    $config['sales_adapter_version']
  );

  $data = [
    'email' => $email,
    'phone' => $phone,
    'source' => 'zapytaj.mfind.pl',
    'agreement_newsletter' => (bool)$newsletter_acceptance,
    'status' => 'Nowy',
    'policy_start_date' => $oc_start_date
  ];

  try {
    $create_lead = $salesAdapter->create($data);
    $response = (array)$create_lead;
    $response = (array)$response['create_lead'];
  } catch(MfindSalesAdapterAPIException $e) {
    $response = array(
    'success' => false,
    'error' => 'MfindSalesAdapterAPIException',
    'message' => $e->getMessage()
    );
  } catch(Exception $e) {
    $response = array(
    'success' => false,
    'error' => 'Create Lead Exception',
    'message' => $e->getMessage()
    );
  }

  if ($response['success'] != true){
    error_log('SF ERROR: ' . print_r($response, true));
  }

  //send email to pomoc@mfind.pl and confimation to the user
  email_send($email, $phone, $message, $file_type, $file_name, $file_encoded);
  $success = true;

  // Insert another email into the database
  $result = db_query("INSERT INTO `emails` (`email`) VALUES ('" . $email . "')", $config);
}

// ---- end of sending functions

/* mandrill send email function - confirmation to the user */
function email_send($email, $phone, $message, $file_type, $file_name, $file_encoded)
{

  require('mailchimp-mandrill-api-php-9f336b08ea14/src/Mandrill.php');

  try {
    $mandrill = new Mandrill('m9TUwgfexMxppqVBKbFHtw');

    $template_name__conf_to_user = 'potwierdzenie-przeslania-pytania-na-zapytaj-mfind-pl';
    $template_content__conf_to_user = array(
      array(
        'name' => 'example name',
        'content' => 'example content'
      )
    );
    $message_mandrill__conf_to_user = array(
      'from_email' => 'pomoc@mfind.pl',
      'to' => array(
        array(
          'email' => ' ' . $email . ' ',
          'type' => 'to'
        )
      ),
      'global_merge_vars' => array(
        array(
          "name" => "usermessage",
          "content" => ' ' . $message . ' '
        )
      ),
      'headers' => array('Reply-To' => 'pomoc@mfind.pl'),
      'important' => false,
      'track_opens' => null,
      'track_clicks' => null,
      'auto_text' => null,
      'auto_html' => null,
      'inline_css' => null,
      'url_strip_qs' => null,
      'preserve_recipients' => null,
      'view_content_link' => null,
      'tracking_domain' => null,
      'signing_domain' => null,
      'return_path_domain' => null,
      'merge' => true,
      'merge_language' => 'mailchimp'
    );

    $template_name__msg_to_help = 'pytanie-z-zapytaj-mfind-pl';
    $template_content__msg_to_help = array(
      array(
        'name' => 'example name',
        'content' => 'example content'
      )
    );
    $message_mandrill__msg_to_help = array(
      'from_email' => 'zapytaj@mfind.pl',
      'to' => array(
        array(
          'email' => 'pomoc@mfind.pl',
          'type' => 'to'
        )
      ),
      'global_merge_vars' => array(
        array(
          "name" => "useremail",
          "content" => ' ' . $email . ' '
        ),
        array(
          "name" => "userphone",
          "content" => ' ' . $phone . ' '
        ),
        array(
          "name" => "usermessage",
          "content" => ' ' . $message . ' '
        )
      ),
      'attachments' => array(
        array(
          "type" => '' . $file_type . '',
          "name" => '' . $file_name . '',
          "content" => '' . $file_encoded . ''
        )
      ),
      'headers' => array('Reply-To' =>  $email),
      'important' => false,
      'track_opens' => null,
      'track_clicks' => null,
      'auto_text' => null,
      'auto_html' => null,
      'inline_css' => null,
      'url_strip_qs' => null,
      'preserve_recipients' => null,
      'view_content_link' => null,
      'bcc_address' => 'pomoc@mfind.pl',
      'tracking_domain' => null,
      'signing_domain' => null,
      'return_path_domain' => null,
      'merge' => true,
      'merge_language' => 'mailchimp'
    );

    $ip_pool = 'Main Pool';
    $result_mandrill = $mandrill->messages->sendTemplate($template_name__conf_to_user,
                               $template_content__conf_to_user,
                               $message_mandrill__conf_to_user,
                               $ip_pool);

    $result_mandrill = $mandrill->messages->sendTemplate($template_name__msg_to_help,
                               $template_content__msg_to_help,
                               $message_mandrill__msg_to_help,
                               $ip_pool);
    /* print_r($result); */

  } catch (Mandrill_Error $e) {
    // Mandrill errors are thrown as exceptions
    echo 'A mandrill error occurred: ' . get_class($e) . ' - ' . $e->getMessage();
    // A mandrill error occurred: Mandrill_Unknown_Subaccount - No subaccount exists with the id 'customer-123'
    throw $e;
  }
}
?>

<!DOCTYPE HTML>
<html lang="pl">
<head>
  <meta charset="utf-8"/>

  <title>zapytaj.mfind.pl - bezpłatne porady ubezpieczeniowe</title>

  <meta name="Expires" content="0">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Cache-Control" content="no-cache">

  <meta name="author" content="mfind">
  <meta name="keywords"
      content="bezpłatne porady ubezpieczeniowe, porady ubezpieczeniowe, pytanie o ubezpieczenie, porada ubezpieczeniowa">
  <meta name="description" content="Zadaj swoje pytanie o ubezpieczenie i uzyskaj bezpłatną poradę ekspertów mfind.">
  <meta name="robots" content="index,follow">

  <meta property="og:locale" content="pl_PL"/>
  <meta property="og:type" content="website"/>
  <meta property="og:url" content="https://zapytaj.mfind.pl"/>
  <meta property="og:title" content="zapytaj.mfind.pl - bezpłatne porady ubezpieczeniowe"/>
  <meta property="og:description"
      content="Zadaj swoje pytanie o ubezpieczenie i uzyskaj bezpłatną poradę ekspertów mfind."/>

  <link href="https://s3.eu-central-1.amazonaws.com/mfind-production/images/zapytaj-mfind/favicon.ico" rel="shortcut icon"
      type="image/vnd.microsoft.icon">

  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1">

  <meta http-equiv="X-UA-Compatible" content="IE=10">

  <link rel="stylesheet" type="text/css" href="fonts/lato.css">
  <link rel="stylesheet" type="text/css" href="fonts/font-awesome.min.css">

  <link rel="stylesheet" type="text/css" href="js/jquery-ui/jquery-ui.css">
  <link rel="stylesheet" type="text/css" href="css/datepicker.css">
  <link rel="stylesheet" type="text/css" href="css/style.css">
  <link rel="stylesheet" type="text/css" href="css/rwd.css">

  <script type="text/javascript" src="js/jquery.js"></script>
  <script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
  <script type="text/javascript" src="js/jquery-ui/jquery.ui.datepicker-pl.js"></script>
  <script type="text/javascript" src="js/expanding.js"></script>
  <script type="text/javascript" src="js/js.js"></script>

  <!--[if lt IE 9]>
  <script type="text/javascript" src="js/html5.js"></script>
  <![endif]-->
</head>
<body>

<!-- Google Tag Manager -->
<noscript>
  <iframe src="//www.googletagmanager.com/ns.html?id=GTM-T6J2TP"
      height="0" width="0" style="display:none;visibility:hidden"></iframe>
</noscript>
<script>(function (w, d, s, l, i) {
    w[l] = w[l] || [];
    w[l].push(
      {'gtm.start': new Date().getTime(), event: 'gtm.js'}
    );
    var f = d.getElementsByTagName(s)[0],
      j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';
    j.async = true;
    j.src =
      '//www.googletagmanager.com/gtm.js?id=' + i + dl;
    f.parentNode.insertBefore(j, f);
  })(window, document, 'script', 'dataLayer', 'GTM-T6J2TP');</script>
<!-- End Google Tag Manager -->

<div class="content-top-wrap">
  <header class="content-top">
    <div class="wrapper">
      <a href="/" title="Porównywarka OC i AC najtańsze ubezpieczenie samochodu" class="logo"><img
          src="https://s3.eu-central-1.amazonaws.com/mfind-production/images/zapytaj-mfind/logo-zapytaj-mfind.png"
          alt="zapytaj.mfind.pl"></a>
      <nav class="main-menu" id="main-menu">
        <a class="lines-button x2 mobile-menu-trigger" id="mobile-menu-trigger" aria-label="Toggle Navigation">
          <span class="lines"></span>
        </a>

        <div class="main-menu-content-wrap">
          <div class="main-menu-content">
            <a href="https://www.mfind.pl/" title="Porównywarka ubezpieczeń mfind" target="_blank"
               class="featured-link">
              <span class="btn-gold">Porównaj tanie OC / AC</span>
              <div class="featured-link-mobile">
                Porównaj oferty oc / ac
                <div class="sub-text">Szybko, za darmo, wiarygodnie</div>
              </div>
            </a>
            <ul>
              <li class="menu-item">
                <a href="https://www.mfind.pl/akademia/" title="AKADEMIA UBEZPIECZEŃ">AKADEMIA
                  UBEZPIECZEŃ</a>
              </li>
              <li class="menu-item"><a href="https://www.mfind.pl/akademia/forum/" title="Forum">Forum</a>
              </li>
            </ul>
            <div class="clear"></div>
          </div>
        </div>
      </nav>
      <div class="clear"></div>
    </div>
  </header>
</div>

<div class="main-content">
  <div class="wrapper clearfix">
    <div class="row">
      <div class="col-70">
        <div class="box">
          <?php if ($success) { ?>
            <!-- success response -->
            <div id="help-form-response" class="help-form-response">
              <section class="form-section">
                <h2 class="form-section-header">Dziękujemy!</h2>
                <p>Twoje pytanie zostało do nas wysłane.</p>
                <p>Na pytanie odpowiadamy zazwyczaj <b>w ciągu 1 dnia roboczego.</b></p>
              </section>
            </div>
            <!--END OF success response -->
          <?php } else { ?>
            <form action="/" method="post" novalidate="novalidate" id="help-form"
                enctype="multipart/form-data">

              <section class="form-section">
                <h2 class="form-section-header"><span class="number">1.</span> Zadaj pytanie</h2>

                <div class="row form-row">
                  <div class="field <?php if ($error_message) { ?>validation-error<?php } ?>">
                    <textarea name="message" id="message"
                          class="expanding"><?php echo $message; ?></textarea>
                    <?php if ($error_message) { ?>
                      <div class="validation-error-msg" id="message_error">To pole jest wymagane,
                        wpisz przynajmniej kilka słów.
                      </div>
                    <?php } ?>
                    <div class="validation-error-msg hide" id="message_error">To pole jest wymagane,
                      wpisz przynajmniej kilka słów.
                    </div>
                  </div>
                  <div class="add-file">
                    <div class="add-file-label"><i class="icon add-file-icon"></i><span
                        id="file-name">Dodaj załącznik</span></div>
                    <div class="file-input-wrapper"><input type="file" name="file" id="file"/></div>
                  </div>
                </div>
              </section>

              <section class="form-section">
                <h2 class="form-section-header"><span class="number">2.</span> Podaj adres e-mail, na
                  który prześlemy odpowiedź</h2>

                <div class="row form-row">
                  <div class="col-50">
                    <div
                      class="field input-mail-gray <?php if ($error_email) { ?>validation-error<?php } ?>">
                      <!-- this input has an "input-mail-gray" class -->
                      <input type="text" id="email" name="email" placeholder="Twój adres e-mail"
                           value="<?php echo $email; ?>">
                    </div>
                  </div>
                  <div class="col-50">
                    <div class="form-row-tooltip">
                      <i class="tooltip-icon info-icon"></i>
                      <div class="tooltip">
                        Nie udostępniamy Twojego adresu e-mail<br> osobom trzecim. Nie wyślemi
                        Ci spamu. Więcej<br> szczegółów w naszej <a
                          href="https://mfind.pl/regulamin-polityka-prywatnosci#polityka-prywatnosci"
                          title="Polityka prywatności mfind" target="_blank">polityce
                          prywatności</a>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="box box-green box-reminder" id="box-reminder">
                  <div class="checkbox-row">
                    <div class="checkbox">
                      <label>
                        <input id="reminder_acceptance" type="checkbox"
                          name="reminder_acceptance" value="1"
                          <?php if ($reminder_acceptance === 1) { ?>checked<?php } ?>>
                        <span>Przypomnij mi o kończącej się polisie OC - <b>za darmo!</b></span>
                      </label>
                    </div>
                  </div>

                  <div class="reminder-fields" id="reminder-fields">
                    <div class="row form-row first-row">
                      <label>Data zakończenia polisy OC</label>
                      <div class="col-50 col-left">
                        <div
                          class="input-calendar <?php if ($reminder_acceptance === 1 && $oc_end_date === '') { ?>validation-error<?php } ?>">
                          <input type="text" id="oc_end_date" name="oc_end_date"
                               placeholder="dd/mm/rrrr" maxlength="10"
                               value="<?php echo $oc_end_date; ?>">
                        </div>
                      </div>
                      <div class="col-50 col-right">
                        <div class="form-row-tooltip">
                          <i class="tooltip-icon info-icon info-green-icon"></i>
                          <div class="tooltip">
                            Otrzymasz przypomnienie e-mailem<br class="mobile-hide"> o dacie
                            zakończenia Twojej polisy OC
                          </div>
                        </div>
                      </div>
                      <div class="clearfix"></div>
                      <?php if ($reminder_acceptance === 1 && $oc_end_date === '') { ?>
                        <div class="validation-error-msg" id="oc_end_date_error">Podaj
                          prawidłową datę abyś przypomnienie mogło być wysłane.
                        </div>
                      <?php } ?>
                      <div class="validation-error-msg hide" id="oc_end_date_error">Podaj
                        prawidłową datę aby przypomnienie mogło być wysłane.
                      </div>
                    </div>

                    <div class="row form-row">
                      <label>Numer telefonu (opcjonalnie)</label>
                      <div class="col-50 col-left">
                        <div class="field field-short">
                          <input type="tel" name="phone" id="phone"
                            value="<?php echo $phone; ?>">
                        </div>
                      </div>
                      <div class="col-50 col-right">
                        <div class="form-row-tooltip">
                          <i class="tooltip-icon info-icon info-green-icon"></i>
                          <div class="tooltip">
                            Otrzymasz darmowe przypomnienie SMS<br class="mobile-hide"> o
                            dacie zakończenia Twojej polisy OC
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </section>

              <div class="row form-row submit-row">
                <div class="col-50">
                  <button type="submit" class="btn btn-loader"><span
                      class="text">Wyślij pytanie</span></button>
                  <input type="hidden" value="9" name="form_source" id="form_source"/>
                </div>
                <div class="col-50 col-right">
                  <div class="form-row-tooltip">
                    <i class="tooltip-icon info-icon"></i>
                    <div class="tooltip">
                      Na pytanie odpowiadamy zazwyczaj <br> <b>w ciągu 1 dnia roboczego</b>
                    </div>
                  </div>
                </div>
              </div>

              <div class="form-row last-checkbox-row">
                <div class="checkbox-row">
                  <div class="checkbox <?php if ($error_terms) { ?>error<?php } ?>">
                    <label>
                      <input id="terms_acceptance" type="checkbox" name="terms_acceptance"
                           value="1" <?php if ($terms_acceptance === 1) { ?>checked<?php } ?>>
                      <span>Akceptuję <a href="https://mfind.pl/regulamin-polityka-prywatnosci#regulamin"
                                 title="Regulamin mfind"
                                 target="_blank">regulamin</a> i <a
                          href="https://mfind.pl/regulamin-polityka-prywatnosci#polityka-prywatnosci"
                          title="Polityka prywatności mfind" target="_blank">politykę
                          prywatności</a> serwisu mfind.pl.</span>
                    </label>
                  </div>
                  <?php if ($error_terms) { ?>
                    <div class="validation-error-msg" id="terms_acceptance_error">Akceptacja
                      polityki prywatności i regulaminu jest wymagana.
                    </div>
                  <?php } ?>
                  <div class="validation-error-msg hide" id="terms_acceptance_error">Akceptacja
                    polityki prywatności i regulaminu jest wymagana.
                  </div>
                </div>
                <div class="checkbox-row">
                  <div class="checkbox">
                    <label>
                      <input id="newsletter_acceptance" type="checkbox"
                           name="newsletter_acceptance" value="1"
                           <?php if ($newsletter_acceptance === 1) { ?>checked<?php } ?>>
                <span>Wyrażam zgodę na otrzymywanie od mfind sp. z o.o. informacji handlowej w rozumieniu<br>
                art. 10 ust. 2 Ustawy z dnia 18 lipca 2002 r. o świadczeniu usług drogą elektroniczną <br>
                (Dz.U. Nr 144, poz. 1204) na podany przy rejestracji adres poczty elektronicznej.</span>
                    </label>
                  </div>
                  <div class="validation-error-msg hide" id="newsletter_acceptance_error">Wyrażenie
                    zgody jest wymagane.
                  </div>
                </div>
              </div>
            </form>
          <?php } ?>
        </div>
      </div>

      <div class="col-30">
        <h1 class="form-caption">Bezpłatne porady ubezpieczeniowe ekspertów mfind</h1>
        <div class="form-counter">
          Odpowiedzieliśmy już na:
          <?php
          function variety($number, $varietyFor1, $varietyFor234, $varietyForOthers) {
            if ($number == 1)
              return $varietyFor1;
            if ($number % 100 >= 10 && $number % 100 <= 20)
              return $varietyForOthers;
            if (in_array($number % 10, array(2, 3, 4)))
              return $varietyFor234;
            return $varietyForOthers;
          }
          ?>
          <div class="counter"><span
              id="help-sum"><?php echo $emails_count; ?></span> <?php echo variety($emails_count, 'pytanie', 'pytania', 'pytań'); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<footer class="page-footer main-page-footer">
  <div class="wrapper clearfix">
    <div>
      <div class="col-40 footer-contact">
        <h5><img src="https://mfind-production.s3.amazonaws.com/images/mfind-logo-dark.png" alt="mfind" /></h5>
        <ul class="menu-items">
          <li>
            <a href="tel:+48224909000" title="telefon">
              <span class="fa fa-phone"></span> +48 22 490 9000
            </a>
          </li>
          <li>
            <a href="mailto:kontakt@mfind.pl" title="Kontakt">
              <span class="fa fa-envelope"></span> kontakt@mfind.pl
            </a>
          </li>
          <li>
            <a href="https://www.facebook.com/akademiamfind" title="mfind facebook" target="blank">
              <span class="fa fa-facebook-square"></span> Polub nas na Facebooku
            </a>
          </li>
          <li>
            <a href="https://twitter.com/akademiamfind" title="mfind twitter" target="blank">
              <span class="fa fa-twitter-square"></span> Obserwuj nas na Twitterze
            </a>
          </li>
          <li>
            <a href="http://www.wykop.pl/ludzie/mfind/" title="mfind wykop" target="blank">
              <span class="fa fa-square"><div class="footer-wykop-icon"></div></span> Mirkuj z nami na Wykopie
            </a>
          </li>
        </ul>
      </div>
      <div class="col-18">
        <h5 class="col-title">O mfind</h5>
        <ul class="menu-items">
          <li>
            <a href="https://mfind.pl/biuroprasowe/o-nas/" title="O nas">O nas</a>
          </li>
          <li>
            <a href="https://mfind.pl/biuroprasowe/" title="Biuro prasowe">Biuro prasowe</a>
          </li>
          <li>
            <a href="https://mfind.pl/dolacz-do-nas/" title="Praca">Praca</a>
          </li>
          <li>
            <a href="https://mfind.pl/regulamin-polityka-prywatnosci#regulamin" title="Regulamin">Regulamin</a>
          </li>
          <li>
            <a href="https://mfind.pl/regulamin-polityka-prywatnosci#polityka-prywatnosci" title="Polityka prywatności">Polityka prywatności</a>
          </li>
        </ul>
      </div>
    </div>
      <div class="col-18">
        <h5 class="col-title">Akademia mfind</h5>
        <ul class="menu-items">
          <li>
            <a href="https://mfind.pl/akademia/" title="Najnowsze porady">Najnowsze porady</a>
          </li>
          <li>
            <a href="https://mfind.pl/akademia/raporty-i-analizy/" title="Raporty i analizy">Raporty i analizy</a>
          </li>
          <li>
            <a href="https://mfind.pl/akademia/druki-ubezpieczeniowe/" title="Druki ubezpieczeniowe">Druki ubezpieczeniowe</a>
          </li>
          <li>
            <a href="https://mfind.pl/akademia/ciekawostki-ubezpieczeniowe/" title="Ciekawostki">Ciekawostki</a>
          </li>
          <li>
            <a href="https://mfind.pl/akademia/forum/" title="Forum">Forum</a>
          </li>
        </ul>
      </div>
      <div class="col-24">
        <h5 class="col-title">Produkty</h5>
        <ul class="menu-items">
          <li>
            <a href="https://mfind.pl/ubezpieczenie-oc-ac/kalkulator-oc-ac" title="Kalkulator OC i AC">Kalkulator OC i AC</a>
          </li>
          <li>
            <a href="https://mfind.pl/ubezpieczenie-oc-ac/kalkulator-oc-ac" title="Ubezpieczenie OC i AC">Ubezpieczenie OC i AC</a>
          </li>
          <li>
            <a href="https://mfind.pl/ubezpieczenie-turystyczne/" title="Ubezpieczenie turystyczne">Ubezpieczenie turystyczne</a>
          </li>
          <li>
            <a href="https://mfind.pl/ubezpieczenie-mieszkania-i-domu/" title="Ubezpieczenie mieszkania i domu">Ubezpieczenie mieszkania i domu</a>
          </li>
          <li>
            <a href="https://mfind.pl/ubezpieczenie-zdrowotne/" title="Ubezpieczenie zdrowotne">Ubezpieczenie zdrowotne</a>
          </li>
        </ul>
      </div>
    </div>
  </div>
  <div class="footer-last-line">
    <div class="last-line-wrapper clearfix">
      <div class="left-text">© <?php echo date('Y');?> mfind it sp. z o.o.</div>
      <div class="right-text">Designed with <span class="fa fa-heart"></span> by tonik.pl</div>
    </div>
  </div>
</footer>
</body>
</html>
