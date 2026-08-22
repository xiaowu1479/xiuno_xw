$is_this_page = param(2, '');
if($is_this_page === 'hot') {
$hotlist = threadlist_read_hot($pagesize);
$pagination = '';
$thread_list_from_default = 0;
$active = 'hot';

$hotlist_tids = arrlist_values($hotlist, 'tid');
$threadlist = thread_find_by_tids($hotlist_tids,array('views'=>-1));
}