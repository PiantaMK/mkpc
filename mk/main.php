<?php
if (in_array($_SERVER['HTTP_HOST'], array('local-mkpc.malahieude.info','mkpc-translations.000webhostapp.com')))
	echo '<script type="text/javascript" src="scripts/mk.js?reload=4"></script>';
else
	echo '<script type="text/javascript" src="scripts/mk.v74.js"></script>';
?>