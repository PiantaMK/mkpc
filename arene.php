<?php
$lettres = Array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'o', 't');
$nbLettres = count($lettres);
$infos = Array();
if (isset($_GET['id'])) {
	include('initdb.php');
	$id = $_GET['id'];
	if ($getMain = mysql_fetch_array(mysql_query('SELECT map,nom,auteur,identifiant,identifiant2,identifiant3,identifiant4 FROM `mkcircuits` WHERE id="'. $id .'" AND type'))) {
		include('getId.php');
		if ((($identifiants[0]==$getMain['identifiant'])&&($identifiants[1]==$getMain['identifiant2'])&&($identifiants[3]==$getMain['identifiant3'])&&($identifiants[3]==$getMain['identifiant4'])) || ($identifiants[0] == 1390635815)) {
			$map = $getMain['map'];
			$cName = $getMain['nom'];
			$cPseudo = $getMain['auteur'];
			$pieces = mysql_query('SELECT * FROM `mkp` WHERE circuit="'.$id.'"');
			while ($piece = mysql_fetch_array($pieces))
				${'p'.$piece['id']} = $piece['piece'];
			$getPos = mysql_query('SELECT * FROM `mkr` WHERE circuit="'.$id.'"');
			while ($pos = mysql_fetch_array($getPos)) {
				$infos['s'.$pos['id']] = $pos['s'];
				$infos['r'.$pos['id']] = $pos['r'];
			}
			for ($i=0;$i<$nbLettres;$i++) {
				$lettre = $lettres[$i];
				$getInfos = mysql_query('SELECT x,y FROM `mk'.$lettre.'` WHERE circuit="'.$id.'"');
				for ($j=0;$info=mysql_fetch_array($getInfos);$j++)
					$infos[$lettre.$j] = $info['x'].','.$info['y'];
			}
		}
	}
	mysql_close();
}
else {
	include('escape_all.php');
	if (isset($_GET['nid']))
		$id = $_GET['nid'];
	$pieces = Array(5,0,0,0,0,4,1,11,11,11,11,3,1,11,11,11,11,3,1,11,11,11,11,3,1,11,11,11,11,3,6,2,2,2,2,7);
	for ($i=0;$i<36;$i++)
		${"p$i"} = (isset($_GET["p$i"])) ? $_GET["p$i"] : $pieces[$i];
	$positions = Array(
		Array(1, 0),
		Array(3, 0),
		Array(11, 3),
		Array(23, 3),
		Array(34, 2),
		Array(32, 2),
		Array(24, 1),
		Array(12, 1)
	);
	for ($i=0;$i<8;$i++) {
		$iPos = $positions[$i];
		$infos["s$i"] = isset($_GET["s$i"]) ? $_GET["s$i"] : $iPos[0];
		$infos["r$i"] = isset($_GET["r$i"]) ? $_GET["r$i"] : $iPos[1];
	}
	unset($pieces);
	$map = (isset($_GET["map"])) ? $_GET["map"] : 9;
	for ($i=0;$i<$nbLettres;$i++) {
		$lettre = $lettres[$i];
		for ($j=0;isset($_GET[$lettre.$j]);$j++)
			$infos[$lettre.$j] = $_GET[$lettre.$j];
	}
}
$snes = ($map <= 13);
include('language.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $language ? 'en':'fr'; ?>" >
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico" />
<?php
include('o_online.php');
?>
<title><?php echo $language ? 'Create a course Mario Kart':'Cr&eacute;er une ar&egrave;ne Mario Kart'; ?></title>
<style type="text/css">
body {
	margin-left: 650px;
	color: white;
	background-color: #030316;
	background-image: url('../images/editor/fond_stars.jpg');
}
a {
	color: white;
}
#circuit {
	position: absolute;
	left: 0px;
	top: 0px;
	width: 600px;
	height: 600px;
}
#circuit img {
	position: absolute;
	width: 100px;
}
img {
	cursor: pointer;
}
#valider input {
	font-weight: bold;
	font-size: 20px;
	margin-left: 10px;
	cursor: pointer;
    background-color: #249;
    border-color: #57c;
    color: #abf;
}
#valider input:hover {
	background-color: #26a;
	border-color: #59B;
}
#valider a {
	font-size: 12pt;
	font-weight: bold;
}
#choose {
	display: none;
	position: fixed;
	z-index: 15;
	margin: 0;
	padding: 5px;
	background-color: #36F;
}
#barre {
	display: block;
	text-align: right;
	width: 99%;
	padding: 4px;
	background-color: #036;
	font-weight: bold;
	font-size: 16px;
	cursor: move;
}
#barre:hover {
	background-color: #339;
}
#barre:active {
	background-color: blue;
}
#barre a:hover {
	color: aqua;
}
.cPiece {
	border: solid 2px #038;
}
.editor-section {
	display: inline-block;
	padding: 5px 12px;
	background-color: rgba(76,70,94, 0.5);
	border-radius: 5px;
}
#pPiece img {
	z-index: 10;
}
#deleteAll {
	margin-left: 0;
}
#deleteAllCtn {
	display: block;
	margin-top: 8px;
	margin-bottom: 2px;
	text-align: center;
}
.startposition {
	display: block;
	position: absolute;
	z-index: 10;
	font-weight: bold;
	opacity: 0.5;
	cursor: pointer;
}
.startposition:hover {
	opacity: 1;
}
.startposition img {
	position: absolute;
	z-index: -1;
}
#advice {
	margin-top: 0;
	font-size: 18px;
}
#advice a {
	color: #CCF;
}
</style>
<script type="text/javascript" src="scripts/create.js?reload=4"></script>
</head>
<body>
<div id="circuit">
<?php
for ($i=0;$i<36;$i++)
	echo '<img src="images/pieces/piececircuit'.$map.'_'. ${"p$i"} .'.png" alt="Piece Mario Kart" style="left: '. ($i%6)*100 .'px; top: '. floor($i/6)*100 .'px;" onmouseover="survol('.$i.')" onclick="change('.$i.')" ondblclick="c=11;var cMap=currentMap();if(cMap==26||cMap==27||cMap==28||cMap==30)c=10;appliquer()" />';
?>
</div>
<p id="pPieces" class="editor-section">
<?php
function objet($infos,$l,$m,$n=null) {
	global $snes;
	if (($n == null) || $snes)
		$n = $l;
	$retour = '<span id="'.$l.'">';
	for ($i=0;isset($infos[$l.$i]);$i++) {
		$getCoords = $infos[$l.$i];
		$retour .= '<img src="images/pieces/piececircuit_'.$n.$m.'.png" alt="'.$l.'" id="'.$l.$i.'" style="position: absolute; left: '.preg_replace("#^(\d+),\d+#", "$1", $getCoords).'px; top: '.preg_replace("#\d+,(\d+)$#", "$1", $getCoords).'px; cursor: pointer;" onload="centerPos(this)" onclick="deplacer(event, this, false)" />';
	}
	return $retour.'<img src="images/pieces/piececircuit_'.$n.$m.'.png" alt="'.$l.'" id="'.$l.$i.'" style="cursor: pointer;" onclick="deplacer(event,this,true);ajouter(this.alt,parseInt(this.id.match(/\d+$/g))+1)" /></span>';
}
echo objet($infos,'o',null).' &nbsp; '.objet($infos,'a',null,'p').' '.objet($infos,'b',null,'q').' '.objet($infos,'c',null,'r').' '.objet($infos,'d',null,'s').' &nbsp; '.objet($infos,"t",$map).'<br />
'.objet($infos,'e',null).' '.objet($infos,'f',null).' &nbsp; '.objet($infos,'g',null).' '.objet($infos,'h',null).' &nbsp; '.objet($infos,'i',null).' '.objet($infos,'j',null);
?>
<span id="deleteAllCtn">
	<input type="button" value="<?php echo $language ? 'Delete all':'Tout supprimer'; ?>" id="deleteAll" onclick="deleteAll('<?php echo $language ? 'Delete all pieces of this circuit ?':'Supprimer toutes les pi\xE8ces de ce circuit ?'; ?>')" />
</span>
</p>
<form method="get" action="arena.php">
<div class="editor-section adv-opt">
<?php
if (isset($_GET['cl']))
	echo '<input type="hidden" name="cl" value="'. htmlspecialchars($_GET['cl']) .'" />';
?>
Type : <select name="map" onchange="changeMap(this.value);this.blur()">
<optgroup label="SNES">
<?php
$bValue = $language ? 'Battle course ':'Ar&egrave;ne  bataille ';
for ($i=9;$i<13;$i++)
	echo '<option value="'.$i.'" '. ($map!=$i ? null : 'selected="selected"') .'>'.$bValue.($i-8).'</option>';
?>
</optgroup>
<optgroup label="GBA">
<?php
$bValues = array(
	$bValue.'1',
	$bValue.'2 - '.($language ? 'Thin':'Fine'),
	$bValue.'2 - Large',
	$bValue.'3',
	$bValue.'4'
);
for ($i=26;$i<=30;$i++)
	echo '<option value="'.$i.'" '. ($map!=$i ? null : 'selected="selected"') .'>'.$bValues[$i-26].'</option>';
?>
</optgroup>
</select>
</div>
<p id="pieces">
<?php
if (isset($id))
	echo '<input type="hidden" name="nid" value="'.$id.'" />';
for ($i=0;$i<36;$i++)
echo '<input type="hidden" name="p'.$i.'" value="'.${"p$i"}.'" />';
$lettres = Array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'o', 't');
foreach ($lettres as $l) {
	for ($i=0;isset($infos[$l.$i]);$i++)
	echo '<input type="hidden" name="'.$l.$i.'" value="'.$infos[$l.$i].'" />';
}
for ($i=0;$i<8;$i++) {
	${"s$i"} = (isset($infos["s$i"]) && isset($infos["r$i"])) ? Array($infos["s$i"],$infos["r$i"]) : $positions[$i];
	echo '<input type="hidden" name="s'.$i.'" value="'.${"s$i"}[0].'" /><input type="hidden" name="r'.$i.'" value="'.${"s$i"}[1].'" />';
}
?>
</p>
<p id="valider"><input type="submit" value="&nbsp; <?php echo $language ? 'Create course':'Cr&eacute;er ar&egrave;ne'; ?> &nbsp;" /></p>
<p><?php
$PJ = $language ? 'P':'J';
for ($i=0;$i<8;$i++)
	echo '<span class="startposition" style="left: '.((${"s$i"}[0]%6)*100+25).'px; top: '.(floor(${"s$i"}[0]/6)*100+25).'px" title="'. ($language ? 'Double-clic to rotate':'Double-clic pour pivoter') .'" onclick="inClick(this, '.$i.')" ondblclick="var kart=this.getElementsByTagName(\'img\')[0];var newR=(kart.src.replace(/000/g,\'\').match(/[0-9]/)[0]*1+1)%4;document.forms[0].r'.$i.'.value=newR;kart.src=\'images/pieces/piececircuit_s\'+newR+\'.png\';return false" oncontextmenu="event.preventDefault();this.ondblclick()" onmousedown="return false"><img src="images/pieces/piececircuit_s'.${"s$i"}[1].'.png" alt="kart" />&nbsp;<br />&nbsp;'.$PJ.($i+1).'</span>';
?></p>
<?php
if ($language) {
	?>
	<p>&nbsp;</p>
	<?php
}
else {
	?>
	<p id="advice">
		Lien utile pour d&eacute;marrer : <a href="topic.php?topic=739" target="_blank">Conseils pour cr&eacute;er un circuit/ar&egrave;ne</a>	
	</p>
	<?php
}
?>
<p><?php
if ($language) {
	?>
Characters on the map <img src="images/pieces/piececircuit_s2.png" alt="perso" style="width: 14px; cursor: default" /> are used to define start positions of players and their direction (double-clic on the image to change direction).<br />
The P1, P2, P3, P4, P5, P6, P7 and P8 define the position used for when you play with less than 8 players.<br />
For instance, if you play with only 4 participants, players start at position P1, P2, P3 and P4.
Note that you control the P1 player.
	<?php
}
else {
	?>
Les persos sur la carte <img src="images/pieces/piececircuit_s2.png" alt="perso" style="width: 14px; cursor: default" /> servent &agrave; indiquer les positions de d&eacute;part des joueurs ainsi que leur direction (faites un double-clic sur l'image pour changer la direction).<br />
Les J1, J2, J3, J4, J5, J6, J7 et J8 permettent de d&eacute;finir les positions utilis&eacute;es lorsque vous jouez &agrave; moins de 8 joueurs.<br />
Ainsi, si vous choisissez de jouer avec seulement 4 participants, les joueurs commenceront aux positions J1, J2, J3 et J4.
Notez que vous contr&ocirc;lez le joueur en J1.
	<?php
}
?></p>
</form>
<p id="choose" style="left: 650px; top: 100px;">
<span id="barre" onmousedown="pos(event)" onmouseup="document.body.onmousemove=undefined"><a href="javascript:fermer()" title="Fermer (&Eacute;chap)"><?php echo $language ? 'Close':'Fermer'; ?></a></span>
<?php
for($i=0;$i<16;$i++)
	echo '<img class="cPiece" src="images/pieces/piececircuit'.$map.'_'.$i.'.png" alt="Piece '.$i.'" onmouseover="apercu('.$i.')" onmouseout="disappear()" onclick="appliquer('.$i.')" />'.(($i+1)%4 ? ' ' : '<br />');
?>
</p>
<a href="create.php"><?php echo $language ? 'Create a VS circuit':'Cr&eacute;er un circuit VS'; ?></a><br />
<a href="mariokart.php"><?php echo $language ? 'Back to Mario Kart PC':'Retour &agrave; Mario Kart PC'; ?></a></p>
</body>
</html>