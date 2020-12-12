<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__ . '/../../../../core/php/core.inc.php';

class github extends eqLogic {
	/* * *************************Attributs****************************** */

	/* * ***********************Methode static*************************** */

	public static function pull() {
		foreach (self::byType('github') as $eqLogic) {
			$eqLogic->scan();
		}
	}

	public static function nameExists($name) {
			$allGithub=eqLogic::byType('github');
			foreach($allGithub as $u) {
				if($name == $u->getName()) return true;
			}
			return false;
	}

	public static function createRepo($repo, $account) {
        if ($repo->private && !config::byKey('getPrivate','github',0)) {
            log::add('github', 'debug', "Repository " . $repo->name . " skipped because private");
            return;
        }
        if ($repo->fork && !config::byKey('getForks','github',0)) {
            log::add('github', 'debug', "Repository " . $repo->name . " skipped because fork");
            return;
        }
		$eqLogicClient = new github();
		$repoId = $repo->id;
		$defaultRoom = intval(config::byKey('defaultParentObject','github','',true));
		$name = (isset($repo->name) && $repo->name) ? $repo->name : $repoId;
		if(self::nameExists($name)) {
			log::add('github', 'debug', "Nom en double ".$name." renommé en ".$name.'_'.$repoId);
			$name = $name.'_'.$repoId;
		}
		log::add('github', 'info', "Repository créé : ".$name."(".$repoId.")");
		$eqLogicClient->setName($name);
		$eqLogicClient->setIsEnable(1);
		$eqLogicClient->setIsVisible(1);
		$eqLogicClient->setLogicalId($repoId);
		$eqLogicClient->setEqType_name('github');
		if($defaultRoom) $eqLogicClient->setObject_id($defaultRoom);
		$eqLogicClient->setConfiguration('type', 'repo');
		$eqLogicClient->setConfiguration('account', $account);
		$eqLogicClient->setConfiguration('image',$eqLogicClient->getImage());
		$eqLogicClient->save();
	}

	public static function syncGithub() {
		log::add('github', 'info', "syncGithub");

        $eqLogics = eqLogic::byType('github');
        foreach ($eqLogics as $eqLogic) {
            if($eqLogic->getConfiguration('type','') != 'account' || $eqLogic->getIsEnable() != 1) {
                continue;
            }
            $content = $eqLogic->executeGithubAPI($eqLogic->getConfiguration('login'), $eqLogic->getConfiguration('token'), 'users/'.$eqLogic->getConfiguration('login').'/repos');
            $obj = json_decode($content);

            if (isset($obj->message)) {
                log::add(__CLASS__, 'error', $eqLogic->getHumanName() . ' users/'.$eqLogic->getConfiguration('login').'/repos:' . $obj->message);
            } 
            else {
                foreach ($obj as $repo) {
                    $existingRepo = github::byLogicalId($repo->id, 'github');
                    if (!is_object($existingRepo)) {
                        // new repo
                        github::createRepo($repo, $eqLogic->getConfiguration('login'));
                        $existingRepo = github::byLogicalId($repo->id, 'github');
                        event::add('jeedom::alert', array(
                            'level' => 'warning',
                            'page' => 'github',
                            'message' => __('Repository inclus avec succès : ' .$existingRepo->name, __FILE__),
                        ));
                    }
                }
                $eqLogic->refreshInfo();
            }
        }
	}

	public static function removeAllRepos($account) {
		$eqLogics = eqLogic::byType('github');
		foreach ($eqLogics as $eqLogic) {
			if($eqLogic->getConfiguration('type') == 'repo' && $eqLogic->getConfiguration('account') == $account) {
				$eqLogic->remove();
			}
		}
	}

	public function preUpdate()
	{
	}

	public function preSave()
	{
	}

	public function postSave() {
		if ($this->getConfiguration('type','') == 'account') {
            if ( $this->getIsEnable() ) {
                $cmd = $this->getCmd(null, 'id');
                if ( ! is_object($cmd)) {
                    $cmd = new githubCmd();
                    $cmd->setName('ID');
                    $cmd->setEqLogic_id($this->getId());
                    $cmd->setLogicalId('id');
                    $cmd->setType('info');
                    $cmd->setSubType('string');
                    $cmd->setGeneric_type('GENERIC_INFO');
                    $cmd->setIsHistorized(0);
                    $cmd->setIsVisible(0);
                    $cmd->save();
                }
                $cmd = $this->getCmd(null, 'login');
                if ( ! is_object($cmd)) {
                    $cmd = new githubCmd();
                    $cmd->setName('Login');
                    $cmd->setEqLogic_id($this->getId());
                    $cmd->setLogicalId('login');
                    $cmd->setType('info');
                    $cmd->setSubType('string');
                    $cmd->setGeneric_type('GENERIC_INFO');
                    $cmd->setIsHistorized(0);
                    $cmd->setIsVisible(0);
                    $cmd->save();
                }
                $cmd = $this->getCmd(null, 'name');
                if ( ! is_object($cmd)) {
                    $cmd = new githubCmd();
                    $cmd->setName('Name');
                    $cmd->setEqLogic_id($this->getId());
                    $cmd->setLogicalId('name');
                    $cmd->setType('info');
                    $cmd->setSubType('string');
                    $cmd->setGeneric_type('GENERIC_INFO');
                    $cmd->setIsHistorized(0);
                    $cmd->setIsVisible(0);
                    $cmd->save();
                }
                $cmd = $this->getCmd(null, 'followers');
                if ( ! is_object($cmd)) {
                    $cmd = new githubCmd();
                    $cmd->setName('Followers');
                    $cmd->setEqLogic_id($this->getId());
                    $cmd->setLogicalId('followers');
                    $cmd->setType('info');
                    $cmd->setSubType('numeric');
                    $cmd->setIsHistorized(1);
                    $cmd->setTemplate('dashboard','tile');
                    $cmd->setTemplate('mobile','tile');
                    $cmd->setOrder(1);
                    $cmd->save();
                }
                $cmd = $this->getCmd(null, 'following');
                if ( ! is_object($cmd)) {
                    $cmd = new githubCmd();
                    $cmd->setName('Following');
                    $cmd->setEqLogic_id($this->getId());
                    $cmd->setLogicalId('following');
                    $cmd->setType('info');
                    $cmd->setSubType('numeric');
                    $cmd->setIsHistorized(1);
                    $cmd->setTemplate('dashboard','tile');
                    $cmd->setTemplate('mobile','tile');
                    $cmd->setOrder(2);
                    $cmd->save();
                }
            }
		} else if ($this->getConfiguration('type','') == 'repo') {
            $cmd = $this->getCmd(null, 'id');
            if ( ! is_object($cmd)) {
                $cmd = new githubCmd();
                $cmd->setName('ID');
                $cmd->setEqLogic_id($this->getId());
                $cmd->setLogicalId('id');
                $cmd->setType('info');
                $cmd->setSubType('string');
                $cmd->setGeneric_type('GENERIC_INFO');
                $cmd->setIsHistorized(0);
                $cmd->setIsVisible(0);
                $cmd->save();
            }
            $cmd = $this->getCmd(null, 'name');
            if ( ! is_object($cmd)) {
                $cmd = new githubCmd();
                $cmd->setName('Name');
                $cmd->setEqLogic_id($this->getId());
                $cmd->setLogicalId('name');
                $cmd->setType('info');
                $cmd->setSubType('string');
                $cmd->setGeneric_type('GENERIC_INFO');
                $cmd->setIsHistorized(0);
                $cmd->setIsVisible(0);
                $cmd->save();
            }
            $cmd = $this->getCmd(null, 'fork');
            if ( ! is_object($cmd)) {
                $cmd = new githubCmd();
                $cmd->setName('Fork');
                $cmd->setEqLogic_id($this->getId());
                $cmd->setLogicalId('fork');
                $cmd->setType('info');
                $cmd->setSubType('binary');
                $cmd->setGeneric_type('GENERIC_INFO');
                $cmd->setIsHistorized(0);
                $cmd->setIsVisible(0);
                $cmd->save();
            }
            $cmd = $this->getCmd(null, 'watchers');
            if ( ! is_object($cmd)) {
                $cmd = new githubCmd();
                $cmd->setName('Watchers');
                $cmd->setEqLogic_id($this->getId());
                $cmd->setLogicalId('watchers');
                $cmd->setType('info');
                $cmd->setSubType('numeric');
                $cmd->setIsHistorized(1);
                $cmd->setTemplate('dashboard','tile');
                $cmd->setTemplate('mobile','tile');
                $cmd->setOrder(1);
                $cmd->save();
            }
            $cmd = $this->getCmd(null, 'forks');
            if ( ! is_object($cmd)) {
                $cmd = new githubCmd();
                $cmd->setName('Forks');
                $cmd->setEqLogic_id($this->getId());
                $cmd->setLogicalId('forks');
                $cmd->setType('info');
                $cmd->setSubType('numeric');
                $cmd->setIsHistorized(1);
                $cmd->setTemplate('dashboard','tile');
                $cmd->setTemplate('mobile','tile');
                $cmd->setOrder(2);
                $cmd->setDisplay('forceReturnLineAfter', 1);
                $cmd->save();
            }
            $cmd = $this->getCmd(null, 'issues');
            if ( ! is_object($cmd)) {
                $cmd = new githubCmd();
                $cmd->setName('Open issues');
                $cmd->setEqLogic_id($this->getId());
                $cmd->setLogicalId('issues');
                $cmd->setType('info');
                $cmd->setSubType('numeric');
                $cmd->setIsHistorized(1);
                $cmd->setTemplate('dashboard','tile');
                $cmd->setTemplate('mobile','tile');
                $cmd->setOrder(3);
                $cmd->save();
            }
            $cmd = $this->getCmd(null, 'pulls');
            if ( ! is_object($cmd)) {
                $cmd = new githubCmd();
                $cmd->setName('Open pull requests');
                $cmd->setEqLogic_id($this->getId());
                $cmd->setLogicalId('pulls');
                $cmd->setType('info');
                $cmd->setSubType('numeric');
                $cmd->setIsHistorized(1);
                $cmd->setTemplate('dashboard','tile');
                $cmd->setTemplate('mobile','tile');
                $cmd->setOrder(4);
                $cmd->setDisplay('forceReturnLineAfter', 1);
                $cmd->save();
            }
            $cmd = $this->getCmd(null, 'daily_unique_clones');
            if ( ! is_object($cmd)) {
                $cmd = new githubCmd();
                $cmd->setName('Clones par jour');
                $cmd->setEqLogic_id($this->getId());
                $cmd->setLogicalId('daily_unique_clones');
                $cmd->setType('info');
                $cmd->setSubType('numeric');
                $cmd->setIsHistorized(1);
                $cmd->setTemplate('dashboard','tile');
                $cmd->setTemplate('mobile','tile');
                $cmd->setOrder(5);
                $cmd->save();
            }
            $cmd = $this->getCmd(null, 'daily_unique_views');
            if ( ! is_object($cmd)) {
                $cmd = new githubCmd();
                $cmd->setName('Vues par jour');
                $cmd->setEqLogic_id($this->getId());
                $cmd->setLogicalId('daily_unique_views');
                $cmd->setType('info');
                $cmd->setSubType('numeric');
                $cmd->setIsHistorized(1);
                $cmd->setTemplate('dashboard','tile');
                $cmd->setTemplate('mobile','tile');
                $cmd->setOrder(6);
                $cmd->save();
            }
            $cmd = $this->getCmd(null, 'private');
            if ( ! is_object($cmd)) {
                $cmd = new githubCmd();
                $cmd->setName('Private');
                $cmd->setEqLogic_id($this->getId());
                $cmd->setLogicalId('private');
                $cmd->setType('info');
                $cmd->setSubType('binary');
                $cmd->setIsHistorized(1);
                $cmd->setIsVisible(0);
                $cmd->save();
            }
		}
	}
    
    public function preInsert() {
      if ($this->getConfiguration('type','') == 'account') {
          $this->setDisplay('height','75px');
      } else {
          $this->setDisplay('height','225px');
      }
      $this->setDisplay('width', '280px');
      $this->setIsEnable(1);
      $this->setIsVisible(1);
    }        

	public function preRemove() {
		if ($this->getConfiguration('type') == "account") {
			self::removeAllRepos($this->getConfiguration('login'));
		}
	}

	public function getImage() {
		if($this->getConfiguration('type') == 'repo'){
			return 'plugins/github/core/assets/repo_icon.png';
		}
		return 'plugins/github/plugin_info/github_icon.png';
	}

	public function scan() {
		if ( $this->getIsEnable() && $this->getConfiguration('type') == 'account') {
            $this->refreshInfo();
		}
	}
    
    public function executeGithubAPI($login, $token, $command) {
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' execute ' . $command);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.github.com/" . $command,
            CURLOPT_HEADER  => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Mobile Safari/537.36",
                "Accept-Encoding: gzip, deflate, br", 
                "Accept: */*",
                "Authorization: Basic " . base64_encode($login.":".$token))
        ));
        $response = curl_exec($curl);
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' result of ' . $command . ': ' . $response);
        curl_close($curl);
        return $response;  
    }

	function refreshInfo() {
		$content = $this->executeGithubAPI($this->getConfiguration('login'), $this->getConfiguration('token'), 'user');
        $obj = json_decode($content);  
    
        if (isset($obj->message)) {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' user:' . $obj->message);
        } 
        else {      
			$this->checkAndUpdateCmd('id', $obj->id);
			$this->checkAndUpdateCmd('login', $obj->login);
			$this->checkAndUpdateCmd('name', $obj->name);
			$this->checkAndUpdateCmd('followers', $obj->followers);
			$this->checkAndUpdateCmd('following', $obj->following);
        }

		$content = $this->executeGithubAPI($this->getConfiguration('login'), $this->getConfiguration('token'), 'users/'.$this->getConfiguration('login').'/repos');
        $obj = json_decode($content);
        
        if (isset($obj->message)) {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' users/'.$this->getConfiguration('login').'/repos:' . $obj->message);
        } 
        else {
            foreach ($obj as $repo) {
                $existingRepo = github::byLogicalId($repo->id, 'github');
                if (!is_object($existingRepo)) {
                    // new repo
                    github::createRepo($repo, $this->getConfiguration('login'));
                    $existingRepo = github::byLogicalId($repo->id, 'github');
                }
                if (is_object($existingRepo)) {
                    if ($existingRepo->getIsEnable()) {
                        $existingRepo->checkAndUpdateCmd('id', $repo->id);
                        $existingRepo->checkAndUpdateCmd('name', $repo->name);
                        $existingRepo->checkAndUpdateCmd('fork', $repo->fork);
                        $existingRepo->checkAndUpdateCmd('watchers', $repo->watchers);
                        $existingRepo->checkAndUpdateCmd('forks', $repo->forks);
                        $existingRepo->checkAndUpdateCmd('issues', $repo->issues);
                        $existingRepo->checkAndUpdateCmd('private', $repo->private);
                        
                        $content = $this->executeGithubAPI($this->getConfiguration('login'), $this->getConfiguration('token'), 'repos/' . $this->getConfiguration('login') . '/' . $repo->name . '/pulls?state=open');
                        $pulls = json_decode($content);  
                        if (isset($obj->message)) {
                            log::add(__CLASS__, 'error', $this->getHumanName() . ' repos/' . $this->getConfiguration('login') . '/' . $repo->name . '/pulls?state=open: ' . $pulls->message);
                        } 
                        else {
                            $existingRepo->checkAndUpdateCmd('pulls', count($pulls));
                        }
                        
                        $content = $this->executeGithubAPI($this->getConfiguration('login'), $this->getConfiguration('token'), 'repos/' . $this->getConfiguration('login') . '/' . $repo->name . '/traffic/clones?per=day');
                        $clones = json_decode($content);
                        if (isset($obj->message)) {
                            log::add(__CLASS__, 'error', $this->getHumanName() . ' repos/' . $this->getConfiguration('login') . '/' . $repo->name . '/traffic/clones?per=day: ' . $clones->message);
                        } 
                        else {
                            $eqLogic_cmd = $existingRepo->getCmd(null, 'daily_unique_clones');
                            $cmdId = $eqLogic_cmd->getId();
                            foreach ($clones->clones as $clone) {
                                $dt = DateTime::createFromFormat('Y-m-d', substr($clone->timestamp, 0, 10));
                                if (is_bool($dt)) {
                                    return;
                                }
                                $dateReal = $dt->format('Y-m-d 12:00:00');
                                $cmdHistory = history::byCmdIdDatetime($cmdId, $dateReal);
                                if (is_object($cmdHistory) && $cmdHistory->getValue() == $clone->uniques) {
                                    log::add(__CLASS__, 'debug', $this->getHumanName() . ' Clones en historique - Aucune action : ' . ' Date = ' . $dateReal . ' => Mesure = ' . $clone->uniques);
                                }
                                else {      
                                    log::add(__CLASS__, 'debug', $this->getHumanName() . ' Enregistrement clones : ' . ' Date = ' . $dateReal . ' => Mesure = ' . $clone->uniques);
                                    $eqLogic_cmd->event($clone->uniques, $dateReal);
                                }
                            }
                        }
                        
                        $content = $this->executeGithubAPI($this->getConfiguration('login'), $this->getConfiguration('token'), 'repos/' . $this->getConfiguration('login') . '/' . $repo->name . '/traffic/views?per=day');
                        $views = json_decode($content);
                        if (isset($obj->message)) {
                            log::add(__CLASS__, 'error', $this->getHumanName() . ' repos/' . $this->getConfiguration('login') . '/' . $repo->name . '/traffic/views?per=day: ' . $clones->message);
                        } 
                        else {
                            $eqLogic_cmd = $existingRepo->getCmd(null, 'daily_unique_views');
                            $cmdId = $eqLogic_cmd->getId();
                            foreach ($views->views as $view) {
                                $dt = DateTime::createFromFormat('Y-m-d', substr($view->timestamp, 0, 10));
                                if (is_bool($dt)) {
                                    return;
                                }
                                $dateReal = $dt->format('Y-m-d 12:00:00');
                                $cmdHistory = history::byCmdIdDatetime($cmdId, $dateReal);
                                if (is_object($cmdHistory) && $cmdHistory->getValue() == $view->uniques) {
                                    log::add(__CLASS__, 'debug', $this->getHumanName() . ' Views en historique - Aucune action : ' . ' Date = ' . $dateReal . ' => Mesure = ' . $view->uniques);
                                }
                                else {      
                                    log::add(__CLASS__, 'debug', $this->getHumanName() . ' Enregistrement views : ' . ' Date = ' . $dateReal . ' => Mesure = ' . $view->uniques);
                                    $eqLogic_cmd->event($view->uniques, $dateReal);
                                }
                            }
                        }
                    }
                }
            }
        }
	}
}

class githubCmd extends cmd
{
	/*	   * *************************Attributs****************************** */


	/*	   * ***********************Methode static*************************** */


	/*	   * *********************Methode d'instance************************* */

	/*	   * **********************Getteur Setteur*************************** */
	public function execute($_options = null) {
	}
}
?>