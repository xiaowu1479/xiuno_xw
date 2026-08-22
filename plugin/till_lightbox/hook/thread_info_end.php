$preg_lightbox = preg_match_all('/<img[^>]*src=["\'](.*?)["\'][^>]*>/i',$first['message_fmt'],$preg_lightbox_result);

if($preg_lightbox){

    for($i=0;$i<count($preg_lightbox_result[0]);$i++){

        $from=$preg_lightbox_result[0][$i];
        $to='<a href="' . $preg_lightbox_result[1][$i] . '" data-lightbox><img class="img-thumbnail" src="' . $preg_lightbox_result[1][$i] . '" /></a>' ;
        $first['message_fmt'] = str_replace($from,$to,$first['message_fmt']);

        }
    
}