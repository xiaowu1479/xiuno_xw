<?php
!defined('DEBUG') AND exit('Access Denied.');
$msg = '标签(TAG)管理已集成到【板块编辑】页面中，请前往：后台 → 板块 → 编辑板块 → 在表单底部找到"标签(TAG)"设置区域进行管理。';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>TAG 标签管理</title>
<link href="<?php echo $conf['view_url'];?>css/bootstrap.min.css<?php echo $static_version;?>" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
	<div class="card">
		<div class="card-body text-center py-5">
			<h4 class="text-muted mb-4"><?php echo lang('tag');?> - 增强版 v2.4</h4>
			<div class="alert alert-info">
				<i class="icon icon-info-circle"></i> 
				<?php echo $msg;?>
			</div>
			<a href="<?php echo url('forum');?>" class="btn btn-primary mr-2">返回首页</a>
			<a href="<?php echo url('admin-forum');?>" class="btn btn-secondary">管理板块</a>
		</div>
	</div>
</div>
</body>
</html>