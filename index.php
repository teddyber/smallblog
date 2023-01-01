<?php
// include(dirname(__FILE__).'/conn.php');
$f3 = require('fff/base.php');
$f3->set('CACHE', FALSE);
$f3->set('DEBUG', 3);
$f3->set('UI', 'ui/');

$f3->route(
    'GET /',
    function ($f3) {
        auth($f3);

        include(dirname(__FILE__) . '/conn.php');

        $db = new DB\SQL(
            "mysql:host=$host;port=3306;dbname=$db",
            $user,
            $pwd
        );
        $message = new DB\SQL\Mapper($db, 'messages');
        $message->load('', array('order' => 'message_date DESC', 'limit' => 20));
        // $messages = array();
        while (!$message->dry()) {
            $message->message_date = date('d F Y à H:i', strtotime($message->message_date));
            // echo  $message->message_date;
            $message->message_date = str_replace(array('December'), array('Décembre'), $message->message_date);
            // echo $message->message_date;

            $m = $message->cast();

            $author = new DB\SQL\Mapper($db, 'personnes');
            $author->load(array('id=?', $message['user']));
            $m['author'] = $author->prenom . " " . $author->nom;

            $messages[] = $m;

            $message->skip();
        }
        $f3->set('messages', $messages);
        $f3->set('content', 'messages.html');
        echo \Template::instance()->render('home.html');
    }
);
// $f3->route(
//     'GET /addmessage',
//     function ($f3) {
//         auth($f3);

//         $f3->set('content', 'addmessage.html');
//         echo \Template::instance()->render('home.html');
//     }
// );
$f3->route(
    'POST /addmessage',
    function ($f3) {
        auth($f3);
        include(dirname(__FILE__) . '/conn.php');

        $db = new DB\SQL(
            "mysql:host=$host;port=3306;dbname=$db",
            $user,
            $pwd
        );
        // echo($f3->get('FILES.photo.tmp_name'));
        // echo file_get_contents($f3->get('FILES.photo.tmp_name'));
        // die;
        $message = new DB\SQL\Mapper($db, 'messages');
        $message->text = $f3->get('POST.text');
        $message->user = $f3->get('SESSION.id');
        // print_r($f3->get('FILES'));
        if($f3->get('FILES.photo.tmp_name')!='') {
            $image_p = resizeImage($f3->get('FILES.photo.tmp_name'), 800, 800);
            ob_start();
            imagejpeg($image_p);
            $imageData = ob_get_contents();
            ob_end_clean();
            $message->photo = $imageData;
        }
        $message->save();
        $f3->reroute('/');
    }
);
$f3->route(
    'GET /pic/@id',
    function ($f3) {
        auth($f3);

        include(dirname(__FILE__) . '/conn.php');

        $db = new DB\SQL(
            "mysql:host=$host;port=3306;dbname=$db",
            $user,
            $pwd
        );
        $message = new DB\SQL\Mapper($db, 'messages');
        $message->load(array('id=?', $f3->get('PARAMS.id')));
        // $messages = array();
        header("Content-type: image/jpeg");
        // print_r(($message));
        echo $message->photo;
    }
);
$f3->route(
    'GET /picauthor/@id',
    function ($f3) {
        auth($f3);

        include(dirname(__FILE__) . '/conn.php');

        $db = new DB\SQL(
            "mysql:host=$host;port=3306;dbname=$db",
            $user,
            $pwd
        );
        $author = new DB\SQL\Mapper($db, 'personnes');
        $author->load(array('id=?', $f3->get('PARAMS.id')));
        header("Content-type: image/jpeg");
        echo $author->pic;
    }
);
// $f3->route(
//     'GET /import/@id',
//     function ($f3) {
//         auth($f3);
//         $json = json_decode(file_get_contents('famileo.json'));
//         // print_r($json[0]->familyWall);
//         include(dirname(__FILE__) . '/conn.php');

//         $db = new DB\SQL(
//             "mysql:host=$host;port=3306;dbname=$db",
//             $user,
//             $pwd
//         );
//         $id=$f3->get('PARAMS.id');
//         for($i=$id*10;$i<($id+1)*10;$i++){
//         // foreach ($json as $page) {
//             foreach ($json[$i]->familyWall as $post) {
//                 $message = new DB\SQL\Mapper($db, 'messages');
//                 $message->id = $post->wall_post_id;
//                 $message->text = $post->text;

//                 $message->user = $post->author_id;
//                 $message->message_date = $post->date;
//                 print_r($message->cast());
//                 $image_p = resizeImage('Famileo_files/' . basename($post->image), 800, 800);
//                 ob_start();
//                 imagejpeg($image_p);
//                 $imageData = ob_get_contents();
//                 ob_end_clean();
//                 $message->photo = $imageData;
//                 // $message->photo = 'Famileo_files/'.basename($post->image);
//                 $message->save();
//             }
//         }
//         $f3->reroute('/import/'.($id+1));
//     }

// );
// $f3->route(
//     'GET /importauthors/@id',
//     function ($f3) {
//         auth($f3);
//         $json = json_decode(file_get_contents('famileo.json'));
//         // print_r($json[0]->familyWall);
//         include(dirname(__FILE__) . '/conn.php');

//         $db = new DB\SQL(
//             "mysql:host=$host;port=3306;dbname=$db",
//             $user,
//             $pwd
//         );
//         $id = $f3->get('PARAMS.id');
//         for ($i = $id * 10; $i < ($id + 1) * 10; $i++) {
//             // foreach ($json as $page) {
//             foreach ($json[$i]->familyWall as $post) {
//                 $author = new DB\SQL\Mapper($db, 'personnes');
//                 $author->load(array('id=?', $post->author_id));
//                 if ($post->author_image != null) {
//                     $image_p = resizeImage('Famileo_files/' . basename($post->author_image), 65, 65);
//                     ob_start();
//                     imagejpeg($image_p);
//                     $imageData = ob_get_contents();
//                     ob_end_clean();
//                     $author->pic = $imageData;
//                     $author->save();
//                 }
//                 // $message = new DB\SQL\Mapper($db, 'messages');
//                 // $message->id = $post->wall_post_id;
//                 // $message->text = $post->text;

//                 // $message->user = $post->author_id;
//                 // $message->message_date = $post->date;
//                 // print_r($message->cast());
//                 // $image_p = resizeImage('Famileo_files/' . basename($post->image), 800, 800);
//                 // ob_start();
//                 // imagejpeg($image_p);
//                 // $imageData = ob_get_contents();
//                 // ob_end_clean();
//                 // $message->photo = $imageData;
//                 // // $message->photo = 'Famileo_files/'.basename($post->image);
//                 // $message->save();
//             }
//         }
//         $f3->reroute('/importauthors/' . ($id + 1));
//     }

// );
$f3->route(
    //TODO if success in URI
    'GET /login',
    function ($f3) {
        // print_r($f3->get('GET'));

        if($f3->exists('GET.success')) $f3->set('success', true); else $f3->set('success', false);
        if($f3->exists('GET.error')) $f3->set('error', true); else $f3->set('error', false);
        if($f3->exists('GET.email')) $f3->set('email', true); else $f3->set('email', false);
        $f3->set('content', 'login.html');
        echo \Template::instance()->render('home.html');
        // echo $form;
    }
);
$f3->route(
    'POST /login',
    function ($f3) {
        if ($f3->exists('POST.forgot')) { //password recover
            $token = urlencode(base64_encode(openssl_encrypt($f3->get('POST')['login'], 'aes128', 'random_key', true, 'iv12345678901234')));
            echo mail($f3->get('POST')['login'], 
            'Password reset', 
            'Pour modifier votre mot de passe sur le site familial, veuillez cliquer sur <a href="https://' . $f3->get('SERVER')['HTTP_HOST'] . $f3->get('SERVER')['REQUEST_URI'] . '/reset?token=' . $token . '">ce lien</a>',
            array(
                'From' => 'ne-pas-repondre@example.com',
                'Reply-To' => 'ne-pas-repondre@example.com',
                'MIME-Version' => '1.0',
                'Content-type' => 'text/html; charset=UTF-8'
            ));
            die;
            $f3->reroute('/login?email');
        } else {
            include(dirname(__FILE__) . '/conn.php');

            $db = new DB\SQL(
                "mysql:host=$host;port=3306;dbname=$db",
                $user,
                $pwd
            );
            $user = new DB\SQL\Mapper($db, 'personnes');
            $user->load(array('courriel=?', $f3->get('POST.login')));

            // echo $f3->get('POST.password');
            // echo $user->password;
            if (password_verify($f3->get('POST.password'), $user->password)) {
                //loggeed in!
                $f3->set('SESSION.id', $user->id);
                $f3->reroute('/');
            } else  $f3->reroute('/login?error')
            ; //TODO message d'erreur et retry
        }
    }
);
$f3->route(
    'GET /login/reset',
    function ($f3) {
        $f3->set('content', 'reset.html');
        echo \Template::instance()->render('home.html');

        // RESET;
    }
);
$f3->route(
    'POST /login/reset',
    function ($f3) {
        $email = openssl_decrypt(urldecode(base64_decode($f3->get('POST.token'))), 'aes128', 'random_key', true, 'iv12345678901234');
        $password = password_hash($f3->get('POST.password'), PASSWORD_BCRYPT);

        include(dirname(__FILE__) . '/conn.php');

        $db = new DB\SQL(
            "mysql:host=$host;port=3306;dbname=$db",
            $user,
            $pwd
        );
        $user = new DB\SQL\Mapper($db, 'personnes');
        $user->load(array('courriel=?', $email));
        $user->password = $password;
        $user->save();
        $f3->reroute('/login?success');
    }
);
$f3->run();






function auth($f3)
{
    if (!$f3->exists('SESSION.id'))
        $f3->reroute('/login');
    // else {
    // 	echo $f3->get('SESSION.id');
    // }
}
/**
 * Resize an image and keep the proportions
 * @author Allison Beckwith <allison@planetargon.com>
 * @param string $filename
 * @param integer $max_width
 * @param integer $max_height
 * @return image
 */
function resizeImage($filename, $max_width, $max_height)
{
    list($orig_width, $orig_height) = getimagesize($filename);

    $width = $orig_width;
    $height = $orig_height;
    // echo $filename;
    # taller
    if ($height > $max_height) {
        $width = ($max_height / $height) * $width;
        $height = $max_height;
    }

    # wider
    if ($width > $max_width) {
        $height = ($max_width / $width) * $height;
        $width = $max_width;
    }

    $image_p = imagecreatetruecolor($width, $height);

    $image = imagecreatefromjpeg($filename);

    imagecopyresampled(
        $image_p,
        $image,
        0,
        0,
        0,
        0,
        $width,
        $height,
        $orig_width,
        $orig_height
    );

    return $image_p;
}