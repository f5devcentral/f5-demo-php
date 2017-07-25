<?php

$EC2 = true;

require_once __DIR__.'/../vendor/autoload.php';
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

Request::setTrustedProxies(array('10.0.0.0/8','127.0.0.1'));
$hashed_password = 'BFEQkknI/c+Nd7BaG7AaiyTfUFby/pkMHy3UsYqKqDcmvHoPRX/ame9TnVuOV2GrBH0JK9g4koW+CgTYI9mK+w==';
$colors = array("adafaf", "ffd734", "0194d2", "a0bf37", "ed7b0c", "004892");

$users = array(
    // password is password
    'corpuser' => array('ROLE_USER', $hashed_password),
    'remoteuser' => array('ROLE_USER', $hashed_password),
    'user.0' => array('ROLE_USER', $hashed_password),
    'user.1' => array('ROLE_USER', $hashed_password),
    'user.2' => array('ROLE_USER', $hashed_password),
    'user.3' => array('ROLE_USER', $hashed_password),
    'user.4' => array('ROLE_USER', $hashed_password),
    'user.5' => array('ROLE_USER', $hashed_password),    
    'adminuser' => array('ROLE_ADMIN', $hashed_password),
);

function twig_vars($app,$request) {
	$twig_vars = array(
        'headers' => $request->headers,
        'color' => $app['color'],
        'node' => $app['nodename'],
        'xff' => $request->getClientIp(),		   
        'http_host' => $request->getHttpHost(),
        'authorization' => $request->headers->get('authorization'),
        'server_addr' => $request->server->get('SERVER_ADDR'),
        'server_port' => $request->getPort(),
        'remote_addr' => $request->server->get('REMOTE_ADDR'),
        'remote_port' => $request->server->get('REMOTE_PORT'),
        'request_uri' => $request->getRequestUri());
    return $twig_vars;
}
$app = new Silex\Application();
$app['debug'] = false; 

$app['color'] = $_SERVER["F5DEMO_COLOR"];
$app['nodename'] = $_SERVER["F5DEMO_NODENAME"];
$app['shortnodename'] = $_SERVER["F5DEMO_SHORT_NODENAME"];
if($EC2) {
    $ip_int = ip2long($_SERVER["SERVER_ADDR"]);
    $color = $colors[($ip_int % 6) ];
    $app['shortnodename'] = gethostname(); 
#    $app['shortnodename'] = $ip_int % 6; 
    $app['color'] = $color;
     
}

$app['svg'] = array('virtualedition','appliance','viprion',
                    'fast','secure','available',
                    'globe','wireless','key','lock','laptop','mobile');
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
    ));
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app->register(new Silex\Provider\SecurityServiceProvider(), array(
    'security.firewalls' => array(
#        'default' => array('stateless' => false,),
        'http-auth' => array(
            'pattern' => '^/basic/',
            'http' => true,
            'users' => $users,
        ),
        'form-auth' => array(
            'pattern' => '^/form/',
            'form' => array('login_path' => '/login','check_path' => '/form/login_check'),
            'users' => $users,
        ),
        
    ),
    'security.access_rules' => array(
        array('^/admin', 'ROLE_ADMIN'),
    ),
    'security.role_hierarchy' => array(
        'ROLE_ADMIN' => array('ROLE_USER'),
    ),
));

$app->get('/', function(Request $request) use ($app) {
    $twig_vars = twig_vars($app,$request);    
    $twig_vars['title'] = '| Home';
    return $app['twig']->render('index.twig',$twig_vars);
	});

$app->get('/dyncss/f5header.css', function() use ($app) {
        return new Response($app['twig']->render('f5header.css.twig', array(
	       'color' => $app['color'],
	       'node' => $app['shortnodename'],)),
	       200,
		array('Content-Type' => 'text/css'));
	});
$app->get('/dyncss/f5footer.css', function() use ($app) {
        return new Response($app['twig']->render('f5footer.css.twig', array(
	       'color' => $app['color'],
	       'node' => $app['shortnodename'],)),
	       200,
		array('Content-Type' => 'text/css'));
	});
$app->get('/dynimage/{filename}.svg', function($filename) use($app) {
    if(in_array($filename,$app['svg'])){
	return new Response($app['twig']->render("$filename.svg.twig", array(
	       'color' => $app['color'],
	       'node' => $app['shortnodename'],)),
	200,
	array('Content-Type' => 'image/svg+xml')
	);
    } else {
        return new Response('not found',404);
    }
});


/* demo URLS */

$app->get('/lorax/', function(Request $request) use ($app) {
    $twig_vars = twig_vars($app,$request);        
    return $app['twig']->render('lorax.twig',$twig_vars);
	});

$app->get('/welcome/', function(Request $request) use ($app) {
    $twig_vars = twig_vars($app,$request);            
    return $app['twig']->render('welcome.twig',$twig_vars);
	});

$app->get('/httprequest/', function(Request $request) use ($app) {
    $twig_vars = twig_vars($app,$request);        
    return $app['twig']->render('httprequest.twig',$twig_vars);
	});
$app->get('/httprequest.json', function(Request $request) use ($app) {
    $twig_vars = twig_vars($app,$request);
    return json_encode($twig_vars, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_PRETTY_PRINT);
    //return $app->json($twig_vars);
	});

$app->get('/headers/', function(Request $request) use ($app) {
    $custom_headers = array(
        'X-Injected' => array('TestInjected1','TestInjected2','TestInjected3'),
        'X-Sensitive-Data' => array('AMEX378282246310005', 'MC5105105105105100', 'Visa4012888888881881'),
        'X-Powered-By' => 'ASP.NET-PleaseHackMeWithMicrosoftHackingTools',
    );
    $twig_vars = twig_vars($app,$request);            
    $twig_vars['headers'] = $request->headers;
    return new Response($app['twig']->render('headers.twig',$twig_vars),200, $custom_headers);
	});

$app->get('/badlinks/', function(Request $request) use ($app) {
    $twig_vars = twig_vars($app,$request);            
    return $app['twig']->render('badlinks.twig',$twig_vars);
	});

$app->get('/privatedata/', function() use($app) {
    return $app['twig']->render('privatedata.twig',array('color' => $app['color']));
});

$app->get('/basic/', function(Request $request) use($app) {
    $token = $app['security']->getToken();
    if (null !== $token) {
        $user = $token->getUser();
    }
    $twig_vars = twig_vars($app,$request);    
    $twig_vars['user'] = $user;
    
    return $app['twig']->render('basic.twig',$twig_vars);
});

$app->get('/login', function(Request $request) use ($app) {
    $twig_vars = twig_vars($app,$request);
    $twig_vars['error'] = $app['security.last_error']($request);
    $twig_vars['last_username'] = $app['session']->get('_security.last_username');
    return $app['twig']->render('login.twig', $twig_vars);
});

$app->get('/form/', function(Request $request) use($app) {
    $token = $app['security']->getToken();
    if (null !== $token) {
        $user = $token->getUser();
    }
    $twig_vars = twig_vars($app,$request);
    $twig_vars['user'] = $user;
    return $app['twig']->render('form.twig',$twig_vars);
});

$app->run();
