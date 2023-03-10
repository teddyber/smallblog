<?php
$f3 = require('fff/base.php');

$f3->set('title', 'Famille Carlier');
$f3->set('email', 'ne-pas-repondre@bcarlier.net');

$f3->set('CACHE', FALSE);
$f3->set('DEBUG', 3);
$f3->set('UI', 'ui/');
$f3->set('hastext', false);
$f3->set('message', true);
include(dirname(__FILE__) . '/conn.php');
$db = new DB\SQL("mysql:host=$host;port=3306;dbname=$db", $user, $pwd);
$f3->set('db', $db);

$f3->route(
    'GET /',
    function ($f3) {
        auth($f3);

        $message = new DB\SQL\Mapper($f3->get('db'), 'messages');
        $message->load('', array('order' => 'message_date DESC', 'limit' => 20));
        while (!$message->dry()) {
            $message->message_date = date('d F Y à H:i', strtotime($message->message_date));
            $message->message_date = str_replace(
                array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'), 
                array('Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'), 
                $message->message_date);

            $m = $message->cast();

            $author = new DB\SQL\Mapper($f3->get('db'), 'personnes');
            $author->load(array('id=?', $message['user']));
            $m['author'] = $author->prenom . " " . $author->nom;
            $m['isauthor'] = $message->user == $f3->get('SESSION.id');

            $messages[] = $m;

            $message->skip();
        }
        $f3->set('page', false);
        $f3->set('navbar', true);
        $f3->set('messages', $messages);
        $f3->set('content', 'messages.html');
        echo \Template::instance()->render('home.html');
    }
);
$f3->route(
    'GET /page/@id',
    function ($f3) {
        auth($f3);

        $message = new DB\SQL\Mapper($f3->get('db'), 'messages');
        $message->load('', array('order' => 'message_date DESC', 'limit' => 20, 'offset' => 20 * $f3->get('PARAMS.id')));
        while (!$message->dry()) {
            $message->message_date = date('d F Y à H:i', strtotime($message->message_date));
            $message->message_date = str_replace(
                array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'), 
                array('Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'), 
                $message->message_date);
            $m = $message->cast();

            $author = new DB\SQL\Mapper($f3->get('db'), 'personnes');
            $author->load(array('id=?', $message['user']));
            $m['author'] = $author->prenom . " " . $author->nom;
            $m['isauthor'] = $message->user == $f3->get('SESSION.id');

            $messages[] = $m;

            $message->skip();
        }
        $f3->set('page', true);
        $f3->set('messages', $messages);
        $f3->set('content', 'messages.html');
        echo \Template::instance()->render('messages.html');
    }
);
$f3->route(
    'POST /addmessage',
    function ($f3) {
        auth($f3);

        $message = new DB\SQL\Mapper($f3->get('db'), 'messages');
        $message->text = $f3->get('POST.text');
        $message->user = $f3->get('SESSION.id');
        // print_r($f3->get('FILES'));
        if ($f3->get('FILES.photo.tmp_name') != '') {
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
    'GET /delete/@id',
    function ($f3) {
        auth($f3);

        $message = new DB\SQL\Mapper($f3->get('db'), 'messages');
        $message->load(array('id=?', $f3->get('PARAMS.id')));
        if ($message->user == $f3->get('SESSION.id'))
            $message->erase();
        $f3->reroute('/');
    }
);
$f3->route(
    'GET /pic/@id',
    function ($f3) {
        auth($f3);


        $message = new DB\SQL\Mapper($f3->get('db'), 'messages');
        $message->load(array('id=?', $f3->get('PARAMS.id')));
        header("Content-type: image/jpeg");
        echo $message->photo;
    }
);
$f3->route(
    'GET /picauthor/@id',
    function ($f3) {
        auth($f3);

        $author = new DB\SQL\Mapper($f3->get('db'), 'personnes');
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
    'GET /login',
    function ($f3) {
        if ($f3->exists('GET.success')) $f3->set('success', true);
        else $f3->set('success', false);
        if ($f3->exists('GET.error')) $f3->set('error', true);
        else $f3->set('error', false);
        if ($f3->exists('GET.email')) $f3->set('email', true);
        else $f3->set('email', false);
        $f3->set('content', 'login.html');
        echo \Template::instance()->render('home.html');
    }
);
$f3->route(
    'POST /login',
    function ($f3) {
        if ($f3->exists('POST.forgot')) { //password recover
            $token = urlencode(base64_encode(openssl_encrypt($f3->get('POST')['login'], 'aes128', 'random_key', true, 'iv12345678901234')));
            echo mail(
                $f3->get('POST')['login'],
                'Réinitialisation du mot de passe',
                'Pour modifier votre mot de passe sur le site familial, veuillez cliquer sur <a href="https://' . $f3->get('SERVER')['HTTP_HOST'] . $f3->get('SERVER')['REQUEST_URI'] . '/reset?token=' . $token . '">ce lien</a>',
                array(
                    'From' => $f3->get('email'),
                    'Reply-To' => $f3->get('email'),
                    'MIME-Version' => '1.0',
                    'Content-type' => 'text/html; charset=UTF-8'
                )
            );
            // die;
            $f3->reroute('/login?email');
        } else {
            $user = new DB\SQL\Mapper($f3->get('db'), 'personnes');
            $user->load(array('courriel=?', $f3->get('POST.login')));

            if (password_verify($f3->get('POST.password'), $user->password)) {
                //logged in!
                $user->last_login = date("Y-m-d H:i:s");
                $user->save();
                $f3->set('SESSION.id', $user->id);
                $f3->reroute('/');
            } else  $f3->reroute('/login?error');
        }
    }
);
$f3->route(
    'GET /login/reset',
    function ($f3) {
        $f3->set('content', 'reset.html');
        echo \Template::instance()->render('home.html');
    }
);
$f3->route(
    'POST /login/reset',
    function ($f3) {
        $email = openssl_decrypt(base64_decode(urldecode($f3->get('POST.token'))), 'aes128', 'random_key', true, 'iv12345678901234');
        // echo urldecode('wGmTLtjHEYqydD03LhtkCC9e6kQlIFyYweH6rGl%2FTSs%3D');
        // echo base64_decode(urldecode('wGmTLtjHEYqydD03LhtkCC9e6kQlIFyYweH6rGl%2FTSs%3D'));
        // echo '_'.openssl_decrypt(base64_decode(urldecode($f3->get('POST.token'))), 'aes128', 'random_key', true, 'iv12345678901234').'-';
        $password = password_hash($f3->get('POST.password'), PASSWORD_BCRYPT);

        $user = new DB\SQL\Mapper($f3->get('db'), 'personnes');
        $user->load(array('courriel=?', $email));
        if (!$user->dry()) {
            $user->password = $password;
            $user->save();
        }
        // die;
        $f3->reroute('/login?success');
    }
);
$f3->route('GET /annuaire', function ($f3) {
    auth($f3);
    $f3->reroute('/annuaire/1');
});
$f3->route('GET /annuaire/@id', function ($f3) {
    auth($f3);
    $id = $f3->get('PARAMS.id');
    $f3->set('hastext', true);
    $f3->set('messages', false);
    $f3->set('annuaire', true);
    $f3->set('navbar', true);
    $f3->set('text', '<div class="table-responsive"><table><thead><th>Nom</th><th>Né(e) le</th><th>Adresse</th><th>Téléphone fixe</th><th>Téléphone portable</th><th>Courriel</th></thead>' 
    . renderPersonneTable($id, $f3)) . "</table></div>";
    echo \Template::instance()->render('home.html');
});
$f3->run();


function renderPersonneTable($id, $f3, $level=0)
{
    $f3->set('level', $level);
    $f3->set('class', '');
    $p='';

    //TODO spouse
    $mariage = new DB\SQL\Mapper($f3->get('db'), 'mariage');
    $mariage->load(array('mari_id=? OR femme_id=?', $id, $id), array('order'=> 'ddm'));
    // echo $f3->get('db')->log();
    $spouse=false;
    $i=0;
    while (!$mariage->dry()) {
        $i++;
        $spouse = true;
        $user = new DB\SQL\Mapper($f3->get('db'), 'personnes');
        $user->load(array('id=?', ($id == $mariage->mari_id ? $mariage->femme_id : $mariage->mari_id)));
        $user->ddn = $user->ddn=='0000-00-00'?'':$user->ddn = date('d/m/Y', strtotime($user->ddn));
        $user->ddd = $user->ddd=='0000-00-00'?'0000-00-00':$user->ddd = date('d/m/Y', strtotime($user->ddd));

        $f3->set('user', $user);
        $f3->set('class', 'spouse wed');
        $f3->set('level', $level);
        $p .= \Template::instance()->render('userRow.html');

        $rows = $f3->get('db')->exec(
            'SELECT f1.fils_id FROM filiation f1, filiation f2, personnes' .
                ' WHERE (f1.pere_id=' . $id . ' AND f2.pere_id=' . $user->id . ') AND f1.fils_id=f2.fils_id AND personnes.id=f1.fils_id' .
                ' ORDER BY ddn',
        );
        foreach ($rows as $key => $r) {
            $p .= renderPersonneTable($r['fils_id'], $f3, $level+1);
        }
        $mariage->skip();
    }
    $user = new DB\SQL\Mapper($f3->get('db'), 'personnes');
    $user->load(array('id=?', $id));
    $user->ddn = $user->ddn=='0000-00-00'?'':$user->ddn = date('d/m/Y', strtotime($user->ddn));
    $user->ddd = $user->ddd=='0000-00-00'?'0000-00-00':$user->ddd = date('d/m/Y', strtotime($user->ddd));

    $f3->set('user', $user);
    $f3->set('level', $level);
    $spouse = $i>0?true:false;

    if ($spouse) $f3->set('class', 'wed');
    $p = \Template::instance()->render('userRow.html') . $p;

    return $p;
}
function renderPersonne($id, $f3)
{
    $enfant = new DB\SQL\Mapper($f3->get('db'), 'filiation');
    $enfant->load(array('pere_id=?', $id));
    $t = '';
    while (!$enfant->dry()) {
        $t .= renderPersonne($enfant->fils_id, $f3);
        $enfant->skip();
    }
    $user = new DB\SQL\Mapper($f3->get('db'), 'personnes');
    $user->load(array('id=?', $id));
    $f3->set('user', $user);
    $p = \Template::instance()->render('user.html');

    return '<div class="personne">' . $p . ($t == '' ? '' : $t) . '</div>';
}



function auth($f3)
{
    if (!$f3->exists('SESSION.id'))
        $f3->reroute('/login');
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
    $exif = exif_read_data($filename, 'IFD0');
    if (isset($exif['Orientation'])) {
        if ($exif['Orientation'] == 3) {
            $image_p = imagerotate($image_p, 180, 0);
        }
        if ($exif['Orientation'] == 6) {
            $image_p = imagerotate($image_p, 270, 0);
        }
        if ($exif['Orientation'] == 8) {
            $image_p = imagerotate($image_p, 90, 0);
        }
    }
    return $image_p;
}
