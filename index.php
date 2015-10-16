<?php
	require 'vendor/autoload.php';
	//set timezone
	date_default_timezone_set('America/Denver');
	
	use Monolog\Logger;
	use Monolog\Handler\StreamHandler;
	
	$app = new \Slim\Slim( array(
		'view' => new \Slim\Views\Twig(),
	));
	$app->add(new \Slim\Middleware\SessionCookie());
	
	$view = $app->view();
	$view->parserOptions = array(
	    'debug' => true
	);
	$view->parserExtensions = array(
	    new \Slim\Views\TwigExtension(),
	);
	
	$app->get('/', function() use($app){
		$app->render('about.twig'); //starts by looking through templates folder
	})->name('home');
	
	$app->get('/contact', function() use($app){
		$app->render('contact.twig'); //starts by looking through templates folder
	})->name('contact');
	
	$app->post('/contact', function() use($app){
		$name = $app->request->post('name');
		$email = $app->request->post('email');
		$msg = $app->request->post('msg');
		
		if(!empty($name) && !empty($email) && !empty($msg)){
			$cleanName = filter_var($name, FILTER_SANITIZE_STRING);
			$cleanEmail = filter_var($email, FILTER_SANITIZE_EMAIL);
			$cleanMsg = filter_var($msg, FILTER_SANITIZE_STRING);
		} else {
			//message the user that there's a problem
			$app->flash('fail', 'All fields are required.');
			$app->redirect('/contact');
		}
		
		$transport = Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -t');
		$mailer = \Swift_Mailer::newInstance($transport);
		
		$message = \Swift_Message::newInstance();
		$message->setSubject('Email From Our Website');
		//http://swiftmailer.org/docs/messages.html
		$message->setFrom(array(
			$cleanEmail => $cleanName
		));
		//if server accepts this email
		$message->setTo(array('brookeclonts@gmail.com')); 
		$message->setBody($cleanMsg);
		
		$result = $mailer->send($message);
		
		if($result > 0) {
			$app->flash('success', 'Thank You! You da best!');
			$app->redirect("/");
		} else {
			$app->flash('fail', 'Something went wrong! Sorry, please try again later.');
			$app->redirect('/contact');
			$log = new Logger('name');
			$log->pushHandler(new StreamHandler('app.txt', Logger::WARNING));
			$log->addWarning('There was a problem with contact us submission');
		}
	});
	
	$app->run();