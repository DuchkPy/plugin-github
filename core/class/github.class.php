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
		$eqLogicClient = new github();
		$repoId = $repo->id;
		$defaultRoom = intval(config::byKey('defaultParentObject','github','',true));
		$name = (isset($repo->name) && $repo->name) ? $repo->name : $repoId;
		if(self::nameExists($name)) {
			log::add('github', 'debug', "Nom en double ".$name." renommé en ".$name.'_'.$repoId);
			$name = $name.'_'.$repoId;
		}
		log::add('github', 'info', "Trouvé Repository ".$name."(".$repoId.")");
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

	public static function syncGithub($what='all') {
		log::add('github', 'info', "syncGithub");

		if($what == 'all' || $what == 'clients') {
			$eqLogics = eqLogic::byType('github');
			foreach ($eqLogics as $eqLogic) {
				if($eqLogic->getConfiguration('type','') != 'account' || $eqLogic->getIsEnable() != 1) {
					continue;
				}
                $content = $eqLogic->executeGithubAPI($this->getConfiguration('login'), $this->getConfiguration('token'), 'users/'.$this->getConfiguration('login').'/repos');
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
                            event::add('jeedom::alert', array(
                                'level' => 'warning',
                                'page' => 'github',
                                'message' => __('Repository inclus avec succès : ' .$existingRepo->name, __FILE__),
                            ));
                        }
                    }
                }
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
                $cmd->save();
            }
		}
	}

	public function preRemove() {
		if ($this->getConfiguration('type') == "account") { // Si c'est un type box il faut supprimer ses clients
			self::removeAllRepos($this->getId());
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
    
    function executeGithubAPI($login, $token, $command) {
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
            $eqLogic_cmd = $this->getCmd(null, 'id');
			$this->checkAndUpdateCmd('id', $obj->id);
            $eqLogic_cmd = $this->getCmd(null, 'login');
			$this->checkAndUpdateCmd('login', $obj->login);
            $eqLogic_cmd = $this->getCmd(null, 'name');
			$this->checkAndUpdateCmd('name', $obj->name);
            $eqLogic_cmd = $this->getCmd(null, 'followers');
			$this->checkAndUpdateCmd('followers', $obj->followers);
            $eqLogic_cmd = $this->getCmd(null, 'following');
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
                        $eqLogic_cmd = $existingRepo->getCmd(null, 'id');
                        $existingRepo->checkAndUpdateCmd('id', $repo->id);
                        $eqLogic_cmd = $existingRepo->getCmd(null, 'name');
                        $existingRepo->checkAndUpdateCmd('name', $repo->name);
                        $eqLogic_cmd = $existingRepo->getCmd(null, 'fork');
                        $existingRepo->checkAndUpdateCmd('fork', $repo->fork);
                        $eqLogic_cmd = $existingRepo->getCmd(null, 'watchers');
                        $existingRepo->checkAndUpdateCmd('watchers', $repo->watchers);
                        $eqLogic_cmd = $existingRepo->getCmd(null, 'forks');
                        $existingRepo->checkAndUpdateCmd('forks', $repo->forks);
                        $eqLogic_cmd = $existingRepo->getCmd(null, 'issues');
                        $existingRepo->checkAndUpdateCmd('issues', $repo->issues);
                        $eqLogic_cmd = $existingRepo->getCmd(null, 'private');
                        $existingRepo->checkAndUpdateCmd('private', $repo->private);
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