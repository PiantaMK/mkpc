<?php
$getCourse = mysql_fetch_array(mysql_query('SELECT course,banned FROM `mkjoueurs` WHERE id="'.$id.'"'));
$course = $getCourse['course'];
if ($course && !$getCourse['banned']) {
	$isBattle = isset($_POST['battle']);
	$pts_ = 'pts_'.($isBattle ? 'battle':'vs');
	if (mysql_numrows(mysql_query('SELECT * FROM `mariokart` WHERE id='.$course.' AND time<='. time())))
		mysql_query('UPDATE `mkjoueurs` SET course=0 WHERE course='.$course.' AND choice_map=0');
	function getMapData() {
		global $course;
		$res = mysql_fetch_array(mysql_query('SELECT m.map,m.time,m.link,o.rules FROM `mariokart` m LEFT JOIN `mkgameoptions` o ON m.link=o.id WHERE m.id='. $course));
		if (!$res['rules'])
			$res['rules'] = '{}';
		return $res;
	}
	$getMap = getMapData();
	$map = $getMap['map'];
	$time = $getMap['time'];
	$continuer = ($map == -1);
	$allChosen = true;
	$joueurs = mysql_query('SELECT choice_map FROM `mkjoueurs` WHERE course='. $course .' ORDER BY id');
	for ($i=0;$joueur=mysql_fetch_array($joueurs);$i++) {
		if (!$joueur['choice_map']) {
			$continuer = false;
			$allChosen = false;
			break;
		}
	}
	$now = round((time()+microtime())*1000);
	$courseRules = json_decode($getMap['rules']);
	if ($continuer) {
		$map = rand(0, mysql_numrows($joueurs)-1);
		$time = $now+5000;
		if (!empty($courseRules->manualTeams))
			$time += 12000;
		mysql_query('UPDATE `mariokart` SET map='. $map .', time='.$time.' WHERE id='. $course);
		$isLocal = !empty($courseRules->friendly) && !empty($courseRules->localScore);
		$joueurs = mysql_query('SELECT j.id,'.($isLocal ? 'IFNULL(r.pts,0) AS pts':'j.'.$pts_.' AS pts').' FROM `mkjoueurs` j LEFT JOIN `mkplayers` p ON j.id=p.id'. ($isLocal ? ' LEFT JOIN `mkgamerank` r ON r.game='.$getMap['link'].' AND j.id=r.player':'') .' WHERE j.course='. $course .' ORDER BY p.place,j.id');
		$nConnect = round($time/67);
		$playerIds = array();
		for ($i=0;$joueur=mysql_fetch_array($joueurs);$i++) {
			$playerIds[] = $joueur['id'];
			$toUpate = 'course='.$course.',controller=0,aPts='. $joueur['pts'] .',connecte='.$nConnect.',tours=1,ballons=1,reserve=4,place='.($i+1);
			mysql_query('INSERT INTO `mkplayers` SET id='. $joueur['id'].','.$toUpate.' ON DUPLICATE KEY UPDATE '.$toUpate);
		}
		$nbPlayers = $i;
		mysql_query('DELETE p FROM `mkplayers` p INNER JOIN `mkjoueurs` j ON p.id=j.id WHERE p.course='.$course.' AND j.course!='.$course);
		if (isset($courseRules->cpuCount) && ($nbPlayers > 1) && ($nbPlayers < $courseRules->cpuCount)) {
			$existingCpus = mysql_query('SELECT p.id FROM `mkplayers` p LEFT JOIN `mkjoueurs` j ON p.id=j.id WHERE p.course='.$course.' AND j.id IS NULL ORDER BY p.id');
			$cpuIds = array();
			while ($existingCpu = mysql_fetch_array($existingCpus)) {
				$cpuIds[] = $existingCpu['id'];
				$i++;
				if ($i >= $courseRules->cpuCount)
					break;
			}
			if ($i < $courseRules->cpuCount) {
				$initialId = 2000000000;
				$minAvailableId = mysql_fetch_array(mysql_query('SELECT IFNULL(MAX(p.id)+1,'.$initialId.') AS id FROM `mkplayers` p LEFT JOIN `mkjoueurs` j ON p.id=j.id WHERE j.id IS NULL'));
				$cpuId = $minAvailableId['id'];
				do {
					$toUpate = 'course='.$course.',controller='.$playerIds[($i-$nbPlayers)%$nbPlayers].',aPts='.($isLocal ? 0:5000).',connecte='.$nConnect.',tours=1,ballons=1,reserve=4,place='.($i+1);
					$maxiter = 10;
					while (!mysql_query('INSERT INTO `mkplayers` SET id='.$cpuId.','.$toUpate)) {
						$cpuId++;
						$maxiter--;
						if (!$maxiter) {
							$cpuId = 0;
							break;
						}
					}
					$cpuIds[] = $cpuId;
					$cpuId++;
					$i++;
				} while ($i < $courseRules->cpuCount);
			}
			$cpuIdsString = implode(',', $cpuIds);
			mysql_query('UPDATE `mkplayers` p LEFT JOIN `mkjoueurs` j ON p.id=j.id SET p.course=(CASE WHEN p.id IN ('. $cpuIdsString .') THEN '. $course .' ELSE 0 END) WHERE p.id IN ('. $cpuIdsString .') OR (p.course='.$course.' AND j.id IS NULL)');
			if ($isLocal)
				mysql_query('UPDATE `mkplayers` p LEFT JOIN `mkgamerank` r ON r.game='.$getMap['link'].' AND p.id=r.player SET p.aPts=IFNULL(r.pts,0) WHERE p.course='.$course.' AND p.controller!=0');
		}
		else
			mysql_query('DELETE FROM `mkplayers` WHERE course='. $course .' AND controller!=0');
	}
	function listPlayers() {
		global $course, $pts_;
		$joueurs = mysql_query(
			'(SELECT j.id,j.'.$pts_.' AS pts,j.joueur,IFNULL(p.place,1) AS place,IFNULL(p.team,-1) AS team,j.choice_map,j.choice_rand,j.nom,0 AS controller FROM `mkjoueurs` j LEFT JOIN `mkplayers` p ON j.id=p.id WHERE j.course='. $course .')
			UNION
			(SELECT id,5000 AS pts,NULL AS joueur,IFNULL(place,1) AS place,IFNULL(team,-1) AS team,1 AS choice_map,1 AS choice_rand,NULL AS nom,controller FROM `mkplayers` WHERE course='. $course .' AND controller!=0)
			ORDER BY id'
		);
		$joueursData = array();
		while ($joueur=mysql_fetch_array($joueurs))
			$joueursData[] = $joueur;
		return $joueursData;
	}
	$joueursData = listPlayers();
	$nbPlayers = 0;
	foreach ($joueursData as &$joueur) {
		if (!$joueur['controller'])
			$nbPlayers++;
	}
	unset($joueur);
	$minPlayers = isset($courseRules->minPlayers) ? $courseRules->minPlayers : 2;
	$nbJoueurs = count($joueursData);
	$enoughPlayers = ($nbJoueurs >= $minPlayers) && ($nbPlayers >= 2);
	if ($continuer && $enoughPlayers) {
		if (!empty($courseRules->team)) {
			$maxJoueursInTeam = ceil($nbJoueurs/2);
			foreach ($joueursData as &$joueur)
				$joueur['score'] = $joueur['pts'];
			unset($joueur);
			$sJoueurs = array();
			foreach ($joueursData as $i=>$joueur)
				$sJoueurs[] = $i;
			function sortPlayerIds($i1,$i2) {
				global $joueursData, $courseRules;
				if (!empty($courseRules->manualTeams)) {
					$t1 = ($joueursData[$i1]['team']!=-1);
					$t2 = ($joueursData[$i2]['team']!=-1);
					if ($t1 && !$t2) return -1;
					if (!$t1 && $t2) return 1;
				}
				$s1 = $joueursData[$i1]['score'];
				$s2 = $joueursData[$i2]['score'];
				if ($s1 == $s2) return 0;
				return ($s1 < $s2) ? 1:-1;
			}
			usort($sJoueurs,'sortPlayerIds');
			$teamScores = array(0,0);
			$teamNbs = array(0,0);
			$teamId = 0;
			foreach ($sJoueurs as $i) {
				$joueur = &$joueursData[$i];
				if (empty($courseRules->manualTeams) || ($joueur['team']==-1))
					$joueur['team'] = $teamId;
				else
					$teamId = $joueur['team'];
				$teamScores[$teamId] += $joueur['score'];
				$teamNbs[$teamId]++;
				if ($teamNbs[$teamId] >= $maxJoueursInTeam)
					$teamScores[$teamId] = INF;
				if ($teamScores[$teamId] >= $teamScores[1-$teamId])
					$teamId = 1-$teamId;
				unset($joueur);
			}
			if ($teamNbs[$teamId] == 0)
				$joueursData[$sJoueurs[0]]['team'] = $teamId;
			foreach ($joueursData as $joueur)
				mysql_query('UPDATE `mkplayers` SET team="'. $joueur['team'] .'" WHERE id="'. $joueur['id'] .'"');
			if (!empty($courseRules->manualTeams)) {
				include('onlineStateUtils.php');
				setCourseExtra($course, array('state' => 'selecting_teams'));
			}
		}
		else
			mysql_query('UPDATE `mkplayers` SET team=-1 WHERE course='. $course);
	}
	if ($enoughPlayers && $allChosen) {
		usleep(100000);
		$joueursData = listPlayers();
		$getMap = getMapData();
		$map = $getMap['map'];
		$now = round((time()+microtime())*1000);
	}
	echo '[[';
	$cpuInc = 0;
	foreach ($joueursData as $i=>$joueur) {
		if ($joueur['controller']) {
			if (!isset($persosList)) {
				ob_start();
				include('getPersos.php');
				$persosList = json_decode(ob_get_clean(), true);
				$persosList = array_keys($persosList);
				srand($course);
				shuffle($persosList);
				srand($now);
				$nbPersos = count($persosList);
			}
			$joueur['joueur'] = $persosList[$cpuInc%$nbPersos];
			$cpuInc++;
			$joueur['nom'] = "CPU $cpuInc";
		}
		echo ($i ? ',':'').'['.$joueur['id'].',"'.$joueur['joueur'].'",'.$joueur['choice_map'].','.$joueur['choice_rand'].','.$joueur['place'].',"'.$joueur['nom'].'",'.$joueur['team'].','.$joueur['controller'].']';
	}
	echo '],'.$map.','.($time-$now).','.round($time/67);
	echo ',{';
	$courseRules = json_decode($getMap['rules']);
	$minPlayers = isset($courseRules->minPlayers) ? $courseRules->minPlayers : 2;
	echo 'minPlayers:'.$minPlayers;
	if (!empty($courseRules->manualTeams))
		echo ',manualTeams:1';
	echo '}';
	echo ']';
	if ($continuer && !$enoughPlayers) {
		mysql_query('UPDATE `mariokart` SET map=-1,time='. time() .' WHERE id='. $course);
		mysql_query('UPDATE `mkjoueurs` j LEFT JOIN `mkplayers` p ON j.id=p.id SET '.(($nbPlayers<2) ? 'j.choice_map=0,':'').'p.connecte=0 WHERE j.course='. $course);
		mysql_query('DELETE p FROM `mkplayers` p LEFT JOIN `mkjoueurs` j ON p.id=j.id WHERE p.course='. $course .' AND j.id IS NULL');
	}
}
else
	echo -1;
mysql_close();
?>