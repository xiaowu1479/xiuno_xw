if(!isset($runtime['digests'])) {
	runtime_set('digests', thread_digest_count());
}
