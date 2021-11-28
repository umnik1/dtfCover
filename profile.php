<?php
  putenv('GDFONTPATH=' . realpath('.'));

  $token = ''; // DTF Token
  $lastfm_user = ''; // LAST.FM Username
  $lastfm_key = ''; // LAST.FM API key
  $shikimori_name = ''; // Shikimori username

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL,"https://api.dtf.ru/v1.8/user/me");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); 
  
  $headers = [
      'X-Device-Token: '.$token.'',
      'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:28.0) Gecko/20100101 Firefox/28.0'
  ];
  
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  
  $user_info = curl_exec ($ch);
  
  curl_close ($ch);

  // DTF
  $result = json_decode($user_info)->result;

  $posts = $result->counters->entries;
  $comments = $result->counters->comments;
  $favorites = $result->counters->favorites;

  // Lastfm
  $json = file_get_contents('https://ws.audioscrobbler.com/2.0/?method=user.getinfo&user='.$lastfm_user.'&api_key='.$lastfm_key.'&format=json');
  $obj = json_decode($json);

  $music_count = $obj->user->playcount;

  $json = file_get_contents('https://ws.audioscrobbler.com/2.0/?method=user.gettoptracks&user='.$lastfm_user.'&api_key='.$lastfm_key.'&period=7day&limit=1&format=json');
  $obj = json_decode($json);

  $artist_name = $obj->toptracks->track[0]->artist->name;
  $artist_track = $obj->toptracks->track[0]->name;

  $json = file_get_contents(str_replace(' ', '%20', 'https://ws.audioscrobbler.com/2.0/?method=track.getInfo&api_key='.$lastfm_key.'&artist='.$artist_name.'&track='.$artist_track.'&format=json'));
  $obj = json_decode($json);

  $track_image = (array)$obj->track->album->image[0];
  $track_image = $track_image['#text'];

  $full_track = $artist_name.' - '.$artist_track;

  // Shikimori
  $json = file_get_contents('https://shikimori.one/api/users/'.$shikimori_name.'/history');
  $obj = json_decode($json);
  $anime_text = strip_tags($obj[0]->description);
  $anime_name = $obj[0]->target->name;
  $anime_image = 'https://shikimori.one'.$obj[0]->target->image->preview;

  $image = imagecreatefrompng('dtf_text.png');
  imagealphablending($image, true);
  
  $red = imagecolorallocate($image, 117,254,245);

  $font_path = realpath('os.ttf');

  // Статистика по дтф
  imagefttext($image, 20, 0, 250, 50, $red, $font_path, $comments);
  imagefttext($image, 20, 0, 250, 89, $red, $font_path, $posts); 
  imagefttext($image, 20, 0, 250, 131, $red, $font_path, $favorites);

  // Музыка
  imagefttext($image, 15, 0, 210, 225, $red, $font_path, $full_track);
  $src = imagecreatefrompng($track_image);
  imagecopymerge($image, $src, 165, 200, 0, 0, 34, 34, 100);
  imagedestroy($src);
  imagefttext($image, 15, 0, 402, 268, $red, $font_path, $music_count);

  // Аниме
  imagefttext($image, 10, 0, 370, 80, $red, $font_path, $anime_name);
  imagefttext($image, 10, 0, 370, 100, $red, $font_path, $anime_text);
  $src = imagecreatefromjpeg($anime_image);
  // получение новых размеров
  $percent = 0.5;
  list($width, $height) = getimagesize($anime_image);
  $new_width = $width * $percent;
  $new_height = $height * $percent;

  // ресэмплирование
  $image_p = imagecreatetruecolor($new_width, $new_height);
  $anime_image = imagecreatefromjpeg($anime_image);

  imagecopyresampled($image_p, $anime_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
  
  imagecopymerge($image, $image_p, 600, 20, 0, 0, 75, 110, 100);
  imagedestroy($src);
  
  $filename = 'generated_image.png';
  ImagePng($image, $filename);
  imagedestroy($image);


  // Загружаем файл
  $cf = new CURLFile("generated_image.png");

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL,"https://api.dtf.ru/v1.8/uploader/upload");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, ["file" => $cf]);

  $headers = [
      'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:28.0) Gecko/20100101 Firefox/28.0'
  ];

  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

  $file = curl_exec($ch);

  // Обновляем обложку
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL,"https://api.dtf.ru/v1.8/user/me/save_cover");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, ["cover" => json_encode(json_decode($file)->result[0])]);

  $headers = [
      'X-Device-Token: '.$token.'',
      'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:28.0) Gecko/20100101 Firefox/28.0'
  ];

  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

  $uploader = curl_exec($ch);