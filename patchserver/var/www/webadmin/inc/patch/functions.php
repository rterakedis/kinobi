<?php

/**
 * Kinobi - external patch source for Jamf Pro
 *
 * @author      Duncan McCracken <duncan.mccracken@mondada.coma.au>
 * @copyright   2018-2019 Mondada Pty Ltd
 * @link        https://mondada.github.io
 * @license     https://github.com/mondada/kinobi/blob/master/LICENSE
 * @version     1.3
 *
 */

// Kinobi configuration file location
define("KINOBI_CONF_PATH", dirname($_SERVER['DOCUMENT_ROOT']) . "/kinobi/conf/kinobi.json");


/**
 * Manage Settings for Kinobi
 *
 */
class KinobiConfig
{
    /**
     * kinobi settings parsed as an array
     * @var array
     */
    private $settings;

    /**
     * Constructor of KinobiConfig
     *
     */
    function __construct()
    {
        if (file_exists(KINOBI_CONF_PATH)) {
			$json_conf = file_get_contents(KINOBI_CONF_PATH);
			$this->settings = json_decode($json_conf, true);
		}
        if (!file_exists(KINOBI_CONF_PATH) || $this->settings == FALSE) {
            $this->settings = array();
        }
    }

    /**
     * Destructor of KinobiConfig
     *
     */
    function __destruct()
    {
    }

    /**
     * Get Setting
     *
     * @param string $setting
     *
     * @return mixed Returns an array, or null if not found
     */
    public function getSetting($setting)
    {
        if (array_key_exists($setting, $this->settings)) {
            return $this->settings[$setting];
        }
    }

    /**
     * Set Setting
     *
     * @param string $setting
     * @param mixed  $value
     *
     * @return null
     */
    public function setSetting($setting, $value)
    {
        $this->settings[$setting] = $value;
        $this->saveSettings();
    }

    /**
     * Delete Setting
     *
     * @param string $setting
     *
     * @return null
     */
    public function deleteSetting($setting)
    {
        if (array_key_exists($setting, $this->settings)) {
            unset($this->settings[$setting]);
            $this->saveSettings();
        }
    }

    /**
     * Save Settings
     *
     * @return null
     */
    private function saveSettings()
    {
        $json_conf = json_encode($this->settings);
        file_put_contents(KINOBI_CONF_PATH, $json_conf);
    }
}


// Initialise configuration object
$kinobi = new KinobiConfig();

// Default Preferences
if (is_null($kinobi->getSetting("uuid"))) {
	$kinobi->setSetting("uuid", createUuid());
}
if (is_null($kinobi->getSetting("cloud"))) {
	$kinobi->setSetting("cloud", false);
}
if (is_null($kinobi->getSetting("pdo"))) {
	$kinobi->setSetting("pdo", array("dsn" => array("prefix" => "sqlite", "dbpath" => dirname($_SERVER['DOCUMENT_ROOT']) . "/kinobi/db/patch_v1.sqlite")));
}
if (is_null($kinobi->getSetting("backup"))) {
	$kinobi->setSetting("backup", array("path" => dirname($_SERVER['DOCUMENT_ROOT']) . "/kinobi/backup", "retention" => 7));
}


// Settings functions

/**
 * Get Users
 *
 * @param  object  $pdo     PDO database connection / Kinobi Settings Object
 * @param  bool    $simple
 *
 * @return array Returns an array.
 */
function getSettingUsers($pdo, $simple = false)
{
	// $users = $kinobi->getSetting("users");

	$users = array();
	if ($pdo) {
		$simple = ($simple ? "" : "password, ");
		$stmt = $pdo->query("SELECT id, username, " . $simple . "reset, token, expires, disabled, web, api FROM users");
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$row['id'] = (int)$row['id'];
			$row['expires'] = (null !== $row['expires'] ? (int)$row['expires'] : null);
			$row['disabled'] = (null !== $row['disabled'] ? (int)$row['disabled'] : null);
			$row['web'] = (null !== $row['web'] ? (int)$row['web'] : null);
			$row['api'] = (null !== $row['api'] ? (int)$row['api'] : null);
			$row['reset'] = (bool)$row['reset'];
			$users[$row['username']] = $row;
		}
	}

	return $users;
}

/**
 * Create User
 *
 * @param  object   $pdo     PDO database connection / Kinobi Settings Object
 * @param  string   $user    Username
 * @param  string   $passwd  Encrypted password
 */
function createUser($pdo, $user, $passwd)
{
	// $users = $kinobi->getSetting("users");
	// $users[$user] = array();
	// $users[$user]['password'] = $passwd;
	// $kinobi->setSetting("users", $users);

	if ($pdo) {
		$stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
		$stmt->execute(array($user, $passwd));
	}
}

/**
 * Rename User
 *
 * @param  object   $pdo   PDO database connection / Kinobi Settings Object
 * @param  string   $user  Username
 * @param  string   $name  New username
 */
function renameUser($pdo, $user, $name)
{
	// $users = $kinobi->getSetting("users");
	// $users[$name] = $users[$user];
	// unset($users[$user]);
	// $kinobi->setSetting("users", $users);

	if ($pdo) {
		$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
		$stmt->execute(array($user));
		$id = $stmt->fetchColumn();
		$stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
		$stmt->execute(array($name, $id));
	}
}

/**
 * Delete User
 *
 * @param  object   $pdo   PDO database connection / Kinobi Settings Object
 * @param  string   $user  Username
 */
function deleteUser($pdo, $user)
{
	// $users = $kinobi->getSetting("users");
	// unset($users[$user]);
	// $kinobi->setSetting("users", $users);

	if ($pdo) {
		$stmt = $pdo->prepare("DELETE FROM users WHERE username = ?");
		$stmt->execute(array($user));
	}
}

/**
 * Set User Setting
 *
 * @param  object   $pdo    PDO database connection / Kinobi Settings Object
 * @param  string   $user   Username
 * @param  string   $key    Setting
 * @param  mixed    $value
 */
function setSettingUser($pdo, $user, $key, $value)
{
	// $users = $kinobi->getSetting("users");
	// $users[$user][$key] = $value;
	// $kinobi->setSetting("users", $users);

	if ($pdo) {
		$stmt = $pdo->prepare("UPDATE users SET " . $key . " = ? WHERE username = ?");
		$stmt->execute(array($value, $user));
	}
}

/**
 * Get Subscription Settings
 *
 * @param  object  $pdo  PDO database connection / Kinobi Settings Object
 *
 * @return array Returns an array.
 */
function getSettingSubscription($pdo)
{
	// $settings = false;

	// $settings = $kinobi->getSetting("subscription");
	// if (!$settings) {
	//	$settings = array("url" => null, "token" => null, "refresh" => 3600, "lastcheckin" => 0);
	// }

	$settings = array();

	if ($pdo) {
		$settings = $pdo->query("SELECT url, token, refresh, lastcheckin FROM subscription LIMIT 1")->fetch(PDO::FETCH_ASSOC);
	}
	$settings['url'] = (isset($settings['url']) ? $settings['url'] : null);
	$settings['token'] = (isset($settings['token']) ? $settings['token'] : null);
	$settings['refresh'] = (isset($settings['refresh']) ? (empty($settings['refresh']) ? 3600 : (int)$settings['refresh']) : 3600);
	$settings['lastcheckin'] = (isset($settings['lastcheckin']) ? (int)$settings['lastcheckin'] : 0);

	return $settings;
}

/**
 * Set Subscription Settings
 *
 * @param  object  $pdo       PDO database connection / Kinobi Settings Object
 * @param  array   $settings  Subscription Settings
 */
function setSettingSubscription($pdo, $settings)
{
	// $kinobi->setSetting("subscription", $settings);

	if ($pdo) {
		$pdo->exec("DELETE FROM subscription");
		$stmt = $pdo->prepare("INSERT INTO subscription (url, token, refresh, lastcheckin) VALUES (?, ?, ?, ?)");
		$stmt->execute(array($settings['url'], $settings['token'], $settings['refresh'], $settings['lastcheckin']));
	}
}

/**
 * Get API Settings
 *
 * @param  object  $pdo  PDO database connection / Kinobi Settings Object
 *
 * @return array Returns an array.
 */
function getSettingApi($pdo)
{
	// $settings = false;

	// $settings = $kinobi->getSetting("api");
	// if (!$settings) {
	//	$settings = array("auto" => true, "reqauth" => false);
	// }

	$settings = array();

	if ($pdo) {
		$settings = $pdo->query("SELECT auto, reqauth FROM api LIMIT 1")->fetch(PDO::FETCH_ASSOC);
	}
	$settings['auto'] = (isset($settings['auto']) ? (bool)$settings['auto'] : false);
	$settings['reqauth'] = (isset($settings['reqauth']) ? (bool)$settings['reqauth'] : false);

	return $settings;
}

/**
 * Set API Settings
 *
 * @param  object  $pdo       PDO database connection / Kinobi Settings Object
 * @param  array   $settings  API Settings
 */
function setSettingApi($pdo, $settings)
{
	// $kinobi->setSetting("api", $settings);

	if ($pdo) {
		$pdo->exec("DELETE FROM api");
		$stmt = $pdo->prepare("INSERT INTO api (auto, reqauth) VALUES (?, ?)");
		$stmt->execute(array((int)$settings['auto'], (int)$settings['reqauth']));
	}
}


// Common Functions

/**
 * Generate UUID
 *
 * @return string Returns a valid UUID v4 string
 */
function createUuid()
{
	return strtoupper(sprintf("%04x%04x-%04x-%04x-%04x-%04x%04x%04x",
		mt_rand(0, 0xffff), mt_rand(0, 0xffff),
		mt_rand(0, 0xffff),
		mt_rand(0, 0x0fff) | 0x4000,
		mt_rand(0, 0x3fff) | 0x8000,
		mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
	));
}

/**
 * Retrieve JSON from an external server
 *
 * @param  string $url   Server URL
 * @param  string $token Bearer token value
 *
 * @return array Returns decoded json array
 */
function fetchJsonArray($url, $token = null)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    if (null !== $token) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $token));
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $title = curl_exec($ch);
    curl_close($ch);

    return json_decode($title, true);
}

/**
 * Get Software Title Summary
 *
 * @param  object  $pdo      PDO database connection
 * @param  string  $ids      Comma sepoarated list of Software Title ids
 * @param  integer $enabled  Return only enabled Software Titles
 *
 * @return array Returns an array.
 */
function getSoftwareTitleSummary($pdo, $ids, $enabled = 1)
{
	$summary = array();

	if (null === $ids) {
		$ids = $pdo->query("SELECT name_id FROM titles")->fetchAll(PDO::FETCH_COLUMN);
	} else {
		$ids = explode(",", trim($ids, ","));
	}

	foreach ($ids as $name_id) {
		$stmt = $pdo->prepare("SELECT name, publisher, modified AS 'lastModified', current AS 'currentVersion', name_id AS 'id' FROM titles WHERE enabled >= ? AND name_id = ?");
		$stmt->execute(array($enabled, $name_id));
		while ($sw_title = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$sw_title['lastModified'] = gmdate("Y-m-d\TH:i:s\Z", $sw_title['lastModified']);
			$override = $pdo->query("SELECT current FROM overrides WHERE name_id = '" . $sw_title['id'] . "'")->fetch(PDO::FETCH_COLUMN);
			if (!empty($override)) {
				$sw_title['currentVersion'] = $override;
			}
			array_push($summary, $sw_title);
		}
	}

	return $summary;
}

/**
 * Get Software Title
 *
 * @param  object  $pdo      PDO database connection
 * @param  string  $id       Software Title id
 * @param  integer $enabled  Return only enabled Software Titles
 *
 * @return array Returns an array, or NULL if not found.
 */
function getSoftwareTitle($pdo, $id, $enabled = 1)
{
	$stmt = $pdo->prepare("SELECT id FROM titles WHERE enabled >= ? AND name_id = ?");
	$stmt->execute(array($enabled, $id));
	$title_id = $stmt->fetch(PDO::FETCH_COLUMN);

	if ($title_id) {
		$title = $pdo->query("SELECT name, publisher, app_name AS 'appName', bundle_id AS 'bundleId', modified AS 'lastModified', current AS 'currentVersion', name_id AS 'id' FROM titles WHERE id = " . $title_id)->fetch(PDO::FETCH_ASSOC);
		$title['lastModified'] = gmdate("Y-m-d\TH:i:s\Z", $title['lastModified']);

		// overrides
		$override = $pdo->query("SELECT current FROM overrides WHERE name_id = '" . $title['id'] . "'")->fetch(PDO::FETCH_COLUMN);
		if (!empty($override)) {
			$title['currentVersion'] = $override;
		}

		// requirements
		$title['requirements'] = $pdo->query("SELECT name, operator, value, type, is_and AS 'and' FROM requirements WHERE title_id = " . $title_id . " ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
		foreach($title['requirements'] as $key => $value) {
			$title['requirements'][$key]['and'] = (is_null($value['and']) ? true : (bool)$value['and']);
		}

		// patches
		$title['patches'] = array();
		$stmt = $pdo->query("SELECT id, version, released AS 'releaseDate', standalone, min_os AS 'minimumOperatingSystem', reboot FROM patches WHERE title_id = " . $title_id . " AND enabled >= " . $enabled . " ORDER BY sort_order");
		while ($patch = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$patch['releaseDate'] = gmdate("Y-m-d\TH:i:s\Z", $patch['releaseDate']);
			$patch['standalone'] = (bool)$patch['standalone'];
			$patch['reboot'] = (bool)$patch['reboot'];

			// killApps
			$patch['killApps'] = $pdo->query("SELECT bundle_id AS 'bundleId', app_name AS 'appName' FROM kill_apps WHERE patch_id = " . $patch['id'])->fetchAll(PDO::FETCH_ASSOC);

			// components
			$patch['components'] = array();
			$comp_stmt = $pdo->query("SELECT id, name, version FROM components WHERE patch_id = " . $patch['id']);
			while ($component = $comp_stmt->fetch(PDO::FETCH_ASSOC)) {
				// criteria
				$component['criteria'] = $pdo->query("SELECT name, operator, value, type, is_and AS 'and' FROM criteria WHERE component_id = " . $component['id'] . " ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
				foreach($component['criteria'] as $key => $value) {
					$component['criteria'][$key]['and'] = (is_null($value['and']) ? true : (bool)$value['and']);
				}

				unset($component['id']);
				array_push($patch['components'], $component);
			}

			// capabilities
			$patch['capabilities'] = $pdo->query("SELECT name, operator, value, type, is_and AS 'and' FROM capabilities WHERE patch_id = " . $patch['id'] . " ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
			foreach($patch['capabilities'] as $key => $value) {
				$patch['capabilities'][$key]['and'] = (is_null($value['and']) ? true : (bool)$value['and']);
			}

			// dependencies
			$patch['dependencies'] = $pdo->query("SELECT name, operator, value, type, is_and AS 'and' FROM dependencies WHERE patch_id = " . $patch['id'] . " ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
			foreach($patch['dependencies'] as $key => $value) {
				$patch['dependencies'][$key]['and'] = (is_null($value['and']) ? true : (bool)$value['and']);
			}

			unset($patch['id']);
			array_push($title['patches'], $patch);
		}

		// extensionAttributes
		$title['extensionAttributes'] = $pdo->query("SELECT key_id AS 'key', script AS 'value', name AS 'displayName' FROM ext_attrs WHERE title_id = " . $title_id)->fetchAll(PDO::FETCH_ASSOC);
		foreach($title['extensionAttributes'] as $key => $value) {
			$title['extensionAttributes'][$key]['value'] = base64_encode($value['value']);
		}

        return $title;
	} else {
		return null;
	}
}