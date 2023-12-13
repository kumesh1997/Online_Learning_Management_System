<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">

	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="description" content="Creativeitem Software Installation" />
	<meta name="author" content="Creativeitem" />

	<title>Installation | Academy-LMS</title>
	<?php include 'styles.php'; ?>
</head>
<body class="page-body">

<div class="page-container horizontal-menu">


	<header class="navbar navbar-fixed-top" style="min-height: 80px; background: linear-gradient(135deg,#0F2027 0,#2C5364 60%) !important;">
		<div class="navbar-inner">
			<!-- logo -->
			<div class="navbar-brand">
				<a href="#">
					<img src="<?php echo base_url('uploads/system/logo-light.png');?>"  style="max-height:40px;"/>
				</a>
			</div>
	    	<div class="navbar-brand pull-right" style="margin-top: 13px;">
	        	Installation
	      	</div>
		</div>
	</header>
	<div class="main-content">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
          			<?php include 'main/'.$page_name.'.php'; ?>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
          			<?php include 'footer.php'; ?>
				</div>
			</div>

		</div>
	</div>

<?php include 'scripts.php'; ?>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2pdf.js@0.9.3/dist/html2pdf.bundle.js"></script>
</body>
</html>
