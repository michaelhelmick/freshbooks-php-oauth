<?php
session_start();
require_once('src/config.php');
require_once('src/freshbooks.php');

if(isset($_SESSION['oauth_token']) && isset($_SESSION['oauth_token_secret']))
{
	$c = new Freshbooks(OAUTH_CONSUMER_KEY, OAUTH_CONSUMER_SECRET, NULL, $_SESSION['subdomain'], $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);	
}
else if(isset($_GET['oauth_token']) && isset($_GET['oauth_verifier']))
{
	$c = new Freshbooks(OAUTH_CONSUMER_KEY, OAUTH_CONSUMER_SECRET, NULL, $_SESSION['subdomain']);
	
	$access_token = $c->getAccessToken($_GET['oauth_token'], $_GET['oauth_verifier']);
	
	$_SESSION['oauth_token'] = $access_token['oauth_token'];
	$_SESSION['oauth_token_secret'] = $access_token['oauth_token_secret'];
	
	header("Location: index.php");
}
else if(isset($_POST['subdomain']))
{
	$_SESSION['subdomain'] = $_POST['subdomain'];
	$c = new Freshbooks(OAUTH_CONSUMER_KEY, OAUTH_CONSUMER_SECRET, OAUTH_CALLBACK, $_SESSION['subdomain']);

	echo '<a href="'.$c->getLoginUrl().'">Login with Freshbooks!</a>';
}

if(isset($_SESSION['oauth_token']) && isset($_SESSION['oauth_token_secret']))
{
	$request = '<?xml version="1.0" encoding="utf-8"?><request method="client.list"><page>1</page><per_page>15</per_page></request>';
	try {
		$clients = $c->post($request);
	}
	catch(FreshbooksError $e)
	{
		$error = $e->getMessage();
	}
}


?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> 
 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"> 
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>Freshbooks</title>
</head> 
 
<body>
	<?php if(isset($_SESSION['oauth_token']) && isset($_SESSION['oauth_token_secret'])): ?>
	
		<?php if($error): ?>
		<ul id="error">
			<li><?php echo $error; ?></li>
		</ul>
		<?php endif; ?>
		
		<?php print_r($clients); ?>
		
	<?php elseif(!isset($_SESSION['subdomain'])): ?>
		
		<form action="#" method="POST">
			http://<input type="text" name="subdomain" />.freshbooks.com
		</form>
		
	<?php endif; ?>
</body>
</html>